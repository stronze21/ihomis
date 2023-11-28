<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use Livewire\Component;
use App\Models\Hospital\Room;
use App\Models\Hospital\Ward;
use App\Models\Pharmacy\Drug;
use App\Jobs\LogDrugOrderIssue;
use App\Jobs\LogDrugStockIssue;
use App\Jobs\DispenseIssueProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\References\ChargeCode;
use Illuminate\Support\Facades\Crypt;
use App\Models\Record\Patients\Patient;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Http\Controllers\SharedController;
use App\Models\Record\Patients\PatientMss;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Record\Admission\PatientRoom;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Record\Encounters\AdmissionLog;
use App\Models\Record\Encounters\EncounterLog;
use App\Models\Record\Prescriptions\Prescription;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;
use App\Models\Pharmacy\Dispensing\DrugOrderReturn;
use App\Models\Pharmacy\Dispensing\OrderChargeCode;
use App\Models\Record\Prescriptions\PrescriptionData;
use App\Models\Record\Prescriptions\PrescriptionDataIssued;

class EncounterTransactionView extends Component
{
    use LivewireAlert;

    protected $listeners = ['charge_items', 'issue_order', 'add_item', 'return_issued', 'add_prescribed_item', 'refresh' => '$refresh', 'delete_item'];

    public $generic, $charge_code = [];
    public $enccode, $location_id, $hpercode, $toecode;

    public $order_qty, $unit_price, $return_qty, $docointkey;
    public $item_id;
    public $ems, $maip, $wholesale, $caf, $type, $konsulta, $pcso, $phic, $pay, $service;

    public $is_ris = false;
    public $remarks;

    public $charges;
    public $encounter;

    public $selected_items = [];

    public $selected_remarks, $new_remarks;

    public $patient;
    public $active_prescription;
    public $adm;
    public $rx_charge_code;

    public $patient_room, $wardname, $rmname;

    public $rx_id, $rx_dmdcomb, $rx_dmdctr, $empid, $mss;

    public $stock_changes = false;

    public function render()
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));

        $rxos = DB::select("SELECT docointkey, pcchrgcod, dodate, pchrgqty, estatus, qtyissued, pchrgup, pcchrgamt, drug_concat, chrgdesc, remarks, mssikey
                            FROM hospital.dbo.hrxo
                            INNER JOIN hdmhdr ON hdmhdr.dmdcomb = hrxo.dmdcomb AND hdmhdr.dmdctr = hrxo.dmdctr
                            INNER JOIN hcharge ON orderfrom = chrgcode
                            LEFT JOIN hpatmss ON hrxo.enccode = hpatmss.enccode
                            WHERE hrxo.enccode = '".$enccode."'
                            ORDER BY dodate DESC");

        $stocks = DB::select("SELECT pharm_drug_stocks.dmdcomb, pharm_drug_stocks.dmdctr, drug_concat, hcharge.chrgdesc, pharm_drug_stocks.chrgcode, hdmhdrprice.retail_price, dmselprice, pharm_drug_stocks.loc_code, MAX(pharm_drug_stocks.dmdprdte) as dmdprdte, SUM(stock_bal) as stock_bal, MAX(id) as id, MIN(exp_date) as exp_date
                                FROM hospital.dbo.pharm_drug_stocks
                                INNER JOIN hcharge on hcharge.chrgcode = pharm_drug_stocks.chrgcode
                                INNER JOIN hdmhdrprice on hdmhdrprice.dmdprdte = pharm_drug_stocks.dmdprdte
                                WHERE loc_code = ?
                                GROUP BY pharm_drug_stocks.dmdcomb, pharm_drug_stocks.dmdctr, pharm_drug_stocks.chrgcode, hdmhdrprice.retail_price, dmselprice, drug_concat, hcharge.chrgdesc, pharm_drug_stocks.loc_code
                                ", [$this->location_id]);

        return view('livewire.pharmacy.dispensing.encounter-transaction-view', compact(
            'rxos',
            'stocks',
        ));
    }

    public function mount($enccode)
    {
        $this->enccode = $enccode;

        $this->location_id = session('pharm_location_id');

        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));

        // $this->encounter = EncounterLog::where('enccode', $enccode)
        //     ->with('patient')->with('rxo')->with('active_prescription')->with('adm')->first();

        $this->encounter = EncounterLog::where('enccode', $enccode)->first();
        $this->mss = PatientMss::where('enccode', $enccode)->first();
        $this->patient = Patient::find($this->encounter->hpercode);
        $this->active_prescription = Prescription::where('enccode', $enccode)->with('employee')->with('data_active')->has('data_active')->get();
        $patient_room = PatientRoom::where('enccode', $enccode)->first();
        if ($patient_room) {
            $this->wardname = Ward::select('wardname')->where('wardcode', $patient_room->wardcode)->first();
            $this->rmname = Room::select('rmname')->where('rmintkey', $patient_room->rmintkey)->first();
        }

        if (!$this->hpercode) {
            $this->hpercode = $this->encounter->hpercode;
            $this->toecode = $this->encounter->toecode;
        }

        if (!$this->charges) {
            $this->charges = ChargeCode::where('bentypcod', 'DRUME')
                ->where('chrgstat', 'A')
                ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS'))
                ->get();
        }

        // $this->stocks = DB::select("SELECT pharm_drug_stocks.dmdcomb, pharm_drug_stocks.dmdctr, drug_concat, hcharge.chrgdesc, pharm_drug_stocks.chrgcode, hdmhdrprice.retail_price, dmselprice, pharm_drug_stocks.loc_code, MAX(pharm_drug_stocks.dmdprdte) as dmdprdte, SUM(stock_bal) as stock_bal, MAX(id) as id, MIN(exp_date) as exp_date
        //                         FROM hospital.dbo.pharm_drug_stocks
        //                         INNER JOIN hcharge on hcharge.chrgcode = pharm_drug_stocks.chrgcode
        //                         INNER JOIN hdmhdrprice on hdmhdrprice.dmdprdte = pharm_drug_stocks.dmdprdte
        //                         WHERE loc_code = ?
        //                         GROUP BY pharm_drug_stocks.dmdcomb, pharm_drug_stocks.dmdctr, pharm_drug_stocks.chrgcode, hdmhdrprice.retail_price, dmselprice, drug_concat, hcharge.chrgdesc, pharm_drug_stocks.loc_code
        //                         ", [$this->location_id]);
    }

    public function charge_items()
    {
        $charge_code = OrderChargeCode::create([
            'charge_desc' => 'a',
        ]);

        $pcchrgcod = 'P' . date('y') . '-' . sprintf('%07d', $charge_code->id);
        $cnt = 0;

        foreach ($this->selected_items as $docointkey) {
            $cnt = DB::update(
                "UPDATE hospital.dbo.hrxo SET pcchrgcod = ?, estatus = 'P' WHERE docointkey = ? AND estatus = 'U'",
                [$pcchrgcod, $docointkey]
            );
        }

        if ($cnt > 0) {
            $this->dispatchBrowserEvent('charged', ['pcchrgcod' => $pcchrgcod]);
        } else {
            $this->alert('error', 'No item to charge.');
        }
    }

    public function issue_order()
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
        $cnt = 0;

        $rxos = DB::table('hospital.dbo.hrxo')->whereIn('docointkey', $this->selected_items)
            ->where('estatus', 'P')->get();

        foreach ($rxos as $rxo) {
            $stocks = DB::select(
                "SELECT * FROM pharm_drug_stocks
                            WHERE dmdcomb = ? AND dmdctr = ? AND chrgcode = ? AND loc_code = ? AND exp_date > ? AND stock_bal > 0
                            ORDER BY exp_date ASC",
                [$rxo->dmdcomb, $rxo->dmdctr, $rxo->orderfrom, session('pharm_location_id'), date('Y-m-d')]
            );
            if ($stocks) {
                $total_deduct = $rxo->pchrgqty;
                $dmdcomb = $rxo->dmdcomb;
                $dmdctr = $rxo->dmdctr;
                $docointkey = $rxo->docointkey;
                $loc_code = $rxo->loc_code;
                $chrgcode = $rxo->orderfrom;
                $unit_price = $rxo->pchrgup;
                $pcchrgamt = $rxo->pcchrgamt;
                $pcchrgcod = $rxo->pcchrgcod;
                $tag = $rxo->tx_type;

                foreach ($stocks as $stock) {
                    $trans_qty = 0;
                    if ($total_deduct) {
                        if (!$rxo->ris) {
                            if ($total_deduct > $stock->stock_bal) {
                                $trans_qty = $stock->stock_bal;
                                $total_deduct -= $stock->stock_bal;
                                $stock_bal = 0;
                            } else {
                                $trans_qty = $total_deduct;
                                $stock_bal = $stock->stock_bal - $total_deduct;
                                $total_deduct = 0;
                            }
                            $cnt = DB::update(
                                "UPDATE hospital.dbo.pharm_drug_stocks SET stock_bal = ? WHERE id = ?",
                                [$stock_bal, $stock->id]
                            );
                        } else {
                            $total_deduct = 0;
                        }
                        //TODO: Job for DrugStockIssue
                        LogDrugStockIssue::dispatch($stock->id, $docointkey, $dmdcomb, $dmdctr, $loc_code, $chrgcode, $stock->exp_date, $trans_qty, $unit_price, $pcchrgamt, session('user_id'), $rxo->hpercode, $rxo->enccode, $this->toecode, $pcchrgcod, $tag, $rxo->ris, $stock->dmdprdte, $stock->retail_price);
                        //TODO: Job for DrugStockLog
                    }
                }
                        //TODO: Job for DrugOrderIssue
            } else {
                return $this->alert('error', 'Insufficient Stock Balance.');
            }
        }

        if ($cnt == 1 || $cnt == 0) {
            foreach ($rxos as $row2) {
                $cnt = DB::update(
                    "UPDATE hospital.dbo.hrxo SET estatus = 'S', qtyissued = ? WHERE docointkey = ? AND estatus = 'P'",
                    [$row2->pchrgqty, $row2->docointkey]
                );
                LogDrugOrderIssue::dispatch($row2->docointkey, $row2->enccode, $row2->hpercode, $row2->dmdcomb, $row2->dmdctr, $row2->pchrgqty, session('employeeid'), $row2->orderfrom, $row2->pcchrgcod, $row2->pchrgup, $row2->ris, $row2->prescription_data_id);
            }
            $this->emit('refresh');
            $this->alert('success', 'Order issued successfully.');
        } else {
            $this->alert('error', 'No item to issue.');
        }
    }

    public function issue_order_old_function()
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
        $cnt = 0;

        $rxos = DrugOrder::whereIn('docointkey', $this->selected_items)
            ->where('estatus', 'P')->get();

        foreach ($rxos as $row) {
            if ($row->items) {
                if ($row->items->sum('stock_bal') >= $row->pchrgqty) {
                    $cnt = 1;
                } else {
                    $cnt = 2;
                    break;
                }
            }
        }

        if ($cnt == 1) {
            foreach ($rxos as $rxo) {
                // $this->update_prescription($row->dmdctr, $row->dmdcomb, $row->docointkey, $row->pchrgqty);
                // $this->deduct_stocks($row->dmdctr, $row->dmdcomb, $row->orderfrom, $row->pchrgqty, $row->loc_code, $row->docointkey, $row->pcchrgcod, $row->tx_type, $row->pcchrgamt, $row->pchrgup, $enccode);

                if ($rxo->prescription_data_id) {
                    $rx_data = PrescriptionData::find($rxo->prescription_data_id);

                    PrescriptionDataIssued::create([
                        'presc_data_id' => $rx_data->id,
                        'docointkey' => $rxo->docointkey,
                        'qtyissued' => $rxo->pchrgqty,
                    ]);

                    $rx_data->stat = 'I';
                    // $rx_data->order_by = $rx_data->rx->empid;
                    // $rx_data->deptcode = $rx_data->rx->employee->deptcode;
                    $rx_data->save();
                }

                //START DEDUCT STOCK
                $total_deduct = $rxo->pchrgqty;
                $dmdcomb = $rxo->dmdcomb;
                $dmdctr = $rxo->dmdctr;
                $docointkey = $rxo->docointkey;
                $loc_code = $rxo->loc_code;
                $chrgcode = $rxo->orderfrom;
                $unit_price = $rxo->pchrgup;
                $pcchrgamt = $rxo->pcchrgamt;
                $pcchrgcod = $rxo->pcchrgcod;
                $tag = $rxo->tx_type;

                $stocks = DrugStock::where('dmdcomb', $dmdcomb)
                    ->where('dmdctr', $dmdctr)
                    ->where('chrgcode', $chrgcode)
                    ->where('loc_code', $loc_code)
                    ->where('exp_date', '>', date('Y-m-d'))
                    ->where('stock_bal', '>', '0')
                    ->oldest('exp_date')
                    ->get();

                foreach ($stocks as $stock) {
                    $trans_qty = 0;
                    if ($total_deduct) {
                        if (!$this->is_ris) {
                            if ($total_deduct > $stock->stock_bal) {
                                $trans_qty = $stock->stock_bal;
                                $total_deduct -= $stock->stock_bal;
                                $stock->stock_bal = 0;
                            } else {
                                $trans_qty = $total_deduct;
                                $stock->stock_bal -= $total_deduct;
                                $total_deduct = 0;
                            }
                            $stock->save();
                        } else {
                            $total_deduct = 0;
                        }

                        $issued_drug = DrugStockIssue::create([
                            'stock_id' => $stock->id,
                            'docointkey' => $docointkey,
                            'dmdcomb' => $dmdcomb,
                            'dmdctr' => $dmdctr,
                            'loc_code' => $loc_code,
                            'chrgcode' => $chrgcode,
                            'exp_date' => $stock->exp_date,
                            'qty' =>  $trans_qty,
                            'pchrgup' =>  $unit_price,
                            'pcchrgamt' =>  $pcchrgamt,
                            'status' => 'Issued',
                            'user_id' => session('user_id'),
                            'hpercode' => $this->hpercode,
                            'enccode' => $enccode,
                            'toecode' => $this->toecode,
                            'pcchrgcod' => $pcchrgcod,

                            'sc_pwd' => $tag == 'sc_pwd' ? $trans_qty : false,
                            'ems' => $tag == 'ems' ? $trans_qty : false,
                            'maip' => $tag == 'maip' ? $trans_qty : false,
                            'wholesale' => $tag == 'wholesale' ? $trans_qty : false,
                            'pay' => $tag == 'pay' ? $trans_qty : false,
                            'medicare' => $tag == 'medicare' ? $trans_qty : false,
                            'service' => $tag == 'service' ? $trans_qty : false,
                            'govt_emp' => $tag == 'govt_emp' ? $trans_qty : false,
                            'caf' => $tag == 'caf' ? $trans_qty : false,
                            'ris' =>  $rxo->ris ? true : false,

                            'dmdprdte' => $stock->dmdprdte,
                        ]);

                        $log = DrugStockLog::firstOrNew([
                            'loc_code' => $stock->loc_code,
                            'dmdcomb' => $stock->dmdcomb,
                            'dmdctr' => $stock->dmdctr,
                            'chrgcode' => $stock->chrgcode,
                            'date_logged' => date('Y-m-d'),
                            'dmdprdte' => $stock->dmdprdte,
                            'unit_price' => $stock->retail_price,
                        ]);
                        $log->time_logged = now();
                        $log->issue_qty += $trans_qty;

                        $log->sc_pwd += $issued_drug->sc_pwd;
                        $log->ems += $issued_drug->ems;
                        $log->maip += $issued_drug->maip;
                        $log->wholesale += $issued_drug->wholesale;
                        $log->pay += $issued_drug->pay;
                        $log->medicare += $issued_drug->medicare;
                        $log->service += $issued_drug->service;
                        $log->govt_emp += $issued_drug->govt_emp;
                        $log->caf += $issued_drug->caf;
                        $log->ris += $issued_drug->ris ? 1 : 0;

                        $log->save();

                        // $this->add_to_inventory($dmdcomb, $dmdctr, $loc_code, $chrgcode, $stock->exp_date, $trans_qty);

                    }
                }
                //END DEDUCT TO STOCKS

                $rxo->estatus = 'S';
                $rxo->qtyissued = $rxo->pchrgqty;
                $rxo->save();

                //START RECORD ISSUED DRUG
                DrugOrderIssue::updateOrCreate([
                    'docointkey' => $docointkey,
                    'enccode' => $rxo->enccode,
                    'hpercode' => $rxo->hpercode,
                    'dmdcomb' => $rxo->dmdcomb,
                    'dmdctr' => $rxo->dmdctr,
                ], [
                    'issuedte' => now(),
                    'issuetme' => now(),
                    'qty' => $rxo->pchrgqty,
                    'issuedby' => session('employeeid'),
                    'status' => 'A', //A
                    'rxolock' => 'N', //N
                    'updsw' => 'N', //N
                    'confdl' => 'N', //N
                    'entryby' => session('employeeid'),
                    'locacode' => 'PHARM', //PHARM
                    'dmdprdte' => now(),
                    'issuedfrom' => $rxo->orderfrom,
                    'pcchrgcod' => $rxo->pcchrgcod,
                    'chrgcode' => $rxo->orderfrom,
                    'pchrgup' => $rxo->pchrgup,
                    'issuetype' => 'c', //c
                    'ris' =>  $rxo->ris ? true : false,
                ]);
                //END RECORD ISSUED DRUG

            }

            $this->emit('refresh');
            $this->alert('success', 'Order issued successfully.');
        } elseif ($cnt == 2) {
            $this->alert('error', 'Insufficient Stock Balance.');
        } else {
            $this->alert('error', 'No item to issue.');
        }
    }

    public function update_prescription($dmdctr, $dmdcomb, $docointkey, $qtyissued)
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));

        $rx_header = Prescription::where('enccode', $enccode)
            ->with('data_active')
            ->get();
        if ($rx_header) {
            foreach ($rx_header as $rxh) {
                $rx_data = $rxh->data_active()
                    ->where('dmdcomb', $dmdcomb)
                    ->where('dmdctr', $dmdctr)
                    ->get();
                if ($rx_data) {
                    PrescriptionDataIssued::create([
                        'presc_data_id' => $rx_data->id,
                        'docointkey' => $docointkey,
                        'qtyissued' => $qtyissued,
                    ]);

                    if ($rx_data->issued()->sum('qtyissued') >= $rx_data->qty) {
                        $rx_data->stat = 'I';
                        $rx_data->save();
                    }
                }
            }
        }
    }

    public function deduct_stocks($dmdctr, $dmdcomb, $chrgcode, $total_deduct, $loc_code, $docointkey, $pcchrgcod, $tag, $pcchrgamt, $unit_price, $enccode)
    {
        $stocks = DrugStock::where('dmdcomb', $dmdcomb)
            ->where('dmdctr', $dmdctr)
            ->where('chrgcode', $chrgcode)
            ->where('loc_code', $loc_code)
            ->where('exp_date', '>', date('Y-m-d'))
            ->where('stock_bal', '>', '0')
            ->oldest('exp_date')
            ->get();

        foreach ($stocks as $stock) {
            $trans_qty = 0;
            if ($total_deduct) {
                if ($total_deduct > $stock->stock_bal) {
                    $trans_qty = $stock->stock_bal;
                    $total_deduct -= $stock->stock_bal;
                    $stock->stock_bal = 0;
                } else {
                    $trans_qty = $total_deduct;
                    $stock->stock_bal -= $total_deduct;
                    $total_deduct = 0;
                }
                $stock->save();

                $issued_drug = DrugStockIssue::create([
                    'stock_id' => $stock->id,
                    'docointkey' => $docointkey,
                    'dmdcomb' => $dmdcomb,
                    'dmdctr' => $dmdctr,
                    'loc_code' => $loc_code,
                    'chrgcode' => $chrgcode,
                    'exp_date' => $stock->exp_date,
                    'qty' =>  $trans_qty,
                    'pchrgup' =>  $unit_price,
                    'pcchrgamt' =>  $pcchrgamt,
                    'status' => 'Issued',
                    'user_id' => session('user_id'),
                    'hpercode' => $this->hpercode,
                    'enccode' => $enccode,
                    'toecode' => $this->toecode,
                    'pcchrgcod' => $pcchrgcod,

                    'sc_pwd' => $tag == 'sc_pwd' ? $trans_qty : false,
                    'ems' => $tag == 'ems' ? $trans_qty : false,
                    'maip' => $tag == 'maip' ? $trans_qty : false,
                    'wholesale' => $tag == 'wholesale' ? $trans_qty : false,
                    'pay' => $tag == 'pay' ? $trans_qty : false,
                    'medicare' => $tag == 'medicare' ? $trans_qty : false,
                    'service' => $tag == 'service' ? $trans_qty : false,
                    'govt_emp' => $tag == 'govt_emp' ? $trans_qty : false,
                    'caf' => $tag == 'caf' ? $trans_qty : false,

                    'dmdprdte' => $stock->dmdprdte,
                ]);

                $log = DrugStockLog::firstOrNew([
                    'loc_code' => $stock->loc_code,
                    'dmdcomb' => $stock->dmdcomb,
                    'dmdctr' => $stock->dmdctr,
                    'chrgcode' => $stock->chrgcode,
                    'date_logged' => date('Y-m-d'),
                    'dmdprdte' => $stock->dmdprdte,
                    'unit_price' => $stock->retail_price,
                ]);
                $log->time_logged = now();
                $log->issue_qty += $trans_qty;

                $log->sc_pwd += $issued_drug->sc_pwd;
                $log->ems += $issued_drug->ems;
                $log->maip += $issued_drug->maip;
                $log->wholesale += $issued_drug->wholesale;
                $log->pay += $issued_drug->pay;
                $log->medicare += $issued_drug->medicare;
                $log->service += $issued_drug->service;
                $log->govt_emp += $issued_drug->govt_emp;
                $log->caf += $issued_drug->caf;

                $log->save();

                // $this->add_to_inventory($dmdcomb, $dmdctr, $loc_code, $chrgcode, $stock->exp_date, $trans_qty);

            } else {
                break;
            }
        }
    }

    public function reset_order()
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
        $items = DrugOrder::where('enccode', $enccode)
            ->whereRaw('item_id IS NOT NULL')
            ->get();
        foreach ($items as $item) {
            $item->estatus = 'U';
            $item->pcchrgcod = null;
            $item->save();
        }
        $this->emit('refresh');
    }

    // public function add_item(DrugStock $dm)
    public function add_item($dmdcomb, $dmdctr, $chrgcode, $loc_code, $dmdprdte, $id, $available, $exp_date)
    {
        $with_rx = false;
        if ($dmdcomb == $this->rx_dmdcomb and $dmdctr == $this->rx_dmdctr) {
            $with_rx = true;
            $rx_id = $this->rx_id;
            $empid = $this->empid;
        }

        $total_deduct = $this->order_qty;

        if ($this->ems) {
            $this->type = 'ems';
        } else if ($this->maip) {
            $this->type = 'maip';
        } else if ($this->wholesale) {
            $this->type = 'wholesale';
        } else if ($this->pay) {
            $this->type = 'pay';
        } else if ($this->service) {
            $this->type = 'service';
        } else if ($this->caf) {
            $this->type = 'caf';
        } else if ($this->is_ris) {
            $this->type = 'ris';
        } else if ($this->pcso) {
            $this->type = 'pcso';
        } else if ($this->phic) {
            $this->type = 'phic';
        } else if ($this->konsulta) {
            $this->type = 'konsulta';
        }

        if ($this->is_ris or $available >= $total_deduct) {
            $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
            DB::insert(
                'INSERT INTO hospital.dbo.hrxo(docointkey, enccode, hpercode, rxooccid, rxoref, dmdcomb, repdayno1, rxostatus,
                    rxolock, rxoupsw, rxoconfd, dmdctr, estatus, entryby, ordcon, orderupd, locacode, orderfrom, issuetype,
                    has_tag, tx_type, ris, pchrgqty, pchrgup, pcchrgamt, dodate, dotime, dodtepost, dotmepost, dmdprdte, exp_date, loc_code, item_id, remarks, prescription_data_id, prescribed_by )
                VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ? )',
                [
                    '0000040' . $this->hpercode . date('m/d/Yh:i:s', strtotime(now())) . $chrgcode . $dmdcomb . $dmdctr,
                    $enccode,
                    $this->hpercode,
                    '1',
                    '1',
                    $dmdcomb,
                    '1',
                    'A',
                    'N',
                    'N',
                    'N',
                    $dmdctr,
                    'U',
                    session('employeeid'),
                    'NEWOR',
                    'ACTIV',
                    'PHARM',
                    $chrgcode,
                    'c',
                    $this->type ? true : false,
                    $this->type,
                    $this->is_ris ? true : false,
                    $this->order_qty,
                    $this->unit_price,
                    $this->order_qty * $this->unit_price,
                    now(),
                    now(),
                    now(),
                    now(),
                    $dmdprdte,
                    $exp_date,
                    $loc_code,
                    $id,
                    $this->remarks ?? '',
                    $with_rx ? $rx_id : null,
                    $with_rx ? $empid : null,
                ]
            );
            if($with_rx){
                DB::connection('webapp')->table('webapp.dbo.prescription_data')
                ->where('id', $rx_id)
                ->update(['stat' => 'I']);
            }

            $this->resetExcept('rx_dmdcomb', 'rx_dmdctr', 'rx_id', 'empid', 'stocks', 'enccode', 'location_id', 'encounter', 'charges', 'hpercode', 'toecode', 'selected_items', 'patient', 'active_prescription', 'adm', 'wardname', 'rmname');
            $this->emit('refresh');
            $this->alert('success', 'Item added.');
        } else {
            $this->alert('error', 'Insufficient stock!');
        }
    }

    public function delete_item()
    {
        $has_delete = false;
        $items = DrugOrder::whereIn('docointkey', $this->selected_items)
            ->where('estatus', 'U')->get();

        foreach ($items as $item) {
            if($item->prescription_data_id){
                DB::connection('webapp')->table('webapp.dbo.prescription_data')
                ->where('id', $item->prescription_data_id)
                    ->update(['stat' => 'A']);
            }
            $item->delete();
            $has_delete = true;
        }

        $this->reset('selected_items');

        if ($has_delete) {
            $this->emit('refresh');
            $this->alert('success', 'Selected item/s deleted!');
        } else {
            $this->alert('error', 'Select pending items only!');
        }
    }

    public function return_issued(DrugOrder $item)
    {
        $this->validate([
            'return_qty' => ['required', 'numeric', 'min:1', 'max:' . $this->order_qty],
            'unit_price' => 'required',
            'docointkey' => 'required',
        ]);
        // dd($this->docointkey);

        //RECORD RETURN ITEM TO hrxoreturn table
        DrugOrderReturn::create([
            'docointkey' => $item->docointkey,
            'enccode' => $item->enccode,
            'hpercode' => $item->hpercode,
            'dmdcomb' => $item->dmdcomb,
            'returndate' => now(),
            'returntime' => now(),
            'qty' => $this->return_qty,
            'returnby' => session('employeeid'),
            'status' => 'A',
            'rxolock' => 'N',
            'updsw' => 'N',
            'confdl' => 'N',
            'entryby' => session('employeeid'),
            'locacode' => $item->locacode,
            'dmdctr' => $item->dmdctr,
            'dmdprdte' => $item->dmdprdte,
            'remarks' => $item->remarks,
            'returnfrom' => $item->orderfrom,
            'chrgcode' => $item->orderfrom,
            'pcchrgcod' => $item->pcchrgcod,
            'rcode' => '',
            'unit_price' => $item->pchrgup,
            'pchrgup' => $item->pchrgup,
            'dmdprdte' => $item->dmdprdte,
        ]);

        //DEDUCT QTYISSUED FROM hrxo and DrugStockIssue table
        $item->pcchrgamt = $item->pchrgup * ($item->qtyissued - $this->return_qty);
        $item->qtyissued -= $this->return_qty;
        $item->save();

        $issued_items = DrugStockIssue::where('docointkey', $this->docointkey)->latest()->with('stock')->get();
        $qty_to_return = $this->return_qty;
        foreach ($issued_items as $stock_issued) {
            if ($qty_to_return > $stock_issued->qty) {
                $returned_qty = $stock_issued->qty;
                $qty_to_return -= $stock_issued->qty;
                $stock_issued->returned_qty = $stock_issued->qty;
                $stock_issued->qty = 0;
            } else {
                $returned_qty = $qty_to_return;
                $stock_issued->qty -= $qty_to_return;
                $stock_issued->returned_qty = $qty_to_return;
                $qty_to_return = 0;
                $stock_issued->qty = 0;
            }
            //Return QTY to DrugStock table
            $stock_issued->stock->stock_bal += $returned_qty;

            $log = DrugStockLog::firstOrNew([
                'loc_code' =>  $stock_issued->stock->loc_code,
                'dmdcomb' =>  $stock_issued->stock->dmdcomb,
                'dmdctr' =>  $stock_issued->stock->dmdctr,
                'chrgcode' =>  $stock_issued->stock->chrgcode,
                'date_logged' => date('Y-m-d'),
                'dmdprdte' =>  $stock_issued->stock->dmdprdte,
                'unit_price' =>  $stock_issued->stock->retail_price,
            ]);
            $log->time_logged = now();
            $log->return_qty += $returned_qty;

            $log->save();
            $stock_issued->stock->save();
            $stock_issued->save();
        }

        $this->emit('refresh');
        $this->alert('success', 'Item returned.');
    }

    public function add_prescribed_item($dmdcomb, $dmdctr)
    {
        $rx_id = $this->rx_id;
        $empid = $this->empid;

        if ($this->sc) {
            $this->type = 'sc_pwd';
        } else if ($this->ems) {
            $this->type = 'ems';
        } else if ($this->maip) {
            $this->type = 'maip';
        } else if ($this->wholesale) {
            $this->type = 'wholesale';
        } else if ($this->pay) {
            $this->type = 'pay';
        } else if ($this->medicare) {
            $this->type = 'medicare';
        } else if ($this->service) {
            $this->type = 'service';
        } else if ($this->caf) {
            $this->type = 'caf';
        } else if ($this->govt) {
            $this->type = 'govt';
        }
        $dm = DrugStock::where('dmdcomb', $dmdcomb)
            ->where('dmdctr', $dmdctr)
            ->where('chrgcode', $this->rx_charge_code)
            ->where('loc_code', $this->location_id)
            ->where('stock_bal', '>', '0')
            ->orderBy('exp_date', 'ASC')
            ->first();


        if ($dm) {
            $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));

            DrugOrder::create([
                'docointkey' => '0000040' . $this->hpercode . date('m/d/Yh:i:s', strtotime(now())) . $dm->chrgcode . $dm->dmdcomb . $dm->dmdctr,
                'enccode' => $enccode,
                'hpercode' => $this->hpercode,
                'rxooccid' => '1',
                'rxoref' => '1',
                'dmdcomb' => $dm->dmdcomb,
                'repdayno1' => '1',
                'rxostatus' => 'A',
                'rxolock' => 'N',
                'rxoupsw' => 'N',
                'rxoconfd' => 'N',
                'dmdctr' => $dm->dmdctr,
                'estatus' => 'U',
                'entryby' => session('employeeid'),
                'ordcon' => 'NEWOR',
                'orderupd' => 'ACTIV',
                'locacode' => 'PHARM',
                'orderfrom' => $dm->chrgcode,
                'issuetype' => 'c',
                'has_tag' => $this->type ? true : false, //added
                'tx_type' => $this->type, //added
                'ris' =>  $this->is_ris ? true : false,
                'pchrgqty' => $this->order_qty,
                'pchrgup' => $dm->current_price->dmselprice,
                'pcchrgamt' => $this->order_qty * $dm->current_price->dmselprice,
                'dodate' => now(),
                'dotime' => now(),
                'dodtepost' => now(),
                'dotmepost' => now(),
                'dmdprdte' => $dm->dmdprdte,
                'exp_date' => $dm->exp_date, //added
                'loc_code' => $dm->loc_code, //added
                'item_id' => $dm->id, //added
                'remarks' => $this->remarks, //added
                'prescription_data_id' => $rx_id,
                'prescribed_by' => $empid,
            ]);
            DB::connection('webapp')->table('webapp.dbo.prescription_data')
            ->where('id', $rx_id)
            ->update(['stat' => 'I']);

            $this->resetExcept('stocks', 'enccode', 'location_id', 'encounter', 'charges', 'hpercode', 'toecode', 'selected_items', 'patient', 'active_prescription', 'adm', 'wardname', 'rmname');
            $this->emit('refresh');
            $this->alert('success', 'Item added.');
        } else {
            $this->alert('error', 'Insufficient stock!');
        }
    }

    public function update_remarks()
    {
        $this->validate(['selected_remarks' => ['required'], 'new_remarks' => ['nullable', 'string', 'max:255']]);
        $rxo = DrugOrder::find($this->selected_remarks);
        $rxo->remarks = $this->new_remarks;
        $rxo->save();
        $this->emit('refresh');
        $this->alert('success', 'Remarks updated');
        $this->reset('selected_remarks', 'new_remarks');
    }
}
