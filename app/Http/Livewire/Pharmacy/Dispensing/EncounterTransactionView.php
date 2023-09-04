<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use Livewire\Component;
use App\Models\Pharmacy\Drug;
use Illuminate\Support\Facades\Auth;
use App\Models\References\ChargeCode;
use Illuminate\Support\Facades\Crypt;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Http\Controllers\SharedController;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use Jantinnerezo\LivewireAlert\LivewireAlert;
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

    protected $listeners = ['charge_items', 'issue_order', 'add_item', 'return_issued', 'add_prescribed_item', 'refresh' => '$refresh'];

    public $generic, $charge_code = [];
    public $enccode, $location_id, $hpercode, $toecode;

    public $order_qty, $unit_price, $return_qty, $docointkey;
    public $item_id;
    public $sc, $ems, $maip, $wholesale, $pay, $medicare, $service, $caf, $govt, $type;
    public $is_ris = false;

    public $charges;
    public $encounter;

    public $selected_items = [];

    public function render()
    {

        $stocks = DrugStock::with('charge')->with('current_price')->has('current_price')
            ->where('loc_code', $this->location_id);
        if ($this->charge_code) {
            $stocks->whereIn('chrgcode', $this->charge_code);
        }
        $stocks->groupBy('dmdcomb', 'dmdctr', 'chrgcode', 'dmdprdte', 'drug_concat')->select('dmdcomb', 'dmdctr', 'drug_concat', 'chrgcode', 'dmdprdte')->selectRaw('SUM(stock_bal) as stock_bal, MAX(id) as id');

        return view('livewire.pharmacy.dispensing.encounter-transaction-view', [
            'stocks' => $stocks->get(),
        ]);
    }

    public function mount($enccode)
    {
        $this->enccode = $enccode;

        $this->location_id = Auth::user()->pharm_location_id;

        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));

        $this->encounter = EncounterLog::where('enccode', $enccode)
            ->with('patient')->with('rxo')->with('active_prescription')->with('adm')->first();

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
    }

    public function charge_items()
    {
        $charge_code = OrderChargeCode::create([
            'charge_desc' => 'a',
        ]);

        $pcchrgcod = 'P' . date('y') . '-' . sprintf('%07d', $charge_code->id);
        $cnt = 0;

        $rxo = DrugOrder::whereIn('docointkey', $this->selected_items)
            ->where('estatus', 'U')->get();

        foreach ($rxo as $rx) {
            if ($this->is_ris or $rx->item and $rx->item->sum('stock_bal') >= $rx->pchrgqty) {
                foreach ($rx->item->all() as $item) {
                    $rx->pcchrgcod = $pcchrgcod;
                    $rx->estatus = 'P';
                    $rx->save();

                    $log = DrugStockLog::firstOrNew([
                        'loc_code' => $item->loc_code,
                        'dmdcomb' => $item->dmdcomb,
                        'dmdctr' => $item->dmdctr,
                        'chrgcode' => $item->chrgcode,
                        'date_logged' => date('Y-m-d'),
                        'dmdprdte' => $item->dmdprdte,
                        'unit_price' => $item->retail_price,
                    ]);
                    $log->time_logged = now();
                    $log->charged_qty += $rx->pchrgqty;

                    $log->save();
                }
                $cnt = 1;
            } else {
                $cnt = 2;
                break;
            }
        }

        $this->emit('refresh');

        if ($cnt == 1) {
            $this->alert('success', 'Charge slip created.');
        } elseif ($cnt == 2) {
            $this->alert('error', 'Insufficient Stock Balance.');
        } else {
            $this->alert('error', 'No item to charge.');
        }
    }

    public function issue_order()
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
        $cnt = 0;

        $rxos = DrugOrder::whereIn('docointkey', $this->selected_items)
            ->where('estatus', 'P')->get();

        foreach ($rxos as $row) {
            if ($row->item) {
                if ($row->item->sum('stock_bal') >= $row->pchrgqty) {
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

                //START UPDATE PRESCRIPTION
                $rx_header = Prescription::where('enccode', $enccode)
                    ->with('data_active')->has('data_active')
                    ->get();
                if ($rx_header) {
                    foreach ($rx_header as $rxh) {
                        $rx_data = $rxh->data_active()
                            ->where('dmdcomb', $rxo->dmdcomb)
                            ->where('dmdctr', $rxo->dmdctr)
                            ->first();
                        if ($rx_data) {
                            PrescriptionDataIssued::create([
                                'presc_data_id' => $rx_data->id,
                                'docointkey' => $rxo->docointkey,
                                'qtyissued' => $rxo->pchrgqty,
                            ]);

                            if ($rx_data->issued()->sum('qtyissued') >= $rx_data->qty) {
                                $rx_data->stat = 'I';
                                $rx_data->save();
                            }

                            $rxo->order_by = $rxh->empid;
                            $rxo->deptcode = $rxh->employee->deptcode;
                        }
                    }
                }
                //END UPDATE PRESCRIPTION

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
                            'user_id' => auth()->user()->id,
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

                    } else {
                        break;
                    }
                }
                //END DEDUCT TO STOCKS

                $row->estatus = 'S';
                $row->qtyissued = $row->pchrgqty;
                $row->save();

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
                    'qty' => $row->pchrgqty,
                    'issuedby' => auth()->user()->employeeid,
                    'status' => 'A', //A
                    'rxolock' => 'N', //N
                    'updsw' => 'N', //N
                    'confdl' => 'N', //N
                    'entryby' => auth()->user()->employeeid,
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
                    'user_id' => auth()->user()->id,
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

    public function add_item(DrugStock $dm)
    {
        $dmdcomb = $dm->dmdcomb;
        $dmdctr = $dm->dmdctr;
        $chrgcode = $dm->chrgcode;
        $loc_code = $dm->loc_code;
        $total_deduct = $this->order_qty;

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

        // $available = SharedController::available_stock($dmdcomb, $dmdctr, $chrgcode, $loc_code);

        $available = DrugStock::where('dmdcomb', $dmdcomb)
            ->where('dmdctr', $dmdctr)
            ->where('chrgcode', $chrgcode)
            ->where('loc_code', $loc_code)
            ->where('stock_bal', '>', '0')
            ->where('exp_date', '>', now())
            ->sum('stock_bal');

        if ($this->is_ris or $available >= $total_deduct) {
            $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
            DrugOrder::updateOrCreate([
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
                'entryby' => auth()->user()->employeeid,
                'ordcon' => 'NEWOR',
                'orderupd' => 'ACTIV',
                'locacode' => 'PHARM',
                'orderfrom' => $dm->chrgcode,
                'issuetype' => 'c',
                'has_tag' => $this->type ? true : false, //added
                'tx_type' => $this->type, //added
                'ris' =>  $this->is_ris ? true : false,
            ], [
                'pchrgqty' => $this->order_qty,
                'pchrgup' => $this->unit_price,
                'pcchrgamt' => $this->order_qty * $this->unit_price,
                'dodate' => now(),
                'dotime' => now(),
                'dodtepost' => now(),
                'dotmepost' => now(),
                'dmdprdte' => $dm->dmdprdte,
                'exp_date' => $dm->exp_date, //added
                'loc_code' => $dm->loc_code, //added
                'item_id' => $dm->id, //added
            ]);
            $this->resetExcept('enccode', 'location_id', 'encounter', 'charges', 'hpercode', 'toecode', 'selected_items');
            $this->emit('refresh');
            $this->alert('success', 'Item added.');
        } else {
            $this->alert('error', 'Insufficient stock!');
        }
    }

    public function add_hrxo($dm)
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
        DrugOrder::updateOrCreate([
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
            'entryby' => auth()->user()->employeeid,
            'ordcon' => 'NEWOR',
            'orderupd' => 'ACTIV',
            'locacode' => 'PHARM',
            'orderfrom' => $dm->chrgcode,
            'issuetype' => 'c',
            'has_tag' => $this->type ? true : false, //added
            'tx_type' => $this->type, //added
        ], [
            'pchrgqty' => $this->order_qty,
            'pchrgup' => $this->unit_price,
            'pcchrgamt' => $this->order_qty * $this->unit_price,
            'dodate' => now(),
            'dotime' => now(),
            'dodtepost' => now(),
            'dotmepost' => now(),
            'dmdprdte' => $dm->dmdprdte,
            'exp_date' => $dm->exp_date, //added
            'loc_code' => $dm->loc_code, //added
            'item_id' => $dm->id, //added
        ]);
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
            'returnby' => auth()->user()->employeeid,
            'status' => 'A',
            'rxolock' => 'N',
            'updsw' => 'N',
            'confdl' => 'N',
            'entryby' => auth()->user()->employeeid,
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

    public function add_prescribed_item(PrescriptionData $rxd)
    {
        $dmdcomb = $rxd->dmdcomb;
        $dmdctr = $rxd->dmdctr;
        $loc_code = $this->location_id;
        $total_deduct = $this->order_qty;

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
            ->where('loc_code', $this->location_id)
            ->where('stock_bal', '>', '0')
            ->orderBy('exp_date', 'ASC')
            ->first();

        if ($dm) {
            $available = DrugStock::where('dmdcomb', $dmdcomb)
                ->where('dmdctr', $dmdctr)
                ->where('loc_code', $loc_code)
                ->where('stock_bal', '>', '0')
                ->where('exp_date', '>', now())
                ->sum('stock_bal');

            if ($this->is_ris or $available >= $total_deduct) {
                $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
                DrugOrder::updateOrCreate([
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
                    'entryby' => auth()->user()->employeeid,
                    'ordcon' => 'NEWOR',
                    'orderupd' => 'ACTIV',
                    'locacode' => 'PHARM',
                    'orderfrom' => $dm->chrgcode,
                    'issuetype' => 'c',
                    'has_tag' => $this->type ? true : false, //added
                    'tx_type' => $this->type, //added
                    'ris' =>  $this->is_ris ? true : false,
                    'prescription_data_id' => $rxd->id,
                    'prescribed_by' => $rxd->rx->empid,
                ], [
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
                ]);
                $this->resetExcept('enccode', 'location_id', 'encounter', 'charges', 'hpercode', 'toecode', 'selected_items');
                $this->emit('refresh');
                $this->alert('success', 'Item added.');
            } else {
                $this->alert('error', 'Insufficient stock!');
            }
        } else {
            $this->alert('error', 'Insufficient stock!');
        }
    }
}
