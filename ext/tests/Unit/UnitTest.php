<?php

namespace Tests\Custom\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Custom\FlightTracking\Controller\Flight;
use App\Custom\FlightTracking\Controller\Distance;
use App\Custom\FlightTracking\Controller\RecentSearch;

class UnitTest extends TestCase
{
  public function test_index_valid_flight_number()
  {
    $flightNumber = 'AI528';
    request()->merge(['flight_no' => $flightNumber]);

    $flight = new Flight();
    $response = $flight->index();

    $this->assertEquals(200, $response->status());
  }

  public function test_index_invalid_flight_number()
  {
    $flightNumber = 'AI';
    request()->merge(['flight_no' => $flightNumber]);

    $flight = new Flight();
    $response = $flight->index();

    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_getAirportLocation_returns_correct_location()
  {
    $flight = new Flight();
    $location = $flight->getAirportLocation('DEL');
    $this->assertEquals('test location', $location);
  }

  // Test insertToRecentSearch method
  // insert returns 0 if failed, else returns insertId
  public function test_insertToRecentSearch_returns_non_0()
  {
    $flight = new Flight();

    $recentSearch = [
      'flight_date' => '2024-08-28',
      'user_id' => 1,
      'flight_no' => 'AI528',
      'airline_name' => 'abcd',
      'departure_iata' => 'JFK',
      'arrival_iata' => 'LAX',
      'departure_at' => '2024-08-27T16:45:00+00:00',
      'arrival_at' => '2024-08-27T20:45:00+00:00',
      'departure_timezone' => 'Asia/Kolkata',
      'arrival_timezone' => 'Asia/Kolkata',
      'status' => 1,
      'last_status' => 'scheduled'
    ];

    $result = $flight->insertToRecentSearch($recentSearch);

    $this->assertNotSame(0, $result);
  }

  // Test insertToRecentSearch method
  // insert returns 0 if failed, else returns insertId
  public function test_insertToRecentSearch_returns_0_for_invalid_data()
  {
    $flight = new Flight();

    $recentSearch = [
      'user_id' => -1,
    ];

    $result = $flight->insertToRecentSearch($recentSearch);

    $this->assertSame(0, $result);
  }

  public function test_disablePrevSearches_returns_1_on_success()
  {
    $flight = new Flight();
    $result = $flight->disablePrevSearches(1, 'AI528', '2024-08-28');

    $this->assertSame(1, $result);
  }
}
