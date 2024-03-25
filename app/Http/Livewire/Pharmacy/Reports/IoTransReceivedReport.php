<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\InOutTransaction;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class IoTransReceivedReport extends Component
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

        $trans = InOutTransaction::where('trans_stat', 'Received')
            ->where('loc_code', session('pharm_location_id'))
            ->whereBetween('updated_at', [$from, $to])
            ->whereHas('item', function ($query) {
                $query->where('chrgcode', $this->filter_charge);
            })
            ->with('drug')
            ->with('location')
            ->get();

        return view('livewire.pharmacy.reports.io-trans-received-report', [
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
