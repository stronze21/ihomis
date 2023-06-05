<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{Auth::user()->location->description}}
            </li>
            <li>
                <i class="mr-1 las la-user-alt la-lg"></i> Patients
            </li>
        </ul>
    </div>
</x-slot>


<div class="flex flex-col py-5 mx-auto max-w-7xl">
    <div class="flex flex-col justify-between">
        <div class="py-3 mt-3">
            <div class="flex">
                <div class="form-control">
                    <label class="input-group input-group-sm">
                        <span class="text-sm whitespace-nowrap">Hospital #</span>
                        <input type="text" placeholder="Search" class="input input-bordered input-sm" wire:model.lazy="searchhpercode" />
                    </label>
                </div>
                <div class="ml-3 form-control">
                    <label class="input-group input-group-sm">
                        <span class="text-sm whitespace-nowrap">First Name</span>
                        <input type="text" placeholder="Search" class="input input-bordered input-sm" wire:model.lazy="searchpatfirst" />
                    </label>
                </div>
                <div class="ml-3 form-control">
                    <label class="input-group input-group-sm">
                        <span class="text-sm whitespace-nowrap">Middle Name</span>
                        <input type="text" placeholder="Search" class="input input-bordered input-sm" wire:model.lazy="searchpatmiddle" />
                    </label>
                </div>
                <div class="ml-3 form-control">
                    <label class="input-group input-group-sm">
                        <span class="text-sm whitespace-nowrap">Last Name</span>
                        <input type="text" placeholder="Search" class="input input-bordered input-sm" wire:model.lazy="searchpatlast" />
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-col justify-center w-full mt-3 overflow-x-auto">
        <table class="table w-full mb-3 table-compact">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient Name</th>
                    <th>Sex</th>
                    <th>Birth Date</th>
                    <th>Age</th>
                    <th>Birth Place</th>
                    <th>Civil Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($patients as $patient)
                    <tr wire:key="select-patient-{{$patient->hpercode}}-{{$loop->iteration}}" wire:click="select_patient('{{$patient->hpercode}}')" style="cursor: pointer">
                        <td>{{$patient->hpercode}}</td>
                        <td>{{$patient->fullname()}}</td>
                        <td>{{$patient->patsex}}</td>
                        <td>{{$patient->bdate_format1()}}</td>
                        <td>{{$patient->age()}}</td>
                        <td>{{$patient->patbplace ?? '...'}}</td>
                        <td>{{$patient->csstat()}}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No record found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-2">
            {{$patients->links()}}
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
