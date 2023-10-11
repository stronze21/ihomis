<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class LogDrugTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pharm_location_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $dmdprdte, $unit_cost, $retail_price, $qty;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($pharm_location_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $dmdprdte, $unit_cost, $retail_price, $qty)
    {
        $this->pharm_location_id = $pharm_location_id;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->chrgcode = $chrgcode;
        $this->trans_date = $trans_date;
        $this->dmdprdte = $dmdprdte;
        $this->unit_cost = $unit_cost;
        $this->retail_price = $retail_price;
        $this->qty = $qty;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = DrugStockLog::firstOrNew([
            'loc_code' =>  $this->pharm_location_id,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'chrgcode' => $this->chrgcode,
            'date_logged' => $this->trans_date,
            'dmdprdte' => $this->dmdprdte,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->retail_price,
        ]);
        $log->time_logged = now();
        $log->beg_bal += $this->qty;

        $log->save();
    }
}
