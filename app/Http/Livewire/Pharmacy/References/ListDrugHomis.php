<?php

namespace App\Http\Livewire\Pharmacy\References;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pharmacy\Drug;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ListDrugHomis extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $search;

    public function render()
    {
        $drugs = Drug::where('dmdstat', 'A')
                    ->with('generic')
                    ->with('form')
                    ->with('route')
                    ->with('strength')
                    ->whereRelation('sub', 'dmhdrsub', 'DRUME')
                    ->whereRelation('generic', 'gendesc', 'LIKE', '%'.$this->search.'%');
        return view('livewire.pharmacy.references.list-drug-homis',[
            'drugs' => $drugs->paginate(20),
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

}
