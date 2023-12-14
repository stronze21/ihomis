<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\PharmLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DailyStockCard extends Component
{
    public $date_from, $location_id;

    public function render()
    {
        $locations = PharmLocation::all();
        $cards = DrugStockCard::select(DB::raw(
            'SUM(rec_revolving) as rec_revolving, SUM(rec_regular) as rec_regular, SUM(rec_others) as rec_others,
            SUM(iss_revolving) as iss_revolving, SUM(iss_regular) as iss_regular, SUM(iss_others) as iss_others,
            SUM(bal_revolving) as bal_revolving, SUM(bal_regular) as bal_regular, SUM(bal_others) as bal_others'
        ), 'drug_concat', 'exp_date', 'stock_date', 'reference')
            ->where('stock_date', $this->date_from)
            ->where('loc_code', $this->location_id)
            ->groupBy('dmdcomb', 'dmdctr', 'exp_date', 'drug_concat')
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
    }
}