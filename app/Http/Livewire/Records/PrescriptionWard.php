<?php

namespace App\Http\Livewire\Records;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Hospital\Ward;
use Illuminate\Support\Facades\DB;
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
        $prescriptions = DB::table(DB::raw('hospital.dbo.henctr enctr'))
            ->rightJoin(DB::raw('webapp.dbo.prescription rx'), 'enctr.enccode', 'rx.enccode')
            ->rightJoin(DB::raw('hospital.dbo.hadmlog adm'), 'enctr.enccode', 'adm.enccode')
            ->rightJoin(DB::raw('hospital.dbo.hpatroom pat_room'), 'rx.enccode', 'pat_room.enccode')
            ->rightJoin(DB::raw('hospital.dbo.hroom room'), 'pat_room.rmintkey', 'room.rmintkey')
            ->rightJoin(DB::raw('hospital.dbo.hward ward'), 'pat_room.wardcode', 'ward.wardcode')
            ->rightJoin(DB::raw('hospital.dbo.hperson pt'), 'adm.hpercode', 'pt.hpercode')
            ->leftJoin(DB::raw('hospital.dbo.hpatmss mss'), 'enctr.enccode', 'mss.enccode')
            ->select(
                'adm.enccode',
                'adm.admdate',
                'adm.hpercode',
                'pt.patfirst',
                'pt.patmiddle',
                'pt.patlast',
                'pt.patsuffix',
                'room.rmname',
                'ward.wardname',
                'mss.mssikey',
                DB::raw("(SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND (data.order_type = '' OR data.order_type IS NULL)) basic"),
                DB::raw("(SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'G24') g24"),
                DB::raw("(SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'OR') 'or'")
            )
            ->where('pt.patstat', 'A')
            ->where('rx.stat', 'A')
            ->where('enctr.encstat', 'A')
            ->where('pat_room.patrmstat', 'A')
            ->where('rx.created_at', '>', '2023-01-01')
            ->orderBy('pt.patlast', 'ASC')
            ->orderBy('pt.patfirst', 'ASC')
            ->orderBy('pt.patmiddle', 'ASC')
            ->orderByDesc('rx.created_at');

        // dd($prescriptions);

        // $prescriptions = Prescription::with('active_adm')
        //     ->with('adm_pat_room')->with('active_basic')->with('active_g24')->with('active_or');

        // if ($this->is_basic) {
        //     $prescriptions->has('active_basic');
        // } else if ($this->is_g24) {
        //     $prescriptions->has('active_g24');
        // } else if ($this->is_or) {
        //     $prescriptions->has('active_or');
        // }

        // $prescriptions->has('active_adm')->has('data_active')
        //     ->whereRelation('adm_pat_room', 'hospital.dbo.hpatroom.wardcode', 'LIKE', $this->wardcode . '%')
        //     ->where('stat', 'A')
        //     ->where('created_at', '>', '2023-01-01');

        // dd($prescriptions->toSql());

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