<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\InOutTransaction;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IoTransIssuedReport extends Component
{
    use WithPagination;

    public $from, $to, $search, $filter_charge = 'DRUME';

    public function render()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG'))
            ->get();


        $trans = InOutTransaction::where(function ($query) {
            $query->where('trans_stat', 'Issued')
                ->orWhere('trans_stat', 'Received');
        })->where('request_from', session('pharm_location_id'))
            ->whereHas('item', function ($query) {
                $query->where('chrgcode', $this->filter_charge);
            })
            ->whereBetween('updated_at', [$from, $to])
            ->with('drug')
            ->with('location')
            ->get();

        // $trans = DB::select("
        //     SELECT io.trans_no, io.created_at, loc.description, drug.drug_concat, io.issued_qty
        //     FROM pharm_io_trans io
        //     JOIN pharm_locations loc ON io.request_from = loc.id
        //     JOIN hdmhdr drug ON io.dmdcomb = drug.dmdcomb AND io.dmdctr = drug.dmdctr
        //     WHERE io.request_from = '" . session('pharm_location_id') . "' AND io.updated_at BETWEEN '" . $from . "' AND '" . $to . "' AND (io.trans_stat = 'Issued' OR io.trans_stat = 'Received')
        // ");

        return view('livewire.pharmacy.reports.io-trans-issued-report', [
            'trans' => $trans,
            'charge_codes' => $charge_codes,
        ]);
    }

    public function mount()
    {
        $this->from = date('Y-m-d', strtotime(now()));
        $this->to = date('Y-m-d', strtotime(now()));
    }
}
