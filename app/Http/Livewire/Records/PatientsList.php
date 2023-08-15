<?php

namespace App\Http\Livewire\Records;

use App\Models\Record\Encounters\EncounterLog;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Models\Record\Patients\Patient;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class PatientsList extends Component
{
    use LivewireAlert;
    use WithPagination;

    protected $listeners = ['walk_in', 'view_enctr'];
    public $enccode;
    public $search = '', $searchpatfirst = '', $searchpatmiddle = '', $searchpatlast = '', $searchhpercode = '', $searchpatdob = '';
    public $hpercode;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSearchpatfirst()
    {
        $this->resetPage();
    }

    public function updatingSearchpatmiddle()
    {
        $this->resetPage();
    }

    public function updatingSearchpatlast()
    {
        $this->resetPage();
    }

    public function updatingSearchhpercode()
    {
        $this->resetPage();
    }

    public function updatingSearchpatdob()
    {
        $this->resetPage();
    }

    public function render()
    {
        $patients = Patient::query()->selectRaw('hpercode, patlast, patfirst, patmiddle, patsex, patbdate, patbplace, patcstat');

        if ($this->searchpatlast || $this->searchpatlast != '')
            $patients->where('patlast', 'LIKE', '%' . $this->searchpatlast . '%');
        if ($this->searchpatmiddle || $this->searchpatmiddle != '')
            $patients->where('patmiddle', 'LIKE', '%' . $this->searchpatmiddle . '%');
        if ($this->searchpatfirst || $this->searchpatfirst != '')
            $patients->where('patfirst', 'LIKE', '%' . $this->searchpatfirst . '%');
        if ($this->searchhpercode || $this->searchhpercode != '')
            $patients->where('hpercode', 'LIKE', '%' . $this->searchhpercode . '%');
        if ($this->searchpatdob || $this->searchpatdob != '')
            $patients->where(DB::raw('CONVERT(date, patbdate)'), '=', $this->searchpatdob);

        $patients = $patients->orderBy('patlast');

        return view('livewire.records.patients-list', [
            'patients' => $patients->paginate(15),
        ]);
    }

    public function select_patient($hpercode)
    {
        $patient = Patient::find($hpercode);
        $encounter = $patient->latest_encounter->first();
        $this->hpercode = $hpercode;
        if ($encounter) {
            $this->enccode = $encounter->enccode;
            $this->alert('info', 'Active ' . $encounter->enctr_type() . ' encounter dated ' . date('F j, Y G:i A', strtotime($encounter->encdate)) . ' found!', [
                'toast' => false,
                'position' => 'center',
                'showConfirmButton' => true,
                'confirmButtonText' => $encounter->enctr_type(),
                'onConfirmed' => 'view_enctr',
                'showDenyButton' => true,
                'denyButtonText' => 'Walk-in',
                'onDenied' => 'walk_in',
                'showCancelButton' => true,
                'reverseButtons' => true,
                'timer' => false,
            ]);
        } else {
            $this->alert('error', 'No active encounter found! Continue as walk in?', [
                'toast' => false,
                'position' => 'center',
                'showConfirmButton' => true,
                'confirmButtonText' => 'Continue',
                'onConfirmed' => 'walk_in',
                'showCancelButton' => true,
                'reverseButtons' => true,
                'timer' => false,
            ]);
        }
    }

    public function view_enctr()
    {

        $enccode = Crypt::encrypt(str_replace(' ', '-', $this->enccode));
        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }

    public function walk_in()
    {
        $check_walkn = EncounterLog::where('encstat', 'W')
            ->where('toecode', 'WALKN')
            ->where('hpercode', $this->hpercode)
            ->latest('encdate')
            ->first();

        if ($check_walkn) {
            $enccode = Crypt::encrypt(str_replace(' ', '-', $check_walkn->enccode));
        } else {
            $new_enccode = '0000040' . $this->hpercode . date('m/d/Yh:i:s', strtotime(now()));
            $new_encounter = EncounterLog::create([
                'enccode' => $new_enccode,
                'fhud' => '0000040',
                'hpercode' => $this->hpercode,
                'encdate' => now(),
                'enctime' => now(),
                'toecode' => 'WALKN',
                'sopcode1' => 'SELPA',
                'encstat' => 'W',
                'confdl' => 'N',
            ]);

            $enccode = Crypt::encrypt(str_replace(' ', '-', $new_encounter->enccode));
        }

        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }
}
