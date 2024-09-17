<?php namespace App\Custom\FlightTracking\Controller;

use DB;
use Auth;
use Request;
class FlightTracking
{
    public function index()
    {
        return view('custom.index');
    }
}