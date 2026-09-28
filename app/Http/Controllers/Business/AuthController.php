<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\District;
use App\Models\Thana;
use App\Models\Union;
use App\Services\DealerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('dealer')->check()) {
            return redirect()->route('business.dashboard');
        }

        return view('business.login');
    }

    public function login(Request $request)
    {
        $request->merge(['phone' => Dealer::normalizePhone($request->input('phone'))]);
        $request->validate(['phone' => 'required|string', 'password' => 'required|string']);

        $throttleKey = 'dealer-login:' . Str::lower($request->phone) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'phone' => 'Too many login attempts. Please try again in ' . RateLimiter::availableIn($throttleKey) . ' seconds.',
            ]);
        }

        $dealer = Dealer::where('phone', $request->phone)->first();
        if (! $dealer || ! Auth::guard('dealer')->validate($request->only('phone', 'password'))) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages(['phone' => 'Phone number or password is incorrect.']);
        }
        RateLimiter::clear($throttleKey);

        // Right credentials but not (yet) allowed in — say why instead of a generic failure.
        if (! $dealer->isApproved()) {
            $message = match ($dealer->status) {
                'pending' => 'Your application is under review. You can log in once the admin approves it.',
                'rejected' => 'Your application was not approved.' . ($dealer->admin_note ? ' Reason: ' . $dealer->admin_note : '') . ' Please contact the admin.',
                default => 'Your business account has been suspended. Please contact the admin.',
            };

            return back()->withInput($request->only('phone'))->with('error', $message);
        }

        Auth::guard('dealer')->login($dealer, $request->boolean('remember'));
        $request->session()->regenerate();
        $dealer->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('business.dashboard'));
    }

    public function showRegister()
    {
        if (Auth::guard('dealer')->check()) {
            return redirect()->route('business.dashboard');
        }

        $districts = District::active()->orderBy('name')->get(['id', 'name', 'bn_name']);

        return view('business.register', compact('districts'));
    }

    public function register(Request $request, DealerService $service)
    {
        $data = $service->validateAndStore($request, requireDocuments: true);

        Dealer::create($data + ['status' => 'pending']);

        return redirect()->route('business.login')
            ->with('success', 'Registration submitted! Your account will be active once the admin reviews and approves it.');
    }

    public function logout(Request $request)
    {
        // Only the dealer guard — a customer logged in on the same browser stays logged in.
        Auth::guard('dealer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('business.login');
    }

    public function thanas(District $district)
    {
        return $district->thanas()->active()->orderBy('name')->get(['id', 'name', 'bn_name']);
    }

    public function unions(Thana $thana)
    {
        return $thana->unions()->active()->orderBy('name')->get(['id', 'name', 'bn_name']);
    }
}
