<div x-data="flightsearch" x-init="initialize">
    <div class="row">
        <div class="col">
            <form class="row g-3" wire:submit="search">
                <div class="col-auto">
                    <input type="text" wire:model="flight_no" id="flight_no"
                        class="form-control form-control-sm rounded-0" placeholder="Enter Flight Number" required />
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-dark mb-3 rounded-0"
                        id="track_form_submit_btn">Track</button>
                </div>
                @error('flight_no')
                    <p class="text-danger my-0">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </div>
    <hr>

    <form class="modal fade" id="travelForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true"
        wire:submit="getTimeInfo" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Enter your location(e.g. Central Park,Delhi)
                    </h1>
                </div>
                <div class="modal-body">
                    <input type="text" wire:model.live="source_location"
                        class="form-control form-control-sm rounded-0 position-relative w-100" id="source_location"
                        placeholder="Enter Your Location" required>
                    @error('source_location')
                        <p class="text-danger my-0">{{ $message }}</p>
                    @enderror
                    @if ($predictions)
                        <ul style="list-style-type:none;">
                            @foreach ($predictions['predictions'] as $prediction)
                                <li wire:click="updateSourceLocation('{{ $prediction['description'] }}')">
                                    {{ $prediction['description'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <input type="hidden" wire:model="destination_location">
                    <input type="hidden" wire:model="mode">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light rounded-0"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-dark rounded-0">Confirm</button>
                </div>
            </div>
        </div>
    </form>

    @if (!$flightDetails == null)
        @if (!array_key_exists('error', $flightDetails))
            <div class="container mt-4">
                <div class="row">
                    <div class="col">
                        <div class="d-flex justify-content-around" id="dates">
                            <button class="date-btn btn btn-sm btn-dark rounded-0">
                                {{ $this->formatFlightDate($flightDetails['flight_date']) }}
                            </button>
                        </div>
                    </div>
                </div>
                <div id="flight-details">
                    @php
                        // Prepare the times
                        $departureScheduled = formatDateTimeUTC($flightDetails['departure']['scheduled']);
                        $departureEstimated = formatDateTimeUTC($flightDetails['departure']['estimated']);
                        $departureActual = formatDateTimeUTC($flightDetails['departure']['actual']);

                        $arrivalScheduled = formatDateTimeUTC($flightDetails['arrival']['scheduled']);
                        $arrivalEstimated = formatDateTimeUTC($flightDetails['arrival']['estimated']);
                        $arrivalActual = formatDateTimeUTC($flightDetails['arrival']['actual']);

                        // Define status actions
                        $statusActions = [
                            'cancelled' => [
                                'departureHeading' => 'Departure',
                                'arrivalHeading' => 'Arrival',
                                'departureTime' => '-',
                                'arrivalTime' => '-',
                            ],
                            'landed' => [
                                'departureHeading' => 'Departed',
                                'arrivalHeading' => 'Arrived',
                                'departureTime' => $departureActual,
                                'arrivalTime' => $arrivalActual,
                                'prevDepartureTime' =>
                                    $flightDetails['departure']['scheduled'] !== $flightDetails['departure']['actual']
                                        ? $departureScheduled
                                        : null,
                                'prevArrivalTime' =>
                                    $flightDetails['arrival']['scheduled'] !== $flightDetails['arrival']['actual']
                                        ? $arrivalScheduled
                                        : null,
                            ],
                            'incident' => [
                                'departureHeading' => 'Departed',
                                'arrivalHeading' => 'Arrival',
                                'departureTime' => $departureActual,
                                'arrivalTime' => '-',
                                'prevDepartureTime' =>
                                    $flightDetails['departure']['scheduled'] !== $flightDetails['departure']['actual']
                                        ? $departureScheduled
                                        : null,
                            ],
                            'diverted' => [
                                'departureHeading' => 'Departed',
                                'arrivalHeading' => 'Estimated Arrival',
                                'departureTime' => $departureActual,
                                'arrivalTime' => $arrivalEstimated,
                                'prevDepartureTime' =>
                                    $flightDetails['departure']['scheduled'] !== $flightDetails['departure']['actual']
                                        ? $departureScheduled
                                        : null,
                            ],
                        ];

                        // Default action when no specific status is matched
                        $defaultAction = [
                            'departureHeading' => $flightDetails['departure']['actual']
                                ? 'Departed'
                                : ($flightDetails['departure']['scheduled'] !== $flightDetails['departure']['estimated']
                                    ? 'Estimated Departure'
                                    : 'Scheduled Departure'),
                            'departureTime' => $flightDetails['departure']['actual']
                                ? $departureActual
                                : ($flightDetails['departure']['scheduled'] !== $flightDetails['departure']['estimated']
                                    ? $departureEstimated
                                    : $departureScheduled),
                            'prevDepartureTime' =>
                                $flightDetails['departure']['actual'] &&
                                $flightDetails['departure']['scheduled'] !== $flightDetails['departure']['actual']
                                    ? $departureScheduled
                                    : null,
                            'arrivalHeading' => $flightDetails['departure']['actual']
                                ? 'Estimated Arrival'
                                : 'Scheduled Arrival',
                            'arrivalTime' => $arrivalScheduled,
                            'prevArrivalTime' => null,
                        ];

                        // Determine which status action to use
                        $flightStatusDetails = isset($statusActions[$flightDetails['flight_status']])
                            ? $statusActions[$flightDetails['flight_status']]
                            : $defaultAction;

                        // Extract values from the chosen status action
                        $departureHeading = $flightStatusDetails['departureHeading'];
                        $arrivalHeading = $flightStatusDetails['arrivalHeading'];
                        $departureTime = $flightStatusDetails['departureTime'];
                        $arrivalTime = $flightStatusDetails['arrivalTime'];
                        $prevDepartureTime = $flightStatusDetails['prevDepartureTime'] ?? null;
                        $prevArrivalTime = $flightStatusDetails['prevArrivalTime'] ?? null;

                        // Calculate flight duration if both actual departure and arrival times exist
                        $flightDuration = null;
                        if ($flightDetails['departure']['actual'] && $flightDetails['arrival']['actual']) {
                            $flightDuration = calculateDuration(
                                $flightDetails['departure']['actual'],
                                $flightDetails['arrival']['actual'],
                            );
                        } else {
                            $flightDuration = calculateDuration(
                                $flightDetails['departure']['estimated'],
                                $flightDetails['arrival']['estimated'],
                            );
                        }

                        // Calculate flight duration if actual times are available
                        if ($flightDetails['departure']['actual'] && $flightDetails['arrival']['actual']) {
                            $flightDuration = calculateDuration(
                                $flightDetails['departure']['actual'],
                                $flightDetails['arrival']['actual'],
                            );
                        } else {
                            $flightDuration = calculateDuration(
                                $flightDetails['departure']['estimated'],
                                $flightDetails['arrival']['estimated'],
                            );
                        }
                    @endphp
                    <div class="flight-info" wire:key="$flightDetails['flight_date']">
                        <div class="">
                            <div class="fs-4">
                                {{ $flightDetails['airline']['iata'] }}{{ $flightDetails['flight']['number'] }}
                            </div>
                            <div><span id="flight_departure_iata_top">{{ $flightDetails['departure']['iata'] }}</span>
                                to
                                <span id="flight_arrival_iata_top">{{ $flightDetails['arrival']['iata'] }}</span>
                            </div>
                            <span id="departure_date">{{ $flightDetails['flight_date'] }}</span>, <span
                                id="flight_status">{{ $flightDetails['flight_status'] }}</span>
                        </div>

                        <div class="d-flex justify-content-around">
                            <div class="fs-3" id="flight_departure_iata_head">
                                {{ $flightDetails['departure']['iata'] }}
                            </div>
                            <div id="flight_duration_head">{{ $flightDuration }}</div>
                            <div class="fs-3" id="flight_arrival_iata_head">{{ $flightDetails['arrival']['iata'] }}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col border-end">
                                <div class="mb-2 fs-5" id="destination_location">
                                    {{ $flightDetails['departure']['location'] }}</div>
                                <div class="row">
                                    <div class="col">
                                        <div>{{ $departureHeading }}</div>
                                        <div class="fs-5" id="departure_time">{{ $departureTime }}</div>
                                        <div class="text-decoration-line-through">
                                            {{ $prevDepartureTime ? $prevDepartureTime : '' }}</div>
                                    </div>
                                    <div class="col">
                                        <div>Terminal</div>
                                        <div class="fs-5">
                                            {{ $flightDetails['departure']['terminal'] ? $flightDetails['departure']['terminal'] : '-' }}
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>Gate</div>
                                        <div class="fs-5">
                                            {{ $flightDetails['departure']['gate'] ? $flightDetails['departure']['gate'] : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col">
                                <div class="mb-2 fs-5" id="arrival_location">
                                    {{ $flightDetails['arrival']['location'] }}
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div>{{ $arrivalHeading }}</div>
                                        <div class="fs-5">{{ $arrivalTime }}</div>
                                        <div class="text-decoration-line-through">
                                            {{ $prevArrivalTime ? $prevArrivalTime : '' }}
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>Terminal</div>
                                        <div class="fs-5">
                                            {{ $flightDetails['arrival']['terminal'] ? $flightDetails['arrival']['terminal'] : '-' }}
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>Gate</div>
                                        <div class="fs-5">
                                            {{ $flightDetails['arrival']['gate'] ? $flightDetails['arrival']['gate'] : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            @if ($flightDetails['error']['code'] == 404)
                <div>No Flights Found</div>
            @endif
        @endif
    @endif



    <div>
        <div id="time-details">
            <!-- distance and time details -->
            @livewire('DistanceComponent')
        </div>
    </div>


    @script
        <script>
            Alpine.data('flightsearch', () => {
                return {
                    a: $wire.entangle('flightDetails'),
                    initialize: function() {
                        this.$watch('a', () => {
                            console.log("hi");
                            fetchLocation();
                        })
                    }
                }
            })

            $wire.on('show-toastr', (event) => {
                toastr[event[0].type](event[0].message);
                resetTooltip();
            });


            //children should take events directly from parent**********************************************************************************

            // fetch locations(syncronous) and then travel information
            async function fetchLocation() {
                try {
                    console.log('hi');
                    sourceLocation = await getLocation();
                    console.log(sourceLocation)
                    $("#source_location").val(sourceLocation.origins);
                    destinationLocation = $("#destination_location").text().trim();
                    destinationDate = $("#departure_date").text();
                    // $wire.source_location = sourceLocation.origins;
                    // $wire.mode = 'Driving';
                    // "origin"=>$this->source_location,"destination"=>$this->destination_location,"mode"=>$this->mode
                    $wire.dispatchTo('DistanceComponent','getTimeInfo',{origin:sourceLocation,destination:destinationLocation,mode:mode});  // event not caught in component 
                    // $wire.dispatchTo('modal-hide');
                    // $wire.getTimeInfo();
                } catch (error) {
                    toastr.error(
                        "Some error occured while fetching travel information"
                    );
                }
            }

            // get source location(Synchronously) using browser navigator engiene (it works asynchronously so had to use Promise)
            function getLocation() {
                return new Promise((resolve, reject) => {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const latitude = position.coords.latitude;
                                const longitude = position.coords.longitude;
                                const origins = `${latitude},${longitude}`;
                                resolve({
                                    origins
                                });
                            },
                            (error) => {
                                toastr.error(
                                    "Please reload the page and allow Location to view distance and travel information"
                                );
                                return;
                            }
                        );
                    } else {
                        toastr.error(
                            "Please reload the page and allow Location to view distance and travel information");
                        return;
                    }
                });
            }

            // open location input modal
            window.changeLocation = function() {
                $("#travelForm").modal('show');
            }

            $wire.on("modal-hide", () => {
                $("#travelForm").modal('hide');
            })

            // change tavel mode : also gets the source location and travel mode and redo the travel, time info repopulation
            window.setMode = function(mode) {
                $wire.mode = mode;
                $wire.getTimeInfo();
            }
        </script>
    @endscript

    @php
        function formatDateTimeUTC($datetime)
        {
            $date = new DateTime($datetime);
            return $date->format('h:i A');
        }

        function calculateDuration($startTime, $endTime)
        {
            $start = new DateTime($startTime);
            $end = new DateTime($endTime);
            $interval = $start->diff($end);
            return $interval->format('%h hours %i minutes');
        }
    @endphp

</div>
