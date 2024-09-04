<?php namespace App\Custom\FlightTracking\Controller;

use DB;
use Auth;
use Request;
use Http;
use Cache;

class Distance
{
    public function __construct(){}

    // retrieve distance and duration based on origin,destination and mode with Google's Matrix API
    // use validation for origins, destinations, mode
    // need error handling response
    public function index()
    {
      
      $origins = request()->query('origins');
      $destinations = request()->query('destinations');
      $mode = request()->query('mode');
      
      if (empty($origins) || empty($destinations) || empty($mode)) {
        return response()->json([
          'status' => 400,
          'status_code' => 'ERROR',
          'data' => [],
          'message' => 'origins, destinations, and mode are required parameters and cannot be empty.'
        ], 400);
      }
  
      $origins = urlencode($origins);
      $destinations = urlencode($destinations);
      $mode = strtolower(urlencode($mode));
      
      
      /* ********************** make this comment out as normal code if you do api call */
      // $apiKey = config("custom.GOOGLE_MAPS_API_KEY");
      // $cacheKey = 'distancedata_origins_' . $origins.'_destinations_'.$destinations.'_mode'.$mode.'_'.Auth::id();
      // $data = Cache::remember($cacheKey, 1800, function () use ($origins, $destinations, $mode, $apiKey) {
      //   $response = Http::get("https://maps.googleapis.com/maps/api/distancematrix/json?origins=$origins&destinations=$destinations&mode=$mode&key=$apiKey")->json();
      //   return $response;
      // });
      /* ******************************************************************************* */

      // // test data 1 instead of api call: available result
      /* *******************************************************************and comment out this code */
      $data='{"destination_addresses": ["'.$destinations.'"],"origin_addresses": ["'.$origins.'"],"rows": [{"elements": [{"distance": {"text": "1,512 km","value": 1512278},"duration": {"text": "1 day 1 hour","value": 88354},"status": "OK"}]}],"status": "OK"}';
      /* ******************************************************************* */
      
      
      return response()->json([
        'status' => 200,
        'status_code' => 'SUCCESS',
        'data' => json_decode($data, true),
        'message' => '',
      ], 200);
    }
}