<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ Auth::user()->location->description }}
            </li>
            <li>
                <i class="mr-1 las la-truck la-lg"></i> Drugs and Medicine Stock Inventory
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col px-5 py-5 mx-auto max-w-screen">
    <div class="flex justify-between">
        <div class="flex justify-end mt-auto">
            <small class="mr-2">Expiry: </small>
            <span class="mr-1 shadow-md badge badge-sm badge-ghost">Out of Stock</span>
            <span class="mr-1 shadow-md badge badge-sm badge-success">>=6 Months till Expiry</span>
            <span class="mr-1 shadow-md badge badge-sm badge-warning">Below 6 Months till expiry</span>
            <span class="mr-1 shadow-md badge badge-sm badge-error">Expired</span>
        </div>
        {{-- <div>
            <button class="btn btn-sm btn-primary" onclick="">Add Delivery</button>
        </div> --}}
        <div class="flex">
            <div class="mt-auto">
                <button class="btn btn-sm btn-primary" onclick="add_item()" wire:loading.attr="disabled">Add
                    Item</button>
            </div>
            @can('filter-stocks-location')
                <div class="ml-3 form-control">
                    <label class="label">
                        <span class="label-text">Current Location</span>
                    </label>
                    <select class="w-full max-w-xs text-sm select select-bordered select-sm select-success"
                        wire:model="location_id">
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->description }}</option>
                        @endforeach
                    </select>
                </div>
            @endcan
            <div class="ml-3 form-control">
                <label class="label">
                    <span class="label-text">Seach generic name</span>
                </label>
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
                    <th>Source of Fund</th>
                    <th>Location</th>
                    <th>Balance as of</th>
                    <th>Generic</th>
                    @role('warehouse')
                        <th>Cost</th>
                    @endrole
                    <th>Price</th>
                    <th>Stock Balance</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stk)
                    <tr class="hover"
                        onclick="update_item({{ $stk->id }}, '{{ $stk->drug_concat() }}', '{{ $stk->chrgcode }}', '{{ $stk->exp_date }}', '{{ $stk->stock_bal }}', '{{ $stk->dmduprice }}', '{{ $stk->has_compounding }}', '{{ $stk->compounding_fee }}')">
                        <th>{{ $stk->chrgdesc }}</th>
                        <td>{{ $stk->description }}</td>
                        <td>{{ $stk->updated_at }}</td>
                        <td class="font-bold">{{ $stk->drug_concat() }}</td>
                        @role('warehouse')
                            <th>{{ $stk->dmduprice }}</th>
                        @endrole
                        <td>{{ $stk->dmselprice }}</td>
                        <td>{{ $stk->stock_bal }}</td>
                        <td>{!! $stk->expiry() !!}</td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="10">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $stocks->links() }}
    </div>
</div>


@push('scripts')
    <script>
        function add_item() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Add Item </span>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="chrgcode">
                            <span class="label-text">Fund Source</span>
                        </label>
                        <select class="text-sm select select-bordered select-sm" id="chrgcode">
                            @foreach ($charge_codes as $charge)
                                <option value="{{ $charge->chrgcode }}">{{ $charge->chrgdesc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full px-2 form-control">
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
                    <div class="w-full px-2 form-control">
                        <label class="label" for="expiry_date">
                            <span class="label-text">Expiry Date</span>
                        </label>
                        <input id="expiry_date" type="date" value="{{ date('Y-m-d', strtotime(now() . '+1 years')) }}" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="qty">
                            <span class="label-text">Beginning Balance</span>
                        </label>
                        <input id="qty" type="number" value="1" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="unit_cost">
                            <span class="label-text">Unit Cost</span>
                        </label>
                        <input id="unit_cost" type="number" step="0.00" class="w-full input input-bordered" />
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
                    const unit_cost = Swal.getHtmlContainer().querySelector('#unit_cost');
                    const chrgcode = Swal.getHtmlContainer().querySelector('#chrgcode');
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
                    @this.set('unit_cost', unit_cost.value);
                    @this.set('chrgcode', chrgcode.value);
                    @this.set('has_compounding', has_compounding.checked);
                    @this.set('compounding_fee', compounding_fee.value);

                    Livewire.emit('add_item_new');
                }
            });
        }

        function update_item(stk_id, stk_drug_name, stk_chrgcode, stk_expiry_date, stk_balance, stk_cost,
            stk_has_compounding, stk_compounding_fee) {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Update Item ` + stk_drug_name + `</span>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="update_chrgcode">
                            <span class="label-text">Fund Source</span>
                        </label>
                        <select class="text-sm select select-bordered select-sm" id="update_chrgcode">
                            @foreach ($charge_codes as $charge)
                                <option value="{{ $charge->chrgcode }}">{{ $charge->chrgdesc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="update_expiry_date">
                            <span class="label-text">Expiry Date</span>
                        </label>
                        <input id="update_expiry_date" type="date" value="` + stk_expiry_date + `" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="update_qty">
                            <span class="label-text">Beginning Balance</span>
                        </label>
                        <input id="update_qty" type="number" value="1" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="update_unit_cost">
                            <span class="label-text">Unit Cost</span>
                        </label>
                        <input id="update_unit_cost" type="number" step="0.01" class="w-full input input-bordered" />
                    </div>
                    <div class="px-2 form-control">
                        <label class="flex mt-3 space-x-3 cursor-pointer">
                            <input type="checkbox" id="update_has_compounding" class="checkbox" />
                            <span class="mr-auto label-text !justify-self-start">Highly Specialised Drugs</span>
                        </label>
                    </div>
                    <div class="w-full px-2 form-control" hidden id="update_compounding_div">
                        <label class="label" for="update_compounding_fee">
                            <span class="label-text">Compounding fee</span>
                        </label>
                        <input id="update_compounding_fee" type="number" step="0.01" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const update_expiry_date = Swal.getHtmlContainer().querySelector('#update_expiry_date');
                    const update_qty = Swal.getHtmlContainer().querySelector('#update_qty');
                    const update_unit_cost = Swal.getHtmlContainer().querySelector('#update_unit_cost');
                    const update_chrgcode = Swal.getHtmlContainer().querySelector('#update_chrgcode');
                    const update_has_compounding = Swal.getHtmlContainer().querySelector(
                        '#update_has_compounding');
                    const update_compounding_div = Swal.getHtmlContainer().querySelector(
                        '#update_compounding_div');
                    const update_compounding_fee = Swal.getHtmlContainer().querySelector(
                        '#update_compounding_fee');

                    update_qty.value = stk_balance;
                    update_unit_cost.value = stk_cost;
                    update_chrgcode.value = stk_chrgcode;
                    update_has_compounding.value = stk_has_compounding;
                    update_compounding_fee.value = stk_compounding_fee;

                    update_compounding_div.style.display = 'none';

                    update_has_compounding.addEventListener('click', function handleClick() {
                        if (update_has_compounding.checked) {
                            update_compounding_div.style.display = 'block';
                        } else {
                            update_compounding_div.style.display = 'none';
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
                    @this.set('expiry_date', update_expiry_date.value);
                    @this.set('qty', update_qty.value);
                    @this.set('unit_cost', update_unit_cost.value);
                    @this.set('chrgcode', update_chrgcode.value);
                    @this.set('has_compounding', update_has_compounding.checked);
                    @this.set('compounding_fee', update_compounding_fee.value);

                    Livewire.emit('update_item_new', stk_id);
                }
            });
        }
    </script>
@endpush
