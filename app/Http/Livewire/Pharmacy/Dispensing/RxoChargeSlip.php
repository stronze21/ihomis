<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use App\Models\Hospital\Employee;
use App\Models\Hospital\Room;
use App\Models\Hospital\Ward;
use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\HrxoSecondary;
use App\Models\Record\Admission\PatientRoom;
use App\Models\Record\Encounters\EncounterLog;
use App\Models\Record\Patients\Patient;
use App\Models\Record\Prescriptions\Prescription;
use App\Models\Record\Prescriptions\PrescriptionData;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RxoChargeSlip extends Component
{
    public $pcchrgcod, $view_returns = false, $returned_qty = 0;
    public $wardname, $room_name, $toecode;

    public function updatedViewReturns()
    {
        $this->reset('returned_qty');
    }

    public function render()
    {
        $pcchrgcod = $this->pcchrgcod;

        // $rxo = DrugOrder::where('pcchrgcod', $pcchrgcod)
        //     ->with('dm')->with('patient')
        //     ->with('prescriptions')
        //     ->latest('dodate');

        // if ($this->view_returns and $rxo->sum('qtyissued') > 0) {
        //     $rxo = $rxo->where('qtyissued', '>', '0');
        // }
        // $rxo = $rxo->get();
        $rxo2 =  HrxoSecondary::where('pcchrgcod', $pcchrgcod)->latest('dodate')->get();

        if ($rxo2->isEmpty()) {
            $rxo2 = DB::select('SELECT hrxo.*, drug_concat, entryby FROM hrxo JOIN hdmhdr ON hrxo.dmdcomb = hdmhdr.dmdcomb AND hrxo.dmdctr = hdmhdr.dmdctr WHERE pcchrgcod = ?', [$pcchrgcod]);
        }

        $patient = Patient::find($rxo2[0]->hpercode);
        $prescription = Prescription::where('enccode', $rxo2[0]->enccode)->first();
        $emp = Employee::where('employeeid', $rxo2[0]->entryby)->first();
        $data = PrescriptionData::find($rxo2[0]->prescription_data_id);
        $dr = Employee::where('employeeid', $data->entry_by ?? 'na')->first();
        $enctr = EncounterLog::where('enccode', $rxo2[0]->enccode)->first();
        $rxo_header = $rxo2[0];
        // $rxo_header = $rxo[0];
        // $prescription = $rxo_header->prescriptions->first();
        $this->toecode = $enctr->toecode;

        // $patient_room = PatientRoom::where('enccode', $rxo[0]->enccode)->latest('hprdate')->first();
        $patient_room = PatientRoom::where('enccode', $rxo2[0]->enccode)->latest('hprdate')->first();
        if ($patient_room) {
            $this->wardname = Ward::select('wardname')->where('wardcode', $patient_room->wardcode)->first();
            $this->room_name = Room::select('rmname')->where('rmintkey', $patient_room->rmintkey)->first();
        }

        return view('livewire.pharmacy.dispensing.rxo-charge-slip', [
            'dr' => $dr,
            'emp' => $emp,
            'patient' => $patient,
            'rxo_header' => $rxo_header,
            'rxo' => $rxo2,
            'prescription' => $prescription,
        ])->layout('layouts.print');
    }

    public function mount($pcchrgcod)
    {
        $this->pcchrgcod = $pcchrgcod;
    }
}
