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
use App\Models\Pharmacy\Dispensing\DrugOrderReturn;
use App\Models\Pharmacy\Dispensing\OrderChargeCode;
use App\Models\Record\Prescriptions\PrescriptionDataIssued;

class EncounterTransactionView extends Component
{
    use LivewireAlert;

    protected $listeners = ['charge_items', 'issue_order', 'add_item', 'return_issued'];

    public $generic, $charge_code = "";
    public $enccode, $location_id, $hpercode, $toecode;

    public $order_qty, $unit_price, $return_qty, $docointkey;
    public $item_id;
    public $sc, $ems, $maip, $wholesale, $pay, $medicare, $service, $caf, $govt, $type;

    public function render()
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
        $encounter = EncounterLog::where('enccode', $enccode)
                                ->with('patient')->with('rxo')->with('active_prescription')->first();


        if(!$this->hpercode){
            $this->hpercode = $encounter->hpercode;
            $this->toecode = $encounter->toecode;
        }

        $charges = DrugStock::select('chrgcode')->with('charge')->where('loc_code', $this->location_id)->groupBy('chrgcode')->get();

        $stocks = DrugStock::with('charge')->with('drug')->with('current_price')->has('current_price')
                        ->where('loc_code', $this->location_id)
                        ->where('chrgcode', 'LIKE', '%'.$this->charge_code.'%')
                        ->whereHas('drug', function ($query) {
                            return $query->whereRelation('generic', 'gendesc','LIKE', '%'.$this->generic.'%');
                        })
                        ->groupBy('dmdcomb', 'dmdctr', 'chrgcode', 'dmdprdte')->select('dmdcomb', 'dmdctr', 'chrgcode', 'dmdprdte')->selectRaw('SUM(stock_bal) as stock_bal, MAX(id) as id');

        return view('livewire.pharmacy.dispensing.encounter-transaction-view', [
            'encounter' => $encounter,
            'stocks' => $stocks->get(),
            'charges' => $charges,
        ]);
    }

    public function mount($enccode)
    {
        $this->enccode = $enccode;
        $this->location_id = Auth::user()->pharm_location_id;
    }

    public function charge_items()
    {
        $charge_code = OrderChargeCode::create([
                            'charge_desc' => 'a',
                        ]);

        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));

        $pcchrgcod = 'P'.date('y').'-'.$charge_code->id;
        $cnt = 0;

        $rxo = DrugOrder::where('enccode', $enccode)
                        ->where('estatus', 'U')
                        ->get();

        foreach($rxo as $rx){
            if($rx->item){
                if($rx->item->stock_bal >= $rx->pchrgqty){
                    $rx->pcchrgcod = $pcchrgcod;
                    $rx->estatus = 'P';
                    $rx->save();

                    $log = DrugStockLog::firstOrNew([
                        'loc_code' => $rx->item->loc_code,
                        'dmdcomb' => $rx->item->dmdcomb,
                        'dmdctr' => $rx->item->dmdctr,
                        'chrgcode' => $rx->item->chrgcode,
                        'date_logged' => date('Y-m-d'),
                        'dmdprdte' => $rx->item->dmdprdte,
                        'unit_price' => $rx->item->markup_price,
                    ]);
                    $log->time_logged = now();
                    $log->charged_qty += $rx->pchrgqty;

                    $log->save();
                    $cnt = 1;
                }else{
                    $cnt = 2;
                    break;
                }
            }else{
                $cnt = 2;
            }
        }

        if($cnt == 1){
            $this->alert('success', 'Charge slip created.');
        }elseif($cnt == 2){
            $this->alert('error', 'Insufficient Stock Balance.');
        }else{
            $this->alert('error', 'No item to charge.');
        }
    }

    public function issue_order()
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
        $cnt = 0;

        $rxo = DrugOrder::where('enccode', $enccode)
                        ->where('estatus', 'P')
                        ->get();

        foreach($rxo as $row)
        {
            if($row->item){
                if($row->item->stock_bal >= $row->pchrgqty){
                    $cnt = 1;
                }else{
                    $cnt = 2;
                    break;
                }
            }
        }

        if($cnt == 1){
            foreach($rxo as $row){
                $this->update_prescription($row->dmdctr, $row->dmdcomb, $row->docointkey, $row->pchrgqty);
                $this->deduct_stocks($row->dmdctr, $row->dmdcomb, $row->orderfrom, $row->pchrgqty, $row->loc_code, $row->docointkey, $row->pcchrgcod, $row->tx_type, $row->pcchrgamt, $row->pchrgup, $enccode);

                $row->estatus = 'S';
                $row->qtyissued = $row->pchrgqty;
                $row->save();

                SharedController::record_hrxoissue($row->docointkey, $row->pchrgqty);
            }
            $this->alert('success', 'Order issued successfully.');
        }elseif($cnt == 2){
            $this->alert('error', 'Insufficient Stock Balance.');
        }else{
            $this->alert('error', 'No item to issue.');
        }
    }

    public function update_prescription($dmdctr, $dmdcomb, $docointkey, $qtyissued)
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));

        $rx_header = Prescription::where('enccode', $enccode)
                                            ->with('data_active')
                                            ->get();
        if($rx_header){
            foreach($rx_header as $rxh){
                $rx_data = $rxh->data_active()
                                ->where('dmdcomb', $dmdcomb)
                                ->where('dmdctr', $dmdctr)
                                ->get();
                if($rx_data){
                    PrescriptionDataIssued::create([
                        'presc_data_id' => $rx_data->id,
                        'docointkey' => $docointkey,
                        'qtyissued' => $qtyissued,
                    ]);

                    if($rx_data->issued()->sum('qtyissued') >= $rx_data->qty){
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

        foreach($stocks as $stock){
            $trans_qty = 0;
            if($total_deduct){
                if($total_deduct > $stock->stock_bal){
                    $trans_qty = $stock->stock_bal;
                    $total_deduct -= $stock->stock_bal;
                    $stock->stock_bal = 0;
                }else{
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
                    'status'=> 'Issued',
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
                    'unit_price' => $stock->markup_price,
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

            }else{
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
        foreach($items as $item)
        {
            $item->estatus = 'U';
            $item->pcchrgcod = null;
            $item->save();
        }
    }

    public function add_item(DrugStock $dm)
    {
        $dmdcomb = $dm->dmdcomb;
        $dmdctr = $dm->dmdctr;
        $chrgcode = $dm->chrgcode;
        $loc_code = $dm->loc_code;
        $total_deduct = $this->order_qty;

        if($this->sc){
            $this->type = 'sc_pwd';
        }else if($this->ems){
            $this->type = 'ems';
        }else if($this->maip){
            $this->type = 'maip';
        }else if($this->wholesale){
            $this->type = 'wholesale';
        }else if($this->pay){
            $this->type = 'pay';
        }else if($this->medicare){
            $this->type = 'medicare';
        }else if($this->service){
            $this->type = 'service';
        }else if($this->caf){
            $this->type = 'caf';
        }else if($this->govt){
            $this->type = 'govt';
        }

        $available = SharedController::available_stock($dmdcomb, $dmdctr, $chrgcode, $loc_code);

        if($available >= $total_deduct){
            $this->add_hrxo($dm);
            $this->resetExcept('enccode', 'location_id');
            $this->alert('success', 'Item added.');
        }else{
            $this->alert('error', 'Insufficient stock!');
        }
    }

    public function add_hrxo($dm)
    {
        $enccode = str_replace('-', ' ', Crypt::decrypt($this->enccode));
        DrugOrder::updateOrCreate([
            'docointkey' => '0000040'.$this->hpercode.date('m/d/Yh:i:s', strtotime(now())).$dm->chrgcode.$dm->dmdcomb.$dm->dmdctr,
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
            'has_tag' => $this->type ? true : false,//added
            'tx_type' => $this->type,//added
        ],[
            'pchrgqty' => $this->order_qty,
            'pchrgup' => $this->unit_price,
            'pcchrgamt' => $this->order_qty * $this->unit_price,
            'dodate' => now(),
            'dotime' => now(),
            'dodtepost' => now(),
            'dotmepost' => now(),
            'dmdprdte' => $dm->dmdprdte,
            'exp_date' => $dm->exp_date,//added
            'loc_code' => $dm->loc_code,//added
            'item_id' => $dm->id,//added
        ]);
    }

    public function return_issued(DrugOrder $item)
    {
        $this->validate([
            'return_qty' => ['required', 'numeric', 'min:1', 'max:'.$this->order_qty],
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
        foreach($issued_items as $stock_issued){
            if($qty_to_return > $stock_issued->qty){
                $returned_qty = $stock_issued->qty;
                $qty_to_return -= $stock_issued->qty;
                $stock_issued->returned_qty = $stock_issued->qty;
                $stock_issued->qty = 0;
            }else{
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
                'unit_price' =>  $stock_issued->stock->markup_price,
            ]);
            $log->time_logged = now();
            $log->return_qty += $returned_qty;

            $log->save();
            $stock_issued->stock->save();
            $stock_issued->save();
        }

        $this->alert('success', 'Item returned.');

    }

}
