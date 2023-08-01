<?php

namespace App\Http\Livewire\Pharmacy\Deliveries;

use Livewire\Component;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\DeliveryDetail;
use App\Models\Pharmacy\DeliveryItems;
use App\Models\Pharmacy\DrugPrice;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class DeliveryView extends Component
{

    use LivewireAlert;

    protected $listeners = ['add_item', 'refresh' => '$refresh', 'edit_item', 'delete_item', 'save_lock'];
    public $delivery_id, $details, $search, $dmdcomb, $expiry_date, $qty, $unit_price, $lot_no;

    public function render()
    {
        $drugs = Drug::with('generic')->with('route')->with('form')->with('strength')
                    ->has('generic')
                    ->where('dmdstat', 'A')
                    ->whereHas('sub', function ($query) {
                        return $query->whereIn('dmhdrsub', array('DRUMB', 'DRUME', 'DRUMK', 'DRUMA', 'DRUMC', 'DRUMR', 'DRUMS', 'DRUMO'));
                    })
                    // ->whereRelation('sub', 'dmhdrsub', 'DRUME')
                    ->whereRelation('generic', 'gendesc', 'LIKE', '%'.$this->search.'%');

        return view('livewire.pharmacy.deliveries.delivery-view', [
            'drugs' => $drugs->get(),
        ]);
    }

    public function mount($delivery_id)
    {
        $this->delivery_id = $delivery_id;
        $this->details = DeliveryDetail::where('id',$delivery_id)
                                        ->with('items')->with('supplier')
                                        ->with('charge')->first();
    }

    public function add_item()
    {
        $this->validate(['dmdcomb' => 'required', 'unit_price' => 'required', 'qty' => 'required', 'expiry_date' => 'required']);

        $markup_price = $this->unit_price + ((float)$this->unit_price * 0.30);
        $total_amount = $this->unit_price * $this->qty;
        $dm = explode(',',$this->dmdcomb);

        $new_item = new DeliveryItems;
        $new_item->delivery_id = $this->details->id;
        $new_item->dmdcomb = $dm[0];
        $new_item->dmdctr = $dm[1];
        $new_item->qty = $this->qty;
        $new_item->unit_price = $this->unit_price;
        $new_item->total_amount = $total_amount;
        $new_item->markup_price = $markup_price;
        $new_item->lot_no = $this->lot_no;
        $new_item->expiry_date = $this->expiry_date;
        $new_item->pharm_location_id = $this->details->pharm_location_id;
        $new_item->charge_code = $this->details->charge_code;
        $new_item->save();

        $this->emit('refresh');
        $this->resetExcept('details', 'delivery_id', 'search');
        $this->alert('success', 'Item added in delivery!');
    }

    public function edit_item($item_id)
    {
        $this->validate(['unit_price' => 'required', 'qty' => 'required', 'expiry_date' => 'required']);

        $markup_price = $this->unit_price + ((float)$this->unit_price * 0.30);
        $total_amount = $this->unit_price * $this->qty;

        $update_item = DeliveryItems::find($item_id);
        $update_item->qty = $this->qty;
        $update_item->unit_price = $this->unit_price;
        $update_item->total_amount = $total_amount;
        $update_item->markup_price = $markup_price;
        $update_item->lot_no = $this->lot_no;
        $update_item->expiry_date = $this->expiry_date;
        $update_item->save();

        $this->emit('refresh');
        $this->resetExcept('details', 'delivery_id', 'search');
        $this->alert('success', 'Item updated!');
    }

    public function delete_item($item_id)
    {
        $delete_item = DeliveryItems::find($item_id);
        $delete_item->delete();

        $this->emit('refresh');
        $this->resetExcept('details', 'delivery_id', 'search');
        $this->alert('info', 'Item deleted!');
    }

    public function save_lock()
    {
        $updated = false;

        foreach($this->details->items->all() as $item)
        {
            $add_to = DrugStock::firstOrCreate([
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'loc_code' => $item->pharm_location_id,
                'chrgcode' => $item->charge_code,
                'exp_date' => $item->expiry_date,
                'markup_price' => $item->markup_price,
            ]);
            $add_to->stock_bal = $add_to->stock_bal + $item->qty;
            $add_to->beg_bal = $add_to->beg_bal + $item->qty;

            $current_price = DrugPrice::where('dmdcomb', $item->dmdcomb)
                                    ->where('dmdctr', $item->dmdctr)
                                    ->where('dmhdrsub', $item->chrgcode)
                                    ->latest('dmdprdte')
                                    ->first();

            if($current_price AND $current_price->dmduprice == $item->unit_price AND $current_price->dmselprice){
                $dmdprdte = $current_price->dmdprdte;
                $dmduprice = $current_price->dmduprice;
                $dmselprice = $current_price->dmselprice;
            }else{
                $new_price = new DrugPrice;
                $new_price->dmdcomb = $add_to->dmdcomb;
                $new_price->dmdctr = $add_to->dmdctr;
                $new_price->dmhdrsub = $add_to->chrgcode;
                $new_price->dmduprice = (100 / 130) * $add_to->markup_price;
                $new_price->dmselprice = $add_to->markup_price;
                $new_price->dmdprdte = now();
                $new_price->expdate = $add_to->exp_date;
                $new_price->stock_id = $add_to->id;
                $new_price->save();

                $dmdprdte = $new_price->dmdprdte;
                $dmduprice = $new_price->dmduprice;
                $dmselprice = $new_price->dmselprice;
            }

            $log = DrugStockLog::firstOrNew([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $add_to->dmdcomb,
                'dmdctr' => $add_to->dmdctr,
                'chrgcode' => $add_to->chrgcode,
                'date_logged' => date('Y-m-d'),
                'dmdprdte' => $dmdprdte,
                'unit_cost' => $dmduprice,
                'unit_price' => $dmselprice,
            ]);
            $log->time_logged = now();
            $log->purchased += $item->qty;
            $add_to->dmdprdte = $dmdprdte;

            $log->save();
            $add_to->save();

            $updated = true;
        }
        if($updated){
            $this->details->status = 'locked';
            $this->details->save();
            $this->emit('refresh');
            $this->alert('success', 'Successfully updated stocks inventory!');
        }else{
            return $this->alert('error', 'Error! There are no drug or medicine that can be added to stock inventory.');
        }
    }
}
