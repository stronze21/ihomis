<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;
use App\Models\Record\Prescriptions\PrescriptionDataIssued;

class LogDrugOrderIssue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $docointkey, $enccode, $hpercode, $dmdcomb, $dmdctr, $pchrgqty, $employeeid, $orderfrom, $pcchrgcod, $pchrgup, $ris, $prescription_data_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($docointkey, $enccode, $hpercode, $dmdcomb, $dmdctr, $pchrgqty, $employeeid, $orderfrom, $pcchrgcod, $pchrgup, $ris, $prescription_data_id)
    {
        $this->onQueue('rxo_issue_logger');
        $this->docointkey = $docointkey;
        $this->enccode = $enccode;
        $this->hpercode = $hpercode;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->pchrgqty = $pchrgqty;
        $this->employeeid = $employeeid;
        $this->orderfrom = $orderfrom;
        $this->pcchrgcod = $pcchrgcod;
        $this->pchrgup = $pchrgup;
        $this->ris = $ris;
        $this->prescription_data_id = $prescription_data_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if ($this->prescription_data_id) {
            PrescriptionDataIssued::create([
                'presc_data_id' => $this->prescription_data_id,
                'docointkey' => $this->docointkey,
                'qtyissued' => $this->pchrgqty,
            ]);
        }
        DrugOrderIssue::updateOrCreate([
            'docointkey' => $this->docointkey,
            'enccode' => $this->enccode,
            'hpercode' => $this->hpercode,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
        ], [
            'issuedte' => now(),
            'issuetme' => now(),
            'qty' => $this->pchrgqty,
            'issuedby' => $this->employeeid,
            'status' => 'A', //A
            'rxolock' => 'N', //N
            'updsw' => 'N', //N
            'confdl' => 'N', //N
            'entryby' => $this->employeeid,
            'locacode' => 'PHARM', //PHARM
            'dmdprdte' => now(),
            'issuedfrom' => $this->orderfrom,
            'pcchrgcod' => $this->pcchrgcod,
            'chrgcode' => $this->orderfrom,
            'pchrgup' => $this->pchrgup,
            'issuetype' => 'c', //c
            'ris' =>  $this->ris ? true : false,
        ]);
    }
}
