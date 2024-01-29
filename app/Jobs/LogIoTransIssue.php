<?php

namespace App\Jobs;

use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogIoTransIssue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $warehouse_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $retail_price, $dmdprdte, $trans_time, $qty, $exp_date, $drug_concat;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($warehouse_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $retail_price, $dmdprdte, $trans_time, $qty, $exp_date, $drug_concat)
    {
        $this->onQueue('iotx');
        $this->warehouse_id = $warehouse_id;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->chrgcode = $chrgcode;
        $this->trans_date = $trans_date;
        $this->retail_price = $retail_price;
        $this->dmdprdte = $dmdprdte;
        $this->trans_time = $trans_time;
        $this->qty = $qty;
        $this->exp_date = $exp_date;
        $this->drug_concat = $drug_concat;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = DrugStockLog::firstOrNew([
            'loc_code' => $this->warehouse_id,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'chrgcode' => $this->chrgcode,
            'date_logged' => $this->trans_date,
            'unit_price' => $this->retail_price,
            'dmdprdte' => $this->dmdprdte,
        ]);
        $log->time_logged = $this->trans_time;
        $log->transferred += $this->qty;
        $log->save();

        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $this->chrgcode,
            'loc_code' => $this->warehouse_id,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'exp_date' => $this->exp_date,
            'stock_date' => $this->trans_date,
            'drug_concat' => $this->drug_concat,
        ]);
        $card->iss += $this->qty;
        $card->bal -= $this->qty;

        $card->save();
    }
}
