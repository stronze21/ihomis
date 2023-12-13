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
                <i class="mr-1 las la-folder la-lg"></i> Stock Card
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
                            <span>Location</span>
                            <select class="text-sm select select-bordered select-sm" wire:model="location_id">
                                <option value="">N/A</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->description }}</option>
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
            </div>
        </div>
        <table class="table bg-white shadow-md table-fixed table-sm" id="table">
            <thead class="font-bold text-center bg-gray-100">
                <tr>
                    <td class="text-sm uppercase border border-black" rowspan='2'>Date</td>
                    <td class="text-sm border border-black" rowspan='2'>Reference</td>
                    <td colspan="3" class="border border-black">Receipt</td>
                    <td colspan="3" class="border border-black">Issued</td>
                    <td colspan="3" class="border border-black">Balance</td>
                    <td class="text-sm uppercase border border-black" rowspan='2'>Total</td>
                    <td class="text-sm border border-black" rowspan='2'>Expiry Date</td>
                </tr>
                <tr>
                    <td class="border border-black">Revolving</td>
                    <td class="border border-black">Regular</td>
                    <td class="border border-black">Others</td>
                    <td class="border border-black">Revolving</td>
                    <td class="border border-black">Regular</td>
                    <td class="border border-black">Others</td>
                    <td class="border border-black">Revolving</td>
                    <td class="border border-black">Regular</td>
                    <td class="border border-black">Others</td>
                </tr>
            </thead>
            <tbody>
                {{-- @forelse ($drugs_ordered as $rxo)
                    <tr classs="border border-black">
                        <td class="text-sm text-right border">{{ $loop->iteration }}</td>
                        <td class="text-sm border">{{ date('F j, Y H:i A', strtotime($rxo->dodate)) }}</td>
                        <td class="text-sm border">{{ $rxo->hpercode }}</td>
                        <td class="text-sm border">{{ $rxo->patient->fullname() }}</td>
                        <td class="text-sm border">{{ $rxo->pcchrgcod }}</td>
                        <td class="text-sm text-right border">{{ $rxo->total_qty }}</td>
                        <td class="text-sm text-right border">{{ $rxo->total_amount }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="22" class="font-bold text-center uppercase bg-red-400 border border-black">No
                            record found!</td>
                    </tr>
                @endforelse --}}
            </tbody>
        </table>
        <div class="mt-2">
            {{-- {{ $drugs_ordered->links() }} --}}
        </div>
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
