<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showSignup()
    {
        return view('customer.signup');
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
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

    /**
     * Show the customer login form.
     */
    public function showLogin()
    {
        return view('customer.login_customer');
    }

    /**
     * Show the owner login form.
     */
    public function showOwnerLogin()
    {
        return view('owner.login_owner');
    }

    /**
     * Handle an incoming login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect based on user type
            if ($user->user_type === 'owner') {
                return redirect()->route('owner.dashboard');
            }

            return redirect()->route('home');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Show the user account page.
     */
    public function account()
    {
        return view('customer.account');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Show the edit profile form.
     */
    public function showEditProfile()
    {
        return view('customer.edit_profile');
    }

    /**
     * Handle profile update request.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
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

    /**
     * Show the change password form.
     */
    public function showChangePassword()
    {
        return view('customer.change_password');
    }

    /**
     * Handle password update request.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $request->user()->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $request->user()->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->route('account')->with('success', 'Password updated successfully!');
    }

    /**
     * Show the reset password form.
     */
    public function showResetPassword()
    {
        return view('customer.password_page.reset_password');
    }

    /**
     * Handle password reset code request.
     * Generates and sends a 6-digit code to the user's email.
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Check if user exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'No account found with this email address.',
            ])->onlyInput('email');
        }

        // Generate a 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store the code with 15-minute expiration
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'code' => $code,
                'token' => \Illuminate\Support\Str::random(64),
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
            ]
        );

        // Send email with code
        Mail::to($request->email)->send(
            new PasswordResetCode($code, $user->first_name)
        );

        $request->session()->put('reset_email', $request->email);

        return redirect()->route('enter-code')->with('success', 'A verification code has been sent to your email.');
    }

    /**
     * Show the enter code form.
     */
    public function showEnterCode()
    {
        if (!session('reset_email')) {
            return redirect()->route('reset-password');
        }

        return view('customer.password_page.enter_code');
    }

    /**
     * Handle code verification.
     * Validates the code and prepares for password reset.
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $email = session('reset_email');

        if (!$email) {
            return redirect()->route('reset-password');
        }

        // Find the reset token
        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        // Check if code exists, matches, and hasn't expired
        if (!$resetToken || $resetToken->code !== $request->code) {
            return back()->withErrors([
                'code' => 'The verification code is invalid.',
            ]);
        }

        if (now()->isAfter($resetToken->expires_at)) {
            return back()->withErrors([
                'code' => 'The verification code has expired. Please request a new one.',
            ]);
        }

        // Store verified status in session
        $request->session()->put('verified_reset_email', $email);

        return redirect()->route('new-password')->with('success', 'Code verified successfully!');
    }

    /**
     * Show the new password form.
     */
    public function showNewPassword()
    {
        if (!session('verified_reset_email')) {
            return redirect()->route('reset-password');
        }

        return view('customer.password_page.new_password');
    }

    /**
     * Handle password reset.
     * Updates the user's password and cleans up reset tokens.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = session('verified_reset_email');

        if (!$email) {
            return redirect()->route('reset-password');
        }

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('reset-password')->withErrors(['email' => 'User not found.']);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Delete reset token
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        // Clear sessions
        $request->session()->forget(['reset_email', 'verified_reset_email']);

        return redirect()->route('login')->with('success', 'Password reset successfully! Please log in with your new password.');
    }
}
