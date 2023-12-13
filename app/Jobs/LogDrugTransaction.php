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

    protected $pharm_location_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $dmdprdte, $unit_cost, $retail_price, $qty, $stock_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($pharm_location_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $dmdprdte, $unit_cost, $retail_price, $qty, $stock_id)
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
            'stock_id' => $this->stock_id,
            'stock_date' => $date,
        ]);

        switch ($this->chrgcode) {
            case 'DRUME': // Regular
                $card->rec_regular += $this->qty;
                break;

            case 'DRUMB': // Revolving
                $card->rec_revolving += $this->qty;
                break;

            default: //DRUMAA, DRUMAB, DRUMC, DRUMK, DRUMR, DRUMS
                $card->rec_others += $this->qty;
        }

        $card->save();
    }
}