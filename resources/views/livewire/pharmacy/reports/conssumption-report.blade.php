<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-file-excel la-lg"></i> Report
            </li>
            <li>
                <i class="mr-1 las la-tablets la-lg"></i> Consumption Report
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="max-w-screen">
    <div class="flex flex-col px-2 py-5">
        <div class="flex justify-end my-2">
            <div class="ml-2">
                <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info"><i
                        class="las la-lg la-file-excel"></i> Export</button>
            </div>
            <div class="ml-2">
                <div class="form-control">
                    <label class="input-group">
                        <span>From</span>
                        <input type="month" class="w-full input input-sm input-bordered"
                            wire:model.lazy="date_from" />
                    </label>
                </div>
            </div>
            <div class="ml-2">
                <div class="form-control">
                    <label class="input-group">
                        <span>Fund Source</span>
                        <select class="select select-bordered select-sm" wire:model="filter_charge">
                            <option></option>
                            @foreach ($charge_codes as $charge)
                                <option value="{{ $charge->chrgcode }},{{ $charge->chrgdesc }}">{{ $charge->chrgdesc }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>
        </div>
        <table class="text-xs bg-white shadow-md table-fixed table-compact" id="table">
            <thead class="font-bold bg-gray-200">
                <tr class="text-center uppercase">
                    <td class="w-2/12 text-xs border border-black">Source of Fund</td>
                    <td class="text-xs border border-black" colspan="2">Beg. Bal.</td>
                    <td class="text-xs border border-black" colspan="2">Total Purchases</td>
                    <td class="text-xs border border-black" colspan="3">Total Avail. For Sale</td>
                    <td class="text-xs border border-black" colspan="1"></td>
                    <td class="text-xs border border-black" colspan="14">Issuances</td>
                    <td class="text-xs border border-black" colspan="2">Ending Bal.</td>
                </tr>
                <tr class="text-center">
                    <td class="text-xs uppercase border border-black">{{ $current_charge }}</td>
                    <td class="text-xs border border-black">QTY.</td>
                    <td class="text-xs border border-black">Amount</td>
                    <td class="text-xs border border-black">QTY.</td>
                    <td class="text-xs border border-black">AMT.</td>
                    <td class="text-xs border border-black">QTY.</td>
                    <td class="text-xs border border-black">Unit <br> Cost</td>
                    <td class="text-xs border border-black">Total <br> Cost</td>
                    <td class="text-xs border border-black">Returns</td>
                    <td class="text-xs border border-black">EMS</td>
                    <td class="text-xs border border-black">MAIP</td>
                    <td class="text-xs border border-black">W.S.</td>
                    <td class="text-xs border border-black">Pay</td>
                    <td class="text-xs border border-black">Service</td>
                    <td class="text-xs border border-black">CAF</td>
                    <td class="text-xs border border-black">PCSO</td>
                    <td class="text-xs border border-black">PHIC</td>
                    <td class="text-xs border border-black">Kon. <br> Pkg.</td>
                    <td class="text-xs border border-black">Issued <br> Total</td>
                    <td class="text-xs border border-black">Selling <br> Price</td>
                    <td class="text-xs border border-black">Total <br> Sales</td>
                    <td class="text-xs border border-black">COGS</td>
                    <td class="text-xs border border-black">Profit</td>
                    <td class="text-xs border border-black">QTY.</td>
                    <td class="text-xs border border-black">Amount</td>
                </tr>
            </thead>
            <tbody>
                @forelse ($drugs_issued as $rxi)
                    <tr classs="border border-black">
                        <td class="text-xs border border-black">
                            <div class="flex flex-col">
                                <div class="text-sm font-bold">{{ $rxi->drug->generic->gendesc }}</div>
                                <div class="ml-10 text-xs text-slate-800">
                                    {{ $rxi->drug->dmdnost }}{{ $rxi->drug->strength->stredesc ?? '' }}
                                    {{ $rxi->drug->form->formdesc ?? '' }}</div>
                            </div>
                        </td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->beg_bal) }}</td>
                        <td class="text-xs text-right border border-black">
                            {{ number_format($rxi->beg_bal * $rxi->current_price->acquisition_cost, 2) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->purchased) }}</td>
                        <td class="text-xs text-right border border-black">
                            {{ number_format($rxi->purchased * $rxi->current_price->acquisition_cost, 2) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->available()) }}</td>
                        <td class="text-xs text-right border border-black">
                            {{ number_format($rxi->current_price->acquisition_cost, 2) }}</td>
                        <td class="text-xs text-right border border-black">
                            {{ number_format($rxi->available_amount(), 2) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->return_qty) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->ems) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->maip) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->wholesale) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->pay) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->service) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->caf) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->pcso) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->phic) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->konsulta) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->issue_qty) }}</td>
                        <td class="text-xs text-right border border-black">
                            {{ number_format($rxi->current_price->dmselprice, 2) }}</td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->total_sales(), 2) }}
                        </td>
                        <td class="text-xs text-right border border-black">{{ number_format($rxi->total_cogs(), 2) }}
                        </td>
                        <td class="text-xs text-right border border-black">
                            {{ number_format($rxi->total_profit(), 2) }}
                        </td>
                        <td class="text-xs text-right border border-black">
                            {{ number_format($rxi->ending_balance(), 2) }}</td>
                        <td class="text-xs text-right border border-black">
                            {{ number_format($rxi->ending_balance() * $rxi->current_price->acquisition_cost, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="25" class="font-bold text-center uppercase bg-red-400 border border-black">No
                            record found!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


@push('scripts')
    <script>
        function ExportToExcel(type, fn, dl) {
            var elt = document.getElementById('table');
            var wb = XLSX.utils.table_to_book(elt, {
                sheet: "sheet1"
            });
            return dl ?
                XLSX.write(wb, {
                    bookType: type,
                    bookSST: true,
                    type: 'base64'
                }) :
                XLSX.writeFile(wb, fn || ('Ward Consumption Report.' + (type || 'xlsx')));
        }
    </script>
@endpush
