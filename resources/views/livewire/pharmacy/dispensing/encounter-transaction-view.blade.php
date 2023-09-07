<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ Auth::user()->location->description }}
            </li>
            <li class="font-bold">
                <i class="mr-1 las la-truck la-lg"></i> Drugs and Medicine Dispensing
            </li>
            <li>
                <i class="mr-1 las la-file-prescription la-lg"></i> {{ $encounter->enccode }}
            </li>
        </ul>
    </div>
</x-slot>

<div class="p-3">
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 xl:col-span-8">
            <div class="flex flex-col max-h-screen p-1 overflow-scroll">
                <div class="flex justify-between my-3">
                    @if ($errors->first())
                        <div class="shadow-lg max-w-fit alert alert-error">
                            <i class="mr-2 las la-lg la-exclamation-triangle"></i> {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="flex ml-auto">
                        <div><button class="ml-2 btn btn-sm btn-warning" onclick="charge_items()"
                                wire:loading.attr="disabled" wire:loading.class="btn-secondary">Charge Slip</button>
                        </div>
                        <div><button class="ml-2 btn btn-sm btn-primary" onclick="issue_order()"
                                wire:loading.attr="disabled" wire:loading.class="btn-secondary">Issue</button></div>
                        {{-- <div><button class="ml-2 btn btn-sm btn-danger" wire:click="reset_order()" wire:loading.attr="disabled" wire:loading.class="btn-secondary">Reset Order</button></div> --}}
                        {{-- @if (auth()->user()->employeeid == '001783') --}}
                        {{-- @endif --}}
                    </div>
                </div>
                <table class="w-full mb-40 text-sm table-compact">
                    <thead class="sticky font-bold bg-gray-200">
                        <tr>
                            <td colspan="4" class="w-1/3 border border-black"><span>Hospital #: </span> <span
                                    class="fw-bold">{{ $encounter->patient->hpercode }}</span></td>
                            <td colspan="7" class="w-2/3 border border-black">
                                <span>Diagnosis: </span>
                                <div class="text-xs font-light">
                                    <p class="break-words">{{ $encounter->diag->diagtext ?? 'N/A' }}</p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="w-1/3 border border-black"><span>Last Name: </span> <span
                                    class="fw-bold">{{ $encounter->patient->patlast }}</span></td>
                            <td colspan="5" class="w-1/3 border border-black"><span>First Name: </span> <span
                                    class="fw-bold">{{ $encounter->patient->patfirst }}</span></td>
                            <td colspan="3" class="w-1/3 border border-black"><span>Middle Name: </span> <span
                                    class="fw-bold">{{ $encounter->patient->patmiddle }}</span></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="w-1/3 border border-black">
                                <span class="fw-bold">
                                    <div class="flex space-x-2">
                                        <span>Room/Encounter Type: </span>
                                        @if ($encounter->toecode == 'ADM' or $encounter->toecode == 'OPDAD' or $encounter->toecode == 'ERADM')
                                            <div> {{ $encounter->adm->patient_room->ward->wardname }}</div>
                                            <div class="text-sm">{{ $encounter->adm->patient_room->room->rmname }} /
                                            </div>
                                        @endif
                                        {{ $encounter->toecode }}
                                    </div>
                                </span>
                            </td>
                            <td colspan="6" class="border border-black"><span>Encounter Date/Time: </span> <span
                                    class="fw-bold">{{ \Carbon\Carbon::create($encounter->encdate)->format('F j, Y / g:i A') }}</span>
                            </td>
                        </tr>
                        <tr class="border border-black">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td colspan="3" class="text-right uppercase">Grand Total:
                                {{ number_format($encounter->rxo->sum('pcchrgamt', 2)) }}</td>
                        </tr>
                        <tr class="border border-black">
                            <td class="text-center w-min"></td>
                            <td class="whitespace-nowrap w-min">Charge Slip</td>
                            <td class="whitespace-nowrap w-min">Date of Order</td>
                            <td class="w-max whitespace-nowrap">Description</td>
                            <td class="w-20 text-right">
                                <div class="tooltip" data-tip="Quantity Ordered">Q.O.</div>
                            </td>
                            <td class="w-20 text-right">
                                <div class="tooltip" data-tip="Quantity Issued">Q.I.</div>
                            </td>
                            <td class="text-right w-min">Price</td>
                            <td class="text-right w-min">Total</td>
                            <td>Remarks</td>
                            <td class="text-center w-min">Status</td>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse ($encounter->rxo->all() as $rxo)
                            @php
                                $concat = explode('_,', $rxo->dm->drug_concat);
                            @endphp
                            <tr class="border">
                                <td class="w-10 text-center">
                                    <input type="checkbox" class="checkbox" wire:model.defer="selected_items"
                                        value="{{ $rxo->docointkey }}" />
                                </td>
                                <td class="whitespace-nowrap w-min" title="View Charge Slip">
                                    @if ($rxo->pcchrgcod)
                                        <a class="font-semibold text-blue-600"
                                            href="{{ route('dispensing.rxo.chargeslip', $rxo->pcchrgcod) }}"
                                            target="_blank">{{ $rxo->pcchrgcod }}</a>
                                    @endif
                                </td>
                                <td class="align-center whitespace-nowrap w-min">
                                    <div class="flex flex-col">
                                        <div>{{ date('m/d/Y', strtotime($rxo->dodate)) }}</div>
                                        <div>{{ date('h:i A', strtotime($rxo->dodate)) }}</div>
                                    </div>
                                </td>
                                <td class="w-max">
                                    <div class="flex flex-col">
                                        <div class="text-xs text-slate-600">{{ $rxo->charge->chrgdesc ?? '' }}</div>
                                        <div class="font-bold">{{ $concat[0] }}</div>
                                        <div class="text-xs text-center text-slate-800">
                                            {{ $concat[1] }}</div>
                                    </div>
                                </td>
                                <td class="w-20 text-right">{{ number_format($rxo->pchrgqty) }}</td>
                                <td class="w-20 text-right whitespace-nowrap">
                                    @if ($rxo->estatus == 'S' and $rxo->qtyissued > 0)
                                        <span class="cursor-pointer tooltip" data-tip="Return"
                                            onclick="return_issued('{{ $rxo->docointkey }}', '{{ $concat[0] }} <br>{{ $concat[1] }}', {{ $rxo->pchrgup }}, {{ $rxo->qtyissued }})">
                                            <i class="text-red-600 las la-lg la-undo-alt"></i>
                                            {{ number_format($rxo->qtyissued) }}
                                        </span>
                                    @else
                                        {{ number_format($rxo->qtyissued) }}
                                    @endif
                                </td>
                                <td class="text-right w-min">{{ number_format($rxo->pchrgup, 2) }}</td>
                                <td class="text-right w-min">{{ number_format($rxo->pcchrgamt, 2) }}</td>
                                <td>
                                    <div class="form-control">
                                        <label class="input-group">
                                            @if ($selected_remarks == $rxo->docointkey)
                                                <input type="text" class="input input-bordered"
                                                    value="{{ $rxo->remarks }}" wire:model.lazy="new_remarks"
                                                    wire:key="rem-input-{{ $rxo->docointkey }}" />
                                                <button class="btn-primary btn btn-square"
                                                    wire:click="update_remarks()"
                                                    wire:key="update-rem-{{ $rxo->docointkey }}">
                                                    <i class="las la-lg la-save"></i>
                                                </button>
                                            @else
                                                <input type="text" class="input input-bordered"
                                                    value="{{ $rxo->remarks }}" disabled />
                                                <button class="btn btn-square"
                                                    wire:click="$set('selected_remarks', '{{ $rxo->docointkey }}')"
                                                    wire:key="set-rem-id-{{ $rxo->docointkey }}">
                                                    <i class="las la-lg la-edit"></i>
                                                </button>
                                            @endif

                                        </label>
                                    </div>
                                </td>
                                <td class="text-center w-min">{!! $rxo->status() !!}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12">EMPTY</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-span-12 xl:col-span-4">
            <div class="flex flex-col space-y-1">
                <div class="w-full" wire:ignore>
                    <select id="filter_charge_code" class="w-full select select-bordered select-sm select2" multiple>
                        {{-- wire:model="charge_code"> --}}
                        @foreach ($charges as $charge)
                            <option value="{{ $charge->chrgcode }}">{{ $charge->chrgdesc }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full">
                    <input type="text" placeholder="Type here" class="w-full input input-sm input-bordered"
                        id="generic" />
                </div>
            </div>
            <div class="mt-2 overflow-x-hidden overflow-y-auto max-h-96">
                <table class="table w-full table-fixed table-compact" id="stockTable">
                    <thead class="sticky top-0 border-b ">
                        <tr>
                            <td>Description</td>
                            <td class="!text-right">Stock And Price</td>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">
                        @forelse($stocks as $stock)
                            @php
                                $concat = explode('_,', $stock->drug_concat);
                            @endphp
                            <tr class="cursor-pointer hover content {{ $stock->chrgcode }}"
                                onclick="select_item('{{ $stock->id }}', '{{ $stock->drug_concat }}', '{{ $stock->current_price->dmselprice }}')">
                                <td class="break-words">
                                    <div>
                                        <span class="text-xs text-slate-600">{{ $stock->charge->chrgdesc }}</span>
                                        {{-- <span class="font-bold">{{ $stock->drug->generic->gendesc }}</span> --}}
                                        <div class="text-sm font-bold text-slate-800">
                                            {{ $concat[0] }}</div>
                                        <div class="text-xs text-center text-slate-800">
                                            {{ $concat[1] }}</div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="flex flex-col">
                                        <div class="ml-5 font-bold">{{ $stock->balance() }}</div>
                                        <div>{!! '&#8369; ' . $stock->current_price->dmselprice !!}</div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2"><i class="las la-lg la-ban"></i> No record found!</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 overflow-auto max-h-96">
                <table class="w-full rounded-lg shadow-md table-compact">
                    <thead class="sticky top-0 bg-gray-200 border-b">
                        <tr>
                            <td class="text-xs">Order at</td>
                            <td class="text-xs">Description</td>
                            <td class="text-xs">QTY</td>
                            <td class="text-xs">Remarks</td>
                            <td class="text-xs">Prescribed by</td>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse($encounter->active_prescription->all() as $presc)
                            @forelse($presc->data_active->all() as $presc_data)
                                <tr class="cursor-pointer hover" {{-- wire:click.prefetch="$set('generic', '{{ $presc_data->dm->generic->gendesc }}')" --}} {{-- wire:click.prefetch="add_item({{ $presc_data->dm->generic->gendesc }})" --}}
                                    onclick="select_rx_item({{ $presc_data->id }}, '{{ $presc_data->dm->drug_concat }}', '{{ $presc_data->qty }}')"
                                    wire:key="select-rx-item-{{ $loop->iteration }}">
                                    <td class="text-xs">
                                        {{ date('Y-m-d', strtotime($presc_data->created_at)) }}
                                        {{ date('h:i A', strtotime($presc_data->created_at)) }}
                                    </td>
                                    <td class="text-xs">{{ $presc_data->dm->drug_concat }}</td>
                                    <td class="text-xs">{{ $presc_data->qty }}</td>
                                    <td class="text-xs">{{ $presc_data->remark }}</td>
                                    <td class="text-xs">{{ $presc->employee->fullname() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"><i class="las la-lg la-ban"></i> No record found!</td>
                                </tr>
                            @endforelse
                        @empty
                            <tr>
                                <td colspan="5"><i class="las la-lg la-ban"></i> No record found!</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        $(document).ready(function() {
            $("#generic").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                var value_select = $('#filter_charge_code').select2('val');

                var myArray = ['DRUMA', 'DRUMAA', 'DRUMAB', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMR',
                    'DRUMS'
                ];
                $.each(value_select, function(index, value_row) {
                    const myArray_index = myArray.indexOf(value_row);
                    const x = myArray.splice(myArray_index, 1);
                });

                $("#stockTableBody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });

                if (value_select.length === 0) {
                    myArray = [];
                }

                $.each(myArray, function(index, value_row_2) {
                    $('.' + value_row_2).hide();
                });
            });

            $('#filter_charge_code').on('change', function() {
                var value = $("#generic").val().toLowerCase();
                var value_select = $('#filter_charge_code').select2('val');

                var myArray = ['DRUMA', 'DRUMAA', 'DRUMAB', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMR',
                    'DRUMS'
                ];

                $.each(value_select, function(index, value_row) {
                    const myArray_index = myArray.indexOf(value_row);
                    const x = myArray.splice(myArray_index, 1);
                });

                $("#stockTableBody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });

                if (value_select.length === 0) {
                    myArray = [];
                }

                $.each(myArray, function(index, value_row_2) {
                    $('.' + value_row_2).hide();
                });
            });
        });

        $('.select2').select2({
            width: 'resolve',
            placeholder: 'Fund Source',
        });

        // $('#filter_charge_code').on('change', function() {
        //     @this.set('charge_code', $('#filter_charge_code').select2('val'));
        // });

        function charge_items() {
            Swal.fire({
                title: 'Are you sure?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                html: `
                        <i data-feather="x-circle" class="w-16 h-16 mx-auto mt-3 text-danger"></i>
                        <div class="mt-2 text-slate-500" id="inf">Create charge slip for all pending items. Continue?</div>
                    `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('charge_items')
                }
            })
        }

        function issue_order() {
            Swal.fire({
                title: 'Are you sure?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                html: `
                        <i data-feather="x-circle" class="w-16 h-16 mx-auto mt-3 text-danger"></i>
                        <div class="mt-2 text-slate-500" id="inf">Issue all chared items. Continue?</div>
                    `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('issue_order')
                }
            })
        }

        function select_item(dm_id, drug, up) {
            Swal.fire({
                html: `
                        <div class="text-xl font-bold">` + drug + `</div>
                        <div class="flex w-full space-x-3">
                            <div class="w-full mb-3 form-control">
                                <label class="label">
                                    <span class="label-text">Quantity</span>
                                </label>
                                <input id="order_qty" type="number" value="1" class="box-border w-64 h-32 p-4 text-7xl input input-bordered" />
                            </div>
                            <div class="w-full">
                                <div class="w-full form-control">
                                    <label class="label">
                                        <span class="label-text">Unit Price</span>
                                    </label>
                                    <input id="unit_price" type="number" step="0.01" class="w-full input input-bordered" />
                                </div>

                                <div class="w-full mb-3 form-control">
                                    <label class="label">
                                        <span class="label-text">TOTAL</span>
                                    </label>
                                    <input id="total" type="number" step="0.01" class="w-full input input-bordered" readonly tabindex="-1" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-2 px-2 text-left gap-y-2">
                            <div class="col-span-4 font-bold">TAG</div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="pay" name="radio" checked>
                                <label class="cursor-pointer" for="pay">
                                    <span class="label-text">PAY</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="sc" name="radio">
                                <label class="cursor-pointer" for="sc">
                                    <span class="label-text">SC/PWD</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="ems" name="radio">
                                <label class="cursor-pointer" for="ems">
                                    <span class="label-text">EMS</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="maip" name="radio">
                                <label class="cursor-pointer" for="maip">
                                    <span class="label-text">MAIP</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="wholesale" name="radio">
                                <label class="cursor-pointer" for="wholesale">
                                    <span class="label-text">WHOLESALE</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="medicare" name="radio">
                                <label class="cursor-pointer" for="medicare">
                                    <span class="label-text">MEDICARE</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="service" name="radio">
                                <label class="cursor-pointer" for="service">
                                    <span class="label-text">SERVICE</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="caf" name="radio">
                                <label class="cursor-pointer" for="caf">
                                    <span class="label-text">CAF</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="govt" name="radio">
                                <label class="cursor-pointer" for="govt">
                                    <span class="label-text">Gov't Emp</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="checkbox" id="is_ris" name="is_ris">
                                <label class="cursor-pointer" for="is_ris">
                                    <span class="label-text">RIS</span>
                                </label>
                            </div>
                        </div>
                        <div class="px-2 mt-2">
                            <textarea id="remarks" class="w-full textarea textarea-bordered" placeholder="Remarks"></textarea>
                        </div>
                            `,
                showCancelButton: true,
                confirmButtonText: `Confirm`,
                didOpen: () => {
                    const order_qty = Swal.getHtmlContainer().querySelector('#order_qty')
                    const unit_price = Swal.getHtmlContainer().querySelector('#unit_price')
                    const total = Swal.getHtmlContainer().querySelector('#total')
                    const sc = Swal.getHtmlContainer().querySelector('#sc')
                    const ems = Swal.getHtmlContainer().querySelector('#ems')
                    const maip = Swal.getHtmlContainer().querySelector('#maip')
                    const wholesale = Swal.getHtmlContainer().querySelector('#wholesale')
                    const pay = Swal.getHtmlContainer().querySelector('#pay')
                    const medicare = Swal.getHtmlContainer().querySelector('#medicare')
                    const service = Swal.getHtmlContainer().querySelector('#service')
                    const caf = Swal.getHtmlContainer().querySelector('#caf')
                    const govt = Swal.getHtmlContainer().querySelector('#govt')
                    const is_ris = Swal.getHtmlContainer().querySelector('#is_ris')
                    const remarks = Swal.getHtmlContainer().querySelector('#remarks')

                    order_qty.focus();
                    unit_price.value = up;
                    total.value = parseFloat(order_qty.value) * parseFloat(unit_price.value)

                    order_qty.addEventListener('input', () => {
                        if (sc.checked) {
                            unit_price.value = unit_price.value - (unit_price.value * 0.20);
                        }
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })

                    unit_price.addEventListener('input', () => {
                        if (sc.checked) {
                            unit_price.value = unit_price.value - (unit_price.value * 0.20);
                        }
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })
                    ems.addEventListener('change', () => {
                        unit_price.value = up;
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })
                    maip.addEventListener('change', () => {
                        unit_price.value = up;
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })
                    wholesale.addEventListener('change', () => {
                        unit_price.value = up;
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })
                    pay.addEventListener('change', () => {
                        unit_price.value = up;
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })
                    medicare.addEventListener('change', () => {
                        unit_price.value = up;
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })
                    service.addEventListener('change', () => {
                        unit_price.value = up;
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })
                    caf.addEventListener('change', () => {
                        unit_price.value = up;
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })
                    govt.addEventListener('change', () => {
                        unit_price.value = up;
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)
                    })

                    sc.addEventListener('change', () => {
                        // @this.set('unit_price',unit_price.value)
                        if (sc.checked) {
                            unit_price.value = unit_price.value - (unit_price.value * 0.20);
                        } else {
                            unit_price.value = up;
                        }
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                            .value)

                    })
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('unit_price', unit_price.value)
                    @this.set('order_qty', order_qty.value)

                    @this.set('sc', sc.checked);
                    @this.set('ems', ems.checked);
                    @this.set('maip', maip.checked);
                    @this.set('wholesale', wholesale.checked);
                    @this.set('pay', pay.checked);
                    @this.set('medicare', medicare.checked);
                    @this.set('service', service.checked);
                    @this.set('caf', caf.checked);
                    @this.set('govt', govt.checked);
                    @this.set('is_ris', is_ris.checked);
                    @this.set('remarks', remarks.value);

                    Livewire.emit('add_item', dm_id)
                }
            });
        }

        function return_issued(docointkey, drug, up, or_qty) {
            Swal.fire({
                html: `
                        <div class="text-xl font-bold">` + drug + `</div>

                        <div class="w-full px-2 mb-3 form-control">
                            <label class="label">
                                <span class="label-text">Issued Qty</span>
                            </label>
                            <input id="order_qty" type="number" value="1" class="w-full input input-bordered disabled bg-slate-200" readonly tabindex='-1' />
                        </div>

                        <div class="w-full px-2 mb-3 form-control">
                            <label class="label">
                                <span class="label-text">Return Qty</span>
                            </label>
                            <input id="return_qty" type="number" max="` + or_qty + `" class="w-full input input-bordered" autofocus/>
                        </div>

                        <div class="w-full px-2 mb-3 form-control">
                            <label class="label">
                                <span class="label-text">Unit Price</span>
                            </label>
                            <input id="unit_price" type="number" step="0.01" class="w-full input input-bordered disabled bg-slate-200" readonly tabindex='-1' />
                        </div>

                        <div class="w-full px-2 mb-3 form-control">
                            <label class="label">
                                <span class="label-text">TOTAL</span>
                            </label>
                            <input id="total" type="number" step="0.01" class="w-full input input-bordered disabled bg-slate-200" readonly tabindex='-1' />
                        </div>
                            `,
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: `Confirm`,
                didOpen: () => {
                    const order_qty = Swal.getHtmlContainer().querySelector('#order_qty');
                    const return_qty = Swal.getHtmlContainer().querySelector('#return_qty');
                    const unit_price = Swal.getHtmlContainer().querySelector('#unit_price');
                    const total = Swal.getHtmlContainer().querySelector('#total');
                    order_qty.value = or_qty;
                    unit_price.value = up;
                    return_qty.focus();

                    return_qty.addEventListener('input', () => {
                        total.value = parseFloat(return_qty.value) * parseFloat(unit_price
                            .value);
                    })

                    unit_price.addEventListener('input', () => {
                        total.value = parseFloat(return_qty.value) * parseFloat(unit_price
                            .value);
                    })
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('unit_price', unit_price.value);
                    @this.set('order_qty', or_qty);
                    @this.set('docointkey', docointkey);
                    @this.set('return_qty', return_qty.value);

                    Livewire.emit('return_issued', docointkey);
                }
            });
        }

        function select_rx_item(rx_id, drug, rx_qty) {
            Swal.fire({
                html: `
                        <div class="text-xl font-bold">` + drug + `</div>
                        <div class="flex w-full space-x-3">
                            <div class="w-full mb-3 form-control">
                                <label class="label">
                                    <span class="label-text">Quantity</span>
                                </label>
                                <input id="rx_order_qty" type="number" value="1" class="box-border w-64 h-32 p-4 text-7xl input input-bordered" />
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-2 px-2 text-left gap-y-2">
                            <div class="col-span-4 font-bold">TAG</div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="rx_pay" name="radio" checked>
                                <label class="cursor-pointer" for="pay">
                                    <span class="label-text">PAY</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="rx_sc" name="radio">
                                <label class="cursor-pointer" for="sc">
                                    <span class="label-text">SC/PWD</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="rx_ems" name="radio">
                                <label class="cursor-pointer" for="ems">
                                    <span class="label-text">EMS</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="rx_maip" name="radio">
                                <label class="cursor-pointer" for="maip">
                                    <span class="label-text">MAIP</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="rx_wholesale" name="radio">
                                <label class="cursor-pointer" for="wholesale">
                                    <span class="label-text">WHOLESALE</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="rx_medicare" name="radio">
                                <label class="cursor-pointer" for="medicare">
                                    <span class="label-text">MEDICARE</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="rx_service" name="radio">
                                <label class="cursor-pointer" for="service">
                                    <span class="label-text">SERVICE</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="rx_caf" name="radio">
                                <label class="cursor-pointer" for="caf">
                                    <span class="label-text">CAF</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="radio" id="rx_govt" name="radio">
                                <label class="cursor-pointer" for="govt">
                                    <span class="label-text">Gov't Emp</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle" type="checkbox" id="rx_is_ris" name="is_ris">
                                <label class="cursor-pointer" for="is_ris">
                                    <span class="label-text">RIS</span>
                                </label>
                            </div>
                        </div>
                        <div class="px-2 mt-2">
                            <textarea id="rx_remarks" class="w-full textarea textarea-bordered" placeholder="Remarks"></textarea>
                        </div>
                    `,
                showCancelButton: true,
                confirmButtonText: `Confirm`,
                didOpen: () => {
                    const rx_order_qty = Swal.getHtmlContainer().querySelector('#rx_order_qty')
                    const rx_sc = Swal.getHtmlContainer().querySelector('#rx_sc')
                    const rx_ems = Swal.getHtmlContainer().querySelector('#rx_ems')
                    const rx_maip = Swal.getHtmlContainer().querySelector('#rx_maip')
                    const rx_wholesale = Swal.getHtmlContainer().querySelector('#rx_wholesale')
                    const rx_pay = Swal.getHtmlContainer().querySelector('#rx_pay')
                    const rx_medicare = Swal.getHtmlContainer().querySelector('#rx_medicare')
                    const rx_service = Swal.getHtmlContainer().querySelector('#rx_service')
                    const rx_caf = Swal.getHtmlContainer().querySelector('#rx_caf')
                    const rx_govt = Swal.getHtmlContainer().querySelector('#rx_govt')
                    const rx_is_ris = Swal.getHtmlContainer().querySelector('#rx_is_ris')
                    const rx_remarks = Swal.getHtmlContainer().querySelector('#rx_remarks')

                    rx_order_qty.focus();
                    rx_order_qty.value = rx_qty;

                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('order_qty', rx_order_qty.value)

                    @this.set('sc', rx_sc.checked);
                    @this.set('ems', rx_ems.checked);
                    @this.set('maip', rx_maip.checked);
                    @this.set('wholesale', rx_wholesale.checked);
                    @this.set('pay', rx_pay.checked);
                    @this.set('medicare', rx_medicare.checked);
                    @this.set('service', rx_service.checked);
                    @this.set('caf', rx_caf.checked);
                    @this.set('govt', rx_govt.checked);
                    @this.set('is_ris', rx_is_ris.checked);
                    @this.set('remarks', rx_remarks.value);

                    Livewire.emit('add_prescribed_item', rx_id)
                }
            });
        }
    </script>
@endpush
