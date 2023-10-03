<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Livewire\Component;
use App\Models\Pharmacy\Drugs\InOutTransaction;

class IoTransIssuedReport extends Component
{
    public function render()
    {
        $trans = InOutTransaction::where('trans_stat', 'Issued')
            ->orWhere('trans_stat', 'Received')
            ->with('drug')
            ->with('location')
            ->paginate(15);

        return view('livewire.pharmacy.reports.io-trans-issued-report', [
            'trans' => $trans,
        ]);
    }
}
