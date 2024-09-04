<?php namespace App\Custom\FlightTracking\Controller;

use DB;
use Auth;
use Request;
class Home
{
    public function __construct(){}

    public function index()
    {
      return view("custom.FlightTracking.Home.index");
    }
}