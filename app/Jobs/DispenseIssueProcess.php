<?php

namespace App\Jobs;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Record\Prescriptions\PrescriptionDataIssued;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class DispenseIssueProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $selected_items, $toecode, $employeeid, $user_id;

    public function middleware(): array
    {
        return [(new WithoutOverlapping())];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($selected_items, $toecode, $employeeid, $user_id)
    {
        $this->onQueue('rxtx');
        $this->selected_items = $selected_items;
        $this->toecode = $toecode;
        $this->employeeid = $employeeid;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $rxos = DrugOrder::whereIn('docointkey', $this->selected_items)
            ->where('estatus', 'S')->get();

        foreach ($rxos as $rxo) {

            if ($rxo->prescription_data_id) {
                PrescriptionDataIssued::create([
                    'presc_data_id' => $rxo->prescription_data_id,
                    'docointkey' => $rxo->docointkey,
                    'qtyissued' => $rxo->pchrgqty,
                ]);
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
                    if (!$rxo->ris) {
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
                        'user_id' => $this->user_id,
                        'hpercode' => $rxo->hpercode,
                        'enccode' => $rxo->enccode,
                        'toecode' => $this->toecode,
                        'pcchrgcod' => $pcchrgcod,

                        'ems' => $tag == 'ems' ? $trans_qty : false,
                        'maip' => $tag == 'maip' ? $trans_qty : false,
                        'wholesale' => $tag == 'wholesale' ? $trans_qty : false,
                        'pay' => $tag == 'pay' ? $trans_qty : false,
                        'service' => $tag == 'service' ? $trans_qty : false,
                        'caf' => $tag == 'caf' ? $trans_qty : false,
                        'ris' =>  $rxo->ris ? true : false,

                        'konsulta' => $tag == 'konsulta' ? $trans_qty : false,
                        'pcso' => $tag == 'pcso' ? $trans_qty : false,
                        'phic' => $tag == 'phic' ? $trans_qty : false,

                        'dmdprdte' => $stock->dmdprdte,
                    ]);

                    $date = Carbon::parse(now())->startOfMonth()->format('Y-m-d');
                    $log = DrugStockLog::firstOrNew([
                        'loc_code' => $stock->loc_code,
                        'dmdcomb' => $stock->dmdcomb,
                        'dmdctr' => $stock->dmdctr,
                        'chrgcode' => $stock->chrgcode,
                        'date_logged' => $date,
                        'dmdprdte' => $stock->dmdprdte,
                        'unit_price' => $stock->retail_price,
                        'consumption_id' => session('active_consumption'),
                    ]);
                    $log->time_logged = now();
                    $log->issue_qty += $trans_qty;

                    $log->wholesale += $issued_drug->wholesale;
                    $log->ems += $issued_drug->ems;
                    $log->maip += $issued_drug->maip;
                    $log->caf += $issued_drug->caf;
                    $log->ris += $issued_drug->ris ? 1 : 0;

                    $log->pay += $issued_drug->pay;
                    $log->service += $issued_drug->service;

                    //removed columns
                    // $log->sc_pwd += $issued_drug->sc_pwd;
                    // $log->medicare += $issued_drug->medicare;
                    // $log->govt_emp += $issued_drug->govt_emp;

                    // added columns
                    $log->konsulta += $issued_drug->konsulta;
                    $log->pcso += $issued_drug->pcso;
                    $log->phic += $issued_drug->phic;

                    $log->save();

                    // $this->add_to_inventory($dmdcomb, $dmdctr, $loc_code, $chrgcode, $stock->exp_date, $trans_qty);


                    $card = DrugStockCard::firstOrNew([
                        'chrgcode' => $stock->chrgcode,
                        'loc_code' => $stock->warehouse_id,
                        'dmdcomb' => $stock->dmdcomb,
                        'dmdctr' => $stock->dmdctr,
                        'exp_date' => $stock->exp_date,
                        'stock_date' => $date,
                        'drug_concat' => $stock->drug_concat(),
                    ]);
                    $card->iss += $trans_qty;
                    $card->bal -= $trans_qty;

                    $card->save();
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
                'issuedby' => $this->employeeid,
                'status' => 'A', //A
                'rxolock' => 'N', //N
                'updsw' => 'N', //N
                'confdl' => 'N', //N
                'entryby' => $this->employeeid,
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
    }
}
