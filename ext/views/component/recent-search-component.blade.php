<div class="container mt-4" id="recent_searches_container">
    <div class="fs-3" id="recent-search-heading">Recent Searches</div>
    @if ($this->recentSearches->isNotEmpty())
        <div class="div my-2" id="recent-searches">
            @foreach ($this->recentSearches as $recentSearch)
                <div wire:key="{{ $recentSearch->id }}" class="row my-2 align-middle flex align-items-center"
                    style="background-color: #FDFDFF;" id="recent_search_row_{{ $recentSearch->id }}">
                    <div class="col">
                        <div class="row">
                            <div class="col" id="recent_search_flight_no_{{ $recentSearch->id }}">
                                {{ $recentSearch->flight_no }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">{{ $recentSearch->departure_iata }} to {{ $recentSearch->arrival_iata }}
                            </div>
                        </div>
                    </div>

                    <div class="col align-middle">
                        {{ $recentSearch->airline_name }}
                    </div>

                    <div class="col">
                        {{ $this->formatFlightDate($recentSearch->flight_date) }}
                    </div>

                    <div class="col">
                        <div>Departure Time</div>
                        <div>{{ convertToAmPm($recentSearch->departure_at) }}</div>
                    </div>

                    <div class="col">
                        <div>Arrival Time</div>
                        <div>{{ convertToAmPm($recentSearch->arrival_at) }}</div>
                    </div>

                    <div class="col">
                        <button type="button" class="btn btn-sm btn-dark rounded-0 delete_recent_buttons"
                            wire:click="delete({{ $recentSearch->id }})" data-bs-toggle="tooltip"
                            data-bs-placement="left" data-bs-title="Delete">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-dark rounded-0 retrack_buttons"
                            wire:click='retrack("{{ $recentSearch->flight_no }}")' data-bs-toggle="tooltip"
                            data-bs-placement="right" data-bs-title="Re-track">
                            <i class="bi bi-geo-alt"></i>
                        </button>
                    </div>

                    <div class="col">
                        <div>Last Tracked</div>
                        <div>{{ formatDateTimeKolkata($recentSearch->created_at) }}, {{ $recentSearch->last_status }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div>
            {{ $this->recentSearches->links() }}
        </div>
    @else
        <div class="my-2">No Results</div>
    @endif

    <script>
        $(function() {
            resetTooltip();
        });
    </script>

    @script
        <script>
            $wire.on('show-toastr', (event) => {
                toastr[event[0].type](event[0].message);
                resetTooltip();
            });
        </script>
    @endscript
    @php
        function formatDateTimeKolkata($datetime)
        {
            $date = new DateTime($datetime, new DateTimeZone('GMT'));
            $date->setTimezone(new DateTimeZone('Asia/Kolkata'));
            return $date->format('D, d M, h:i A');
        }
    
        function convertToAmPm($datetime)
        {
            $date = new DateTime($datetime);
            return $date->format('h:i A');
        }
    @endphp
</div>

