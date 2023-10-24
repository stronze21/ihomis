<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-exchange la-lg"></i> Issued IO Transactions
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="flex flex-col p-5 mx-auto">
    <div class="flex justify-between">
        <div class="ml-2">
            <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info"><i class="las la-lg la-file-excel"></i>
                Export</button>
        </div>
        <div class="ml-2">
            <div class="form-control">
                <label class="input-group input-group-sm">
                    <span><i class="las la-search"></i></span>
                    <input type="text" placeholder="Search" class="input input-bordered input-sm"
                        wire:model.lazy="search" />
                </label>
            </div>
        </div>
    </div>
    <div class="flex flex-col justify-center w-full mt-2 overflow-x-auto">
        <table class="table w-full table-compact" id="table">
            <thead>
                <tr>
                    <th class="w-1/12">Reference</th>
                    <th class="w-1/12">Date Requested</th>
                    <th class="w-1/12">Issued to</th>
                    <th class="w-6/12">Item Issued</th>
                    <th class="w-1/12">Issued QTY</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trans as $tran)
                    <tr class="cursor-pointer hover" wire:key="select-txt-{{ $loop->iteration . $tran->id }}">
                        <th>{{ $tran->trans_no }}</th>
                        <td>{{ $tran->created_at() }}</td>
                        <td>{{ $tran->location->description }}</td>
                        <td>{{ $tran->drug->drug_concat() }}</td>
                        <td>{{ $tran->issued_qty < 1 ? '0' : $tran->issued_qty }}</td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="10">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $trans->links() }}
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
