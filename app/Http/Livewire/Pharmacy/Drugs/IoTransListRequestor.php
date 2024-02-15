<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Events\IoTransEvent;
use App\Events\IoTransNewRequest;
use App\Events\IoTransRequestUpdated;
use App\Events\UserUpdated;
use App\Jobs\LogIoTransIssue;
use App\Jobs\LogIoTransReceive;
use App\Models\Pharmacy\DrugPrice;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\Drugs\InOutTransaction;
use App\Models\Pharmacy\Drugs\InOutTransactionItem;
use App\Models\Pharmacy\PharmLocation;
use App\Models\User;
use App\Notifications\IoTranNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class IoTransListRequestor extends Component
{
    use LivewireAlert;
    use WithPagination;

    protected $listeners = ['add_request', 'cancel_tx', 'receive_issued', 'refreshComponent' => '$refresh', 'add_more_request', 'issue_request'];

    public $stock_id, $requested_qty, $remarks;
    public $selected_request, $chrgcode, $issue_qty = 0;
    public $issued_qty = 0;
    public $received_qty = 0;
    public $available_drugs;
    public $locations, $location_id;
    public $search;

    public function render()
    {
        $trans = InOutTransaction::whereHas('drug', function ($query) {
            $query->where('drug_concat', 'like', '%' . $this->search . '%');
        })->with('location')
            ->with('charge')
            ->where(function ($query) {
                $query->where('loc_code', session('pharm_location_id'))
                    ->orWhere('request_from', session('pharm_location_id'));
            })
            ->latest();

        $drugs = DrugStock::with('drug')->select(DB::raw('MAX(id) as id'), 'dmdcomb', 'dmdctr', 'drug_concat')
            ->where('exp_date', '>', now())
            ->orderBy('drug_concat', 'ASC')
            ->groupBy('dmdcomb', 'dmdctr', 'drug_concat');

        return view('livewire.pharmacy.drugs.io-trans-list-requestor', [
            'trans' => $trans->paginate(20),
            'drugs' => $drugs->get(),
        ]);
    }

    public function mount()
    {
        $this->locations = PharmLocation::where('id', '<>', session('pharm_location_id'))->get();
    }

    public function add_request()
    {
        $this->validate(['stock_id' => ['required', 'numeric']]);
        $stock = DrugStock::find($this->stock_id);
        $dmdcomb = $stock->dmdcomb;
        $dmdctr = $stock->dmdctr;

        $current_qty = DrugStock::where('loc_code', $this->location_id)
            ->where('dmdcomb', $dmdcomb)->where('dmdctr', $dmdctr)
            ->where('stock_bal', '>', '0')->where('exp_date', '>', now())
            ->groupBy('dmdcomb', 'dmdctr')->sum('stock_bal');

        $this->validate([
            'requested_qty' => ['required', 'numeric', 'min:1', 'max:' . $current_qty],
            'remarks' => ['nullable', 'string'],
        ]);

        $reference_no = Carbon::now()->format('y-m-') . (sprintf("%04d", count(InOutTransaction::select(DB::raw('COUNT(trans_no)'))->groupBy('trans_no')->get()) + 1));

        $io_tx = InOutTransaction::create([
            'trans_no' => $reference_no,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'requested_qty' => $this->requested_qty,
            'requested_by' => session('user_id'),
            'loc_code' => session('pharm_location_id'),
            'request_from' => $this->location_id,
        ]);

        $location = PharmLocation::find($this->location_id);
        IoTransNewRequest::dispatch($location, $io_tx);
        $location->notify(new IoTranNotification($io_tx, session('user_id')));

        $this->resetExcept('locations');
        $this->alert('success', 'Request added!');
    }

    public function add_more_request()
    {
        $this->validate(['stock_id' => ['required', 'numeric']]);
        $stock = DrugStock::find($this->stock_id);
        $dmdcomb = $stock->dmdcomb;
        $dmdctr = $stock->dmdctr;

        $past = InOutTransaction::where('loc_code', session('pharm_location_id'))->latest()->first();

        $current_qty = DrugStock::where('loc_code', $past->request_from)
            ->where('dmdcomb', $dmdcomb)->where('dmdctr', $dmdctr)
            ->where('stock_bal', '>', '0')->where('exp_date', '>', now())
            ->groupBy('dmdcomb', 'dmdctr')->sum('stock_bal');

        $this->validate([
            'requested_qty' => ['required', 'numeric', 'min:1', 'max:' . $current_qty],
            'remarks' => ['nullable', 'string'],
        ]);

        $reference_no = $past->trans_no;

        $io_tx = InOutTransaction::create([
            'trans_no' => $reference_no,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'requested_qty' => $this->requested_qty,
            'requested_by' => session('user_id'),
            'loc_code' => session('pharm_location_id'),
            'request_from' => $past->request_from,
        ]);

        $warehouse = PharmLocation::find('1');
        IoTransNewRequest::dispatch($warehouse, $io_tx);
        $warehouse->notify(new IoTranNotification($io_tx, session('user_id')));

        $this->resetExcept('locations');
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
        $this->available_drugs = DrugStock::with('charge')->with('drug')
            ->select('chrgcode', DB::raw('SUM(stock_bal) as "avail"'))
            ->where('loc_code', $txn->request_from)->where('stock_bal', '>', '0')
            ->where('exp_date', '>', now())
            ->where('dmdcomb', $txn->dmdcomb)
            ->where('dmdctr', $txn->dmdctr)
            ->groupBy('chrgcode')
            ->get();
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
        $this->resetExcept('locations');
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
    }

    public function issue_request()
    {
        $requested_qty = $this->selected_request->requested_qty;
        $this->validate([
            'issue_qty' => ['required', 'numeric', 'min:1', 'max:' . $requested_qty],
            'chrgcode' => ['required'],
            'selected_request' => ['required'],
            'remarks' => ['nullable', 'string', 'max:255']
        ]);

        $issue_qty = $this->issue_qty;
        $issued_qty = 0;
        $location_id = PharmLocation::find($this->selected_request->request_from)->id;

        $available_qty = DrugStock::where('dmdcomb', $this->selected_request->dmdcomb)
            ->where('dmdctr', $this->selected_request->dmdctr)
            ->where('chrgcode', $this->chrgcode)
            ->where('exp_date', '>', date('Y-m-d'))
            ->where('loc_code', $location_id)
            ->where('stock_bal', '>', '0')
            ->groupBy('chrgcode')
            ->sum('stock_bal');

        if ($available_qty >= $issue_qty) {

            $stocks = DrugStock::where('dmdcomb', $this->selected_request->dmdcomb)
                ->where('dmdctr', $this->selected_request->dmdctr)
                ->where('chrgcode', $this->chrgcode)
                ->where('exp_date', '>', date('Y-m-d'))
                ->where('loc_code', $location_id)
                ->where('stock_bal', '>', '0')
                ->oldest('exp_date')
                ->get();

            foreach ($stocks as $stock) {
                if ($issue_qty) {
                    if ($issue_qty > $stock->stock_bal) {
                        $trans_qty = $stock->stock_bal;
                        $issue_qty -= $stock->stock_bal;
                        $stock->stock_bal = 0;
                    } else {
                        $trans_qty = $issue_qty;
                        $stock->stock_bal -= $issue_qty;
                        $issue_qty = 0;
                    }

                    $issued_qty += $trans_qty;

                    $trans_item = InOutTransactionItem::create([
                        'stock_id' => $stock->id,
                        'iotrans_id' => $this->selected_request->id,
                        'dmdcomb' => $this->selected_request->dmdcomb,
                        'dmdctr' => $this->selected_request->dmdctr,
                        'from' => $this->selected_request->request_from,
                        'to' => $this->selected_request->loc_code,
                        'chrgcode' => $stock->chrgcode,
                        'exp_date' => $stock->exp_date,
                        'qty' => $trans_qty,
                        'status' => 'Pending',
                        'user_id' => session('user_id'),
                        'retail_price' => $stock->retail_price,
                        'dmdprdte' => $stock->dmdprdte,
                    ]);
                    $stock->save();
                    LogIoTransIssue::dispatch($location_id, $trans_item->dmdcomb, $trans_item->dmdctr, $trans_item->chrgcode, date('Y-m-d'), $stock->retail_price, $stock->dmdprdte, now(), $trans_item->qty, $stock->exp_date, $stock->drug_concat());
                }
            }
            $this->selected_request->issued_qty = $issued_qty;
            $this->selected_request->issued_by = session('user_id');
            $this->selected_request->trans_stat = 'Issued';

            $this->selected_request->save();

            IoTransRequestUpdated::dispatch($this->selected_request, 'A requested drugs/medicine has been issued from the warehouse.');
            $this->dispatchBrowserEvent('toggleIssue');
            $this->alert('success', 'Request issued successfully!');
            $this->resetExcept('locations');
        } else {
            $this->alert('error', 'Failed to issue medicine. Selected fund source insufficient stock!');
        }
    }
}
