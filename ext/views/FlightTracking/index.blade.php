@extends('custom.FlightTracking.template')
@section('content')
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

  <div id="spinner" class="d-none">
    <div style="height:100vh; background-color:#AAA3; top:0; left:0;" class="w-100 d-flex justify-content-center align-items-center position-fixed">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  </div>
  
  <div class="main-content">
    <div class="container mt-4">
      <div class="row">
        <div class="col">
          <form class="row g-3" id="track_form">
            @csrf
            <div class="col-auto">
              <input type="text" name="flight_no" class="form-control form-control-sm rounded-0" id="flight_no"
                placeholder="Enter Flight Number" required>
            </div>
  
            <div class="col-auto">
              <button type="submit" class="btn btn-sm btn-dark mb-3 rounded-0" id="track_form_submit_btn">Track</button>
            </div>
          </form>
        </div>
      </div>
      <p id="flight-error"></p>
      <hr>
  
      <!-- modal to get address if geolocation fails/denied or change location-->
      <form class="modal fade" id="travelForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        @csrf
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="exampleModalLabel">Enter your location(e.g. Central Park,Delhi)</h1>
            </div>
            <div class="modal-body">
                <input type="text" name="source_location" class="form-control form-control-sm rounded-0 position-relative w-100"
                  id="source_location" placeholder="Enter Your Location" required>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-sm btn-light rounded-0" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-sm btn-dark rounded-0">Confirm</button>
            </div>
          </div>
        </div>
      </form>
  
      <div class="container mt-4">
        <div class="row">
          <div class="col">
            <div class="d-flex justify-content-around" id="dates">
              <!-- dates -->
            </div>
          </div>
        </div>
        <div id="flight-details">
          <!-- flight details -->
        </div>
      </div>
  
      <div>
        <div id="time-details">
          <!-- distance and time details -->
        </div>
        <div id="time-details-error"></div>
  
      </div>
    </div>
  
    <div class="container mt-4" id="recent_searches_container">
      <div class="fs-3 d-none" id="recent-search-heading">Recent Searches</div>
      <div class="div my-2" id="recent-searches">
        <!-- recent searches-->
      </div>
    </div>
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

    $(document).ready(function() {
      getRecentSearches();

      $('#track_form').on('submit', function(event) {
        event.preventDefault();
        var formData = $(this).serialize();
        $("#spinner").removeClass('d-none');
        renderAll(formData);
      });
    });

    // get recent searches data
    function getRecentSearches() {
      kstych.ajax.do(
        `flighttracking/recentsearch`,
        ``,
        `some_id`,
        randomKey(),
        'singlefail',
        'GET',
        ((response)=>{
          $("#recent-searches").empty();
          console.log("Recent Searches");
          if(response.length<=2){
            $("#recent-searches").append(`<div>No recent searches available.</div>`);
            return;
          }
          renderRecentSearches(JSON.parse(response));
        }),
        ((xhr,status,error)=> {
          console.log(error);
        })
      );
    }
    
    // populate recent searches container
    function renderRecentSearches(response){
      $("#recent-search-heading").removeClass('d-none');
      response.map((recentSearch) => {
        $("#recent-searches").append(`
			    <div class="row my-2 align-middle" style="background-color: #FDFDFF;" id="recent_search_row_${recentSearch.id}">
			      <div class="col">
			    	<div class="row"><div class="col" id="recent_search_flight_no_${recentSearch.id}">${recentSearch.flight_no}</div></div>
			    	<div class="row"><div class="col">${recentSearch.departure_iata} to ${recentSearch.arrival_iata}</div></div>
			      </div>
          
			      <div class="col align-middle">
			    	${recentSearch.airline_name}
			      </div>
          
			      <div class="col">
			    	${recentSearch.flight_date}
			      </div>
          
			      <div class="col">
			    	<div>Departure Time</div>
			    	<div>${formatTimeUTC(recentSearch.departure_at)}</div>
			      </div>
          
			      <div class="col">
			    	<div>Arrival Time</div>
			    	<div>${formatTimeUTC(recentSearch.arrival_at)}</div>
			      </div>

			      <div class="col">
			    	<button class="btn btn-sm btn-dark rounded-0 delete_recent_buttons" id="delete_recent_button_${recentSearch.id}" onclick="deleteRecent(${recentSearch.id})" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Delete">
			    	  <i class="bi bi-x-lg"></i>
			    	</button>
			    	<button class="btn btn-sm btn-dark rounded-0 retrack_buttons" id="retrack_button_${recentSearch.id}" onclick="track('${recentSearch.flight_no}')" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Re-track">
			    	  <i class="bi bi-geo-alt"></i>
			    	</button>
			      </div>
          
			      <div class="col">
			    	<div>Last Tracked</div>
			    	<div>${formatTimeLocal(recentSearch.created_at)} - ${recentSearch.last_status}</div>
			      </div>
			    </div>
		    `);
        initializeTooltips();
      });
    }

    
    // populate dates, flight details and destination-time information 
    function renderAll(formData) {
      $("#flight-error").text('');

      $.ajax({
        type: 'GET',
        url: '/flighttracking/flight',
        data: formData,
        success: function(response) {
          console.log("Flight Data");
          console.log(response);

          // console.log(response.message);
          
          renderDates(response.data);
          getRecentSearches();

          $('#dates').on('click', '.date-btn', function() {
            const selectedDate = $(this).data('date');
            renderFlights(selectedDate, response.data);
            $("#spinner").removeClass('d-none');
            fetchLocation();
          });

          if (response.data.data.length > 0) {
            const initialDate = response.data.data[0].flight_date;
            renderFlights(initialDate, response.data);
            fetchLocation();
          }
        },
        error: function(xhr, status, error) {
          if(xhr.status==422){
            $("#flight-error").text(xhr.responseJSON.errors.flight_no[0]);
          }
          else{
            $("#flight-error").text('Sorry can not find flight information');
          }
          $("#spinner").addClass('d-none');
        }
      });
    }

    // populate dates container
    function renderDates(response) {
      const dates = new Set(response.data.map(flight => flight.flight_date));
      $('#dates').empty();
      dates.forEach(date => {
        const formattedDate = new Date(date).toLocaleDateString('en-GB', {
          weekday: 'short',
          day: '2-digit',
          month: 'short'
        });
        $('#dates').append(
          `<div class="date-btn btn btn-sm btn-dark rounded-0" data-date="${date}">${formattedDate}</div>`
        );
      });
    }

    // populate flight details container
    function renderFlights(date, response) {
      $('#flight-details').empty();
      const flightsForDate = response.data.filter(flight => flight.flight_date === date);

      flightsForDate.forEach(flight => {
        departureScheduled = formatTimeUTC(flight.departure.scheduled, flight.departure.timezone);
        departureEstimated = formatTimeUTC(flight.departure.estimated, flight.departure.timezone);
        departureActual = formatTimeUTC(flight.departure.actual, flight.departure.timezone);

        arrivalScheduled = formatTimeUTC(flight.arrival.scheduled, flight.arrival.timezone);
        arrivalEstimated = formatTimeUTC(flight.arrival.estimated, flight.arrival.timezone);
        arrivalActual = formatTimeUTC(flight.arrival.actual, flight.arrival.timezone);

        // custom Departure and arrival headings based on flight_status, scheduled and estimated departure/arrival datetime
        const statusActions = {
          cancelled: {
            departureHeading: 'Departure',
            arrivalHeading: 'Arrival',
            departureTime: '-',
            arrivalTime: '-'
          },
          landed: {
            departureHeading: 'Departed',
            arrivalHeading: 'Arrived',
            departureTime: departureActual,
            arrivalTime: arrivalActual,
            prevDepartureTime: flight.departure.scheduled !== flight.departure.actual ?
              departureScheduled : null,
            prevArrivalTime: flight.arrival.scheduled !== flight.arrival.actual ? arrivalScheduled : null
          },
          incident: {
            departureHeading: 'Departed',
            arrivalHeading: 'Arrival',
            departureTime: departureActual,
            arrivalTime: '-',
            prevDepartureTime: flight.departure.scheduled !== flight.departure.actual ?
              departureScheduled : null
          },
          diverted: {
            departureHeading: 'Departed',
            arrivalHeading: 'Esitamated Arrival',
            departureTime: departureActual,
            arrivalTime: arrivalEstimated,
            prevDepartureTime: flight.departure.scheduled !== flight.departure.actual ?
              departureScheduled : null
          }
        };

        const defaultAction = {
          departureHeading: flight.departure.actual ? 'Departed' : flight.departure.scheduled !==
            flight.departure.estimated ? 'Estimated Departure' : 'Scheduled Departure',
          departureTime: flight.departure.actual ? departureActual : flight.departure.scheduled !==
            flight.departure.estimated ? departureEstimated : departureScheduled,
          prevArrivalTime: null,
          prevDepartureTime: flight.departure.actual ? flight.departure.scheduled !== flight.departure
            .actual ? departureScheduled : null : null,
          arrivalHeading: flight.departure.actual ? 'Estimated Arrival' : 'Scheduled Arrival',
          arrivalTime: arrivalScheduled
        };

        const {
          departureHeading,
          arrivalHeading,
          departureTime,
          arrivalTime,
          prevArrivalTime,
          prevDepartureTime
        } = statusActions[flight.flight_status] || defaultAction;

        if (flight.departure.actual && flight.arrival.actual) {
          flightDuration = calculateDuration(flight.departure.actual, flight.arrival.actual);
        } else {
          flightDuration = calculateDuration(flight.departure.estimated, flight.arrival.estimated);
        }
        
        $('#flight-details').append(`
		      <div class="flight-info">
		        <div class="">
		      	  <div class="fs-4">${flight.airline.iata}${flight.flight.number}</div>
		      	  <div><span id="flight_departure_iata_top">${flight.departure.iata}</span> to <span id="flight_arrival_iata_top">${flight.arrival.iata}</span></div>
		      	  <span id="departure_date">${flight.flight_date}</span>, <span id="flight_status">${flight.flight_status}</span>
		        </div>
          
		        <div class="d-flex justify-content-around">
		      	<div class="fs-3" id="flight_departure_iata_head">${flight.departure.iata}</div>
		      	<div id="flight_duration_head">${flightDuration}</div>
		      	<div class="fs-3" id="flight_arrival_iata_head">${flight.arrival.iata}</div>
		        </div>
          
		        <div class="row">
		      	<div class="col border-end">
		      	  <div class="mb-2 fs-5" id="destination_location">${flight.departure.location}</div>
		      	  <div class="row">
		      		<div class="col">
		      		  <div>${departureHeading}</div>
		      		  <div class="fs-5" id="departure_time">${departureTime}</div>
		      		  <div class="text-decoration-line-through">${prevDepartureTime?prevDepartureTime:''}</div>
		      		</div>
		      		<div class="col">
		      		  <div>Terminal</div>
		      		  <div class="fs-5">${flight.departure.terminal?flight.departure.terminal:'-'}</div>
		      		</div>
		      		<div class="col">
		      		  <div>Gate</div>
		      		  <div class="fs-5">${flight.departure.gate?flight.departure.gate:'-'}</div>
		      		</div>
		      	  </div>
		      	</div>
          
		      	<div class="col">
		      	  <div class="mb-2 fs-5" id="arrival_location">${flight.arrival.location}</div>
		      	  <div class="row">
		      		<div class="col">
		      		  <div>${arrivalHeading}</div>
		      		  <div class="fs-5">${arrivalTime}</div>
		      		  <div class="text-decoration-line-through">${prevArrivalTime?prevArrivalTime:''}</div>
		      		</div>
		      		<div class="col">
		      		  <div>Terminal</div>
		      		  <div class="fs-5">${flight.arrival.terminal?flight.arrival.terminal:'-'}</div>
		      		</div>
		      		<div class="col">
		      		  <div>Gate</div>
		      		  <div class="fs-5">${flight.arrival.gate?flight.arrival.gate:'-'}</div>
		      		</div>
		      	  </div>
		      	</div>
          
		        </div>
          
		      </div>
	      `);
      });
      $('#flight-details').append('<hr>');
    }

    // fetch locations(syncronous) and then travel information
    async function fetchLocation() {
      try {
        sourceLocation = await getLocation();

        $("#source_location").val(sourceLocation.origins);
        destinationLocation = $("#destination_location").text().trim();
        destinationDate = $("#departure_date").text();
        mode = 'Driving';
        // first get travel info(syncronous) and then populate travel information container
        getTimeInfo(sourceLocation.origins,destinationLocation,mode,destinationDate)
        .then(timeInfo => {
          status = 'SUCCESS';
          message = 'Succesfully fetched details';
          renderTimes(status,message,mode,timeInfo);                                                                                
        })
        .catch(error => {
          status = 'ERROR';
          renderTimes(status,error,mode,{});
        });

      } catch (error) {
        status = 'SOURCE_ERROR';
        renderTimes(status,error,mode,{});
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
              resolve({ origins });
            },
            (error) => {
              reject("Please reload the page and allow Location to view distance and travel information");
            }
          );
        } else {
          reject("Geolocation is not supported by this browser. Try entering your location.");
        }
      });
    }

    // prepares data to render for travel information container
    async function getTimeInfo(sourceLocation,destinationLocation,mode,destinationDate){
      return new Promise((resolve,reject)=>{
        $.ajax({
          type: 'GET',
          url: '/flighttracking/distance?origins='+encodeURIComponent(sourceLocation)+'&destinations='+encodeURIComponent(destinationLocation)+'&mode='+mode,
          success: function(data) {
            response = data.data;
            console.log("Time info");
            console.log(response);
            if (response.status !== "OK") {
                reject('Something Went Wrong while fetching travel information');
            }
            else{
              //if response.origin_addresses then give a resolve message location not found and dont change source location value
              if(response.origin_addresses.length==0){
                reject('Source Location not Found');
                return;
              }
              sourceLocation = response.origin_addresses;
              destinationLocation = response.destination_addresses;
              if(response.rows[0].elements[0].status !=="OK") {
                resolve({
                  sourceLocation:sourceLocation,
                  destinationLocation:destinationLocation,
                  distance:'N/A',
                  message:'No results Found'
                });
                return;
              }
              else{
                data = response.rows[0].elements[0];
                distance = data.distance.text;
                requiredTime = data.duration.text;
                requiredTimeInSeconds = data.duration.value;
  
                targetTime = $("#departure_time").text();
                departureStatus = $("#flight_status").text();
                message = '-';
  
                if(departureStatus == 'scheduled') {
                  message = `${getTravelTime(targetTime,requiredTimeInSeconds,destinationDate)}`
                }
                else if(departureStatus == 'cancelled') {
                  message = 'Flight is Cancelled';
                }
                else {
                  message = 'Flight is already Departed';
                }
              }
              $("#source_location").val(sourceLocation);
              resolve({
                sourceLocation:sourceLocation,
                destinationLocation:destinationLocation,
                message:message,
                distance:distance
              });
            }
          },
          error: function(xhr, status, error) {
            console.log(error.message());
            reject('Something Went Wrong');
          }
        });
      })
    }

    // render the travel container
    function renderTimes(status,message,mode, data) {
      $("#spinner").addClass('d-none');
      $("#time-details-error").empty();
      $("#time-details").empty();

      if(status !== 'SUCCESS'){
        $("#time-details-error").html('<span class="text-danger">*</span>'+message);
      }

      $("#time-details").append(`
        <div class="row mt-2">
        
          <div class="col">
            <div>[<span>${data.sourceLocation?data.sourceLocation:'N/A'}</span><span class="text-dark">to</span> <span>${data.destinationLocation?data.destinationLocation:'N/A'}</span>]</div>
            <div>${data.distance?data.distance:'Distance Not Found'}</div>
          </div>
        
          <div class="col">
            <div>${data.message?data.message:'N/A'}</div>
          </div>
        
          <div class="col">
            <div class="row">
              <div class="col-1">By</div>
              <div class="col">
                <div class="dropdown">
                  <button class="btn btn-sm btn-light rounded-0 dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false" id="mode">
                    ${mode}
                  </button>
                  <ul class="dropdown-menu m-0 p-0 rounded-0">
                    <li><button class="dropdown-item btn btn-light" onclick="setMode('Driving')">Driving</button></li>
                    <li><button class="dropdown-item btn btn-light" onclick="setMode('Transit')">Transit</button></li>
                    <li><button class="dropdown-item btn btn-light" onclick="setMode('Walking')">Walking</button></li>
                  </ul>
                  <button type="button" class="btn btn-sm btn-dark rounded-0" onclick="changeLocation()">Change Your Location</button>
                </div>
              </div>
            </div>
          </div>
        
        </div>
      `);
      }

    // calculate time informations with formatting
    function getTravelTime(destinationTime, durationInSeconds, destinationDate) {
      const [time, modifier] = destinationTime.split(' ');
      let [hours, minutes] = time.split(':').map(Number);
      hours = (modifier === 'PM' && hours !== 12) ? hours + 12 : hours % 12;

      const targetDate = new Date(`${destinationDate}T${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`);
      
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

      if (latestStartTime.getDate() === now.getDate() && latestStartTime.getMonth() === now.getMonth() && latestStartTime.getFullYear() === now.getFullYear()) {
          travelDate = 'today';
      } else if (latestStartTime.getDate() === now.getDate() + 1 && latestStartTime.getMonth() === now.getMonth() && latestStartTime.getFullYear() === now.getFullYear()) {
          travelDate = 'tomorrow';
      } else {
          travelDate = departureDate;
      }

      return `You can leave ${travelDate} at ${startHours}:${startMinutes} ${startModifier}${travelDate !== 'today' && travelDate !== 'tomorrow' ? ` on ${departureDate}` : ''}.`;
    }

    // convert seconds into hours minutes
    const convertSeconds = (seconds) => {
      hours = Math.floor(seconds / 3600);
      minutes = Math.floor((seconds % 3600) / 60);
    
      hourString = hours > 0 ? `${hours} hour${hours > 1 ? 's' : ''}` : '';
      minuteString = minutes > 0 ? `${minutes} minute${minutes > 1 ? 's' : ''}` : '';
    
      return hourString && minuteString ? `${hourString} : ${minuteString}`
          : hourString || minuteString || '0 minutes';
    };

    // calculate duration between start and end time
    function calculateDuration(start, end) {
      const startDate = new Date(start);
      const endDate = new Date(end);
      const diffMs = endDate - startDate;
      const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
      const diffMins = Math.round((diffMs % (1000 * 60 * 60)) / (1000 * 60));
      return `${diffHrs}h ${diffMins}m`;
    }

    // format datetime into hours minutes
    function formatTimeUTC(dateString) {
      const utcDate = new Date(dateString);

      const options = {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: 'UTC'
      };
      return new Intl.DateTimeFormat('en-US', options).format(utcDate);
    }

    function formatTimeLocal(dateString) {
      const utcDate = new Date(dateString);

      const options = {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
      };
      return new Intl.DateTimeFormat('en-US', options).format(utcDate);
    }

    // delete recent history based on recent history's id
    function deleteRecent(recentSearchId) {
      kstych.ajax.do(
        `flighttracking/recentsearch/${recentSearchId}`,
        ``,
        `some_id`,
        randomKey(),
        'singlefail',
        'DELETE',
        ((response)=>{
          successDeleteHandler(response);
        }),
        ((xhr, status, error)=> {
          errorDeleteHandler(xhr, status, error);
        })
      );
    }

    function successDeleteHandler(response) {
      getRecentSearches();
    }

    function errorDeleteHandler(xhr, status, error) {
      console.log(xhr);
    }

    // track from search history
    function track(flightNo) {
      $("#flight_no").val(flightNo);
      //trigger the form submit, that will do all the populations again
      $('#track_form').submit();
    }

    // gets new source location and mode(from html element) and redo the travel operation only
    $("#travelForm").on('submit',(e)=>{
      e.preventDefault();
      $("#spinner").removeClass('d-none');
      sourceLocation = $("#source_location").val().trim();
      destinationLocation = $("#destination_location").text().trim();
      mode = $("#mode").text().trim();
      destinationDate = $("#departure_date").text();
      $("#travelForm").modal('hide');
      getTimeInfo(sourceLocation,destinationLocation,mode,destinationDate)
      .then(timeInfo => {
        status = 'SUCCESS';
        message = 'Succesfully fetched details';
        renderTimes(status,message,mode,timeInfo);                                                                                
      })
      .catch(error => {
        status = 'ERROR';
        renderTimes(status,error,mode,{});
      });
    })

    // open location input modal
    function changeLocation() {
      $("#travelForm").modal('show');
    }

    // change tavel mode : also gets the source location and travel mode and redo the travel, time info repopulation
    function setMode(mode) {
      $("#spinner").removeClass('d-none');
      sourceLocation = $("#source_location").val().trim();
      
      destinationLocation = $("#destination_location").text();
      destinationDate = $("#departure_date").text();
      getTimeInfo(sourceLocation,destinationLocation,mode,destinationDate)
      .then(timeInfo => {
        status = 'SUCCESS';
        message = 'Succesfully fetched details';
        renderTimes(status,message,mode,timeInfo);                                                                                
      })
      .catch(error => {
        status = 'ERROR';
        renderTimes(status,error,mode,{});
      });
    }
  </script>


<script>
  $(document).ready(function() {

    // location input suggestion
    $('#source_location').on('input', function(e) {
      const input = $(this);
      const query = input.val();
      if(query.length>0){
        $.ajax({
          method:'GET',
          url: '/flighttracking/flight/autocomplete',
          data: { query: query },
          success: function(data) {
            console.log('Address Recommendations');
            response = data.data;
            console.log(response);
  
            if (response.predictions && response.predictions.length > 0) {
              const bestMatch = response.predictions[0].description;
              // Only suggest if the best match starts with the current query
              if (bestMatch.toLowerCase().startsWith(query.toLowerCase())) {
                input.val(bestMatch);
                input[0].setSelectionRange(query.length, bestMatch.length);
              }
            }
          },
          error: function(xhr,status,error) {
            console.error('Error fetching autocomplete suggestions');
          }
        });
      }
    });
  });
</script>
@endsection
