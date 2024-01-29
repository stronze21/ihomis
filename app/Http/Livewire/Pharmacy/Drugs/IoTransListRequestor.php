<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Events\UserUpdated;
use App\Events\IoTransEvent;
use App\Events\IoTransNewRequest;
use App\Jobs\LogIoTransReceive;
use Livewire\WithPagination;
use App\Models\Pharmacy\DrugPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Notifications\IoTranNotification;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Pharmacy\Drugs\InOutTransaction;
use App\Models\Pharmacy\Drugs\InOutTransactionItem;

class IoTransListRequestor extends Component
{
    use WithPagination;
    use LivewireAlert;

    protected $listeners = ['add_request', 'cancel_tx', 'receive_issued', 'refreshComponent' => '$refresh'];

    public $stock_id, $requested_qty, $remarks;
    public $selected_request, $chrgcode, $issue_qty = 0;
    public $issued_qty = 0;
    public $received_qty = 0;
    public $available_drugs;

    public function render()
    {
        $trans = InOutTransaction::with('drug')->with('location')
            ->with('charge')
            ->where('loc_code', session('pharm_location_id'));

        $drugs = DrugStock::with('drug')->select(DB::raw('MAX(id) as id'), 'dmdcomb', 'dmdctr', DB::raw('SUM(stock_bal) as "avail"'), 'drug_concat')
            ->whereRelation('location', 'description', 'LIKE', '%Warehouse%')
            ->where('stock_bal', '>', '0')->where('exp_date', '>', now())
            ->orderBy('drug_concat', 'ASC')
            ->groupBy('dmdcomb', 'dmdctr', 'drug_concat');

        return view('livewire.pharmacy.drugs.io-trans-list-requestor', [
            'trans' => $trans->latest()->paginate(20),
            'drugs' => $drugs->get(),
        ]);
    }

    public function add_request()
    {
        $this->validate(['stock_id' => ['required', 'numeric']]);
        $stock = DrugStock::find($this->stock_id);
        $dmdcomb = $stock->dmdcomb;
        $dmdctr = $stock->dmdctr;

        $current_qty = DrugStock::whereRelation('location', 'description', 'LIKE', '%Warehouse%')
            ->where('dmdcomb', $dmdcomb)->where('dmdctr', $dmdctr)
            ->where('stock_bal', '>', '0')->where('exp_date', '>', now())
            ->groupBy('dmdcomb', 'dmdctr')->sum('stock_bal');

        $this->validate([
            'requested_qty' => ['required', 'numeric', 'min:1', 'max:' . $current_qty],
            'remarks' => ['nullable', 'string'],
        ]);

        $reference_no = $this->reference_no ?? Carbon::now()->format('y-m-') . (sprintf("%04d", InOutTransaction::groupBy('reference_no')->count() + 1));

        $io_tx = InOutTransaction::create([
            'trans_no' => $reference_no,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'requested_qty' => $this->requested_qty,
            'requested_by' => session('user_id'),
            'loc_code' => session('pharm_location_id'),
        ]);

        $warehouse = PharmLocation::find('1');
        IoTransNewRequest::dispatch($warehouse, $io_tx);
        $warehouse->notify(new IoTranNotification($io_tx, session('user_id')));

        $this->alert('success', 'Request added!');
    }

    public function notify_request()
    {
        $io_tx = InOutTransaction::latest()->first();
        $warehouse = PharmLocation::find('1');
        IoTransNewRequest::dispatch($warehouse);
        $warehouse->notify(new IoTranNotification($io_tx, session('user_id')));
        $this->alert('success', 'Dispatched');
    }

    public function notify_user()
    {
        $user = User::find(session('user_id'));
        UserUpdated::dispatch($user);
    }

    public function select_request(InOutTransaction $txn)
    {
        $this->selected_request = $txn;
        $this->issue_qty = $txn->requested_qty;
        $this->available_drugs = $txn->warehouse_stock_charges->all();
        $this->dispatchBrowserEvent('toggleIssue');
    }

    public function cancel_tx(InOutTransaction $txn)
    {
        $trans_id = $txn->id;

        $issued_items = InOutTransactionItem::where('iotrans_id', $trans_id)
            ->where('status', 'Pending')
            ->latest('exp_date')
            ->get();

        if ($issued_items) {
            foreach ($issued_items as $item) {
                $from_stock = $item->from_stock;
                $from_stock->stock_bal += $item->qty;
                $from_stock->save();

                $item->status = 'Cancelled';
                $item->save();
            }
        }

        $txn->issued_qty = 0;
        $txn->trans_stat = 'Cancelled';
        $txn->save();

        $this->alert('success', 'Transaction cancelled. All issued items has been returned to the warehouse!');
        $this->reset();
    }

    public function receive_issued(InOutTransaction $txn)
    {
        $trans_id = $txn->id;

        $issued_items = InOutTransactionItem::where('iotrans_id', $trans_id)
            ->where('status', 'Pending')
            ->latest('exp_date')
            ->get();
        if ($issued_items) {
            foreach ($issued_items as $item) {

                $stock = DrugStock::firstOrCreate([
                    'dmdcomb' => $item->dmdcomb,
                    'dmdctr' => $item->dmdctr,
                    'loc_code' => $item->to,
                    'chrgcode' => $item->chrgcode,
                    'exp_date' => $item->exp_date,
                    'retail_price' => $item->retail_price,
                    'dmdprdte' => $item->dmdprdte,
                    'drug_concat' => $item->dm->drug_concat,
                ]);
                $stock->stock_bal += $item->qty;
                $stock->beg_bal += $item->qty;
                $txn->received_by += $item->qty;

                $item->status = 'Received';

                $stock->save();
                $item->save();
                LogIoTransReceive::dispatch($item->to, $item->dmdcomb, $item->dmdctr, $item->chrgcode, date('Y-m-d'), $item->dmdprdte, $item->retail_price, now(), $item->qty, $stock->id, $stock->exp_date, $stock->drug_concat());
            }
        }

        $txn->trans_stat = 'Received';
        $txn->save();

        $this->alert('success', 'Transaction successful. All items received!');
        $this->reset();
    }
}
