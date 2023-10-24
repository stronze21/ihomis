<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
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


<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    <div class="flex space-x-8 justify-even">
        <div class="form-control">
            <label for="filter_wardcode">
                <span class="label-text">Ward</span>
            </label>
            <select id="filter_wardcode" class="w-full select select-bordered select-sm">
                <option value="All">All</option>
                @foreach ($wards as $ward)
                    <option value="{{ $ward->slug_desc() }}">{{ $ward->wardname }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full max-w-xs form-control">
            <label for="filter_type">
                <span class="label-text">Rx Tag</span>
            </label>
            <select id="filter_type" class="w-full select select-bordered select-sm">
                <option value="All">All</option>
                <option value="has-basic"><i class="las la-2g la-prescription"></i> Basic</option>
                <option value="has-g24"><i class="las la-2g la-hourglass-start"></i> G24</option>
                <option value="has-or"><i class="las la-2g la-syringe"></i> OR</option>
            </select>
        </div>
        <div class="w-full max-w-xs form-control ">
            <label for="patient_name">
                <span class="label-text">Patient</span>
            </label>
            <input type="text" placeholder="Patient Name" class="w-full input input-sm input-bordered"
                id="patient_name" />
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
            <table class="table w-full mb-3 table-compact" wire:ignore>
                <thead>
                    <tr>
                        <th>Date Admitted</th>
                        <th>Patient Name</th>
                        <th>Department</th>
                        <th>Rx Tag</th>
                    </tr>
                </thead>
                <tbody id="admittedTable">
                    @forelse ($prescriptions as $rx)
                        <tr wire:key="view-enctr-{{ $rx->enccode }}-{{ $loop->iteration }}"
                            class="cursor-pointer hover clickable-row content {{ Illuminate\Support\Str::slug($rx->wardname, '-') }}@if ($rx->basic) has-basic @elseif ($rx->g24) has-g24 @elseif ($rx->or) has-or @else has-none @endif"
                            data-href="{{ route('dispensing.view.enctr', ['enccode' => Crypt::encrypt(str_replace(' ', '-', $rx->enccode))]) }}">
                            <td>
                                <div class="flex-col">
                                    <div>{{ Carbon\Carbon::parse($rx->admdate)->format('Y/m/d') }}</div>
                                    <div>{{ Carbon\Carbon::parse($rx->admdate)->format('g:i A') }}</div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex-col">
                                    <div>
                                        {{ $rx->patlast . ', ' . $rx->patfirst . ' ' . $rx->patsuffix . ' ' . $rx->patmiddle }}
                                    </div>
                                    <div class="text-sm"><span
                                            class="badge badge-ghost badge-sm">{{ $rx->hpercode }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex-col">
                                    <div>{{ $rx->wardname }}</div>
                                    <div>{{ $rx->rmname }}</div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $basic = $rx->basic;
                                    $g24 = $rx->g24;
                                    $or = $rx->or;
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
                var filter_type = $('#filter_type').val();
                var wards = [
                    @foreach ($wards as $filt_ward)
                        '{{ $filt_ward->slug_desc() }}',
                    @endforeach
                ];
                var type = $('#filter_type').val().toLowerCase();

                var types = [
                    'has-basic',
                    'has-g24',
                    'has-or',
                    'has-none',
                ];

                var ward_index = wards.indexOf(filter_wardcode);
                var x = wards.splice(ward_index, 1);

                var type_index = types.indexOf(type);
                var y = types.splice(type_index, 1);

                $("#admittedTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });

                if (filter_wardcode === 'All') {
                    wards = [];
                } else {
                    $.each(wards, function(index, value_row_2) {
                        $('.' + value_row_2).hide();
                    });
                }

                if (filter_type === 'All') {
                    types = [];
                } else {
                    $.each(types, function(index_type, value_type) {
                        $('.' + value_type).hide();
                    });
                }
            });

            $('#filter_wardcode').on('change', function() {
                var value = $('#patient_name').val().toLowerCase();
                var filter_wardcode = $('#filter_wardcode').val();
                var filter_type = $('#filter_type').val();
                var wards = [
                    @foreach ($wards as $filt_ward)
                        '{{ $filt_ward->slug_desc() }}',
                    @endforeach
                ];
                var type = $('#filter_type').val().toLowerCase();

                var types = [
                    'has-basic',
                    'has-g24',
                    'has-or',
                    'has-none',
                ];

                var ward_index = wards.indexOf(filter_wardcode);
                var x = wards.splice(ward_index, 1);

                var type_index = types.indexOf(type);
                var y = types.splice(type_index, 1);

                $("#admittedTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });

                if (filter_wardcode === 'All') {
                    wards = [];
                } else {
                    $.each(wards, function(index, value_row_2) {
                        $('.' + value_row_2).hide();
                    });
                }

                if (filter_type === 'All') {
                    types = [];
                } else {
                    $.each(types, function(index_type, value_type) {
                        $('.' + value_type).hide();
                    });
                }


            });

            $('#filter_type').on('change', function() {
                var value = $('#patient_name').val().toLowerCase();
                var filter_wardcode = $('#filter_wardcode').val();
                var filter_type = $('#filter_type').val();
                var wards = [
                    @foreach ($wards as $filt_ward)
                        '{{ $filt_ward->slug_desc() }}',
                    @endforeach
                ];
                var type = $('#filter_type').val().toLowerCase();

                var types = [
                    'has-basic',
                    'has-g24',
                    'has-or',
                    'has-none',
                ];

                var ward_index = wards.indexOf(filter_wardcode);
                var x = wards.splice(ward_index, 1);

                var type_index = types.indexOf(type);
                var y = types.splice(type_index, 1);

                $("#admittedTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });

                if (filter_wardcode === 'All') {
                    wards = [];
                } else {
                    $.each(wards, function(index, value_row_2) {
                        $('.' + value_row_2).hide();
                    });
                }

                if (filter_type === 'All') {
                    types = [];
                } else {
                    $.each(types, function(index_type, value_type) {
                        $('.' + value_type).hide();
                    });
                }
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
