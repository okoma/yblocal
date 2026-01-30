<?php
namespace App\Http\Controllers;

use App\Http\Requests\SavedBusinessRequest;
use App\Models\Business;
use App\Models\SavedBusiness;
use Illuminate\Support\Facades\Auth;

class SavedBusinessController extends Controller
{
    public function store(SavedBusinessRequest $request, $businessType, $slug)
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        $saved = SavedBusiness::firstOrCreate([
            'user_id' => $user->id,
            'business_id' => $business->id,
        ]);

        return redirect()->back()->with('status', __('Business saved.'));
    }

    public function destroy($businessType, $slug)
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        SavedBusiness::where('user_id', $user->id)->where('business_id', $business->id)->delete();

        return redirect()->back()->with('status', __('Business removed from saved list.'));
    }
}
