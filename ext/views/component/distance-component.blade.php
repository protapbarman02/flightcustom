<div class="row mt-2">
    @if ($travelData != null)
        @if ($travelData['status'] != 'OK')
            {{ $travelData['error_message'] }}
        @else
            @php
                if ($travelData['rows'][0]['elements'][0]['status'] !== 'OK') {
                    $distance = null;
                    $message = null;
                } else {
                    $data = $travelData['rows'][0]['elements'][0];
                    $distance = $data['distance']['text'];
                }
            @endphp

            @script
                <script>
                    requiredTimeInSeconds = $wire.travelData.rows[0].elements[0].duration.value;

                    departureStatus = $("#flight_status").text();
                    targetTime = $("#departure_time").text();
                    destinationDate = $("#departure_date").text();

                    if (departureStatus == 'scheduled') {
                        $wire.set('message',getTravelTime(targetTime, requiredTimeInSeconds, destinationDate));
                    } else if (departureStatus == 'cancelled') {
                        $wire.set('message','Flight is Cancelled');
                    } else {
                        $wire.set('message','Flight is already Departed');
                    }
                </script>
            @endscript

            <div class="col">
                <div>
                    [<span>{{ $travelData['origin_addresses'] ? $travelData['origin_addresses'][0] : 'N/A' }}</span><span
                        class="text-dark"> to</span>
                    <span>{{ $travelData['destination_addresses'] ? $travelData['destination_addresses'][0] : 'N/A' }}</span>]
                </div>
                <div>{{ $distance ? $distance : 'Distance Not Found' }}</div>
            </div>

            <div class="col">
                <div>{{ $message }}</div>
            </div>

            <div class="col">
                <div class="row">
                    <div class="col-1">By</div>
                    <div class="col">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light rounded-0 dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false" id="mode">
                                {{ $mode }}
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

    <script>
        // calculate time informations with formatting
        window.getTravelTime = function(destinationTime, durationInSeconds, destinationDate) {
            const [time, modifier] = destinationTime.split(' ');
            let [hours, minutes] = time.split(':').map(Number);
            hours = (modifier === 'PM' && hours !== 12) ? hours + 12 : hours % 12;

            const targetDate = new Date(
                `${destinationDate}T${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`);

            const latestStartTime = new Date(targetDate.getTime() - durationInSeconds * 1000);

            const now = new Date();
            const timeRemaining = targetDate.getTime() - now.getTime();

            if (timeRemaining < durationInSeconds * 1000) {
                return `You cannot reach the destination on time. Required time: ${convertSeconds(durationInSeconds)}.`;
            }

            const startHours = latestStartTime.getHours() % 12 || 12;
            const startMinutes = String(latestStartTime.getMinutes()).padStart(2, '0');
            const startModifier = latestStartTime.getHours() >= 12 ? 'PM' : 'AM';

            const departureDate = latestStartTime.toISOString().split('T')[0];
            let travelDate;

            if (latestStartTime.getDate() === now.getDate() && latestStartTime.getMonth() === now.getMonth() &&
                latestStartTime.getFullYear() === now.getFullYear()) {
                travelDate = 'today';
            } else if (latestStartTime.getDate() === now.getDate() + 1 && latestStartTime.getMonth() === now
            .getMonth() &&
                latestStartTime.getFullYear() === now.getFullYear()) {
                travelDate = 'tomorrow';
            } else {
                travelDate = departureDate;
            }

            return `You can leave ${travelDate} at ${startHours}:${startMinutes} ${startModifier}${travelDate !== 'today' && travelDate !== 'tomorrow' ? ` on ${departureDate}` : ''}.`;
        }

        // convert seconds into hours minutes
        window.convertSeconds = function(seconds) {
            hours = Math.floor(seconds / 3600);
            minutes = Math.floor((seconds % 3600) / 60);

            hourString = hours > 0 ? `${hours} hour${hours > 1 ? 's' : ''}` : '';
            minuteString = minutes > 0 ? `${minutes} minute${minutes > 1 ? 's' : ''}` : '';

            return hourString && minuteString ? `${hourString} : ${minuteString}` :
                hourString || minuteString || '0 minutes';
        };
    </script>

</div>
