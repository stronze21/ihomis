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
                            <span>Date</span>
                            <input type="date" class="w-full input input-sm input-bordered"
                                wire:model.lazy="date_from" />
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <table class="table bg-white shadow-md table-fixed table-sm" id="table">
            <thead class="font-bold text-center bg-gray-100">
                <tr>
                    <td class="text-sm uppercase border border-black" rowspan='2'>Drug</td>
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
                @forelse ($cards as $card)
                    @php
                        $ref = $card->reference;
                        $receipt = $card->rec_revolving + $card->rec_regular + $card->rec_others;
                        $issued = $card->iss_revolving + $card->iss_regular + $card->iss_others;
                        $balance = $card->bal_revolving + $card->bal_regular + $card->bal_others;
                        $total = $ref + $receipt - $issued;
                    @endphp
                    <tr classs="border border-black">
                        <td class="text-sm border">{{ $card->drug_concat }}</td>
                        <td class="text-sm text-right border">{{ $card->stock_date }}</td>
                        <td class="text-sm text-right border">{{ $card->reference }}</td>
                        <td class="text-sm border">{{ $card->rec_revolving }}</td>
                        <td class="text-sm border">{{ $card->rec_regular }}</td>
                        <td class="text-sm border">{{ $card->rec_others }}</td>
                        <td class="text-sm border">{{ $card->iss_revolving }}</td>
                        <td class="text-sm border">{{ $card->iss_regular }}</td>
                        <td class="text-sm border">{{ $card->iss_others }}</td>
                        <td class="text-sm border">{{ $card->bal_revolving }}</td>
                        <td class="text-sm border">{{ $card->bal_regular }}</td>
                        <td class="text-sm border">{{ $card->bal_others }}</td>
                        <td class="text-sm text-right border">{{ $total }}</td>
                        <td class="text-sm border">{{ $card->exp_date }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="22" class="font-bold text-center uppercase bg-red-400 border border-black">No
                            record found!</td>
                    </tr>
                @endforelse
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
