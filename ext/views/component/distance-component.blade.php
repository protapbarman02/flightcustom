<div class="row mt-2">
    @if ($travelData != null)
        @if ($travelData['status'] != 'OK')
            {{ $travelData['error_message'] }}
        @else
            @php 
                if($travelData['rows'][0]['elements'][0]['status']!=="OK") {
                    $distance = null;
                    $message = null;
                } else{
                    $data = $travelData['rows'][0]['elements'][0];
                    $distance = $data['distance']['text'];
                    $requiredTime = $data['duration']['text'];
                    $requiredTimeInSeconds = $data['duration']['value'];

                    // $targetTime = $("#departure_time").text();
                    // $departureStatus = $("#flight_status").text();

                    // if($departureStatus == 'scheduled') {
                    // //   $message = getTravelTime($targetTime,$requiredTimeInSeconds,$destinationDate);
                    //     $message = 'lol';
                    // }
                    // else if($departureStatus == 'cancelled') {
                    //   $message = 'Flight is Cancelled';
                    // }
                    // else {
                    //   $message = 'Flight is already Departed';
                    // }
                }
            @endphp

            <div class="col">
                <div>[<span>{{$travelData['origin_addresses']?$travelData['origin_addresses'][0]:'N/A'}}</span><span class="text-dark"> to</span>
                    <span>{{$travelData['destination_addresses']?$travelData['destination_addresses'][0]:'N/A'}}</span>]
                </div>
                <div>{{$distance?$distance:'Distance Not Found'}}</div>
            </div>

            <div class="col">
                {{-- <div>{{$message?$message:'N/A'}}</div> --}}
            </div>

            <div class="col">
                <div class="row">
                    <div class="col-1">By</div>
                    <div class="col">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light rounded-0 dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false" id="mode">
                                {{$mode}}
                            </button>
                            <ul class="dropdown-menu m-0 p-0 rounded-0">
                                <li><button class="dropdown-item btn btn-light"
                                        onclick="setMode('Driving')">Driving</button>
                                </li>
                                <li><button class="dropdown-item btn btn-light"
                                        onclick="setMode('Transit')">Transit</button>
                                </li>
                                <li><button class="dropdown-item btn btn-light"
                                        onclick="setMode('Walking')">Walking</button>
                                </li>
                            </ul>
                            <button type="button" class="btn btn-sm btn-dark rounded-0"
                                onclick="changeLocation()">Change Your
                                Location</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif


</div>
