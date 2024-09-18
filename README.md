# Flight Tracking Custom

Test summaries are returned instead of actual API calls to Aviationstack, Google Maps, and API Ninjas.

## To Make the API Calls

### Flight Controller

Head over to `custom/packages/flightcontroller/controller/flight` and follow the instructions on the following lines:

- Lines 47, 64
- Lines 143, 163
- Lines 215, 229

### Distance Controller

Head over to `custom/packages/flightcontroller/controller/distance` and follow the instructions on the following lines:

- Lines 33, 42

## If Any API Call Fails

If any API call fails, generate a new API key and update it in the `.env` file.
