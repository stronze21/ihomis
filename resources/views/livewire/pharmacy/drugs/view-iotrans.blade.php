<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-exchange la-lg"></i> IO Transactions
            </li>
            <li>
                {{ $reference_no }}
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col p-5 mx-auto mt-5 max-w-screen-2xl">
    <div class="p-4 mb-3 bg-white rounded-lg">
        <div class="flex justify-end space-x-3">
            <button class="btn btn-sm btn-primary" onclick="add_item()" wire:loading.attr="disabled">Add
                Item</button>
            <button class="btn btn-sm" onclick="printMe()" wire:loading.attr="disabled">Print</button>
        </div>
    </div>
    @if ($errors->first())
        <div class="mb-3 shadow-lg alert alert-error">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 w-6 h-6 stroke-current" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif
    <div class="flex flex-col p-5 bg-white rounded-lg" id="print">
        <div class="flex justify-between w-full pb-2 border-b">
            <div class="flex flex-col w-1/2">
                <div class="flex">
                    <div class="w-36">Reference No:</div>
                    <div class="font-bold uppercase w-96">{{ $reference_no }}</div>
                </div>
                <div class="flex">
                    <div class="w-36"> Request FROM:</div>
                    <div class="font-bold uppercase w-96">{{ $trans[0]->location->description }}</div>
                </div>
                <div class="flex">
                    <div class="w-36"> Request TO:</div>
                    <div class="font-bold uppercase w-96">
                        {{ $trans[0]->from_location ? $trans[0]->from_location->description : '' }}
                    </div>
                </div>
            </div>
        </div>
        <table class="table w-full mt-3 table-compact">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date Requested</th>
                    <th>Item Requested</th>
                    <th class="text-right">Requested QTY</th>
                    <th class="text-right">Issued QTY</th>
                    <th>Fund Source</th>
                    <th class="text-center">Status</th>
                    <th>Date Updated</th>
                </tr>
            </thead>
            <tbody class="border">
                @forelse ($trans as $tran)
                    <tr class="cursor-pointer hover">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $tran->created_at() }}</td>
                        <td>{{ $tran->drug->drug_concat() }}</td>
                        <td class="text-right">{{ number_format($tran->requested_qty) }}</td>
                        <td class="text-right">
                            {{ number_format($tran->issued_qty < 1 ? '0' : $tran->issued_qty) }}
                        </td>
                        <td>
                            @php
                                if ($tran->trans_stat == 'Issued' or $tran->trans_stat == 'Received') {
                                    echo $tran->items->first()->charge->chrgdesc;
                                }
                            @endphp
                        </td>
                        <td>{!! $tran->stat() !!}</td>
                        <td>{{ $tran->updated_at2() }}</td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="8">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
    <script>
        function printMe() {
            var printContents = document.getElementById('print').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
@endpush
{{-- @push('scripts')
    <script>
        function add_item() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Add Item </span>
                    <div class="w-full form-control">
                        <label class="label" for="dmdcomb">
                            <span class="label-text">Drug/Medicine</span>
                        </label>
                        <select class="select select-bordered select2" id="dmdcomb">
                            <option disabled selected>Choose drug/medicine</option>
                            @foreach ($drugs as $drug)
                                <option value="{{ $drug->dmdcomb }},{{ $drug->dmdctr }}">{{ $drug->drug_concat() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="expiry_date">
                            <span class="label-text">Expiry Date</span>
                        </label>
                        <input id="expiry_date" type="date" value="{{ date('Y-m-d', strtotime(now() . '+1 years')) }}" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="qty">
                            <span class="label-text">QTY</span>
                        </label>
                        <input id="qty" type="number" value="1" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="unit_price">
                            <span class="label-text">Unit Cost</span>
                        </label>
                        <input id="unit_price" type="number" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="lot_no">
                            <span class="label-text">Lot No</span>
                        </label>
                        <input id="lot_no" type="text" class="w-full input input-bordered" />
                    </div>
                    <div class="px-2 form-control">
                        <label class="flex mt-3 space-x-3 cursor-pointer">
                            <input type="checkbox" id="has_compounding" class="checkbox" />
                            <span class="mr-auto label-text !justify-self-start">Highly Specialised Drugs</span>
                        </label>
                    </div>
                    <div class="w-full px-2 form-control" hidden id="compounding_div">
                        <label class="label" for="compounding_fee">
                            <span class="label-text">Compounding fee</span>
                        </label>
                        <input id="compounding_fee" type="number" step="0.01" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const dmdcomb = Swal.getHtmlContainer().querySelector('#dmdcomb');
                    const expiry_date = Swal.getHtmlContainer().querySelector('#expiry_date');
                    const qty = Swal.getHtmlContainer().querySelector('#qty');
                    const unit_price = Swal.getHtmlContainer().querySelector('#unit_price');
                    const lot_no = Swal.getHtmlContainer().querySelector('#lot_no');
                    const has_compounding = Swal.getHtmlContainer().querySelector('#has_compounding');
                    const compounding_div = Swal.getHtmlContainer().querySelector('#compounding_div');
                    const compounding_fee = Swal.getHtmlContainer().querySelector('#compounding_fee');

                    compounding_div.style.display = 'none';

                    has_compounding.addEventListener('click', function handleClick() {
                        if (has_compounding.checked) {
                            compounding_div.style.display = 'block';
                        } else {
                            compounding_div.style.display = 'none';
                        }
                    });

                    $('.select2').select2({
                        dropdownParent: $('.swal2-container'),
                        width: 'resolve',
                    });
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('dmdcomb', $('#dmdcomb').select2('val'));
                    @this.set('expiry_date', expiry_date.value);
                    @this.set('qty', qty.value);
                    @this.set('unit_price', unit_price.value);
                    @this.set('lot_no', lot_no.value);
                    @this.set('has_compounding', has_compounding.checked);
                    @this.set('compounding_fee', compounding_fee.value);

                    Livewire.emit('add_item');
                }
            });
        }

        function edit_item(item_id, item_lot_no, item_qty, item_unit_price, item_retail_price, item_total_amount,
            item_expiry_date, drug_name) {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold uppercase"> Update Item: <br>` + drug_name + ` </span>

                    <div class="w-full form-control">
                        <label class="label" for="update_expiry_date">
                            <span class="label-text">Expiry Date</span>
                        </label>
                        <input id="update_expiry_date" type="date" class="w-full input input-bordered" min="{{ date('Y-m-d') }}"/>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="update_qty">
                            <span class="label-text">QTY</span>
                        </label>
                        <input id="update_qty" type="number" value="1" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="update_unit_price">
                            <span class="label-text">Unit Price</span>
                        </label>
                        <input id="update_unit_price" type="number" step="0.01" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="update_lot_no">
                            <span class="label-text">Lot No</span>
                        </label>
                        <input id="update_lot_no" type="text" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                showDenyButton: true,
                denyButtonText: `Remove`,
                showConfirmButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const update_expiry_date = Swal.getHtmlContainer().querySelector('#update_expiry_date');
                    const update_qty = Swal.getHtmlContainer().querySelector('#update_qty');
                    const update_unit_price = Swal.getHtmlContainer().querySelector('#update_unit_price');
                    const update_lot_no = Swal.getHtmlContainer().querySelector('#update_lot_no');

                    update_expiry_date.value = item_expiry_date;
                    update_qty.value = item_qty;
                    update_unit_price.value = item_unit_price;
                    update_lot_no.value = item_lot_no;
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('qty', update_qty.value);
                    @this.set('unit_price', update_unit_price.value);
                    @this.set('lot_no', update_lot_no.value);
                    @this.set('expiry_date', update_expiry_date.value);

                    Livewire.emit('edit_item', item_id);
                } else if (result.isDenied) {
                    Swal.fire({
                        html: `<span class="text-xl font-bold uppercase"> Remove Item: <br>` + drug_name +
                            ` </span>`,
                        icon: 'error',
                        showCancelButton: true,
                        showConfirmButton: true,
                        confirmButtonText: `Confirm`,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Livewire.emit('delete_item', item_id);
                        }
                    });
                }
            });
        }

        function save_lock() {
            Swal.fire({
                title: 'Are you sure?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                html: `
                        <i data-feather="x-circle" class="w-16 h-16 mx-auto mt-3 text-danger"></i>
                        <div class="mt-2 text-slate-500" id="inf">All items in this delivery will be added to your current stocks and no changes can be made after. <br>This process cannot be undone. Continue?</div>
                    `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('save_lock')
                }
            })
        }
    </script>
@endpush --}}
