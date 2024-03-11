<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Models\Pharmacy\PharmLocation;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class StockSummary extends Component
{

    public $search, $location_id;

    public function render()
    {

        $stocks = DB::select("SELECT hcharge.chrgdesc, pds.drug_concat, SUM(pds.stock_bal) as stock_bal
                            FROM pharm_drug_stocks as pds
                            JOIN hcharge ON pds.chrgcode = hcharge.chrgcode
                            WHERE pds.loc_code = " . $this->location_id . " AND pds.drug_concat LIKE '%" . $this->search . "%'
                            GROUP BY pds.drug_concat, pds.loc_code, hcharge.chrgdesc
                    ");

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.drugs.stock-summary', [
            'stocks' => $stocks,
            'locations' => $locations,
        ]);
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
    }
}