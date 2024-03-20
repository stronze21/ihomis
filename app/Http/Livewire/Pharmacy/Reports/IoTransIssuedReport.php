<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\InOutTransaction;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class IoTransIssuedReport extends Component
{
    use WithPagination;

    public $from, $to, $search;

    public function render()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();

        $trans = InOutTransaction::where(function ($query) {
            $query->where('trans_stat', 'Issued')
                ->orWhere('trans_stat', 'Received');
        })->where('request_from', session('pharm_location_id'))
            ->whereBetween('updated_at', [$from, $to])
            ->with('drug')
            ->with('location')
            ->get();

        return view('livewire.pharmacy.reports.io-trans-issued-report', [
            'trans' => $trans,
        ]);
    }

    public function mount()
    {
        $this->from = date('Y-m-d', strtotime(now()));
        $this->to = date('Y-m-d', strtotime(now()));
    }
}
