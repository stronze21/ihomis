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

class LogIoTransReceive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to, $dmdcomb, $dmdctr, $chrgcode, $date_logged, $dmdprdte, $retail_price, $time_logged, $qty, $stock_id, $exp_date, $drug_concat;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($to, $dmdcomb, $dmdctr, $chrgcode, $date_logged, $dmdprdte, $retail_price, $time_logged, $qty, $stock_id, $exp_date, $drug_concat)
    {
        $this->onQueue('iotx');
        $this->to = $to;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->chrgcode = $chrgcode;
        $this->date_logged = $date_logged;
        $this->dmdprdte = $dmdprdte;
        $this->retail_price = $retail_price;
        $this->time_logged = $time_logged;
        $this->qty = $qty;
        $this->stock_id = $stock_id;
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
            'loc_code' => $this->to,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'chrgcode' => $this->chrgcode,
            'date_logged' => $this->date_logged,
            'dmdprdte' => $this->dmdprdte,
            'unit_price' => $this->retail_price,
        ]);
        $log->time_logged = $this->time_logged;
        $log->received += $this->qty;
        $log->save();

        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $this->chrgcode,
            'loc_code' => $this->to,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'exp_date' => $this->exp_date,
            'stock_date' => $this->date_logged,
            'drug_concat' => $this->drug_concat,
        ]);
        $card->rec += $this->qty;
        $card->bal += $this->qty;

        $card->save();
    }
}
