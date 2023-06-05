<?php

namespace App\Http\Livewire\Records;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Hospital\Ward;
use Illuminate\Support\Facades\Crypt;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Record\Prescriptions\Prescription;

class PrescriptionWard extends Component
{

    use WithPagination;
    use LivewireAlert;

    public $wardcode = '1F', $wards;

    public function updatingWardcode(){ $this->resetPage(); }

    public function render()
    {
        $prescriptions = Prescription::with('active_adm')
                                    ->with('adm_pat_room')->with('data_active')
                                    ->with('active_g24')->with('active_or')->with('active_basic')
                                    ->has('active_adm')->has('data_active')
                                    ->whereRelation('adm_pat_room', 'hospital.dbo.hpatroom.wardcode', $this->wardcode)
                                    ->where('stat', 'A')
                                    ->where('created_at', '>', '2022-01-01');

        return view('livewire.records.prescription-ward', [
            'prescriptions' => $prescriptions->paginate(10),
        ]);
    }

    public function mount()
    {
        $this->wards = Ward::where('wardstat', 'A')
                            ->get();
    }

    public function view_enctr($enccode)
    {
        $enccode = Crypt::encrypt(str_replace(' ', '-', $enccode));
        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }
}
