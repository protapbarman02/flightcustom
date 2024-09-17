<?php namespace App\Custom\FlightTracking\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;

use DB;
use Auth;
use Request;
use Http;
use Cache;

class DistanceComponent extends Component
{
    // retrieve distance and duration based on origin,destination and mode with Google's Matrix API
    // use validation for origins, destinations, mode
    // need error handling response

    public $travelData;
    public $mode;

    #[On('getTimeInfo')]
    public function getTimeInfo($data)
    {
      $origins = $data['origin'];
      $destinations = $data['destination'];
      $this->mode = $data['mode'];
      
      
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
      $this->travelData=json_decode($data, true);
    }

    public function render()
    {
      return view('custom.component.distance-component');
    }
}