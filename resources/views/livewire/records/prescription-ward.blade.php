<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ Auth::user()->location->description }}
            </li>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> Prescriptions
            </li>
            <li>
                <i class="mr-1 las la-prescription-alt la-lg"></i> Wards
            </li>
        </ul>
    </div>
    <div class="flex justify-center">
        <x-jet-nav-link class="ml-2" href="{{ route('rx.ward') }}" :active="request()->routeIs('rx.ward')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Wards') }}
        </x-jet-nav-link>
        <x-jet-nav-link class="ml-2" href="{{ route('rx.opd') }}" :active="request()->routeIs('rx.opd')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Out Patient Department') }}
        </x-jet-nav-link>
        <x-jet-nav-link class="ml-2" href="{{ route('rx.er') }}" :active="request()->routeIs('rx.er')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Emergency Room') }}
        </x-jet-nav-link>
    </div>
</x-slot>


<div class="flex flex-col py-5 mx-auto max-w-7xl">
    <div class="flex justify-between space-x-8">
        <div class="flex space-x-8">
            <div class="form-control">
                <select id="filter_wardcode" class="w-full select select-bordered select-sm">
                    <option value="All">All</option>
                    @foreach ($wards as $ward)
                        <option value="{{ $ward->slug_desc() }}">{{ $ward->wardname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-control">
                <input type="text" placeholder="Patient Name" class="w-full input input-sm input-bordered"
                    id="patient_name" />
            </div>
        </div>
        <div class="btn-group">
            <button class="btn btn-sm tooltip {{ $is_basic ? 'btn-primary' : '' }}" data-tip="BASIC"
                wire:click="toggle_basic">
                <i class="las la-2g la-prescription"></i>
            </button>
            <button class="btn btn-sm tooltip {{ $is_g24 ? 'btn-primary' : '' }}" data-tip="Good For 24 Hrs"
                wire:click="toggle_g24">
                <i class="las la-2g la-hourglass-start"></i>
            </button>
            <button class="btn btn-sm tooltip {{ $is_or ? 'btn-primary' : '' }}" data-tip="For Operating Use"
                wire:click="toggle_or">
                <i class="las la-2g la-syringe"></i>
            </button>
        </div>
    </div>
    <div class="flex flex-col justify-center w-full mt-3 overflow-x-auto">
        <div>
            <span wire:loading>
                <i class="las la-spinner la-lg animate-spin"></i>
                Processing...
            </span>
        </div>
        <div wire:loading.class="hidden">
            <table class="table w-full mb-3 table-compact">
                <thead>
                    <tr>
                        <th>Date Admitted</th>
                        <th>Patient Name</th>
                        <th>Department</th>
                        <th>Order Type</th>
                    </tr>
                </thead>
                <tbody id="admittedTable">
                    @forelse ($prescriptions as $rx)
                        <tr wire:key="view-enctr-{{ $rx->enccode }}-{{ $loop->iteration }}"
                            class="cursor-pointer hover clickable-row content {{ $rx->adm_pat_room->ward->slug_desc() }}"
                            data-href="{{ route('dispensing.view.enctr', ['enccode' => Crypt::encrypt(str_replace(' ', '-', $rx->enccode))]) }}">
                            <td>
                                <div class="flex-col">
                                    <div>{{ $rx->active_adm->disdate_format1() }}</div>
                                    <div>{{ $rx->active_adm->distime_format1() }}</div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex-col">
                                    <div>{{ $rx->active_adm->patient->fullname() }}</div>
                                    <div class="text-sm"><span
                                            class="badge badge-ghost badge-sm">{{ $rx->active_adm->hpercode }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex-col">
                                    <div>{{ $rx->adm_pat_room->ward->wardname }}</div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $basic = $rx->active_basic->count();
                                    $g24 = $rx->active_g24->count();
                                    $or = $rx->active_or->count();
                                @endphp
                                <ul class="text-sm rounded-md menu menu-horizontal bg-base-200">
                                    @if ($basic)
                                        <li>
                                            <div class="tooltip" data-tip="BASIC"><i
                                                    class="las la-2g la-prescription"></i>
                                                <div class="badge badge-accent badge-xs">{{ $basic }}</div>
                                            </div>
                                        </li>
                                    @endif
                                    @if ($g24)
                                        <li>
                                            <div class="tooltip" data-tip="Good For 24 Hrs"><i
                                                    class="las la-2g la-hourglass-start"></i>
                                                <div class="badge badge-error badge-xs">{{ $g24 }}</div>
                                            </div>
                                        </li>
                                    @endif
                                    @if ($or)
                                        <li>
                                            <div class="tooltip" data-tip="For Operating Use"><i
                                                    class="las la-2g la-syringe"></i>
                                                <div class="badge badge-secondary badge-xs">{{ $or }}</div>
                                            </div>
                                        </li>
                                    @endif
                                </ul>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No record found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{-- <div class="mt-2">
                {{ $prescriptions->links() }}
            </div> --}}
        </div>
    </div>
</div>
@push('scripts')
    <script async>
        $(document).ready(function() {

            $("#patient_name").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                var filter_wardcode = $('#filter_wardcode').val();
                var wards = [
                    @foreach ($wards as $filt_ward)
                        '{{ $filt_ward->slug_desc() }}',
                    @endforeach
                ];

                var ward_index = wards.indexOf(filter_wardcode);
                var x = wards.splice(ward_index, 1);

                $("#admittedTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });

                if (filter_wardcode === 'All') {
                    wards = [];
                }

                $.each(wards, function(index, value_row_2) {
                    $('.' + value_row_2).hide();
                });
            });

            $('#filter_wardcode').on('change', function() {
                var value = $('#patient_name').val().toLowerCase();
                var filter_wardcode = $('#filter_wardcode').val();
                var wards = [
                    @foreach ($wards as $filt_ward)
                        '{{ $filt_ward->slug_desc() }}',
                    @endforeach
                ];

                var ward_index = wards.indexOf(filter_wardcode);
                var x = wards.splice(ward_index, 1);

                $("#admittedTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });

                if (filter_wardcode === 'All') {
                    wards = [];
                }

                $.each(wards, function(index, value_row_2) {
                    $('.' + value_row_2).hide();
                });
            });
        });

        $('.select2').select2({
            width: 'resolve',
            placeholder: 'Filter by ward',
        });


        $(document).ready(function($) {
            $(".clickable-row").click(function() {
                window.location = $(this).data("href");
            });
        });
    </script>
@endpush
