<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use Livewire\Component;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\DrugPrice;
use Illuminate\Support\Facades\Auth;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class StockList extends Component
{
    use LivewireAlert;

    protected $listeners = ['add_item', 'refresh' => '$refresh'];

    public $search;
    public $location_id;
    public $dmdcomb, $chrgcode, $expiry_date, $qty, $unit_cost, $lot_no;

    public function render()
    {
        $drugs = Drug::with('generic')->with('route')->with('form')->with('strength')
            ->has('generic')->has('route')->has('form')->has('strength')
            ->where('dmdstat', 'A')
            ->whereRelation('sub', 'dmhdrsub', 'DRUME');

        $stocks = DrugStock::with('charge')->with('location')->with('drug')->with('current_price')->has('current_price')
            ->where('loc_code', $this->location_id)
            ->whereHas('drug', function ($query) {
                return $query->whereRelation('generic', 'gendesc', 'LIKE', '%' . $this->search . '%');
            })->paginate(20);

        $locations = PharmLocation::all();

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMB', 'DRUME', 'DRUMK'))
            ->get();

        return view('livewire.pharmacy.drugs.stock-list', [
            'stocks' => $stocks,
            'charge_codes' => $charge_codes,
            'locations' => $locations,
            'drugs' => $drugs->get(),
        ]);
    }

    public function mount()
    {
        $this->location_id = Auth::user()->pharm_location_id;
    }

    public function add_item()
    {
        $this->validate([
            'dmdcomb' => 'required',
            'unit_cost' => 'required',
            'qty' => 'required',
            'expiry_date' => 'required',
            'chrgcode' => 'required',
        ]);

        $markup_price = $this->unit_cost + ((float)$this->unit_cost * 0.30);
        $total_amount = $this->unit_cost * $this->qty;
        $dm = explode(',', $this->dmdcomb);

        $stock = DrugStock::firstOrCreate([
            'dmdcomb' => $dm[0],
            'dmdctr' => $dm[1],
            'loc_code' =>  Auth::user()->pharm_location_id,
            'chrgcode' => $this->chrgcode,
            'exp_date' => $this->expiry_date,
            'markup_price' => $markup_price,
        ]);
        $stock->stock_bal = $stock->stock_bal + $this->qty;
        $stock->beg_bal = $stock->beg_bal + $this->qty;

        $current_price = DrugPrice::where('dmdcomb', $dm[0])
            ->where('dmdctr', $dm[1])
            ->where('dmhdrsub', $this->chrgcode)
            ->latest('dmdprdte')
            ->first();

        if ($current_price and $current_price->dmduprice == $this->unit_cost and $current_price->dmselprice) {
            $dmdprdte = $current_price->dmdprdte;
            $dmduprice = $current_price->dmduprice;
            $dmselprice = $current_price->dmselprice;
        } else {
            $new_price = new DrugPrice;
            $new_price->dmdcomb = $stock->dmdcomb;
            $new_price->dmdctr = $stock->dmdctr;
            $new_price->dmhdrsub = $stock->chrgcode;
            $new_price->dmduprice = (100 / 130) * $stock->markup_price;
            $new_price->dmselprice = $stock->markup_price;
            $new_price->dmdprdte = now();
            $new_price->expdate = $stock->exp_date;
            $new_price->stock_id = $stock->id;
            $new_price->save();

            $dmdprdte = $new_price->dmdprdte;
            $dmduprice = $new_price->dmduprice;
            $dmselprice = $new_price->dmselprice;
        }
        $stock->dmdprdte = $dmdprdte;

        $log = DrugStockLog::firstOrNew([
            'loc_code' =>  Auth::user()->pharm_location_id,
            'dmdcomb' => $stock->dmdcomb,
            'dmdctr' => $stock->dmdctr,
            'chrgcode' => $stock->chrgcode,
            'date_logged' => date('Y-m-d'),
            'dmdprdte' => $dmdprdte,
            'unit_cost' => $dmduprice,
            'unit_price' => $dmselprice,
        ]);
        $log->time_logged = now();
        $log->beg_bal += $this->qty;

        $log->save();
        $stock->save();

        $this->resetExcept('location_id');
        $this->alert('success', 'Item beginning balance has been saved!');
    }
}
