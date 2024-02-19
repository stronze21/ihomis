<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Livewire\Component;
use App\Models\Pharmacy\Drugs\InOutTransaction;
use Livewire\WithPagination;

class IoTransReceivedReport extends Component
{

    use WithPagination;

    public function render()
    {
        $trans = InOutTransaction::where('trans_stat', 'Received')
            ->where('loc_code', session('pharm_location_id'))
            ->with('drug')
            ->with('location')
            ->paginate(15);

        return view('livewire.pharmacy.reports.io-trans-received-report', [
            'trans' => $trans,
        ]);
    }
}
