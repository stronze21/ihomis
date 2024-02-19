<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ConssumptionReport extends Component
{
    public $month, $filter_charge = 'DRUME,Drugs and Medicines (Regular)';
    public $date_from, $date_to;
    public $location_id;

    public function render()
    {
        $date_from = Carbon::parse($this->date_from . '-01')->startOfMonth()->format('Y-m-d');
        $date_to = Carbon::parse($this->date_from . '-01')->endOfMonth()->format('Y-m-d');

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        $drugs_issued = DB::select('SELECT pdsl.dmdcomb, pdsl.dmdctr, pdsl.dmdprdte,
                                        pdsl.loc_code,
                                        pdsl.purchased as purchased,
                                        pdsl.beg_bal as beg_bal,
                                        pdsl.ems as ems,
                                        pdsl.maip as maip,
                                        pdsl.wholesale as wholesale,
                                        pdsl.opdpay as opdpay,
                                        pdsl.pay as pay,
                                        pdsl.service as service,
                                        pdsl.konsulta as konsulta,
                                        pdsl.pcso as pcso,
                                        pdsl.phic as phic,
                                        pdsl.caf as caf,
                                        pdsl.issue_qty as issue_qty,
                                        pdsl.return_qty as return_qty,
                                        stre.stredesc,
                                        frm.formdesc,
                                        gen.gendesc,
                                        drug.dmdnost,
                                        price.acquisition_cost,
                                        price.dmselprice,
                                        loc.description as location
                                    FROM [pharm_drug_stock_logs] as [pdsl]
                                    INNER JOIN hdmhdr as drug ON pdsl.dmdcomb = drug.dmdcomb AND pdsl.dmdctr = drug.dmdctr
                                    INNER JOIN hdruggrp as grp ON drug.grpcode = grp.grpcode
                                    INNER JOIN hgen as gen ON grp.gencode = gen.gencode
                                    INNER JOIN hstre as stre ON drug.strecode = stre.strecode
                                    INNER JOIN hform as frm ON drug.formcode = frm.formcode
                                    INNER JOIN hdmhdrprice as price ON pdsl.dmdprdte = price.dmdprdte
                                    INNER JOIN pharm_locations as loc ON pdsl.loc_code = loc.id
                                    WHERE [chrgcode] = ? and [date_logged] = ?
                                    ORDER BY gen.gendesc ASC', [$filter_charge[0], $date_from]);

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.reports.conssumption-report', [
            'charge_codes' => $charge_codes,
            // 'charges' => $charges,
            'current_charge' => $filter_charge[1],
            'drugs_issued' => $drugs_issued,
            'locations' => $locations,
        ]);
    }

    public function mount()
    {
        $this->date_from = date('Y-m', strtotime(now()));
        $this->location_id = session('pharm_location_id');
    }
}
