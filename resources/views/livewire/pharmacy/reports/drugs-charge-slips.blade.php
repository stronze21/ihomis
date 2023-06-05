<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{Auth::user()->location->description}}
            </li>
            <li>
                <i class="mr-1 las la-file-excel la-lg"></i> Report
            </li>
            <li>
                <i class="mr-1 las la-file-invoice la-lg"></i> Charge Slips Summary
            </li>
        </ul>
    </div>
</x-slot>

<div class="max-w-screen">
    <div class="flex flex-col px-2 py-5 overflow-auto">
        <div class="flex justify-between my-2">
            <div class="flex justify-between">
            </div>
            <div class="flex justify-end">
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>Location</span>
                            <select class="text-sm select select-bordered select-sm" wire:model="location_id">
                                  <option value="">N/A</option>
                              @foreach ($locations as $loc)
                                  <option value="{{$loc->id}}">{{$loc->description}}</option>
                              @endforeach
                            </select>
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>From</span>
                            <input type="datetime-local" class="w-full input input-sm input-bordered" max="{{$date_to}}" wire:model.lazy="date_from" />
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>To</span>
                            <input type="datetime-local" class="w-full input input-sm input-bordered" min="{{$date_from}}" wire:model.lazy="date_to" />
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <table class="table bg-white shadow-md table-fixed table-compact">
            <thead class="font-bold bg-gray-200">
                <tr>
                    <td class="text-sm uppercase border">#</td>
                    <td class="text-sm border">Date Ordered</td>
                    <td class="text-sm border">Hosp. #</td>
                    <td class="text-sm border">Name of Patient</td>
                    <td class="text-sm border">Charge Slip</td>
                    <td class="text-sm text-right border">Total Items</td>
                    <td class="text-sm text-right border">Amount</td>
                </tr>
            </thead>
            <tbody>
                @forelse ($drugs_ordered as $rxo)
                <tr classs="border border-black">
                    <td class="text-sm text-right border">{{$loop->iteration}}</td>
                    <td class="text-sm border">{{date('F j, Y H:i A' ,strtotime($rxo->dodate))}}</td>
                    <td class="text-sm border">{{$rxo->hpercode}}</td>
                    <td class="text-sm border">{{$rxo->patient->fullname()}}</td>
                    <td class="text-sm border">{{$rxo->pcchrgcod}}</td>
                    <td class="text-sm text-right border">{{$rxo->total_qty}}</td>
                    <td class="text-sm text-right border">{{$rxo->total_amount}}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="22" class="font-bold text-center uppercase bg-red-400 border border-black">No record found!</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-2">
            {{$drugs_ordered->links()}}
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
