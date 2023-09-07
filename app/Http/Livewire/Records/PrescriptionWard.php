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

    public $wardcode, $wards;
    public $is_basic = true, $is_g24, $is_or;

    public function updatingWardcode()
    {
        $this->resetPage();
    }

    public function toggle_basic()
    {
        $this->is_basic = !$this->is_basic;
        $this->resetPage();
    }

    public function toggle_g24()
    {
        $this->is_g24 = !$this->is_g24;
        $this->resetPage();
    }

    public function toggle_or()
    {
        $this->is_or = !$this->is_or;
        $this->resetPage();
    }

    public function render()
    {
        $prescriptions = Prescription::with('active_adm')
            ->with('adm_pat_room')->with('active_basic')->with('active_g24')->with('active_or');

        if ($this->is_basic) {
            $prescriptions->has('active_basic');
        } else if ($this->is_g24) {
            $prescriptions->has('active_g24');
        } else if ($this->is_or) {
            $prescriptions->has('active_or');
        }

        $prescriptions->has('active_adm')->has('data_active')
            ->whereRelation('adm_pat_room', 'hospital.dbo.hpatroom.wardcode', 'LIKE', $this->wardcode . '%')
            ->where('stat', 'A')
            ->where('created_at', '>', '2023-01-01');

        return view('livewire.records.prescription-ward', [
            'prescriptions' => $prescriptions->get(),
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
