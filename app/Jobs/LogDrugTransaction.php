<?php

namespace App\Jobs;

use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogDrugTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pharm_location_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $dmdprdte, $unit_cost, $retail_price, $qty, $stock_id, $exp_date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($pharm_location_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $dmdprdte, $unit_cost, $retail_price, $qty, $stock_id, $exp_date)
    {
        $this->onQueue('stocklogger');
        $this->pharm_location_id = $pharm_location_id;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->chrgcode = $chrgcode;
        $this->trans_date = $trans_date;
        $this->dmdprdte = $dmdprdte;
        $this->unit_cost = $unit_cost;
        $this->retail_price = $retail_price;
        $this->qty = $qty;
        $this->stock_id = $stock_id;
        $this->exp_date = $exp_date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $date = Carbon::parse($this->trans_date)->startOfMonth()->format('Y-m-d');

        $log = DrugStockLog::firstOrNew([
            'loc_code' =>  $this->pharm_location_id,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'chrgcode' => $this->chrgcode,
            'date_logged' => $date,
            'dmdprdte' => $this->dmdprdte,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->retail_price,
        ]);
        $log->time_logged = now();
        $log->beg_bal += $this->qty;

        $log->save();

        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $this->chrgcode,
            'loc_code' => $this->pharm_location_id,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'exp_date' => $this->exp_date,
            'stock_date' => $date,
        ]);
        $card->rec += $this->qty;
        $card->bal += $this->qty;

        $card->save();
    }
}
