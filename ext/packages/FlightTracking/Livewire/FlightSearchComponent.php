<?php

namespace App\Custom\FlightTracking\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Url;

use Cache;
use DB;
use Auth;
use Exception;
use Request;
use Validator;
use Http;

class FlightSearchComponent extends Component
{
	// #[Url(as:'s', history:true)]		//error when reload the page with url value present
	#[Rule('required|min:4|max:8', as: 'Flight Number')]
	public $flight_no = '';
	
	public $flightDetails;
	
	#[Rule('required|min:4|max:40', as: 'Your Location')]
	public $source_location;

	public $destination_location;
	public $mode='driving';

	public $predictions;

	protected $listeners = ['updateFlightNoAndTrack'];
	public function updateFlightNoAndTrack($newFlightNo)
	{
		$this->flight_no = $newFlightNo;
		$this->search();
	}

	// retireve flight information based on Flight IATA using aviationstack API
	public function search()
	{
		$this->validateOnly('flight_no');
		$flightNumber = $this->flight_no;

		/* ********************** make this commented code as normal if you do api call */
		// $key = config("custom.AVIATION_API_KEY");
		// $cacheKey = 'flightdata_' . $this->flight_no.'_'.Auth::id();
		// $data = Cache::remember($cacheKey, 1800, function () use($flightNumber, $key) {
		//   $response = Http::get("http://api.aviationstack.com/v1/flights?access_key=".$key."&flight_iata=".$flightNumber)->json();
		//   return $response;
		// });
		// if(array_key_exists('error',$data)){
		//   if($data['error']['code']!=404){
		//     $this->dispatch('show-toastr', ['type' => 'error', 'code'=>$data['error']['code'], 'message' => $data['error']['message']]);
		//   }
		//   $this->flightDetails = $data;
		//   return;
		// }

		//************************* */

		// *********************** and comment out this code */
		// // test data instead of api call
		$cacheKey = 'flightdata_' . $flightNumber . '_' . Auth::id();
		$data = Cache::remember($cacheKey, 1800, function () use ($flightNumber) {
			$jsonString = '{"pagination":{"limit":100,"offset":0,"count":2,"total":2},"data":[{"flight_date":"2024-08-28","flight_status":"scheduled","departure":{"airport":"Indira Gandhi International","timezone":"Asia\/Kolkata","iata":"DEL","icao":"' . $flightNumber . '","terminal":"3","gate":"55","delay":5,"scheduled":"2024-08-27T16:45:00+00:00","estimated":"2024-08-27T16:45:00+00:00","actual":null,"estimated_runway":null,"actual_runway":null,"location":"Indira Gandhi International Airport, New Delhi, NCT"},"arrival":{"airport":"Cochin International","timezone":"Asia\/Kolkata","iata":"COK","icao":"VOCI","terminal":"1","gate":null,"baggage":null,"delay":null,"scheduled":"2024-08-27T20:00:00+00:00","estimated":"2024-08-27T20:00:00+00:00","actual":null,"estimated_runway":null,"actual_runway":null,"location":"Cochin International Airport, Cochin, Kerala"},"airline":{"name":"Air India","iata":"AI","icao":"AIC"},"flight":{"number":"528","iata":"AB2233","icao":"AIC528","codeshared":null},"aircraft":null,"live":null},{"flight_date":"2024-08-21","flight_status":"landed","departure":{"airport":"Indira Gandhi International","timezone":"Asia\/Kolkata","iata":"DEL","icao":"VIDP","terminal":"3","gate":"42A","delay":34,"scheduled":"2024-08-21T16:45:00+00:00","estimated":"2024-08-21T16:45:00+00:00","actual":"2024-08-21T17:18:00+00:00","estimated_runway":"2024-08-21T17:18:00+00:00","actual_runway":"2024-08-21T17:18:00+00:00","location":"Indira Gandhi International Airport, New Delhi, NCT"},"arrival":{"airport":"Cochin International","timezone":"Asia\/Kolkata","iata":"COK","icao":"VOCI","terminal":"1","gate":null,"baggage":null,"delay":10,"scheduled":"2024-08-21T20:00:00+00:00","estimated":"2024-08-21T20:00:00+00:00","actual":"2024-08-21T20:10:00+00:00","estimated_runway":"2024-08-21T20:10:00+00:00","actual_runway":"2024-08-21T20:10:00+00:00","location":"Cochin International Airport, Cochin, Kerala"},"airline":{"name":"Air India","iata":"AI","icao":"AIC"},"flight":{"number":"529","iata":"AB2233","icao":"AIC528","codeshared":null},"aircraft":{"registration":"VT-CIF","iata":"A20N","icao":"A20N","icao24":"800BFB"},"live":null}]}';
			return json_decode($jsonString, true);
		});
		//**********************/

		// add Airport's full address with response
		if (isset($data['data']) && is_array($data['data'])) {
			foreach ($data['data'] as &$flight) {
				$flight['departure']['location'] = $this->getAirportLocation($flight['departure']['iata']);
				$flight['arrival']['location'] = $this->getAirportLocation($flight['arrival']['iata']);
			}
			unset($flight);
		}
		$flights = $data['data'];

		// sort by date desc
		usort($flights, function ($a, $b) {
			return strtotime($b['flight_date']) - strtotime($a['flight_date']);
		});
		$latestFlight = $flights[0];

		// populate recent search data
		$recentSearch = [
			'flight_date' => $latestFlight['flight_date'],
			'user_id' => Auth::id(),
			'flight_no' => $latestFlight['flight']['iata'],
			'airline_name' => $latestFlight['airline']['name'],
			'departure_iata' => $latestFlight['departure']['iata'],
			'arrival_iata' => $latestFlight['arrival']['iata'],
			'departure_at' => $latestFlight['departure']['actual'] ? $latestFlight['departure']['actual'] : $latestFlight['departure']['estimated'],
			'arrival_at' => $latestFlight['arrival']['actual'] ? $latestFlight['arrival']['actual'] : $latestFlight['arrival']['estimated'],
			'departure_timezone' => $latestFlight['departure']['timezone'],
			'arrival_timezone' => $latestFlight['arrival']['timezone'],
			'status' => 1,
			'last_status' => $latestFlight['departure']['actual']
				? (
					$latestFlight['arrival']['actual']
					? $latestFlight['flight_status']
					: 'departed'
				)
				: (
					$latestFlight['flight_status'] == 'cancelled'
					? $latestFlight['flight_status']
					: 'scheduled'
				)
		];

		// insert latest flight into recent search table
		$insertId = $this->insertToRecentSearch($recentSearch);
		if ($insertId == 0) {
			$this->dispatch('show-toastr', ['type' => 'error', 'message' => 'Flight information fetched successfully but failed to insert flight information into recent searches\' table']);
		}

		// disable(status=0) previous searches with same iata and user id
		$check = $this->disablePrevSearches($insertId, $latestFlight['flight']['iata'], $latestFlight['flight_date']);
		if ($check == 0) {
			$this->dispatch('show-toastr', ['type' => 'error', 'message' => 'Recent searches\' table update error']);
		}
		$this->flightDetails = $latestFlight;
		$this->destination_location = $latestFlight['departure']['location'];
		$this->dispatch('recent-search-updated', $recentSearch);
	}

	public function render()
	{
		return view('custom.component.flight-search-component');
	}


	// get Airport Location info based on Airport IATA using api-ninjas' API
	public function getAirportLocation($iata)
	{
		$url = 'https://api.api-ninjas.com/v1/airports?iata=' . $iata;


		/* ********************** make this comment out code(till line 142) as normal code if you do api call */
		// $key = config("custom.API_NINJAS_API_KEY");
		// $cacheKey = 'locationdata_' . $iata . '_' . Auth::id();
		// $data = Cache::remember($cacheKey, 1800, function () use ($iata, $key, $url) {
		//   $response = Http::withHeaders([
		//     'X-Api-Key' => $key
		//   ])->get($url)->json();
		//   return $response;
		// });

		// $location = implode(', ', [
		//   $data[0]['name'],
		//   $data[0]['city'],
		//   $data[0]['region']
		// ]);

		// return $location;
		//************************* */


		// *********************** and comment out this code */
		// test data instead of api call
		return "test location";
		//************************* */
	}

	// insert into recent_searches table
	public function insertToRecentSearch($data)
	{
		$RecentSearch = kmodel('RecentSearch');

		try {
			$RecentSearch = new $RecentSearch();
			$recentSearch = $RecentSearch->create($data);
			$lastInsertId = $recentSearch->id;
			return $lastInsertId;
		} catch (Exception $e) {
			return 0;
		}
	}

	//disable(status=0) previous searches with same iata and user id and date
	public function disablePrevSearches($id, $flightNo, $flight_date)
	{
		try {
			Kmodel('RecentSearch')::where('flight_no', $flightNo)
				->where('flight_date', $flight_date)
				->where('user_id', Auth::id())
				->whereNot('id', $id)
				->update(['status' => 0]);
			return 1;
		} catch (Exception $e) {
			return 0;
		}
	}

	public function formatFlightDate($date)
	{
	    return date('D, d M', strtotime($date));
	}

	// public function getTimeInfo()
	// {
	// 	$data = ["origin"=>$this->source_location,"destination"=>$this->destination_location,"mode"=>$this->mode];
	// 	$this->dispatch('getTimeInfo',$data);
	// 	$this->dispatch('modal-hide');
	// }
	
	public function updated($prop)
	{
		if($prop=='source_location'){
			$this->validateOnly('source_location');
			/* ********************** make this comment out code as normal code if you do api call */
			// $key = config("custom.GOOGLE_MAPS_API_KEY");
			// $source = $this->source_location;
			// $cacheKey = 'autoCompleteData_' . $source . '_' . Auth::id();
			// $data = Cache::remember($cacheKey, 3600, function () use($source,$key) {
			//   $response = Http::get("https://maps.googleapis.com/maps/api/place/autocomplete/json?input=" . $source . "&key=$key")->json();
			//    return $response;
			// });
			// $this->predictions = $data;
			// $this->predictions = array_slice($this->predictions['predictions'], 0, 5);
			/* ***************************************************************************************8 */



			// *********************** and comment out this code till line 365 */
			// test data instead of api call
			$data = '{
				"predictions": [
				  {
					"description": "Albania",
					"matched_substrings": [
					  {
						"length": 1,
						"offset": 0
					  }
					],
					"place_id": "ChIJLUwnvfM7RRMR7juY1onlfAc",
					"reference": "ChIJLUwnvfM7RRMR7juY1onlfAc",
					"structured_formatting": {
					  "main_text": "Albania",
					  "main_text_matched_substrings": [
						{
						  "length": 1,
						  "offset": 0
						}
					  ]
					},
					"terms": [
					  {
						"offset": 0,
						"value": "Albania"
					  }
					],
					"types": [
					  "geocode",
					  "country",
					  "political"
					]
				  },
				  {
					"description": "Australia",
					"matched_substrings": [
					  {
						"length": 1,
						"offset": 0
					  }
					],
					"place_id": "ChIJ38WHZwf9KysRUhNblaFnglM",
					"reference": "ChIJ38WHZwf9KysRUhNblaFnglM",
					"structured_formatting": {
					  "main_text": "Australia",
					  "main_text_matched_substrings": [
						{
						  "length": 1,
						  "offset": 0
						}
					  ]
					},
					"terms": [
					  {
						"offset": 0,
						"value": "Australia"
					  }
					],
					"types": [
					  "geocode",
					  "country",
					  "political"
					]
				  },
				  {
					"description": "Argentina",
					"matched_substrings": [
					  {
						"length": 1,
						"offset": 0
					  }
					],
					"place_id": "ChIJZ8b99fXKvJURqA_wKpl3Lz0",
					"reference": "ChIJZ8b99fXKvJURqA_wKpl3Lz0",
					"structured_formatting": {
					  "main_text": "Argentina",
					  "main_text_matched_substrings": [
						{
						  "length": 1,
						  "offset": 0
						}
					  ]
					},
					"terms": [
					  {
						"offset": 0,
						"value": "Argentina"
					  }
					],
					"types": [
					  "geocode",
					  "country",
					  "political"
					]
				  },
				  {
					"description": "Aruba",
					"matched_substrings": [
					  {
						"length": 1,
						"offset": 0
					  }
					],
					"place_id": "ChIJ23da4s84hY4RL4yBiT6KavE",
					"reference": "ChIJ23da4s84hY4RL4yBiT6KavE",
					"structured_formatting": {
					  "main_text": "Aruba",
					  "main_text_matched_substrings": [
						{
						  "length": 1,
						  "offset": 0
						}
					  ]
					},
					"terms": [
					  {
						"offset": 0,
						"value": "Aruba"
					  }
					],
					"types": [
					  "geocode",
					  "country",
					  "political"
					]
				  },
				  {
					"description": "Austria",
					"matched_substrings": [
					  {
						"length": 1,
						"offset": 0
					  }
					],
					"place_id": "ChIJfyqdJZsHbUcRr8Hk3XvUEhA",
					"reference": "ChIJfyqdJZsHbUcRr8Hk3XvUEhA",
					"structured_formatting": {
					  "main_text": "Austria",
					  "main_text_matched_substrings": [
						{
						  "length": 1,
						  "offset": 0
						}
					  ]
					},
					"terms": [
					  {
						"offset": 0,
						"value": "Austria"
					  }
					],
					"types": [
					  "geocode",
					  "country",
					  "political"
					]
				  }
				],
				"status": "OK"
			}';
			$this->predictions = json_decode($data,true);
		}
	}

	public function updateSourceLocation($location)
	{
		$this->source_location = $location;
	}
}
