<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Livewire\Component;
use App\Models\Pharmacy\Drugs\InOutTransaction;
use Livewire\WithPagination;

class IoTransIssuedReport extends Component
{

    use WithPagination;

    public function render()
    {
        $trans = InOutTransaction::where(function ($query) {
            $query->where('trans_stat', 'Issued')
                ->orWhere('trans_stat', 'Received');
        })->where('request_from', session('pharm_location_id'))
            ->with('drug')
            ->with('location')
            ->paginate(15);

        return view('livewire.pharmacy.reports.io-trans-issued-report', [
            'trans' => $trans,
        ]);
    }
}
