<?php
$b=new \App\Kstych\Browser\KChrome();
// login
$b->visit('http://localhost/');
$b->runjs(
    '$("#loginusername").val("admin");$("#loginpassword").val("yb9738z");$(".btn.bg-blue.btn-block.btn-ladda.btn-ladda-progress.ladda-button").click();'
);
sleep(2);

/* the page contains a track form and list of recent searches */
$b->visit('http://localhost/flighttracking/home');
sleep(1);
// // screenshot of full page
$png=$b->screenshot();
echo "<img src='data:image/png;base64,".base64_encode($png)."' width=100%>";

$track_form = $b->findElement('track_form','id');
if ($track_form->isDisplayed()) {
    echo "SUCCESS : track form is visible"."</br>";
} else{
    echo "FAILURE : track form is not visible";
}
// // screenshot of track form
$track_form_png = $track_form->takeElementScreenshot();
echo "<img src='data:image/png;base64,".base64_encode($track_form_png)."' width=100%>";

// recent searches container includes list of recent searches, if no recent searches available then shows 'no recent searches available'
$recent_searches_container = $b->findElement('recent_searches_container','id');
if ($recent_searches_container->isDisplayed()) {
    echo "SUCCESS : recent searches container is visible"."</br>";
} else{
    echo "FAILURE : recent searches container is not visible"."</br>";
}
// // screenshot of recent search container
$recent_searches_container_png = $recent_searches_container->takeElementScreenshot();
echo "<img src='data:image/png;base64,".base64_encode($recent_searches_container_png)."' width=100%>";

/* track form submission invalid +  spinner visibility check*/
// check validation error message showing when invalid input(less then 4 characters) is given as flight number
$b->runjs(
    '$("#flight_no").val("abc");$("#track_form_submit_btn").click();'
);
// spinner is visible right after clicking on submit
$track_form_submit_spinner = $b->findElement('spinner','id');
if ($track_form_submit_spinner->isDisplayed()) {
    echo "SUCCESS : Spinner is visible"."</br>";
} else{
    echo "FAILURE : Spinner  is not visible"."</br>";
}
sleep(2);
echo "data loaded"."</br>";
// spinner is not visible after loading data
$track_form_submit_spinner = $b->findElement('spinner','id');
if ($track_form_submit_spinner->isDisplayed()) {
    echo "FAILURE : Spinner is visible"."</br>";
} else{
    echo "SUCCESS : Spinner  is not visible"."</br>";
}

$flight_error_msg = $b->findElement('flight-error','id');
if ($flight_error_msg->isDisplayed()) {
    echo "SUCCESS : Flight input error message is visible"."</br>";
} else{
    echo "FAILURE : Flight input error message  is not visible";
}
// // screenshot of Flight input error message
$flight_error_msg_png = $flight_error_msg->takeElementScreenshot();
echo "<img src='data:image/png;base64,".base64_encode($flight_error_msg_png)."' width=100%>";

/* track form submission valid*/
// track form submission with valid flight number should populate the date and flight container and the flight container should contain text identical to input value
$b->runjs(
    '$("#flight_no").val("AI528");$("#track_form_submit_btn").click();'
);
sleep(2);
// dates container
$dates = $b->findElement('dates','id');
if ($dates->getText()!="") {
    echo "SUCCESS : Flight details container have data"."</br>";
    // // screenshot of Dates
    $dates_png = $dates->takeElementScreenshot();
    echo "<img src='data:image/png;base64,".base64_encode($dates_png)."' width=100%>";
} else{
    echo "FAILURE : Dates container does not have data"."<br>";
}
// flight details container
$flight_details = $b->findElement('flight-details','id');
if ($flight_details->getText()!="") {
    echo "SUCCESS : Flight details container have data"."</br>";
    if(str_contains($flight_details->getText(),"AI528")){
        echo "SUCCESS : Flight details contains flight information relevant to the search";
    } else{
        echo "FAILURE : Flight details contains irrelevant data";
    }
    // // screenshot of Flight details
    $flight_details_png = $flight_details->takeElementScreenshot();
    echo "<img src='data:image/png;base64,".base64_encode($flight_details_png)."' width=100%>";
} else{
    echo "FAILURE : Flight details container does not have data"."<br>";
}

// flight details container : departure iata on top
$flight_departure_iata_top = $b->findElement('flight_departure_iata_top','id');
if ($flight_departure_iata_top->getText()!="") {
    echo "SUCCESS : contains flight departure iata"."</br>";
    // // screenshot 
    $flight_departure_iata_top_png = $flight_departure_iata_top->takeElementScreenshot();
    echo "<img src='data:image/png;base64,".base64_encode($flight_departure_iata_top_png)."' width=50px>";
} else{
    echo "FAILURE : does not contain flight departure iata"."<br>";
}
// flight details container : arrival iata on top
$flight_arrival_iata_top = $b->findElement('flight_arrival_iata_top','id');
if ($flight_arrival_iata_top->getText()!="") {
    echo "SUCCESS : contains flight arrival iata"."</br>";
    // // screenshot
    $flight_arrival_iata_top_png = $flight_arrival_iata_top->takeElementScreenshot();
    echo "<img src='data:image/png;base64,".base64_encode($flight_arrival_iata_top_png)."' width=50px>";
} else{
    echo "FAILURE : does not contain flight arrival iata"."<br>";
}

// flight details container : destination full location
$destination_location = $b->findElement('destination_location','id');
if ($destination_location->getText()!="" || $destination_location->getText()!="N/A") {
    echo "SUCCESS : contains flight destination location"."</br>";
    // // screenshot 
    $destination_location_png = $destination_location->takeElementScreenshot();
    echo "<img src='data:image/png;base64,".base64_encode($destination_location_png)."' width=100%>";
} else{
    echo "FAILURE : does not contain flight destination location"."<br>";
}

// flight details container : arrival full location
$arrival_location = $b->findElement('arrival_location','id');
if ($arrival_location->getText()!="" || $arrival_location->getText()!="N/A") {
    echo "SUCCESS : contains flight arrival location"."</br>";
    // // screenshot 
    $arrival_location_png = $arrival_location->takeElementScreenshot();
    echo "<img src='data:image/png;base64,".base64_encode($arrival_location_png)."' width=100%>";
} else{
    echo "FAILURE : does not contain flight arrival location"."<br>";
}

sleep(2);
// time details container       //unable to trigger geo-location
$time_details = $b->findElement('time-details','id');
if ($time_details->getText()!="") {
    echo "SUCCESS : Time details container have data"."</br>";
    // // screenshot of Flight details
    $time_details_png = $time_details->takeElementScreenshot();
    echo "<img src='data:image/png;base64,".base64_encode($time_details_png)."' width=100%>";
} else{
    echo "FAILURE : Time details container does not have data"."<br>";
}

// as flight was searched previously in this test, recent search data is available
/* test on recent search container */
// retrack button test : should change the track_form flight_no value to this re track items' flight_no
$recent_searches_retrack_buttons = $b->findElements('retrack_buttons','className');       // if it throws error, than recent search data is not visible even after tracking a flight
$top_recent_searches_retrack_button_id = $recent_searches_retrack_buttons[0]->getAttribute('id');

$parts = explode('_', $top_recent_searches_retrack_button_id);
$top_recent_search_id_number = end($parts);
$top_recent_search_flight_no_id = "recent_search_flight_no_".$top_recent_search_id_number; 
$top_recent_search_flight_no = $b->findElement("$top_recent_search_flight_no_id","id")->getText();
$b->runjs(
    '$("#'.$top_recent_searches_retrack_button_id.'").click();'
);
$track_form_flight_no_value = $b->findElement('flight_no','id')->getAttribute('value');
if($track_form_flight_no_value !== $top_recent_search_flight_no){
    echo "FAILURE: retrack button not updating track form's flight no, hence wrong flight_no will be submitted"."</br>";
}
else{
    echo "SUCCESS: retrack button properly updating track form's flight no"."</br>";
}

// delete functionality test
$recent_searches = $b->findElements('delete_recent_buttons','className');       // if it throws error, than recent search data is not visible even after re tracking a flight
$top_recent_search_id = $recent_searches[0]->getAttribute('id');

$parts = explode('_', $top_recent_search_id);
$top_recent_search_id_number = end($parts);
$top_recent_search_row_id = "recent_search_row_".$top_recent_search_id_number;      //getting the row id
$b->runjs(
    '$("#'.$top_recent_search_id.'").click();'
);
sleep(2);
try{
    $deleted_row = $b->findElement("$top_recent_search_row_id","id");
    echo "FAILURE : deleted row is still visible"."</br>";
} catch (Exception $e){
    // echo $e->getMessage();
    echo "SUCCESS : deleted row is not visible"."</br>";
}