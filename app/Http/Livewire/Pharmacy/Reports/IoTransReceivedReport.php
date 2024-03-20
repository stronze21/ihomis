<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\InOutTransaction;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class IoTransReceivedReport extends Component
{

    use WithPagination;

    public $from, $to, $search;

    public function render()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();

        $trans = InOutTransaction::where('trans_stat', 'Received')
            ->where('loc_code', session('pharm_location_id'))
            ->whereBetween('updated_at', [$from, $to])
            ->with('drug')
            ->with('location')
            ->get();

        return view('livewire.pharmacy.reports.io-trans-received-report', [
            'trans' => $trans,
        ]);
    }

    public function mount()
    {
        $this->from = date('Y-m-d', strtotime(now()));
        $this->to = date('Y-m-d', strtotime(now()));
    }
}
