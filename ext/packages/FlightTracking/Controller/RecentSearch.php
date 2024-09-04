<?php namespace App\Custom\FlightTracking\Controller;

use DB;
use Auth;
use Request;
class RecentSearch
{
  public function __construct(){}

  public function index()
  {
    $recentSearches = kmodel('RecentSearch')::where('status',1)
      ->where('user_id',Auth::id())
      ->orderBy('created_at','desc')
      ->get();
    return response()->json($recentSearches);
  }

  public function destroy($id)
  {
    try {
          //delete the one and make prev latest one's status 1 based on user_id, flight_no
    $recentSearch = Kmodel('RecentSearch')::find($id);
      if (!$recentSearch) {
        return response()->json([
          'status' => 'error',
          'status_code' => 404,
          'message' => 'Search not found'
        ],404);
      }
  
      $flight_no = $recentSearch->flight_no;
  
      // delete
      if ($recentSearch->delete()) {
        // update
        $recentSearch = Kmodel('RecentSearch')::where('user_id', Auth::id())
          ->where('flight_no', $flight_no)
          ->where('status', 0)
          ->whereNot('id', $id)
          ->orderBy('id','desc')
          ->first();
  
        if ($recentSearch) {
          $recentSearch->status  = 1;
          $recentSearch->save();
        }
  
        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'Search deleted successfully'
        ],200);
      }
    } catch (Exception $e) {
      return $e;
    }


    

  }
}