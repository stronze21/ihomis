<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Hospital\Ward;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;

class DrugsIssuedWards extends Component
{
    public $locations, $location_id;
    public $date_from, $date_to;
    public $charge_codes, $filter_charge;
    public $wards, $wardcode;

    public function render()
    {
        $this->date_from = Carbon::parse($this->date_from)->startOfWeek()->format('Y-m-d H:i:s');
        $this->date_to = Carbon::parse($this->date_to)->endOfWeek()->format('Y-m-d H:i:s');

        $drugs_issued = DB::select("SELECT ward.wardname, drug.drug_concat, charge.chrgdesc, SUM(rxo.qty) as qty
                                    FROM hospital.dbo.hrxoissue rxo
                                    INNER JOIN hospital.dbo.hpatroom pat_room ON rxo.enccode = pat_room.enccode
                                    INNER JOIN webapp.dbo.prescription_data_issued rx_i ON rxo.docointkey = rx_i.docointkey
                                    INNER JOIN hospital.dbo.hward ward ON pat_room.wardcode = ward.wardcode
                                    INNER JOIN hospital.dbo.hdmhdr drug ON rxo.dmdcomb = drug.dmdcomb AND rxo.dmdctr = drug.dmdctr
                                    INNER JOIN hospital.dbo.hcharge charge ON rxo.chrgcode = charge.chrgcode
                                    WHERE rxo.issuedte BETWEEN ? AND ?
                                    AND ward.wardcode LIKE ?
                                    AND rxo.chrgcode LIKE ?
                                    GROUP BY ward.wardname, drug.drug_concat, charge.chrgdesc
                                    ORDER BY ward.wardname ASC, drug.drug_concat ASC
                                    ", [$this->date_from, $this->date_to, $this->wardcode ?? '%%', $this->filter_charge ?? '%%']);

        return view('livewire.pharmacy.reports.drugs-issued-wards', compact(
            'drugs_issued',
        ));
    }

    public function mount()
    {
        $this->locations = PharmLocation::all();
        $this->wards = Ward::all();
        $this->location_id = session('pharm_location_id');
        $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d H:i:s');
        $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d H:i:s');
        $this->charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS'))
            ->get();
    }
}
