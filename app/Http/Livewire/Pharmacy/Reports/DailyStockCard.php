<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\PharmLocation;
use Carbon\Carbon;
use Livewire\Component;

class DailyStockCard extends Component
{
    public $date_from, $date_to, $location_id;

    public function render()
    {
        $locations = PharmLocation::all();



        return view('livewire.pharmacy.reports.daily-stock-card', compact(
            'locations',
        ));
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d H:i:s');
        $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d H:i:s');
    }
}