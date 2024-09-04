<?php

namespace Tests\Custom\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Custom\FlightTracking\Controller\Flight;
use App\Custom\FlightTracking\Controller\Distance;
use App\Custom\FlightTracking\Controller\RecentSearch;
use Cache;

class IntegrationTest extends TestCase
{
 
  // // add access to users (NOT A TEST) (RUN ONLY ONCE WHEN INITIALIZING TEST OR IF TEST DB IS RESET)
  // public function add_access_for_flighttracking()
  // {
  //   $adminrole = kmodel("Role")::where('name','Admin')->first();

  //   $flightmodule =  kmodel("RoleModule")::where("module", "FlightTracking")->where("role_id", $adminrole->id)->first();

  //   if (!$flightmodule) {
  //     $flightmodule = kmodel("RoleModule")::make();
  //     $flightmodule->role_id = $adminrole->id;
  //     $flightmodule->module = "FlightTracking";
  //   }
  //   $flightmodule->acl = 'Admin';

  //   $flightmodule->setData("submodules", "Home,RecentSearch,Flight,Distance", false);
  //   $flightmodule->save();
  //   // dump(auth()->user()->roles()->where('name','Admin')->first()->modules()->get()->toArray());

  // }

  // perform login operation (required for authenticated routes but NOT A INDIVIDUAL TEST for flighttracking)
  public function login_redirect_from($url)
  {
    $response = $this->get($url);
    $this->assertContains($response->status(), [302]);
    \Auth::login(\App\Models\User::find(1));
    // $this->add_access_for_flighttracking();
  }

  public function test_flighttracking_home_route_returns_success_response_and_correct_view()
  {
    $url = '/flighttracking/home';
    $this->login_redirect_from($url);

    $response = $this->get($url);
    $response->assertStatus(200);
    $response->assertViewIs('custom.FlightTracking.Home.index');
  }
  
  public function test_flight_api_returns_validation_error_when_flight_no_is_not_provided()
  {
    $url = '/flighttracking/flight?flight_no=';
    $this->login_redirect_from($url);
    
    $response = $this->get($url);
    $response->assertStatus(422)->assertJsonValidationErrors(['flight_no']);
  }

  public function test_flight_api_returns_validation_error_when_flight_no_is_invalid()
  {
    $url = '/flighttracking/flight?flight_no=abc';
    $this->login_redirect_from($url);
    
    $response = $this->get($url);
    $response->assertStatus(422)->assertJsonValidationErrors(['flight_no']);
  }

  public function test_flight_api_returns_successfull_message_when_flight_no_is_valid()
  {
    $url = '/flighttracking/flight?flight_no=AI528';
    $this->login_redirect_from($url);
    
    $response = $this->get($url);
    $response->assertStatus(200)->assertJson([
      'message' => 'Successfully fetched flight information'
    ]);
  }

  public function test_successfull_flight_response_contains_airport_location()
  {
    $url = '/flighttracking/flight?flight_no=AI528';
    $this->login_redirect_from($url);
    
    $response = $this->get($url);
    $data = $response->json('data.data');
    $this->assertEquals('test location', $data[0]['departure']['location']);
    $this->assertEquals('test location', $data[0]['arrival']['location']);
  }


  // // problem with mock/////////////////////////////////////////////////////////////////////////////////
  // public function test_returns_sucessfull_flight_response_but_with_insertion_error_if_insertion_fails()
  // {
  //   $this->mock(Flight::class, function ($mock) {
  //       $mock->shouldReceive('insertToRecentSearch')->andReturn(0);
  //   });

  //   $url = '/flighttracking/flight?flight_no=AI528';
  //   $this->login_redirect_from($url);
    
  //   $response = $this->get($url);

  //   $this->assertEquals('ERROR', $response->json('status'));
  //   $this->assertEquals('INSERT_ERROR', $response->json('type'));
  // }


  // combine with backend logic for insert and update recent search
  public function test_disables_previous_searches_with_same_iata_and_user_id_and_date()
  {
    $flight = new Flight();

    $flight_id = $flight->insertToRecentSearch([
        'flight_no' => 'AI528',
        'user_id' => 1,
        'status' => 1,
        'flight_date'=>'2024-08-28'
    ]);

    $url = '/flighttracking/flight?flight_no=AI528';
    $this->login_redirect_from($url);
    
    $this->get($url);
    // static data will be inserted same_iata, user_id and date, hence the previous inserted data should have status 0

    $this->assertDatabaseHas('recent_searches', [
      'id'=>$flight_id,
      'status' => 0
    ]);
  }

  // // problem with mock///////////////////////////////////////////////////////////////////////////////
  // public function test_returns_sucessfull_flight_response_but_with_updation_error_if_updation_fails()
  // {
  //   $this->mock(Flight::class, function ($mock) {
  //       $mock->shouldReceive('disablePrevSearches')->andReturn(0);
  //   });

  //   $url = '/flighttracking/flight?flight_no=AI528';
  //   $this->login_redirect_from($url);
    
  //   $response = $this->get($url);

  //   $this->assertEquals('ERROR', $response->json('status'));
  //   $this->assertEquals('UPDATE_ERROR', $response->json('type'));
  // }


  public function test_flighttracking_flight_route_full_feature_returns_success_response_with_successfull_insertion_and_updation()
  {
    $url = '/flighttracking/flight?flight_no=AI528';
    $this->login_redirect_from($url);

    $response = $this->get($url);
    $response->assertStatus(200);
    $this->assertEquals('SUCCESS', $response->json('status'));
    $this->assertEquals('', $response->json('type'));
    $this->assertEquals('Successfully fetched flight information', $response->json('message'));
    $this->assertIsArray($response->json('data'), 'Data should be an array');
  }


  public function test_flighttracking_recent_search_route_returns_success_response()
  {
    $url = '/flighttracking/recentsearch';
    $this->login_redirect_from($url);

    $response = $this->get($url);
    $response->assertStatus(200);
  }

  public function test_flighttracking_recent_search_route_returns_success_response_and_recent_search_data()
  {
    $flight = new Flight();

    $flight->insertToRecentSearch([
        'flight_no' => 'AI528',
        'user_id' => 1,
        'status' => 1,
        'flight_date'=>'2024-08-28',
    ]);

    $url = '/flighttracking/recentsearch';
    $this->login_redirect_from($url);

    $response = $this->get($url);
    
    $data = $response->json();
    $this->assertIsArray($data);
    $this->assertNotEmpty($data);
  }

  public function test_recent_search_destroy_route_returns_status_code_404_upon_recent_search_id_not_found()
  {
    $url = '/flighttracking/recentsearch';
    $this->login_redirect_from($url);

    $response = $this->deleteJson('/flighttracking/recentsearch/99999999');
    $response->assertStatus(404);
  }

  public function test_recent_search_destroy_route_deletes_search_and_updates_previous_search()
  {
    $flight = new Flight();

    $flight1_id = $flight->insertToRecentSearch([
        'flight_no' => 'AI528',
        'user_id' => 1,
        'status' => 0,
        'flight_date'=>'2024-08-28',
    ]);

    $flight2_id = $flight->insertToRecentSearch([
      'flight_no' => 'AI528',
      'user_id' => 1,
      'status' => 1,
      'flight_date'=>'2024-08-28',
    ]);
  
    $url = '/flighttracking/recentsearch/';
    $this->login_redirect_from($url);

    $response = $this->deleteJson('/flighttracking/recentsearch/'.$flight2_id);
    $response->assertStatus(200);

    $this->assertDatabaseHas('recent_searches', [
        'id' => $flight1_id,
        'status' => 1,
    ]);

    $this->assertSoftDeleted('recent_searches', [
        'id' => $flight2_id,
    ]);
  }


  public function test_returns_distance_and_duration_successfull_data_with_valid_inputs()
  {
    $url = '/flighttracking/distance?origins=New+Delhi&destinations=Mumbai&mode=driving';
    $this->login_redirect_from($url);
    
    $response = $this->get($url);
    $response->assertStatus(200);
    $this->assertIsArray($response->json('data'));
  }

  public function test_distance_and_duration_returns_error_when_missing_required_query_parameters()
  {
    $url = '/flighttracking/distance?origins=&mode=driving';
    $this->login_redirect_from($url);

    $response = $this->get($url);
    $response->assertStatus(400);
  }

  public function test_autocomplete_features_show_route_returns_error_when_id_is_not_autocomplete()
  {
    $url = '/flighttracking/flight/NotAutocomplete';
    $this->login_redirect_from($url);

    $response = $this->get($url);
    $response->assertStatus(400);
    $response->assertJson([
      'status' => 400,
      'status_code' => 'ERROR',
      'data' => [],
      'message' => 'Route not found. The ID must be "autocomplete".'
    ]);
  }


  public function test_autocomplete_features_show_route_returns_success_when_id_is_autocomplete()
  {
    $url = '/flighttracking/flight/autocomplete';
    $this->login_redirect_from($url);

    $response = $this->get($url);
    $response->assertStatus(200);
    $response->assertJson([
      'status' => 200,
      'status_code' => 'SUCCESS',
      'data' => []
    ]);
  }
}