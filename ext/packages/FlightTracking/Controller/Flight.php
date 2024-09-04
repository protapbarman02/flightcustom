<?php

namespace App\Custom\FlightTracking\Controller;

use Cache;
use DB;
use Auth;
use Exception;
use Request;
use Validator;
use Http;

class Flight
{
  public function __construct() {}

  // retireve flight information based on Flight IATA using aviationstack API
  public function index()
  {
    $status = 'SUCCESS';
    $message = 'Successfully fetched flight information';
    $type = '';

    $flightNumber = request()->query->get('flight_no');

    $validator = Validator::make(['flight_no' => $flightNumber], [
      'flight_no' => 'required|string|min:4', // Adjust validation rules as needed
    ]);

    // Check if validation fails
    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    /* ********************** make this comment out as normal code if you do api call */
    // $key = config("custom.AVIATION_API_KEY");

    // $cacheKey = 'flightdata_' . $flightNumber.'_'.Auth::id();
    // $data = Cache::remember($cacheKey, 1800, function () use($flightNumber, $key) {
    //   $response = Http::get("http://api.aviationstack.com/v1/flights?access_key=".$key."&flight_iata=".$flightNumber)->json();
    //   return $response;
    // });
    //************************* */

    // *********************** and comment out this code */
    // // test data instead of api call
    $cacheKey = 'flightdata_' . $flightNumber.'_'.Auth::id();
    $data = Cache::remember($cacheKey, 1800, function () use($flightNumber) {
      $jsonString = '{"pagination":{"limit":100,"offset":0,"count":2,"total":2},"data":[{"flight_date":"2024-08-28","flight_status":"scheduled","departure":{"airport":"Indira Gandhi International","timezone":"Asia\/Kolkata","iata":"DEL","icao":"'.$flightNumber.'","terminal":"3","gate":"55","delay":5,"scheduled":"2024-08-27T16:45:00+00:00","estimated":"2024-08-27T16:45:00+00:00","actual":null,"estimated_runway":null,"actual_runway":null,"location":"Indira Gandhi International Airport, New Delhi, NCT"},"arrival":{"airport":"Cochin International","timezone":"Asia\/Kolkata","iata":"COK","icao":"VOCI","terminal":"1","gate":null,"baggage":null,"delay":null,"scheduled":"2024-08-27T20:00:00+00:00","estimated":"2024-08-27T20:00:00+00:00","actual":null,"estimated_runway":null,"actual_runway":null,"location":"Cochin International Airport, Cochin, Kerala"},"airline":{"name":"Air India","iata":"AI","icao":"AIC"},"flight":{"number":"528","iata":"AI528","icao":"AIC528","codeshared":null},"aircraft":null,"live":null},{"flight_date":"2024-08-21","flight_status":"landed","departure":{"airport":"Indira Gandhi International","timezone":"Asia\/Kolkata","iata":"DEL","icao":"VIDP","terminal":"3","gate":"42A","delay":34,"scheduled":"2024-08-21T16:45:00+00:00","estimated":"2024-08-21T16:45:00+00:00","actual":"2024-08-21T17:18:00+00:00","estimated_runway":"2024-08-21T17:18:00+00:00","actual_runway":"2024-08-21T17:18:00+00:00","location":"Indira Gandhi International Airport, New Delhi, NCT"},"arrival":{"airport":"Cochin International","timezone":"Asia\/Kolkata","iata":"COK","icao":"VOCI","terminal":"1","gate":null,"baggage":null,"delay":10,"scheduled":"2024-08-21T20:00:00+00:00","estimated":"2024-08-21T20:00:00+00:00","actual":"2024-08-21T20:10:00+00:00","estimated_runway":"2024-08-21T20:10:00+00:00","actual_runway":"2024-08-21T20:10:00+00:00","location":"Cochin International Airport, Cochin, Kerala"},"airline":{"name":"Air India","iata":"AI","icao":"AIC"},"flight":{"number":"529","iata":"AI529","icao":"AIC528","codeshared":null},"aircraft":{"registration":"VT-CIF","iata":"A20N","icao":"A20N","icao24":"800BFB"},"live":null}]}';
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

    // // insert latest flight into recent search table
    $insertId = $this->insertToRecentSearch($recentSearch);
    if($insertId==0){
      $status = 'ERROR';
      $type = 'INSERT_ERROR';
      $message = 'Flight information fetched successfully but failed to insert flight information into recent searches\' table';
    }

    // disable(status=0) previous searches with same iata and user id
    $check = $this->disablePrevSearches($insertId,$latestFlight['flight']['iata'],$latestFlight['flight_date']);
    if($check==0){
      $status = 'ERROR';
      $type = 'UPDATE_ERROR';
      $message = 'Recent searches\' table update error';
    }

    $response = [
      'status' => $status,
      'type' => $type,
      'data' => $data,
      'message' => $message
    ];
    return response()->json($response);
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
  public function disablePrevSearches($id, $flight_no, $flight_date)
  {
    try {
      Kmodel('RecentSearch')::where('flight_no', $flight_no)
        ->where('flight_date', $flight_date)
        ->where('user_id', Auth::id())
        ->whereNot('id', $id)
        ->update(['status' => 0]);
      return 1;
    } catch (Exception $e) {
      return 0;
    }
  }

  public function show($id)
  {
    if ($id !== "autocomplete") {
      return response()->json([
        'status' => 400,
        'status_code' => 'ERROR',
        'data' => [],
        'message' => 'Route not found. The ID must be "autocomplete".'
    ], 400);
    } else {
      $query = urlencode(Request::get('query'));
      
      /* ********************** make this comment out code as normal code if you do api call */
      // $key = config("custom.GOOGLE_MAPS_API_KEY");
      // $cacheKey = 'autoCompleteData_' . $query . '_' . Auth::id();
      // $data = Cache::remember($cacheKey, 3600, function () use($query,$key) {
      //   $response = Http::get("https://maps.googleapis.com/maps/api/place/autocomplete/json?input=" . $query . "&key=$key")->json();
      //    return $response;
      // });
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
/* ***************************************************************************** */
return response()->json([
  'status' => 200,
  'status_code' => 'SUCCESS',
  'data' => json_decode($data, true)
], 200);
    }
  }
}
