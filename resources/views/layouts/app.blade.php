<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="emerald">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="apple-touch-icon" href="{{ asset('logo.png') }}?v={{ config('app.version') }}">
        <link rel="icon" href="{{ asset('logo.png') }}?v={{ config('app.version') }}">

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
        <link rel="stylesheet" href="{{mix('css/app.css')}}">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

        <!-- Styles -->
        @livewireStyles

        <!-- Scripts -->
    </head>
    <body class="max-h-screen overflow-auto font-sans antialiased ">
        <x-jet-banner />

        <div class="min-h-screen bg-gray-100">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="px-4 py-2 mx-auto sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts
        <x-livewire-alert::scripts />
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
        <script src="{{mix('js/app.js')}}"></script>
        @stack('scripts')

        <script>
            // Pusher.logToConsole = true;

            Echo.private(`ioTrans.{{auth()->user()->pharm_location_id}}`)
            .listen('IoTransNewRequest', (e) => {
                // console.log(e.requestor);
                Swal.fire({
                    icon: 'info',
                    title: 'New Request from '+e.requestor,
                })
            });
        </script>
    </body>
</html>
