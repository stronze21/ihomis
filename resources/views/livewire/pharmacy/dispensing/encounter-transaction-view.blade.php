<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
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
                        <div><button class="ml-2 btn btn-sm btn-error" onclick="delete_item()"
                                wire:loading.attr="disabled" wire:loading.class="btn-secondary">Delete Item/s</button>
                        </div>
                        <div><button class="ml-2 btn btn-sm btn-warning" onclick="charge_items()"
                                wire:loading.attr="disabled" wire:loading.class="btn-secondary">Charge Slip</button>
                        </div>
                        <div><button class="ml-2 btn btn-sm btn-primary" onclick="issue_order()"
                                wire:loading.attr="disabled" wire:loading.class="btn-secondary">Issue</button></div>
                    </div>
                </div>
                <table class="w-full mb-40 text-sm table-compact">
                    <thead class="sticky font-bold bg-gray-200" wire:ignore>
                        <tr>
                            <td colspan="4" class="w-1/3 border border-black"><span>Hospital #: </span> <span
                                    class="fw-bold">{{ $patient->hpercode }}</span></td>
                            <td colspan="7" class="w-2/3 border border-black">
                                <span>Diagnosis: </span>
                                <div class="text-xs font-light">
                                    <p class="break-words">{{ $encounter->diag->diagtext ?? 'N/A' }}</p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="w-1/3 border border-black"><span>Last Name: </span> <span
                                    class="fw-bold">{{ $patient->patlast }}</span></td>
                            <td colspan="5" class="w-1/3 border border-black"><span>First Name: </span> <span
                                    class="fw-bold">{{ $patient->patfirst }}</span></td>
                            <td colspan="3" class="w-1/3 border border-black"><span>Middle Name: </span> <span
                                    class="fw-bold">{{ $patient->patmiddle }}</span></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="w-1/3 border border-black">
                                <span class="fw-bold">
                                    <div class="flex space-x-2">
                                        <span>Room/Encounter Type: </span>
                                        @if ($encounter->toecode == 'ADM' or $encounter->toecode == 'OPDAD' or $encounter->toecode == 'ERADM')
                                            <div> {{ $wardname->wardname }}</div>
                                            <div class="text-sm">{{ $rmname->rmname }} /
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
                        <tr>
                            <td colspan="11" class="border border-black">Patient Classification:
                                @php
                                    $class = '---';
                                    if ($mss) {
                                        switch ($mss->mssikey) {
                                            case 'MSSA11111999':
                                            case 'MSSB11111999':
                                                $class = 'Pay';
                                                break;

                                            case 'MSSC111111999':
                                                $class = 'PP1';
                                                break;

                                            case 'MSSC211111999':
                                                $class = 'PP2';
                                                break;

                                            case 'MSSC311111999':
                                                $class = 'PP3';
                                                break;

                                            case 'MSSD11111999':
                                                $class = 'Indigent';
                                                break;

                                            default:
                                                $class = '---';
                                        }
                                    }
                                @endphp
                                <span class="uppercase">{{ $class }}</span>
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
                                <span id="sum"></span>
                                {{-- {{ number_format($encounter->rxo->sum('pcchrgamt'), 2) }}</td> --}}
                            </td>
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
                        @forelse ($rxos as $rxo)
                            @php
                                $concat = explode('_,', $rxo->drug_concat);
                                $drug = implode('', $concat);
                            @endphp
                            <tr class="border">
                                <td class="w-10 text-center">
                                    <input type="checkbox"
                                        class="checkbox{{ '-' . ($rxo->pcchrgcod ?? $loop->iteration) }}"
                                        wire:model.defer="selected_items" value="{{ $rxo->docointkey }}" />
                                </td>
                                <td class="whitespace-nowrap w-min" title="View Charge Slip">
                                    @if ($rxo->pcchrgcod)
                                        <a rel="noopener noreferrer" class="font-semibold text-blue-600"
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
                                        <div class="text-xs text-slate-600">{{ $rxo->chrgdesc ?? '' }}</div>
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
                                <td class="text-right w-min total">{{ number_format($rxo->pcchrgamt, 2) }}</td>
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
                                @php
                                    if ($rxo->estatus == 'U') {
                                        $badge = '<span class="badge badge-sm badge-warning">Pending</span>';
                                    } elseif ($rxo->estatus == 'P') {
                                        $badge = '<span class="badge badge-sm badge-secondary">Charged</span>';
                                    } elseif ($rxo->estatus == 'S') {
                                        $badge = '<span class="badge badge-sm badge-success">Issued</span>';
                                    }
                                @endphp
                                <td class="text-center w-min">{!! $badge !!}</td>
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
                        id="generic" wire:model.lazy="generic" />
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
                        @foreach ($stocks as $key => $stock)
                            @php
                                $concat = explode('_,', $stock->drug_concat);
                                $drug = implode('', $concat);
                            @endphp
                            <tr class="cursor-pointer hover content {{ $stock->chrgcode }}"
                                onclick="select_item('{{ $stock->id }}', '{{ $drug }}', '{{ $stock->dmselprice }}', '{{ $stock->dmdcomb }}', '{{ $stock->dmdctr }}', '{{ $stock->chrgcode }}', '{{ $stock->loc_code }}', '{{ $stock->dmdprdte }}', '{{ $stock->id }}', {{ $stock->stock_bal }}, '{{ $stock->exp_date }}')">
                                <td class="break-words">
                                    <div>
                                        <span class="text-xs text-slate-600">{{ $stock->chrgdesc }}</span>

                                        @if (Carbon\Carbon::parse($stock->exp_date)->diffInDays(now(), false) >= 1 && $stock->stock_bal > 0)
                                            <span class="badge badge-sm badge-danger">^
                                                {{ Carbon\Carbon::create($stock->exp_date)->format('F j, Y') }}</span>
                                        @elseif (Carbon\Carbon::parse($stock->exp_date)->diffInDays(now(), false) > -168 && $stock->stock_bal > 0)
                                            <span class="badge badge-sm badge-warning">^
                                                {{ Carbon\Carbon::create($stock->exp_date)->format('F j, Y') }}</span>
                                        @elseif ($stock->stock_bal < 1)
                                            <span class="badge badge-sm badge-ghost">^
                                                {{ Carbon\Carbon::create($stock->exp_date)->format('F j, Y') }}</span>
                                        @elseif (Carbon\Carbon::parse($stock->exp_date)->diffInDays(now(), false) <= -168)
                                            <span class="badge badge-sm badge-success">^
                                                {{ Carbon\Carbon::create($stock->exp_date)->format('F j, Y') }}</span>
                                        @endif

                                        <div class="text-sm font-bold text-slate-800">
                                            {{ $concat[0] }}</div>
                                        <div class="text-xs text-center text-slate-800">
                                            {{ $concat[1] }}</div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="flex flex-col">
                                        <div class="ml-5 font-bold">
                                            {{ number_format($stock->stock_bal ?? 0, 0) }}
                                        </div>
                                        <div>{!! '&#8369; ' . $stock->dmselprice !!}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
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
                        @forelse($active_prescription as $presc)
                            @forelse($presc->data_active->all() as $presc_data)
                                <tr class="cursor-pointer hover" {{-- wire:click.prefetch="$set('generic', '{{ $presc_data->dm->generic->gendesc }}')" --}} {{-- wire:click.prefetch="add_item({{ $presc_data->dm->generic->gendesc }})" --}}
                                    onclick="select_rx_item({{ $presc_data->id }}, '{{ $presc_data->dm->drug_concat() }}', '{{ $presc_data->qty }}', '{{ $presc->empid }}', '{{ $presc_data->dmdcomb }}', '{{ $presc_data->dmdctr }}')"
                                    wire:key="select-rx-item-{{ $loop->iteration }}">
                                    <td class="text-xs">
                                        {{ date('Y-m-d', strtotime($presc_data->created_at)) }}
                                        {{ date('h:i A', strtotime($presc_data->created_at)) }}
                                    </td>
                                    <td class="text-xs">{{ $presc_data->dm->drug_concat() }}</td>
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
        var data;
        $(document).ready(function() {
            $("#generic").trigger("change");
            $('input:checkbox').change(function() {
                if ($(this).is(':checked')) {
                    $('.' + this.className).prop("checked", true);
                }
            });

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

            $("#generic").on("change", function() {
                if (@this.rx_id) {
                    @this.rx_id = null;
                    @this.empid = null;
                }
            })

            $('#filter_charge_code').on('change', function() {
                var value = $("#generic").val().toLowerCase();
                var value_select = $('#filter_charge_code').select2('val');

                data = $('#filter_charge_code').select2('data');

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

            grand_total();
        });

        function grand_total() {
            var sum = 0;
            $(".total").each(function() {
                sum += parseFloat($(this).text().replace(',', '').replace(',', ''));
            });
            $('#sum').text(number_format(sum, 2, '.', ','));
        }

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

        function delete_item(item_id) {
            Swal.fire({
                title: 'Are you sure?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                html: `
                <i data-feather="x-circle" class="w-16 h-16 mx-auto mt-3 text-error"></i>
                <div class="mt-2 text-slate-500" id="inf">You can only delete pending items. All deleted items cannot be recovered. Continue?</div>
            `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('delete_item')
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


        @if ($encounter->toecode == 'OPD')
            function select_item(dm_id, drug, up, dmdcomb, dmdctr, chrgcode, loc_code, dmdprdte, id, available, exp_date) {
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
                                <input class="toggle toggle-success" type="radio" id="na" name="radio" checked>
                                <label class="cursor-pointer" for="na">
                                    <span class="label-text">PAY</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="ems" name="radio">
                                <label class="cursor-pointer" for="ems">
                                    <span class="label-text">EMS</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="konsulta" name="radio">
                                <label class="cursor-pointer" for="konsulta">
                                    <span class="label-text">Konsulta Package</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="wholesale" name="radio">
                                <label class="cursor-pointer" for="wholesale">
                                    <span class="label-text">WHOLESALE</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="caf" name="radio">
                                <label class="cursor-pointer" for="caf">
                                    <span class="label-text">CAF</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="maip" name="radio">
                                <label class="cursor-pointer" for="maip">
                                    <span class="label-text">MAIP</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="is_ris" name="radio">
                                <label class="cursor-pointer" for="is_ris">
                                    <span class="label-text">RIS</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="pcso" name="radio">
                                <label class="cursor-pointer" for="pcso">
                                    <span class="label-text">PCSO</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="phic" name="radio">
                                <label class="cursor-pointer" for="phic">
                                    <span class="label-text">PHIC</span>
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
                        const ems = Swal.getHtmlContainer().querySelector('#ems')
                        const maip = Swal.getHtmlContainer().querySelector('#maip')
                        const wholesale = Swal.getHtmlContainer().querySelector('#wholesale')
                        const caf = Swal.getHtmlContainer().querySelector('#caf')
                        const is_ris = Swal.getHtmlContainer().querySelector('#is_ris')
                        const remarks = Swal.getHtmlContainer().querySelector('#remarks')
                        const konsulta = Swal.getHtmlContainer().querySelector('#konsulta')
                        const pcso = Swal.getHtmlContainer().querySelector('#pcso')
                        const phic = Swal.getHtmlContainer().querySelector('#phic')

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
                    }
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                        @this.set('unit_price', unit_price.value)
                        @this.set('order_qty', order_qty.value)

                        @this.set('ems', ems.checked);
                        @this.set('maip', maip.checked);
                        @this.set('wholesale', wholesale.checked);
                        @this.set('konsulta', konsulta.checked);
                        @this.set('pcso', pcso.checked);
                        @this.set('phic', phic.checked);
                        @this.set('caf', caf.checked);
                        @this.set('is_ris', is_ris.checked);
                        @this.set('remarks', remarks.value);

                        Livewire.emit('add_item', dmdcomb, dmdctr, chrgcode, loc_code, dmdprdte, id,
                            available, exp_date)
                    }
                });
            }

            function select_rx_item(rx_id, drug, rx_qty, empid, rx_dmdcomb, rx_dmdctr) {

                var search = drug.split(",");
                $("#generic").val(search[0]);
                $("#generic").trigger('keyup');
                @this.rx_id = rx_id;
                @this.rx_dmdcomb = rx_dmdcomb;
                @this.rx_dmdctr = rx_dmdctr;
                @this.empid = empid;

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
                            <div class="col-span-4 font-bold">Fund Source</div>
                            <div class="col-span-4">
                            <select id="rx_charge_code" class="w-full select select-bordered select-sm">
                            </select>
                            </div>
                            <div class="col-span-4 font-bold">TAG</div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="rx_na" name="radio" checked>
                                <label class="cursor-pointer" for="na">
                                    <span class="label-text">PAY</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="rx_ems" name="radio">
                                <label class="cursor-pointer" for="ems">
                                    <span class="label-text">EMS</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="rx_konsulta" name="radio">
                                <label class="cursor-pointer" for="konsulta">
                                    <span class="label-text">Konsulta Package</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="rx_wholesale" name="radio">
                                <label class="cursor-pointer" for="wholesale">
                                    <span class="label-text">WHOLESALE</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="rx_caf" name="radio">
                                <label class="cursor-pointer" for="caf">
                                    <span class="label-text">CAF</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="rx_maip" name="radio">
                                <label class="cursor-pointer" for="maip">
                                    <span class="label-text">MAIP</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="rx_is_ris" name="radio">
                                <label class="cursor-pointer" for="is_ris">
                                    <span class="label-text">RIS</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="rx_pcso" name="radio">
                                <label class="cursor-pointer" for="pcso">
                                    <span class="label-text">PCSO</span>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <input class="toggle toggle-success" type="radio" id="rx_phic" name="radio">
                                <label class="cursor-pointer" for="phic">
                                    <span class="label-text">PHIC</span>
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
                        const rx_charge_code = Swal.getHtmlContainer().querySelector('#rx_charge_code')
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

                        $.each(data, function(index, value) {
                            if (index == 0) {
                                rx_charge_code.options[rx_charge_code.options.length] = new Option(
                                    value[
                                        'text'], value['id'], true, true);
                            } else {
                                rx_charge_code.options[rx_charge_code.options.length] = new Option(
                                    value[
                                        'text'], value['id']);
                            }
                        });
                        rx_order_qty.focus();
                        rx_order_qty.value = rx_qty;

                    }
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                        @this.set('order_qty', rx_order_qty.value)

                        @this.set('rx_charge_code', rx_charge_code.value);
                        @this.set('ems', rx_ems.checked);
                        @this.set('maip', rx_maip.checked);
                        @this.set('wholesale', rx_wholesale.checked);
                        @this.set('konsulta', rx_konsulta.checked);
                        @this.set('pcso', rx_pcso.checked);
                        @this.set('phic', rx_phic.checked);
                        @this.set('caf', rx_caf.checked);
                        @this.set('is_ris', rx_is_ris.checked);
                        @this.set('remarks', rx_remarks.value);

                        Livewire.emit('add_prescribed_item', rx_dmdcomb, rx_dmdctr);
                    }
                });
            }
        @else
            function select_item(dm_id, drug, up, dmdcomb, dmdctr, chrgcode, loc_code, dmdprdte, id, available, exp_date) {
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

                        order_qty.focus();
                        unit_price.value = up;
                        total.value = parseFloat(order_qty.value) * parseFloat(unit_price.value)

                        order_qty.addEventListener('input', () => {
                            total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                                .value)
                        })

                        unit_price.addEventListener('input', () => {
                            total.value = parseFloat(order_qty.value) * parseFloat(unit_price
                                .value)
                        })
                    }
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                        @this.set('unit_price', unit_price.value)
                        @this.set('order_qty', order_qty.value)
                        @this.set('remarks', remarks.value);

                        Livewire.emit('add_item', dmdcomb, dmdctr, chrgcode, loc_code, dmdprdte, id,
                            available, exp_date)
                    }
                });
            }

            function select_rx_item(rx_id, drug, rx_qty, empid, rx_dmdcomb, rx_dmdctr) {

                var search = drug.split(",");
                $("#generic").val(search[0]);
                $("#generic").trigger('keyup');
                @this.rx_id = rx_id;
                @this.rx_dmdcomb = rx_dmdcomb;
                @this.rx_dmdctr = rx_dmdctr;
                @this.empid = empid;

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
                            <div class="col-span-4 font-bold">Fund Source</div>
                            <div class="col-span-4">
                                <select id="rx_charge_code" class="w-full select select-bordered select-sm">
                                </select>
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
                        const rx_charge_code = Swal.getHtmlContainer().querySelector('#rx_charge_code')
                        const rx_remarks = Swal.getHtmlContainer().querySelector('#rx_remarks')

                        $.each(data, function(index, value) {
                            if (index == 0) {
                                rx_charge_code.options[rx_charge_code.options.length] = new Option(
                                    value[
                                        'text'], value['id'], true, true);
                            } else {
                                rx_charge_code.options[rx_charge_code.options.length] = new Option(
                                    value[
                                        'text'], value['id']);
                            }
                        });
                        rx_order_qty.focus();
                        rx_order_qty.value = rx_qty;

                    }
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                        @this.set('order_qty', rx_order_qty.value)
                        @this.set('rx_charge_code', rx_charge_code.value);
                        @this.set('remarks', rx_remarks.value);

                        Livewire.emit('add_prescribed_item', rx_dmdcomb, rx_dmdctr);
                    }
                });
            }
        @endif

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

        window.addEventListener('charged', event => {
            window.open('{{ url('/dispensing/encounter/charge') }}' + '/' +
                event.detail.pcchrgcod, '_blank');
        });
    </script>
@endpush
