<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\Drugs\DrugStockIssue;

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
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        // $drugs_issued = DrugStockIssue::from('pharm_drug_stock_issues as pdsi')
        //                             ->join('hcharge as hc', 'hc.chrgcode', 'pdsi.chrgcode')
        //                             ->join('pharm_drug_stocks as pds', 'pdsi.stock_id', 'pds.id', 'pds.retail_price')
        //                             ->selectRaw("MAX(hc.chrgdesc) chrgdesc, pdsi.dmdcomb, pdsi.dmdctr, MAX(pdsi.stock_id), SUM(pds.beg_bal) as beg_bal, SUM(pds.stock_bal) as stock_bal, pds.retail_price")
        //                             ->selectRaw("SUM(pdsi.sc_pwd) as sc_pwd, SUM(pdsi.ems) as ems, SUM(pdsi.maip) as maip, SUM(pdsi.wholesale) as wholesale, SUM(pdsi.pay) as pay")
        //                             ->selectRaw("SUM(pdsi.medicare) as medicare, SUM(pdsi.service) as service, SUM(pdsi.govt_emp) as govt_emp, SUM(pdsi.caf) as caf")
        //                             ->selectRaw("SUM(pdsi.qty) as qty, SUM(pdsi.pcchrgamt) as pcchrgamt")
        //                             ->with('drug')
        //                             ->where('pdsi.loc_code', $this->location_id)
        //                             ->whereBetween('pdsi.created_at', [$this->date_from, $this->date_to])
        //                             ->groupBy('pdsi.dmdcomb', 'pdsi.dmdctr', 'pdsi.chrgcode')
        //                             ->groupBy('pds.dmdcomb', 'pds.dmdctr', 'pds.chrgcode', 'pds.retail_price')
        //                             ->get();

        $drugs_issued = DrugStockLog::from('pharm_drug_stock_logs as pdsl')
            ->selectRaw("chrgcode, pdsl.dmdcomb, pdsl.dmdctr, pdsl.dmdprdte,
                                        pdsl.purchased as purchased,
                                        pdsl.beg_bal as beg_bal,
                                        pdsl.ems as ems,
                                        pdsl.maip as maip,
                                        pdsl.wholesale as wholesale,
                                        pdsl.pay as pay,
                                        pdsl.service as service,
                                        pdsl.konsulta as konsulta,
                                        pdsl.pcso as pcso,
                                        pdsl.phic as phic,
                                        pdsl.caf as caf,
                                        pdsl.issue_qty as issue_qty,
                                        pdsl.return_qty as return_qty
                                        ")
            ->where('chrgcode', $filter_charge[0])
            ->where('date_logged', $date_from)
            ->with('charge')->with('drug')
            ->get();

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.reports.conssumption-report', [
            'charge_codes' => $charge_codes,
            // 'charges' => $charges,
            'current_charge' => $filter_charge[0],
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