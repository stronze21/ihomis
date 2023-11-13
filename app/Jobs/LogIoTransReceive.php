<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class LogIoTransReceive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to, $dmdcomb, $dmdctr, $chrgcode, $date_logged, $dmdprdte, $retail_price, $time_logged, $qty;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($to, $dmdcomb, $dmdctr, $chrgcode, $date_logged, $dmdprdte, $retail_price, $time_logged, $qty)
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
    }
}
