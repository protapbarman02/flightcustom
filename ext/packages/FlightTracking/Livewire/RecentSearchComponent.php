<?php

namespace App\Custom\FlightTracking\Livewire;

use DB;
use Auth;
use Request;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class RecentSearchComponent extends Component
{

    use withPagination, WithoutUrlPagination;
    protected $paginationTheme = 'bootstrap';

    #[Computed()]
    public function recentSearches()
    {
        return kmodel('RecentSearch')::where('status', 1)
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(2);
    }

    public function delete($recentSearchId)
    {
        try {
            //delete the one and make prev latest one's status 1 based on user_id, flight_no
            $recentSearch = Kmodel('RecentSearch')::find($recentSearchId);
            if (!$recentSearch) {
                $this->dispatch('show-toastr', ['type' => 'error', 'message' => 'Recent Search Not Found']);
            } else{
                $flight_no = $recentSearch->flight_no;
                // delete
                if ($recentSearch->delete()) {
                    // update
                    $recentSearch = Kmodel('RecentSearch')::where('user_id', Auth::id())
                        ->where('flight_no', $flight_no)
                        ->where('status', 0)
                        ->whereNot('id', $recentSearchId)
                        ->orderBy('id', 'desc')
                        ->first();
    
                    if ($recentSearch) {
                        $recentSearch->status  = 1;
                        $recentSearch->save();
                    }
                    $this->dispatch('show-toastr', ['type' => 'success', 'message' => 'Successfully Removed']);
                    $this->resetPage();
                } else{
                    $this->dispatch('show-toastr', ['type' => 'error', 'message' => 'Delete Failed']);
                }
            }
        } catch (Exception $e) {
            $this->dispatch('show-toastr', ['type' => 'error', 'message' => 'Something Went Wrong']);
        }
    }

    #[On('recent-search-updated')]
    public function render()
    {
        return view('custom.component.recent-search-component');
    }


    public function retrack($recentSearch)
    {
        $this->dispatch('updateFlightNoAndTrack',$recentSearch);
    }

    public function formatFlightDate($date)
	{
	    return date('D, d M', strtotime($date));
	}
}
