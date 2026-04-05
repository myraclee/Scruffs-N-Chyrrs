<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    /**
     * Centralized email validation rules.
     */
    private function emailRules($uniqueRule = null)
    {
        $rules = [
            'required',
            'string',
            // ✅ FIXED: supports subdomains (e.g. yahoo.com.ph)
            'regex:/^[a-zA-Z0-9]+([._+\-]?[a-zA-Z0-9]+)*@[a-zA-Z0-9]+([.\-]?[a-zA-Z0-9]+)*\.[a-zA-Z]{2,}$/'
        ];

        if ($uniqueRule) {
            $rules[] = $uniqueRule;
        }

        return $rules;
    }

    public function showSignup()
    {
        return view('customer.signup');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            // ✅ FIXED unique rule
            'email' => $this->emailRules('unique:users,email'),
            'contact_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('home');
    }

    public function showLogin()
    {
        return view('customer.login_customer');
    }

    public function showOwnerLogin()
    {
        return view('owner.login_owner');
    }

    /**
     * Handle login with lockout logic.
     */
    public function login(Request $request)
    {
        $rules = [
            'email' => $this->emailRules(),
            'password' => ['required', 'string'],
        ];

        $messages = [
            'email.regex' => 'The email field format is invalid.',
            'password.required' => 'The password field is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();

            // ✅ Keeps password error hidden if email format is wrong
            if ($errors->has('email') && str_contains($errors->first('email'), 'format is invalid')) {
                if (!empty($request->password)) {
                    $errors->forget('password');
                }
            }

            return back()->withErrors($errors)->withInput($request->only('email'));
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Permanent lock
            if ($user->is_locked) {
                return back()
                    ->withErrors(['password' => 'Account locked due to multiple failed attempts.'])
                    ->withInput($request->only('email'));
            }

            // Temporary lock
            if ($user->lockout_until && now()->lessThan($user->lockout_until)) {
                return back()
                    ->withInput($request->only('email'))
                    ->with('lockout_until', $user->lockout_until->timestamp);
            }
        }

        // Attempt login
        if (Auth::attempt($request->only('email', 'password'))) {
            if ($user) {
                $user->update([
                    'login_attempts' => 0,
                    'lockout_until' => null
                ]);
            }

            $request->session()->regenerate();

            return ($user && $user->user_type === 'owner')
                ? redirect()->route('owner.dashboard')
                : redirect()->route('home');
        }

        // Failed attempts
        if ($user) {
            $user->increment('login_attempts');

            if ($user->login_attempts >= 5) {
                $user->update(['is_locked' => true, 'lockout_until' => null]);

                return back()
                    ->withErrors(['password' => 'Account locked due to multiple failed attempts.'])
                    ->withInput($request->only('email'));
            }

            if ($user->login_attempts == 4) {
                $lockoutUntil = now()->addMinutes(5);
                $user->update(['lockout_until' => $lockoutUntil]);

                return back()
                    ->withInput($request->only('email'))
                    ->with('lockout_until', $lockoutUntil->timestamp);
            }

            if ($user->login_attempts == 3) {
                $lockoutUntil = now()->addMinutes(2);
                $user->update(['lockout_until' => $lockoutUntil]);

                return back()
                    ->withInput($request->only('email'))
                    ->with('lockout_until', $lockoutUntil->timestamp);
            }
        }

        return back()
            ->withErrors(['password' => 'Invalid email or password.'])
            ->withInput($request->only('email'));
    }

    public function account()
    {
        return view('customer.account');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showEditProfile()
    {
        return view('customer.edit_profile');
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules('unique:users,email,' . $request->user()->id),
            'contact_number' => ['required', 'string', 'max:20'],
        ]);

        $request->user()->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
        ]);

        return redirect()->route('account')->with('success', 'Profile updated successfully!');
    }

    public function showChangePassword()
    {
        return view('customer.change_password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $request->user()->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->route('account')->with('success', 'Password updated successfully!');
    }

    public function showResetPassword()
    {
        return view('customer.password_page.reset_password');
    }

    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => $this->emailRules()
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'No account found with this email address.'])
                ->onlyInput('email');
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'code' => $code,
                'token' => \Illuminate\Support\Str::random(64),
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
            ]
        );

        Mail::to($request->email)->send(new PasswordResetCode($code, $user->first_name));

        $request->session()->put('reset_email', $request->email);

        return redirect()->route('enter-code')
            ->with('success', 'A verification code has been sent to your email.');
    }

    public function showEnterCode()
    {
        if (!session('reset_email')) return redirect()->route('reset-password');

        return view('customer.password_page.enter_code');
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6']
        ]);

        $email = session('reset_email');

        if (!$email) return redirect()->route('reset-password');

        $resetToken = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$resetToken || $resetToken->code !== $request->code) {
            return back()->withErrors(['code' => 'The verification code is invalid.']);
        }

        if (now()->isAfter($resetToken->expires_at)) {
            return back()->withErrors(['code' => 'The verification code has expired. Please request a new one.']);
        }

        $request->session()->put('verified_reset_email', $email);

        return redirect()->route('new-password')->with('success', 'Code verified successfully!');
    }

    public function showNewPassword()
    {
        if (!session('verified_reset_email')) return redirect()->route('reset-password');

        return view('customer.password_page.new_password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()]
        ]);

        $email = session('verified_reset_email');

        if (!$email) return redirect()->route('reset-password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('reset-password')
                ->withErrors(['email' => 'User not found.']);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $request->session()->forget(['reset_email', 'verified_reset_email']);

        return redirect()->route('login')
            ->with('success', 'Password reset successfully! Please log in with your new password.');
    }
}