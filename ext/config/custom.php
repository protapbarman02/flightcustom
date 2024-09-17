<?php
return [
  "AVIATION_API_KEY"=>env("AVIATION_API_KEY"),
  "API_NINJAS_API_KEY"=>env("API_NINJAS_API_KEY"),
  "GOOGLE_MAPS_API_KEY"=>env("GOOGLE_MAPS_API_KEY"),
  'livewire' => [
    'components' => [
      "RecentSearchComponent" => '\App\Custom\FlightTracking\Livewire\RecentSearchComponent',
      "FlightSearchComponent" => '\App\Custom\FlightTracking\Livewire\FlightSearchComponent',
      "DistanceComponent" => '\App\Custom\FlightTracking\Livewire\DistanceComponent',
    ]
  ],
];