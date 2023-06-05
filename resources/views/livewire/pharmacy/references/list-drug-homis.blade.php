<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{Auth::user()->location->description}}
            </li>
            <li class="font-bold">
                <i class="mr-1 las la-cog la-lg"></i>Settings
            </li>
            <li>
                <i class="mr-1 las la-map-marker la-lg"></i> Homis Drugs and Medicine
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col py-5 mx-auto max-w-7xl">
    <div class="flex justify-between">
        <div>
        </div>
        <div>
            <div class="form-control">
                <label class="input-group input-group-sm">
                    <span><i class="las la-search"></i></span>
                    <input type="text" placeholder="Search" class="input input-bordered input-sm" wire:model.lazy="search" />
                </label>
              </div>
        </div>
    </div>
    <div class="flex flex-col justify-center w-full mt-2 overflow-x-auto">
        <table class="table w-full table-compact table-zebra table-bordered">
            <thead>
                <tr>
                    <th>Combination</th>
                    <th>Counter</th>
                    <th>Generic</th>
                    <th>Strength</th>
                    <th>Form</th>
                    <th>Route</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($drugs as $drug)
                <tr>
                    <th>{{$drug->dmdcomb}}</th>
                    <th>{{$drug->dmdctr}}</th>
                    <td>{{$drug->generic->gendesc}}</td>
                    <td>{{$drug->dmdnost.' '.$drug->strength->stredesc}}</td>
                    <td>{{$drug->form->formdesc}}</td>
                    <td>{{$drug->route->rtedesc}}</td>
                </tr>
                @empty
                <tr>
                    <th class="text-center" colspan="3">No record found!</th>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{$drugs->links()}}
      </div>
</div>
