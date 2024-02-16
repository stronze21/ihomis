<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Models\Pharmacy\Drugs\InOutTransaction;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ViewIotrans extends Component
{
    use LivewireAlert;

    public $reference_no;

    public function render()
    {
        $trans = InOutTransaction::where('trans_no', $this->reference_no)->with('drug')
            ->with('charge')
            ->get();

        return view('livewire.pharmacy.drugs.view-iotrans', [
            'trans' => $trans,
        ]);
    }

    public function mount($reference_no)
    {
        $this->reference_no = $reference_no;
    }
}
