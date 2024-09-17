@extends('custom.layout')
@section('content')
<div class="main-content">
    <div class="container mt-4">
        @livewire("FlightSearchComponent")
        @livewire("RecentSearchComponent")
    </div>
</div>
@endsection