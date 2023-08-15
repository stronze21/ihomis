<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use Livewire\Component;

class RxoChargeSlip extends Component
{
    public $pcchrgcod, $view_returns = false, $returned_qty = 0;

    public function updatedViewReturns()
    {
        $this->reset('returned_qty');
    }

    public function render()
    {
        $pcchrgcod = $this->pcchrgcod;

        $rxo = DrugOrder::where('pcchrgcod', $pcchrgcod)
            ->with('dm')->with('charge')->with('patient')
            ->with('prescriptions')
            ->latest('dodate');

        if ($this->view_returns and $rxo->sum('qtyissued') > 0) {
            $rxo = $rxo->where('qtyissued', '>', '0');
        }
        $rxo = $rxo->get();

        $rxo_header = $rxo[0];
        $prescription = $rxo_header->prescriptions->first();

        return view('livewire.pharmacy.dispensing.rxo-charge-slip', [
            'rxo_header' => $rxo_header,
            'rxo' => $rxo,
            'prescription' => $prescription,
        ])->layout('layouts.print');
    }

    public function mount($pcchrgcod)
    {
        $this->pcchrgcod = $pcchrgcod;
    }
}
