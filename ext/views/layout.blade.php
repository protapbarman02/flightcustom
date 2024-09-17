<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script type="text/javascript" src="{{ url('/app/js-min-nosip-' . config('kstych.config.app_version')) }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css">
    <title>FlightTracking</title>

    <style>
        body {
            background-color: #F8F9FD;
            color: #57595a;
            height: 100vh;
        }

        .form-control:focus {
            border-color: #7c7c7c;
            box-shadow: none;
        }

        .main-content {
            height: calc(100vh - 60px - 40px);
            overflow-y: scroll;
        }

        .page-link {
            color: black;
        }

        .page-link:hover {
            color: black;
        }

        .active>.page-link,
        .page-link.active {
            background-color: black;
            border-color: black;
            color: white;
        }

        ul.pagination {
            border-radius: 0px;
        }

        .date-active {
            background-color: transparent;
            color: black;
        }

        .custom-modal {
            position: fixed;
            width: 100%;
            max-width: 500px;
            background: #fafafa;
            min-height: 250px;
            padding: 40px;
            transform: translateX(50%);
        }

        .custom-modal input {
            width: 100%;
            height: 28px;
            margin-bottom: 15px;
            padding: 5px;
        }
    </style>
</head>

<body style="position: relative;">
    <div id="header" class="bg-dark" style="height:50px;">
        <div class="container py-2" style="max-width:90%">
            <a href="/flighttracking/" class="fs-4 text-light text-decoration-none">Flight Tracking</a>
        </div>
    </div>

    @php
        $configmodules = [];
        if (Auth::check()) {
            $configmodules = Auth::user()->configModules();
        }
        $jsarr = [];
        foreach ($configmodules as $m => $marr) {
            $jsarr[$m] = ['icon' => $marr['icon']];
        }
    @endphp

    @yield('content')

    <div class="position-fixed bg-dark" style="bottom:0;left:0;right:0; height:50px;" id="footer">
        <footer class="page-footer font-small">
            <div class="text-center py-3">
                <a href="/flighttracking/" style="color:white" class="text-decoration-none"> Flight Tracking -
                    Kstych</a>
            </div>
        </footer>
    </div>
    <script>
        // ajax configuration
        kstych.ajax.modulearr = {!! json_encode($jsarr) !!};
        kstych.ajax.cache.enabled = false;
        kstychAppObject = {!! json_encode((new \App\Kstych\Auth\KAuthLib())->jsUserObject(), true) !!};
        kstychAppObject.user.uimode = "default";
        kstych.ui.resetjuidialog = function() {}
        kstych.ui.resetJsActions = function() {}
        kstych.ajax.loaderhtml = function(targetdiv) {}
        kstych.notify.notify = function(title, msg, options, kcallback) {}

        // generates a randomkey for ajax call
        function randomKey() {
            return Math.random().toString(36).substring(2, 10);
        }

        function resetTooltip() {
            $('[data-bs-toggle="tooltip"]').tooltip('dispose');
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        }
    </script>

</body>

</html>
