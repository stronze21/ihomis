<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pharmacy\Drug;
use App\Jobs\LogDrugTransaction;
use App\Models\Pharmacy\DrugPrice;
use Illuminate\Support\Facades\DB;
use App\Events\IoTransRequestIssued;
use Illuminate\Support\Facades\Auth;
use App\Events\IoTransRequestUpdated;
use App\Jobs\LogIoTransIssue;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Pharmacy\Drugs\InOutTransaction;
use App\Models\Pharmacy\Drugs\InOutTransactionItem;

class IoTransList extends Component
{
    use WithPagination;
    use LivewireAlert;

    protected $listeners = ['add_request', 'cancel_issued', 'refreshComponent' => '$refresh', 'issue_request'];
    // protected $listeners = ['add_request', 'cancel_issued', 'echo:io-trans,new-request' => 'notifyRequest'];
    // protected $listeners = ['add_request', 'cancel_issued', 'echo:private-io-trans,new-request' => 'notifyRequest', 'echo:io-trans,new-request' => 'notifyRequest'];

    public $stock_id, $requested_qty, $remarks;
    public $selected_request, $chrgcode, $issue_qty = 0;
    public $issued_qty = 0;
    public $received_qty = 0;
    public $available_drugs;


    public function render()
    {
        $trans = InOutTransaction::with('drug')->with('location')
            ->with('charge');

        $drugs = DrugStock::with('drug')->select(DB::raw('MAX(id) as id'), 'dmdcomb', 'dmdctr', DB::raw('SUM(stock_bal) as "avail"'))
            ->whereRelation('location', 'description', 'LIKE', '%Warehouse%')
            ->where('stock_bal', '>', '0')->where('exp_date', '>', now())
            ->groupBy('dmdcomb', 'dmdctr');

        return view('livewire.pharmacy.drugs.io-trans-list', [
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

        $reference_no = Carbon::now()->format('y-m-') . (sprintf("%04d", InOutTransaction::count() + 1));

        InOutTransaction::create([
            'trans_no' => $reference_no,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'requested_qty' => $this->requested_qty,
            'requested_by' => auth()->user()->id,
            'loc_code' => auth()->user()->pharm_location_id,
        ]);

        $this->alert('success', 'Request added!');
    }

    public function select_request(InOutTransaction $txn)
    {
        $this->selected_request = $txn;
        $this->issue_qty = $txn->requested_qty;
        $this->available_drugs = $txn->warehouse_stock_charges->all();
        $this->dispatchBrowserEvent('toggleIssue');
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
        $warehouse_id = PharmLocation::where('description', 'LIKE', '%' . 'Warehouse')->first()->id;

        $available_qty = DrugStock::where('dmdcomb', $this->selected_request->dmdcomb)
            ->where('dmdctr', $this->selected_request->dmdctr)
            ->where('chrgcode', $this->chrgcode)
            ->where('exp_date', '>', date('Y-m-d'))
            ->where('loc_code', $warehouse_id)
            ->where('stock_bal', '>', '0')
            ->groupBy('chrgcode')
            ->sum('stock_bal');

        if ($available_qty >= $issue_qty) {

            $stocks = DrugStock::where('dmdcomb', $this->selected_request->dmdcomb)
                ->where('dmdctr', $this->selected_request->dmdctr)
                ->where('chrgcode', $this->chrgcode)
                ->where('exp_date', '>', date('Y-m-d'))
                ->where('loc_code', $warehouse_id)
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
                        'from' => session('pharm_location_id'),
                        'to' => $this->selected_request->loc_code,
                        'chrgcode' => $stock->chrgcode,
                        'exp_date' => $stock->exp_date,
                        'qty' => $trans_qty,
                        'status' => 'Pending',
                        'user_id' => auth()->user()->id,
                        'retail_price' => $stock->retail_price,
                        'dmdprdte' => $stock->dmdprdte,
                    ]);
                    $stock->save();
                    LogIoTransIssue::dispatch($warehouse_id, $trans_item->dmdcomb, $trans_item->dmdctr, $trans_item->chrgcode, date('Y-m-d'), $stock->retail_price, $stock->dmdprdte, now(), $trans_item->qty);
                }
            }
            $this->selected_request->issued_qty = $issued_qty;
            $this->selected_request->issued_by = Auth::user()->id;
            $this->selected_request->trans_stat = 'Issued';

            $this->selected_request->save();

            IoTransRequestUpdated::dispatch($this->selected_request, 'A requested drugs/medicine has been issued from the warehouse.');
            $this->dispatchBrowserEvent('toggleIssue');
            $this->alert('success', 'Request issued successfully!');
            $this->reset();
        } else {
            $this->alert('error', 'Failed to issue medicine. Selected fund source insufficient stock!');
        }
    }

    public function select_issued(InOutTransaction $txn)
    {
        $this->selected_request = $txn;
        $this->available_drugs = $txn->warehouse_stock_charges->all();
        $this->dispatchBrowserEvent('toggleIssue');
    }

    public function cancel_issued(InOutTransaction $txn)
    {
        $trans_id = $txn->id;

        $issued_items = InOutTransactionItem::where('iotrans_id', $trans_id)
            ->where('status', 'Pending')
            ->latest('exp_date')
            ->get();
        foreach ($issued_items as $item) {
            $from_stock = $item->from_stock;
            $from_stock->stock_bal += $item->qty;
            $from_stock->save();

            $item->status = 'Cancelled';
            $item->save();

            $log = DrugStockLog::firstOrNew([
                'loc_code' => $from_stock->loc_code,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'date_logged' => date('Y-m-d'),
                'dmdprdte' => $from_stock->dmdprdte,
                'unit_price' => $from_stock->retail_price,
            ]);
            $log->time_logged = now();
            $log->transferred -= $item->qty;

            $log->save();
        }

        $txn->issued_qty = 0;
        $txn->trans_stat = 'Cancelled';
        $txn->save();
        $this->alert('success', 'Issued items successfully recalled!');
        $this->reset();
    }
}
