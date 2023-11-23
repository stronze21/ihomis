<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class LogDrugStockIssue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $stock_id, $docointkey, $dmdcomb, $dmdctr, $loc_code, $chrgcode, $exp_date, $trans_qty, $unit_price, $pcchrgamt, $user_id, $hpercode, $enccode, $toecode, $pcchrgcod, $tag, $ris, $dmdprdte, $retail_price;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($stock_id, $docointkey, $dmdcomb, $dmdctr, $loc_code, $chrgcode, $exp_date, $trans_qty, $unit_price, $pcchrgamt, $user_id, $hpercode, $enccode, $toecode, $pcchrgcod, $tag, $ris, $dmdprdte, $retail_price)
    {
        $this->onQueue('rx_issue_logger');
        $this->stock_id = $stock_id;
        $this->docointkey = $docointkey;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->loc_code = $loc_code;
        $this->chrgcode = $chrgcode;
        $this->exp_date = $exp_date;
        $this->trans_qty = $trans_qty;
        $this->unit_price = $unit_price;
        $this->pcchrgamt = $pcchrgamt;
        $this->user_id = $user_id;
        $this->hpercode = $hpercode;
        $this->enccode = $enccode;
        $this->toecode = $toecode;
        $this->pcchrgcod = $pcchrgcod;
        $this->tag = $tag;
        $this->ris = $ris;
        $this->dmdprdte = $dmdprdte;
        $this->retail_price = $retail_price;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $issued_drug = DrugStockIssue::create([
            'stock_id' => $this->stock_id,
            'docointkey' => $this->docointkey,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'loc_code' => $this->loc_code,
            'chrgcode' => $this->chrgcode,
            'exp_date' => $this->exp_date,
            'qty' =>  $this->trans_qty,
            'pchrgup' =>  $this->unit_price,
            'pcchrgamt' =>  $this->pcchrgamt,
            'status' => 'Issued',
            'user_id' => $this->user_id,
            'hpercode' => $this->hpercode,
            'enccode' => $this->enccode,
            'toecode' => $this->toecode,
            'pcchrgcod' => $this->pcchrgcod,

            'ems' => $this->tag == 'ems' ? $this->trans_qty : false,
            'maip' => $this->tag == 'maip' ? $this->trans_qty : false,
            'wholesale' => $this->tag == 'wholesale' ? $this->trans_qty : false,
            'pay' => $this->tag == 'pay' ? $this->trans_qty : false,
            'service' => $this->tag == 'service' ? $this->trans_qty : false,
            'caf' => $this->tag == 'caf' ? $this->trans_qty : false,
            'ris' =>  $this->rxo->ris ? true : false,

            'konsulta' => $this->tag == 'konsulta' ? $this->trans_qty : false,
            'pcso' => $this->tag == 'pcso' ? $this->trans_qty : false,
            'phic' => $this->tag == 'phic' ? $this->trans_qty : false,

            'dmdprdte' => $this->dmdprdte,
        ]);

        $log = DrugStockLog::firstOrNew([
            'loc_code' => $this->loc_code,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'chrgcode' => $this->chrgcode,
            'date_logged' => date('Y-m-d'),
            'dmdprdte' => $this->dmdprdte,
            'unit_price' => $this->retail_price,
        ]);
        $log->time_logged = now();
        $log->issue_qty += $this->trans_qty;

        $log->wholesale += $issued_drug->wholesale;
        $log->ems += $issued_drug->ems;
        $log->maip += $issued_drug->maip;
        $log->caf += $issued_drug->caf;
        $log->ris += $issued_drug->ris ? 1 : 0;

        $log->pay += $issued_drug->pay;
        $log->service += $issued_drug->service;

        // added columns
        $log->konsulta += $issued_drug->konsulta;
        $log->pcso += $issued_drug->pcso;
        $log->phic += $issued_drug->phic;

        $log->save();
    }
}