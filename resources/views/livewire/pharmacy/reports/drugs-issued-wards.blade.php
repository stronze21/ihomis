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
                <i class="mr-1 las la-clone la-lg"></i> Drug Issuance
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="max-w-screen">
    <div class="flex flex-col px-2 py-5 overflow-auto">
        <div class="flex justify-between my-2">
            <div class="flex justify-between">
            </div>
            <div class="flex justify-end">
                <div class="ml-2">
                    <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info"><i
                            class="las la-lg la-file-excel"></i> Export</button>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>Ward</span>
                            <select class="text-sm select select-bordered select-sm" wire:model="wardcode">
                                <option value="All">All</option>
                                @foreach ($wards as $ward)
                                    <option value="{{ $ward->wardcode }}">{{ $ward->wardname }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>From</span>
                            <input type="datetime-local" class="w-full input input-sm input-bordered"
                                max="{{ $date_to }}" wire:model.lazy="date_from" />
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>To</span>
                            <input type="datetime-local" class="w-full input input-sm input-bordered"
                                min="{{ $date_from }}" wire:model.lazy="date_to" />
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
                                    <option value="{{ $charge->chrgcode }}">
                                        {{ $charge->chrgdesc }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <table class="table bg-white shadow-md table-fixed table-compact" id="table">
            <thead class="font-bold bg-gray-200">
                <tr>
                    <td class="text-sm text-center uppercase border">#</td>
                    <td class="text-sm border">Ward</td>
                    <td class="text-sm border">Item Description</td>
                    <td class="text-sm text-right border">TOTAL</td>
                </tr>
            </thead>
            <tbody>
                @forelse ($drugs_issued as $rxi)
                    @php
                        $concat = implode(',', explode('_,', $rxi->drug_concat));
                    @endphp
                    <tr class="border border-black">
                        <td class="text-sm text-right border">{{ $loop->iteration }}</td>
                        <td class="text-sm border">{{ $rxi->wardname }}</td>
                        <td class="text-sm border">
                            <div class="flex flex-col">
                                <div class="text-xs text-slate-500">
                                    {{ $rxi->chrgdesc }}
                                </div>
                                <div class="text-sm font-bold">{{ $concat }}</div>
                            </div>
                        </td>
                        <td class="text-sm text-right border">{{ $rxi->qty }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="22" class="font-bold text-center uppercase bg-red-400 border border-black">No
                            record found!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="my-modal" class="modal-toggle" wire:loading.attr="checked" />
    <div class="modal">
        <div class="modal-box">
            <div>
                <span>
                    <i class="las la-spinner la-lg animate-spin"></i>
                    Processing...
                </span>
            </div>
        </div>
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
