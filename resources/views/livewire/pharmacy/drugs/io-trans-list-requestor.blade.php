<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-exchange la-lg"></i> IO Transactions
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col p-5 mx-auto">
    <div class="flex justify-between">
        @can('request-drugs')
            <div>
                <button class="btn btn-sm btn-primary" onclick="add_request()">Add Request</button>
            </div>
        @endcan
        {{-- <div>
            <button class="btn btn-sm btn-primary" wire:click="notify_request()">notify_request</button>
        </div>
        <div>
            <button class="btn btn-sm btn-primary" wire:click="notify_user()">notify_user</button>
        </div> --}}
        <div>
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
        <table class="table w-full table-compact">
            <thead>
                <tr>
                    <th class="w-1/12">Reference</th>
                    <th class="w-1/12">Date Requested</th>
                    <th class="w-1/12">Requestor</th>
                    <th class="w-6/12">Item Requested</th>
                    <th class="w-1/12">Requested QTY</th>
                    <th class="w-1/12">Issued QTY</th>
                    <th class="w-1/12">Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trans as $tran)
                    <tr class="cursor-pointer hover" wire:key="select-txt-{{ $loop->iteration . $tran->id }}"
                        @if ($tran->trans_stat == 'Requested') onclick="cancel_tx({{ $tran->id }})" @endif
                        @if ($tran->trans_stat == 'Issued' and session('pharm_location_id') == $tran->loc_code) @can('receive-requested-drugs') onclick="receive_issued('{{ $tran->id }}', '{{ $tran->drug->drug_concat() }}', '{{ number_format($tran->issued_qty) }}')" @endcan @endif>
                        <th>{{ $tran->trans_no }}</th>
                        <td>{{ $tran->created_at() }}</td>
                        <td>{{ $tran->location->description }}</td>
                        <td>{{ $tran->drug->drug_concat() }}</td>
                        <td>{{ number_format($tran->requested_qty) }}</td>
                        <td>{{ number_format($tran->issued_qty < 1 ? '0' : $tran->issued_qty) }}</td>
                        <td>{!! $tran->updated_at() !!}</td>
                        <td></td>
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
        function add_request() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Request Drugs/Medicine </span>
                    <div class="w-full form-control">
                        <label class="label" for="stock_id">
                            <span class="label-text">Drug/Medicine</span>
                        </label>
                        <select class="select select-bordered select2" id="stock_id">
                            <option disabled selected>Choose drug/medicine</option>
                            @foreach ($drugs as $drug)
                                <option value="{{ $drug->id }}">{{ $drug->drug->drug_concat() }} - [avail QTY: {{ number_format($drug->avail) }}]</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="requested_qty">
                            <span class="label-text">Request QTY</span>
                        </label>
                        <input id="requested_qty" type="text" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="remarks">
                            <span class="label-text">Remarks</span>
                        </label>
                        <input id="remarks" type="text" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const stock_id = Swal.getHtmlContainer().querySelector('#stock_id');
                    const requested_qty = Swal.getHtmlContainer().querySelector('#requested_qty');
                    const remarks = Swal.getHtmlContainer().querySelector('#remarks');

                    $('.select2').select2({
                        dropdownParent: $('.swal2-container'),
                        width: 'resolve',
                        dropdownCssClass: "text-sm",
                    });

                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('stock_id', stock_id.value);
                    @this.set('requested_qty', requested_qty.value);
                    @this.set('remarks', remarks.value);

                    Livewire.emit('add_request');
                }
            });
        }

        function cancel_tx(trans_id) {
            Swal.fire({
                title: 'Are you sure you want to cancel this transaction?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                confirmButtonColor: 'red',
                html: `
                    <i data-feather="x-circle" class="w-16 h-16 mx-auto mt-3 text-danger"></i>
                    <div class="mt-2 text-slate-500" id="inf">All items issued that have not been received will return to warehouse. <br>This process cannot be undone. Continue?</div>
                `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('cancel_tx', trans_id)
                }
            })
        }

        function receive_issued(trans_id, drug, issued_drug_qty) {
            Swal.fire({
                html: `
                    <span class="text-lg text-xl font-bold"> Receive Drugs/Medicine </span>
                    <div class="w-full mt-3 form-control">
                        <span class="font-bold text-7xl"> ` + issued_drug_qty + ` </span>
                        <span class="text-2xl font-medium"> ` + drug + ` </span>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Receive`,
                didOpen: () => {
                    const received_qty = Swal.getHtmlContainer().querySelector('#received_qty');
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('receive_issued', trans_id);
                }
            });
        }


        window.addEventListener('toggleIssue', event => {
            $('#issueModal').click();
        })

        Echo.private(`ioTrans.{{ session('pharm_location_id') }}`)
            .listen('IoTransRequestUpdated', (e) => {
                Livewire.emit('refreshComponent');
            });
    </script>
@endpush
