<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DailyStockCard extends Component
{
    public $date_from, $location_id, $drugs, $dmdcomb, $dmdctr, $fund_sources, $selected_drug, $selected_fund, $chrgcode, $chrgdesc;


    public function updatedSelectedDrug()
    {
        $drug = $this->selected_drug;
        $selected_drug = explode(',', $drug);
        $this->dmdcomb = $selected_drug[0];
        $this->dmdctr = $selected_drug[1];
    }

    public function updatedSelectedFund()
    {
        $fund = $this->selected_fund;
        $selected_fund = explode(',', $fund);
        $this->chrgcode = $selected_fund[0];
        $this->chrgdesc = $selected_fund[1];
    }

    public function render()
    {
        $locations = PharmLocation::all();
        $cards = DrugStockCard::select(DB::raw('SUM(rec) as rec, SUM(iss) as iss, SUM(bal) as bal'), 'drug_concat', 'exp_date', 'stock_date', 'reference', 'chrgcode')
            ->where('dmdcomb', $this->dmdcomb)
            ->where('dmdctr', $this->dmdctr)
            ->where('stock_date', $this->date_from)
            ->where('loc_code', $this->location_id);

        if ($this->selected_fund) {
            $cards = $cards->where('chrgcode', $this->chrgcode);
        }

        $cards = $cards->groupBy('dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode')
            ->orderBy('drug_concat', 'ASC')
            ->orderBy('exp_date', 'ASC')
            ->get();

        return view('livewire.pharmacy.reports.daily-stock-card', compact(
            'locations',
            'cards',
        ));
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->date_from = Carbon::parse(now())->format('Y-m-d');
        $this->drugs =  DrugStockCard::where('stock_date', $this->date_from)
            ->where('loc_code', $this->location_id)
            ->groupBy('dmdcomb', 'dmdctr', 'drug_concat', 'chrgcode')
            ->get();

        $this->fund_sources = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS'))
            ->get();
    }
}
