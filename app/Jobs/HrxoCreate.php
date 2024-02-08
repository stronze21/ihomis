<?php

namespace App\Jobs;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\HrxoSecondary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HrxoCreate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $docointkey;


    public function middleware(): array
    {
        return [(new WithoutOverlapping())];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($docointkey)
    {
        $this->onQueue('hrxo');
        $this->docointkey = $docointkey;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $hrxo2 = HrxoSecondary::where('docointkey', $this->docointkey)->first();

        $created = DrugOrder::create([
            'docointkey' => $this->docointkey,
            'enccode' => $hrxo2->enccode,
            'hpercode' => $hrxo2->hpercode,
            'rxooccid' => '1',
            'rxoref' => '1',
            'dmdcomb' => $hrxo2->dmdcomb,
            'repdayno1' => '1',
            'rxostatus' => 'A',
            'rxolock' => 'N',
            'rxoupsw' => 'N',
            'rxoconfd' => 'N',
            'dmdctr' => $hrxo2->dmdctr,
            'estatus' => $hrxo2->estatus,
            'entryby' => $hrxo2->entryby,
            'ordcon' => 'NEWOR',
            'orderupd' => 'ACTIV',
            'locacode' => 'PHARM',
            'orderfrom' => $hrxo2->chrgcode,
            'issuetype' => 'c',
            'has_tag' => $hrxo2->has_tag,
            'tx_type' => $hrxo2->tx_type,
            'ris' => $hrxo2->ris,
            'pchrgqty' => $hrxo2->pchrgqty,
            'pchrgup' => $hrxo2->dmselprice,
            'pcchrgamt' => $hrxo2->pcchrgamt,
            'dodate' => $hrxo2->dodate,
            'dotime' => $hrxo2->dotime,
            'dodtepost' => $hrxo2->dodtepost,
            'dotmepost' => $hrxo2->dotmepost,
            'dmdprdte' => $hrxo2->dmdprdte,
            'exp_date' => $hrxo2->exp_date, //added
            'loc_code' => $hrxo2->loc_code, //added
            'item_id' => $hrxo2->id, //added
            'remarks' => $hrxo2->remarks, //added
            'prescription_data_id' => $hrxo2->prescription_data_id,
            'prescribed_by' => $hrxo2->prescribed_by,
            'hidden' => '1',
        ]);
        // $created = DB::insert(
        //     'INSERT INTO hospital.dbo.hrxo(docointkey, enccode, hpercode, rxooccid, rxoref, dmdcomb, repdayno1, rxostatus,
        //             rxolock, rxoupsw, rxoconfd, dmdctr, estatus, entryby, ordcon, orderupd, locacode, orderfrom, issuetype,
        //             has_tag, tx_type, ris, pchrgqty, pchrgup, pcchrgamt, dodate, dotime, dodtepost, dotmepost, dmdprdte,
        //             exp_date, loc_code, item_id, remarks, prescription_data_id, prescribed_by )
        //         VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        //                 ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        //                 ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        //                 ?, ?, ?, ?, ?, ? )',
        //     [
        //         $this->docointkey,
        //         $hrxo2->enccode,
        //         $hrxo2->hpercode,
        //         '1',
        //         '1',
        //         $hrxo2->dmdcomb,
        //         '1',
        //         'A',
        //         'N',
        //         'N',
        //         'N',
        //         $hrxo2->dmdctr,
        //         'U',
        //         $hrxo2->entryby,
        //         'NEWOR',
        //         'ACTIV',
        //         'PHARM',
        //         $hrxo2->orderfrom,
        //         'c',
        //         $hrxo2->has_tag,
        //         $hrxo2->tx_type,
        //         $hrxo2->ris,
        //         $hrxo2->pchrgqty,
        //         $hrxo2->pchrgup,
        //         $hrxo2->pcchrgamt,
        //         $hrxo2->dodate,
        //         $hrxo2->dotime,
        //         $hrxo2->dodtepost,
        //         $hrxo2->dotmepost,
        //         $hrxo2->dmdprdte,
        //         $hrxo2->exp_date,
        //         $hrxo2->loc_code,
        //         $hrxo2->item_id,
        //         $hrxo2->remarks,
        //         $hrxo2->prescription_data_id,
        //         $hrxo2->prescribed_by,
        //     ]
        // );
        Log::info($created);
    }
}
