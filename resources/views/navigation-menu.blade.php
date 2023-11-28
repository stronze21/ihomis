<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="px-4 mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="flex items-center shrink-0">
                    <a  rel="noopener noreferrer" href="{{ route('dashboard') }}">
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
                        <x-jet-nav-link class="ml-2" href="{{ route('rx.ward') }}" :active="request()->routeIs('rx.*')">
                            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Prescriptions') }}
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
                                        Reports
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
                                <x-jet-dropdown-link href="{{ route('reports.issuance.returns') }}">
                                    {{ __('Returned Log') }}
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
                                {{-- <x-jet-dropdown-link href="{{ route('reports.issuance.log') }}">
                                {{ __('Transaction Log') }}
                            </x-jet-dropdown-link> --}}

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
                                        Settings
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

                                <x-jet-dropdown-link href="{{ route('ref.location') }}">
                                    {{ __('Location') }}
                                </x-jet-dropdown-link>

                                <div class="border-t border-gray-100"></div>

                                <x-jet-dropdown-link href="{{ route('ref.dmd') }}">
                                    {{ __('Drugs and Meds (Homis)') }}
                                </x-jet-dropdown-link>

                                <div class="border-t border-gray-100"></div>

                                <x-jet-dropdown-link href="{{ route('ref.permissions') }}">
                                    {{ __('Permissions') }}
                                </x-jet-dropdown-link>

                                <div class="border-t border-gray-100"></div>

                                <x-jet-dropdown-link href="{{ route('ref.users') }}">
                                    {{ __('Manage Users') }}
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
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition bg-white border border-transparent rounded-md hover:text-gray-700 focus:outline-none">
                                    <i class="las la-lg la-user"></i>
                                    {{ session('user_name') }}

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
