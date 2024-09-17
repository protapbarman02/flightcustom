# DO NOT FOLLOW THIS NOW, PROJECT IS GETTING UPDATED TO LIVEWIRE, WILL UPDATE SOON.
# Flight Tracking Custom

Test summaries are returned instead of actual API calls to Aviationstack, Google Maps, and API Ninjas.

## To Make the API Calls

### Flight Controller

Head over to `custom/packages/flightcontroller/controller/flight` and follow the instructions on the following lines:

- Lines 34, 44
- Lines 126, 146
- Lines 195, 205

### Distance Controller

Head over to `custom/packages/flightcontroller/controller/distance` and follow the instructions on the following lines:

- Lines 38, 46

## If Any API Call Fails

If any API call fails, generate a new API key and update it in the `.env` file.
