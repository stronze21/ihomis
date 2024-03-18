{{-- <div class="text-sm navbar bg-base-100">
    <div class="navbar-start">
        <div class="dropdown">
            <label tabindex="0" class="btn btn-ghost lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                </svg>
            </label>
            <ul tabindex="0" class="p-2 mt-3 shadow menu menu-compact dropdown-content bg-base-100 rounded-box w-52">
                <li><a><i class="mr-1 las la-lg la-tachometer-alt"></i> Dashboard</a></li>
                <li tabindex="0">
                    <a class="justify-between">
                        Parent
                        <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24">
                            <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z" />
                        </svg>
                    </a>
                    <ul class="p-2">
                        <li><a>Submenu 1</a></li>
                        <li><a>Submenu 2</a></li>
                    </ul>
                </li>
                <li><a>Item 3</a></li>
            </ul>
        </div>
        <div class="hidden lg:flex">
            <a rel="noopener noreferrer" class="text-xl normal-case btn btn-ghost" href="{{ route('dashboard') }}">
                <x-jet-application-mark class="block w-auto h-9" />
            </a>
            <ul class="px-1 mr-auto menu menu-horizontal">
                <li class="mt-2 dropdown">
                    <x-jet-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        <i class="las la-lg la-tachometer-alt"></i> {{ __('Dashboard') }}
                    </x-jet-nav-link>
                </li>
                <li class="mt-2 dropdown">
                    <x-jet-nav-link href="{{ route('patients.list') }}" :active="request()->routeIs('patients.*')">
                        <i class="las la-lg la-user-alt"></i> {{ __('Patients') }}
                    </x-jet-nav-link>
                </li>
                <li tabindex="0" class="mt-2 dropdown">
                    <x-jet-nav-link :active="request()->routeIs('rx.*') || request()->routeIs('dispensing.rxo.pending')">
                        <i class="las la-lg la-file-prescription"></i> Rx/Orders
                        <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24">
                            <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                        </svg>
                    </x-jet-nav-link>
                    <ul
                        class="overflow-y-auto shadow-2xl dropdown-content bg-base-100 text-base-content rounded-t-box rounded-b-box">
                        <li><a href="{{ route('rx.opd') }}">OPD Rx</a></li>
                        <li><a href="{{ route('rx.ward') }}">Ward Rx</a></li>
                        <li><a href="{{ route('rx.er') }}">ER Rx</a></li>
                        <li>
                            <a href="{{ route('dispensing.rxo.pending') }}">Pending Orders</a>
                        </li>
                    </ul>
                </li>
                <li tabindex="0" class="mt-2 dropdown">
                    <x-jet-nav-link :active="request()->routeIs('dmd.stk') || request()->routeIs('iotrans.*')">
                        <i class="las la-lg la-pills"></i> Drugs & Meds
                        <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24">
                            <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                        </svg>
                    </x-jet-nav-link>
                    <ul
                        class="overflow-y-auto shadow-2xl dropdown-content bg-base-100 text-base-content rounded-t-box rounded-b-box">
                        <li><a href="{{ route('dmd.stk') }}">Stocks</a></li>
                        <li><a href="{{ route('iotrans.list') }}">IO Trans</a></li>
                        <li><a href="{{ route('iotrans.requests') }}">IO Trans Requests</a></li>
                        <li><a href="{{ route('dmd.stk.ris') }}">Ward RIS</a></li>
                    </ul>
                </li>
                <li tabindex="0" class="mt-2 dropdown">
                    <x-jet-nav-link :active="request()->routeIs('delivery.*')">
                        <i class="las la-lg la-truck-loading"></i> Purchases
                        <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24">
                            <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                        </svg>
                    </x-jet-nav-link>
                    <ul
                        class="overflow-y-auto shadow-2xl dropdown-content bg-base-100 text-base-content rounded-t-box rounded-b-box">
                        <li><a href="{{ route('delivery.list') }}">Deliveries</a></li>
                        <li><a href="{{ route('delivery.ep') }}">Emergency Purchase</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <div class="navbar-end">
        @can('view-reports')
            <!-- Reports Dropdown -->
            <div class="relative ml-3">
                <x-jet-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 border border-transparent rounded-md focus:outline-none">
                                <i class="las la-lg la-file-excel"></i>
                            </button>
                        </span>
                    </x-slot>

                    <x-slot name="content">

                        <x-jet-dropdown-link href="{{ route('reports.stkcrd') }}">
                            {{ __('Stock Card') }}
                        </x-jet-dropdown-link>
                        <x-jet-dropdown-link href="{{ route('reports.consumption') }}">
                            {{ __('Consumption Report') }}
                        </x-jet-dropdown-link>
                        <x-jet-dropdown-link href="{{ route('reports.consumption.depts') }}">
                            {{ __('Consumption Department') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.all') }}">
                            {{ __('Drug Issuance') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.total') }}">
                            {{ __('Total Drugs Issued') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.returns') }}">
                            {{ __('Returned Log') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.returns.summary') }}">
                            {{ __('Returned Log Summary') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.charges') }}">
                            {{ __('Summary of Charge Slip') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.consumption.wards') }}">
                            {{ __('Ward Consumption') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.iotrans.issued') }}">
                            {{ __('IO Trans Issued') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.iotrans.received') }}">
                            {{ __('IO Trans Received') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.delivery.sum') }}">
                            {{ __('Deliveries Summary') }}
                        </x-jet-dropdown-link>

                    </x-slot>
                </x-jet-dropdown>
            </div>
        @endcan
        @can('view-settings')
            <!-- Settings Dropdown -->
            <div class="relative ml-3">
                <x-jet-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 border border-transparent rounded-md focus:outline-none">
                                <i class="las la-lg la-cog"></i>
                            </button>
                        </span>
                    </x-slot>

                    <x-slot name="content">

                        <x-jet-dropdown-link href="{{ route('ref.location') }}">
                            {{ __('Location') }}
                        </x-jet-dropdown-link>

                        <div class="border-t border-gray-100"></div>

                        <x-jet-dropdown-link href="{{ route('ref.dmd') }}">
                            {{ __('Drugs and Meds (Homis)') }}
                        </x-jet-dropdown-link>

                        <x-jet-dropdown-link href="{{ route('ref.pndf') }}">
                            {{ __('PNDF Generics (Homis)') }}
                        </x-jet-dropdown-link>

                        <div class="border-t border-gray-100"></div>

                        <x-jet-dropdown-link href="{{ route('ref.permissions') }}">
                            {{ __('Permissions') }}
                        </x-jet-dropdown-link>


                        <x-jet-dropdown-link href="{{ route('ref.users') }}">
                            {{ __('Manage Users') }}
                        </x-jet-dropdown-link>

                        <div class="border-t border-gray-100"></div>

                        <x-jet-dropdown-link href="{{ route('ref.wards') }}">
                            {{ __('Manage RIS Wards') }}
                        </x-jet-dropdown-link>

                    </x-slot>
                </x-jet-dropdown>
            </div>
        @endcan
        <!-- Account Dropdown -->
        <div class="relative ml-3">
            <x-jet-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <span class="inline-flex rounded-md">
                        <button type="button"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 uppercase transition bg-white border border-transparent rounded-md hover:text-gray-700 focus:outline-none">
                            <i class="las la-lg la-user"></i>
                            @php
                                $words = preg_split('/[\s,_-]+/', session('user_name'));
                                $acronym = '';

                                foreach ($words as $w) {
                                    $acronym .= mb_substr($w, 0, 1);
                                }
                                echo $acronym;
                            @endphp

                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                </x-slot>

                <x-slot name="content">
                    <!-- Account Management -->
                    <div class="block px-4 py-2 text-xs text-gray-400">
                        {{ __('Manage Account') }}
                    </div>

                    <x-jet-dropdown-link href="{{ route('profile.show') }}">
                        {{ __('Profile') }}
                    </x-jet-dropdown-link>

                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                        <x-jet-dropdown-link href="{{ route('api-tokens.index') }}">
                            {{ __('API Tokens') }}
                        </x-jet-dropdown-link>
                    @endif

                    <div class="border-t border-gray-100"></div>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf

                        <x-jet-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                            {{ __('Log Out') }}
                        </x-jet-dropdown-link>
                    </form>
                </x-slot>
            </x-jet-dropdown>
        </div>
    </div>
</div> --}}

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="px-4 mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="flex items-center shrink-0">
                    <a rel="noopener noreferrer" href="{{ route('dashboard') }}">
                        <x-jet-application-mark class="block w-auto h-9" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:-my-px sm:ml-10 sm:flex">
                    <x-jet-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        <i class="mr-1 las la-lg la-tachometer-alt"></i> {{ __('Dashboard') }}
                    </x-jet-nav-link>
                    @can('view-patients')
                        <x-jet-nav-link class="ml-2" href="{{ route('patients.list') }}" :active="request()->routeIs('patients.*')">
                            <i class="mr-1 las la-lg la-user-alt"></i> {{ __('Patients') }}
                        </x-jet-nav-link>
                    @endcan
                    @can('view-prescriptions')
                        @if (session('pharm_location_name') == 'OPD Pharmacy')
                            <x-jet-nav-link class="ml-2" href="{{ route('rx.opd') }}" :active="request()->routeIs('rx.*')">
                                <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Prescriptions') }}
                            </x-jet-nav-link>
                        @else
                            <x-jet-nav-link class="ml-2" href="{{ route('rx.ward') }}" :active="request()->routeIs('rx.*')">
                                <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Prescriptions') }}
                            </x-jet-nav-link>
                        @endif
                    @endcan
                    @can('view-prescriptions')
                        <x-jet-nav-link class="ml-2" href="{{ route('dispensing.rxo.pending') }}" :active="request()->routeIs('dispensing.rxo.pending')">
                            <i class="mr-1 las la-lg la-pause-circle"></i> {{ __('Pending Orders') }}
                        </x-jet-nav-link>
                    @endcan
                    @can('view-stocks')
                        <x-jet-nav-link class="ml-2" href="{{ route('dmd.stk') }}" :active="request()->routeIs('dmd.stk')">
                            <i class="mr-1 las la-lg la-pills"></i> {{ __('Stocks') }}
                        </x-jet-nav-link>
                    @endcan
                    @can('view-iotrans')
                        <x-jet-nav-link class="ml-2" href="{{ route('iotrans.list') }}" :active="request()->routeIs('iotrans.list')">
                            <i class="mr-1 las la-lg la-exchange-alt"></i> {{ __('IO Trans') }}
                        </x-jet-nav-link>
                    @endcan
                    @can('view-iotrans-limited')
                        <x-jet-nav-link class="ml-2" href="{{ route('iotrans.requests') }}" :active="request()->routeIs('iotrans.requests')">
                            <i class="mr-1 las la-lg la-exchange-alt"></i> {{ __('IO Trans Requests') }}
                        </x-jet-nav-link>
                    @endcan
                    @can('view-deliveries')
                        <x-jet-nav-link class="ml-2" href="{{ route('delivery.list') }}" :active="request()->routeIs('delivery.list')">
                            <i class="mr-1 las la-lg la-truck-loading"></i> {{ __('Deliveries') }}
                        </x-jet-nav-link>
                    @endcan
                    @can('view-eps')
                        <x-jet-nav-link class="ml-2" href="{{ route('delivery.ep') }}" :active="request()->routeIs('delivery.ep')">
                            <i class="mr-1 las la-lg la-first-aid"></i> {{ __('Emergency Purchase') }}
                        </x-jet-nav-link>
                    @endcan
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                @can('view-reports')
                    <!-- Reports Dropdown -->
                    <div class="relative ml-3">
                        <x-jet-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <span class="inline-flex rounded-md">
                                    <button type="button"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 border border-transparent rounded-md focus:outline-none">
                                        <i class="las la-lg la-file-excel"></i>
                                    </button>
                                </span>
                            </x-slot>

                            <x-slot name="content">

                                <x-jet-dropdown-link href="{{ route('reports.stkcrd') }}">
                                    {{ __('Stock Card') }}
                                </x-jet-dropdown-link>
                                <x-jet-dropdown-link href="{{ route('reports.consumption') }}">
                                    {{ __('Consumption Report') }}
                                </x-jet-dropdown-link>
                                <x-jet-dropdown-link href="{{ route('reports.consumption.depts') }}">
                                    {{ __('Consumption Department') }}
                                </x-jet-dropdown-link>
                                <div class="border-t border-gray-100"></div>
                                <x-jet-dropdown-link href="{{ route('reports.issuance.all') }}">
                                    {{ __('Drug Issuance') }}
                                </x-jet-dropdown-link>
                                <div class="border-t border-gray-100"></div>
                                <x-jet-dropdown-link href="{{ route('reports.issuance.total') }}">
                                    {{ __('Total Drugs Issued') }}
                                </x-jet-dropdown-link>
                                <div class="border-t border-gray-100"></div>
                                <x-jet-dropdown-link href="{{ route('reports.issuance.returns') }}">
                                    {{ __('Returned Log') }}
                                </x-jet-dropdown-link>
                                <div class="border-t border-gray-100"></div>
                                <x-jet-dropdown-link href="{{ route('reports.issuance.returns.summary') }}">
                                    {{ __('Returned Log Summary') }}
                                </x-jet-dropdown-link>
                                <div class="border-t border-gray-100"></div>
                                <x-jet-dropdown-link href="{{ route('reports.issuance.charges') }}">
                                    {{ __('Summary of Charge Slip') }}
                                </x-jet-dropdown-link>
                                <div class="border-t border-gray-100"></div>
                                <x-jet-dropdown-link href="{{ route('reports.consumption.wards') }}">
                                    {{ __('Ward Consumption') }}
                                </x-jet-dropdown-link>
                                <div class="border-t border-gray-100"></div>
                                <x-jet-dropdown-link href="{{ route('reports.iotrans.issued') }}">
                                    {{ __('IO Trans Issued') }}
                                </x-jet-dropdown-link>
                                <div class="border-t border-gray-100"></div>
                                <x-jet-dropdown-link href="{{ route('reports.iotrans.received') }}">
                                    {{ __('IO Trans Received') }}
                                </x-jet-dropdown-link>
                                <div class="border-t border-gray-100"></div>
                                <x-jet-dropdown-link href="{{ route('reports.delivery.sum') }}">
                                    {{ __('Deliveries Summary') }}
                                </x-jet-dropdown-link>

                            </x-slot>
                        </x-jet-dropdown>
                    </div>
                @endcan
                @can('view-settings')
                    <!-- Settings Dropdown -->
                    <div class="relative ml-3">
                        <x-jet-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <span class="inline-flex rounded-md">
                                    <button type="button"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 border border-transparent rounded-md focus:outline-none">
                                        <i class="las la-lg la-cog"></i>
                                    </button>
                                </span>
                            </x-slot>

                            <x-slot name="content">

                                <x-jet-dropdown-link href="{{ route('ref.location') }}">
                                    {{ __('Location') }}
                                </x-jet-dropdown-link>

                                <div class="border-t border-gray-100"></div>

                                <x-jet-dropdown-link href="{{ route('ref.dmd') }}">
                                    {{ __('Drugs and Meds (Homis)') }}
                                </x-jet-dropdown-link>

                                <x-jet-dropdown-link href="{{ route('ref.pndf') }}">
                                    {{ __('PNDF Generics (Homis)') }}
                                </x-jet-dropdown-link>

                                <div class="border-t border-gray-100"></div>

                                <x-jet-dropdown-link href="{{ route('ref.permissions') }}">
                                    {{ __('Permissions') }}
                                </x-jet-dropdown-link>


                                <x-jet-dropdown-link href="{{ route('ref.users') }}">
                                    {{ __('Manage Users') }}
                                </x-jet-dropdown-link>

                                <div class="border-t border-gray-100"></div>

                                <x-jet-dropdown-link href="{{ route('ref.wards') }}">
                                    {{ __('Manage RIS Wards') }}
                                </x-jet-dropdown-link>

                            </x-slot>
                        </x-jet-dropdown>
                    </div>
                @endcan
                <!-- Account Dropdown -->
                <div class="relative ml-3">
                    <x-jet-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <span class="inline-flex rounded-md">
                                <button type="button"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 uppercase transition bg-white border border-transparent rounded-md hover:text-gray-700 focus:outline-none">
                                    <i class="las la-lg la-user"></i>
                                    @php
                                        $words = preg_split('/[\s,_-]+/', session('user_name'));
                                        $acronym = '';

                                        foreach ($words as $w) {
                                            $acronym .= mb_substr($w, 0, 1);
                                        }
                                        echo $acronym;
                                    @endphp

                                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </span>
                        </x-slot>

                        <x-slot name="content">
                            <!-- Account Management -->
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                {{ __('Manage Account') }}
                            </div>

                            <x-jet-dropdown-link href="{{ route('profile.show') }}">
                                {{ __('Profile') }}
                            </x-jet-dropdown-link>

                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                <x-jet-dropdown-link href="{{ route('api-tokens.index') }}">
                                    {{ __('API Tokens') }}
                                </x-jet-dropdown-link>
                            @endif

                            <div class="border-t border-gray-100"></div>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf

                                <x-jet-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                                    {{ __('Log Out') }}
                                </x-jet-dropdown-link>
                            </form>
                        </x-slot>
                    </x-jet-dropdown>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center -mr-2 sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 text-gray-400 transition rounded-md hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500">
                    <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-jet-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-jet-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex items-center px-4">
                <div>
                    <div class="text-base font-medium text-gray-800">{{ session('user_name') }}</div>
                    <div class="text-sm font-medium text-gray-500">{{ session('user_email') }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Account Management -->
                <x-jet-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                    {{ __('Profile') }}
                </x-jet-responsive-nav-link>

                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                    <x-jet-responsive-nav-link href="{{ route('api-tokens.index') }}" :active="request()->routeIs('api-tokens.index')">
                        {{ __('API Tokens') }}
                    </x-jet-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf

                    <x-jet-responsive-nav-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                        {{ __('Log Out') }}
                    </x-jet-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
