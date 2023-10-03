<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ Auth::user()->location->description }}
            </li>
            <li>
                <i class="mr-1 las la-exchange la-lg"></i> Issued IO Transactions
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col p-5 mx-auto">
    <div class="flex justify-between">
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
