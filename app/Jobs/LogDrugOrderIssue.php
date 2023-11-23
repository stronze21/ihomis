<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogDrugOrderIssue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $docointkey, $enccode, $hpercode, $dmdcomb, $dmdctr, $pchrgqty, $employeeid, $orderfrom, $pcchrgcod, $pchrgup, $ris;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($docointkey, $enccode, $hpercode, $dmdcomb, $dmdctr, $pchrgqty, $employeeid, $orderfrom, $pcchrgcod, $pchrgup, $ris)
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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
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