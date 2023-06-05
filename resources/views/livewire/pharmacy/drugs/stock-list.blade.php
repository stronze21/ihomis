<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{Auth::user()->location->description}}
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
                <button class="btn btn-sm btn-primary" onclick="add_item()" wire:loading.attr="disabled">Add Item</button>
            </div>
            @can('filter-stocks-location')
            <div class="ml-3 form-control">
                <label class="label">
                    <span class="label-text">Current Location</span>
                </label>
                <select class="w-full max-w-xs text-sm select select-bordered select-sm select-success" wire:model="location_id">
                  @foreach ($locations as $loc)
                      <option value="{{$loc->id}}">{{$loc->description}}</option>
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
                    <input type="text" placeholder="Search" class="input input-bordered input-sm" wire:model.lazy="search" />
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
                <tr class="hover">
                    <th>{{$stk->charge->chrgdesc}}</th>
                    <td>{{$stk->location->description}}</td>
                    <td>{{$stk->updated_at}}</td>
                    <td class="font-bold">{{$stk->drug->drug_name()}}</td>
                    @role('warehouse')
                    <th>{{$stk->current_price->dmduprice}}</th>
                    @endrole
                    <td>{{$stk->current_price->dmselprice}}</td>
                    <td>{{$stk->balance()}}</td>
                    <td>{!!$stk->expiry()!!}</td>
                </tr>
                @empty
                <tr>
                    <th class="text-center" colspan="10">No record found!</th>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{$stocks->links()}}
      </div>
</div>


@push('scripts')
    <script>
    function add_item()
    {
        Swal.fire({
            html: `
                    <span class="text-xl font-bold"> Add Item </span>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="chrgcode">
                            <span class="label-text">Fund Source</span>
                        </label>
                        <select class="text-sm select select-bordered select-sm" id="chrgcode">
                            @foreach ($charge_codes as $charge)
                                <option value="{{$charge->chrgcode}}">{{$charge->chrgdesc}}</option>
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
                                <option value="{{$drug->dmdcomb}},{{$drug->dmdctr}}">{{$drug->generic->gendesc}} {{$drug->dmdnost}} {{$drug->strength->stredesc ?? $drug->strecode}} {{$drug->form->formdesc ?? $drug->formcode}} {{$drug->route->rtedesc}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="expiry_date">
                            <span class="label-text">Expiry Date</span>
                        </label>
                        <input id="expiry_date" type="date" value="{{date('Y-m-d', strtotime(now().'+1 years'))}}" class="w-full input input-bordered" />
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
                        <input id="unit_cost" type="number" step="0.01" class="w-full input input-bordered" />
                    </div>`,
            showCancelButton: true,
            confirmButtonText: `Save`,
            didOpen: () => {
                const dmdcomb = Swal.getHtmlContainer().querySelector('#dmdcomb');
                const expiry_date = Swal.getHtmlContainer().querySelector('#expiry_date');
                const qty = Swal.getHtmlContainer().querySelector('#qty');
                const unit_cost = Swal.getHtmlContainer().querySelector('#unit_cost');
                const chrgcode = Swal.getHtmlContainer().querySelector('#chrgcode');

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

                Livewire.emit('add_item');
            }
        });
    }
    </script>
@endpush
