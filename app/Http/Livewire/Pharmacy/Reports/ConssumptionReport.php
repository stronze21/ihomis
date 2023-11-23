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
    public $month, $filter_charge = 'DRUMB,*Drugs and Meds (Revolving) Satellite';
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
                                        SUM(pdsl.purchased) as purchased,
                                        SUM(pdsl.beg_bal) as beg_bal,
                                        SUM(pdsl.ems) as ems,
                                        SUM(pdsl.maip) as maip,
                                        SUM(pdsl.wholesale) as wholesale,
                                        SUM(pdsl.pay) as pay,
                                        SUM(pdsl.service) as service,
                                        SUM(pdsl.konsulta) as konsulta,
                                        SUM(pdsl.pcso) as pcso,
                                        SUM(pdsl.phic) as phic,
                                        SUM(pdsl.caf) as caf,
                                        SUM(pdsl.issue_qty) as issue_qty,
                                        SUM(pdsl.return_qty) as return_qty
                                        ")
            ->where('chrgcode', $filter_charge[0])
            ->whereBetween('time_logged', [$date_from, $date_to])
            ->with('charge')->with('drug')
            ->groupBy('pdsl.dmdcomb', 'pdsl.dmdctr', 'pdsl.chrgcode')
            ->groupBy('pdsl.dmdprdte')
            ->get();

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