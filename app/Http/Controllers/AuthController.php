<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\CustomerCart;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\Product;
use App\Mail\PasswordResetCode;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthController extends Controller
{
    /**
     * @var list<string>
     */
    private const FINISHED_ORDER_STATUSES = ['completed', 'cancelled'];

    /**
     * @var list<string>
     */
    private const DASHBOARD_PENDING_ORDER_STATUSES = ['waiting', 'approved', 'preparing', 'ready'];

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

    /**
     * Issue and deliver a 6-digit verification code using the existing reset token table.
     */
    private function issueVerificationCode(User $user): void
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'code' => $code,
                'token' => \Illuminate\Support\Str::random(64),
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
            ]
        );

        Mail::to($user->email)->send(new PasswordResetCode($code, $user->first_name));
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

        $user = DB::transaction(function () use ($request): User {
            $isFirstSignup = ! User::query()->lockForUpdate()->exists();

            return User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'password' => Hash::make($request->password),
                'user_type' => $isFirstSignup ? 'owner' : 'customer',
            ]);
        });

        Auth::login($user);

        return $user->isOwner()
            ? redirect()->route('owner.dashboard')
            : redirect()->route('home');
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
                    ->withInput($request->only('email'))
                    ->with('show_unlock_option', true);
            }

            // User finished unlock verification but still needs to set a new password.
            if ($user->must_reset_password) {
                return back()
                    ->withErrors(['password' => 'Password reset required before login. Use email verification to unlock your account.'])
                    ->withInput($request->only('email'))
                    ->with('show_unlock_option', true);
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
                    'lockout_until' => null,
                    'must_reset_password' => false,
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
                $user->update([
                    'is_locked' => true,
                    'lockout_until' => null,
                ]);

                return back()
                    ->withErrors(['password' => 'Account locked due to multiple failed attempts.'])
                    ->withInput($request->only('email'))
                    ->with('show_unlock_option', true);
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

        return $this->buildSecureSignOutRedirect(routeName: 'home');
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

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isOwner()) {
            return back()->withErrors([
                'account_deletion' => 'Owner accounts cannot be deleted using this page.',
            ]);
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password_confirmation' => ['required', 'string', 'same:current_password'],
        ], [
            'new_password_confirmation.same' => 'The password confirmation does not match your current password.',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $hasUnfinishedGroupedOrders = CustomerOrderGroup::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', self::FINISHED_ORDER_STATUSES)
            ->exists();

        $hasUnfinishedLineOrders = CustomerOrder::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', self::FINISHED_ORDER_STATUSES)
            ->exists();

        $hasUnfinishedLegacyOrders = Order::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', self::FINISHED_ORDER_STATUSES)
            ->exists();

        if ($hasUnfinishedGroupedOrders || $hasUnfinishedLineOrders || $hasUnfinishedLegacyOrders) {
            return back()->withErrors([
                'account_deletion' => 'Your account cannot be deleted while you still have pending or unfinished orders.',
            ]);
        }

        $userId = $user->id;
        $userEmail = $user->email;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($userId, $userEmail): void {
            $this->archiveDeletedAccountDashboardContributions($userId);

            // Explicit hard-delete for user-linked records, including nullable legacy foreign keys.
            Order::query()->where('user_id', $userId)->delete();
            CustomerCart::query()->where('user_id', $userId)->delete();
            CustomerOrder::query()->where('user_id', $userId)->delete();
            CustomerOrderGroup::query()->where('user_id', $userId)->delete();

            DB::table('sessions')->where('user_id', $userId)->delete();
            DB::table('password_reset_tokens')->where('email', $userEmail)->delete();

            User::query()->whereKey($userId)->delete();
        });

        return $this->buildSecureSignOutRedirect(
            routeName: 'home',
            flashData: ['success' => 'Your account has been permanently deleted.'],
        );
    }

    /**
     * Build a hardened post-sign-out redirect response.
     *
     * This clears auth-related cookies where possible and asks modern browsers
     * to clear cookies/storage/cache for the current origin.
     *
     * @param array<string, mixed> $flashData
     */
    private function buildSecureSignOutRedirect(string $routeName, array $flashData = []): RedirectResponse
    {
        $response = redirect()->route($routeName);

        if ($flashData !== []) {
            $response->with($flashData);
        }

        foreach ($this->getLogoutCookieNames() as $cookieName) {
            $response->withCookie(cookie()->forget($cookieName));
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        $response->headers->set('Clear-Site-Data', '"cache", "cookies", "storage"');

        return $response;
    }

    /**
     * @return list<string>
     */
    private function getLogoutCookieNames(): array
    {
        $cookieNames = [
            (string) config('session.cookie'),
            'XSRF-TOKEN',
        ];

        $guard = Auth::guard('web');
        if ($guard instanceof SessionGuard) {
            /** @var string $recallerCookie */
            $recallerCookie = $guard->getRecallerName();
            $cookieNames[] = $recallerCookie;
        }

        return array_values(array_unique(array_filter($cookieNames)));
    }

    private function archiveDeletedAccountDashboardContributions(int $userId): void
    {
        $groups = CustomerOrderGroup::query()
            ->where('user_id', $userId)
            ->get(['id', 'status', 'total_price', 'created_at']);

        if ($groups->isEmpty()) {
            return;
        }

        $dailyAggregates = [];
        $nonCancelledGroupDateById = [];

        foreach ($groups as $group) {
            $metricDate = $group->created_at?->toDateString() ?? now()->toDateString();

            if (! isset($dailyAggregates[$metricDate])) {
                $dailyAggregates[$metricDate] = [
                    'total_sales' => 0.0,
                    'items_sold' => 0,
                    'total_orders' => 0,
                    'received_payment' => 0,
                    'pending_payment' => 0,
                    'canceled_orders' => 0,
                ];
            }

            $dailyAggregates[$metricDate]['total_orders']++;

            if ($group->status === 'completed') {
                $dailyAggregates[$metricDate]['received_payment']++;
            }

            if (in_array($group->status, self::DASHBOARD_PENDING_ORDER_STATUSES, true)) {
                $dailyAggregates[$metricDate]['pending_payment']++;
            }

            if ($group->status === 'cancelled') {
                $dailyAggregates[$metricDate]['canceled_orders']++;
                continue;
            }

            $dailyAggregates[$metricDate]['total_sales'] += (float) $group->total_price;
            $nonCancelledGroupDateById[(int) $group->id] = $metricDate;
        }

        $monthlyProductAggregates = [];

        if ($nonCancelledGroupDateById !== []) {
            $orders = CustomerOrder::query()
                ->whereIn('customer_order_group_id', array_keys($nonCancelledGroupDateById))
                ->get(['customer_order_group_id', 'product_id', 'quantity', 'created_at']);

            $productNames = Product::query()
                ->whereIn('id', $orders->pluck('product_id')->unique()->values())
                ->pluck('name', 'id');

            foreach ($orders as $order) {
                $groupId = (int) $order->customer_order_group_id;
                $metricDate = $nonCancelledGroupDateById[$groupId] ?? null;

                if ($metricDate !== null) {
                    $dailyAggregates[$metricDate]['items_sold'] += (int) $order->quantity;
                }

                $orderCreatedAt = $order->created_at ?? now();
                $year = (int) $orderCreatedAt->format('Y');
                $month = (int) $orderCreatedAt->format('n');
                $productId = (int) $order->product_id;
                $monthlyKey = $year.'-'.$month.'-'.$productId;

                if (! isset($monthlyProductAggregates[$monthlyKey])) {
                    $monthlyProductAggregates[$monthlyKey] = [
                        'year' => $year,
                        'month' => $month,
                        'product_id' => $productId,
                        'product_name' => (string) ($productNames[$productId] ?? 'Unknown Product'),
                        'total_quantity' => 0,
                    ];
                }

                $monthlyProductAggregates[$monthlyKey]['total_quantity'] += (int) $order->quantity;
            }
        }

        foreach ($dailyAggregates as $metricDate => $aggregate) {
            $this->persistDailyDashboardContribution($metricDate, $aggregate);
        }

        foreach ($monthlyProductAggregates as $aggregate) {
            $this->persistMonthlyProductContribution($aggregate);
        }
    }

    /**
     * @param array{
     *   total_sales: float,
     *   items_sold: int,
     *   total_orders: int,
     *   received_payment: int,
     *   pending_payment: int,
     *   canceled_orders: int
     * } $aggregate
     */
    private function persistDailyDashboardContribution(string $metricDate, array $aggregate): void
    {
        $now = now();

        $existing = DB::table('dashboard_deleted_account_daily_metrics')
            ->where('metric_date', $metricDate)
            ->first();

        if ($existing) {
            DB::table('dashboard_deleted_account_daily_metrics')
                ->where('metric_date', $metricDate)
                ->update([
                    'total_sales' => round((float) $existing->total_sales + (float) $aggregate['total_sales'], 2),
                    'items_sold' => (int) $existing->items_sold + (int) $aggregate['items_sold'],
                    'total_orders' => (int) $existing->total_orders + (int) $aggregate['total_orders'],
                    'received_payment' => (int) $existing->received_payment + (int) $aggregate['received_payment'],
                    'pending_payment' => (int) $existing->pending_payment + (int) $aggregate['pending_payment'],
                    'canceled_orders' => (int) $existing->canceled_orders + (int) $aggregate['canceled_orders'],
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('dashboard_deleted_account_daily_metrics')->insert([
            'metric_date' => $metricDate,
            'total_sales' => round((float) $aggregate['total_sales'], 2),
            'items_sold' => (int) $aggregate['items_sold'],
            'total_orders' => (int) $aggregate['total_orders'],
            'received_payment' => (int) $aggregate['received_payment'],
            'pending_payment' => (int) $aggregate['pending_payment'],
            'canceled_orders' => (int) $aggregate['canceled_orders'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param array{year: int, month: int, product_id: int, product_name: string, total_quantity: int} $aggregate
     */
    private function persistMonthlyProductContribution(array $aggregate): void
    {
        $now = now();

        $existing = DB::table('dashboard_deleted_account_monthly_product_sales')
            ->where('year', $aggregate['year'])
            ->where('month', $aggregate['month'])
            ->where('product_id', $aggregate['product_id'])
            ->first();

        if ($existing) {
            DB::table('dashboard_deleted_account_monthly_product_sales')
                ->where('id', $existing->id)
                ->update([
                    'product_name' => $aggregate['product_name'],
                    'total_quantity' => (int) $existing->total_quantity + (int) $aggregate['total_quantity'],
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('dashboard_deleted_account_monthly_product_sales')->insert([
            'year' => $aggregate['year'],
            'month' => $aggregate['month'],
            'product_id' => $aggregate['product_id'],
            'product_name' => $aggregate['product_name'],
            'total_quantity' => $aggregate['total_quantity'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
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
                ->withErrors(['email' => 'Invalid email address.'])
                ->onlyInput('email');
        }

        $this->issueVerificationCode($user);

        $request->session()->forget(['unlock_email', 'verified_reset_email']);
        $request->session()->put('reset_email', $request->email);

        return redirect()->route('enter-code')
            ->with('success', 'A verification code has been sent to your email.');
    }

    public function sendUnlockCode(Request $request)
    {
        $request->validate([
            'email' => $this->emailRules(),
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && ($user->is_locked || $user->must_reset_password)) {
            $this->issueVerificationCode($user);
        }

        $request->session()->forget(['reset_email', 'verified_reset_email']);
        $request->session()->put('unlock_email', $request->email);

        return redirect()->route('enter-code')
            ->with('success', 'If your account is eligible, a verification code has been sent to your email.');
    }

    public function showEnterCode()
    {
        $hasResetFlow = session()->has('reset_email');
        $hasUnlockFlow = session()->has('unlock_email');

        if (!$hasResetFlow && !$hasUnlockFlow) {
            return redirect()->route('reset-password');
        }

        return view('customer.password_page.enter_code', [
            'isUnlockFlow' => $hasUnlockFlow && !$hasResetFlow,
        ]);
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6']
        ]);

        $isUnlockFlow = session()->has('unlock_email');
        $email = $isUnlockFlow ? session('unlock_email') : session('reset_email');

        if (!$email) {
            return redirect()->route('reset-password');
        }

        $resetToken = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$resetToken || !is_string($resetToken->code) || !is_string($resetToken->expires_at)) {
            return back()->withErrors(['code' => 'The verification code is invalid.']);
        }

        if (!hash_equals($resetToken->code, (string) $request->code)) {
            return back()->withErrors(['code' => 'The verification code is invalid.']);
        }

        if (now()->greaterThanOrEqualTo($resetToken->expires_at)) {
            return back()->withErrors(['code' => 'The verification code has expired. Please request a new one.']);
        }

        if ($isUnlockFlow) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'is_locked' => false,
                    'login_attempts' => 0,
                    'lockout_until' => null,
                    'must_reset_password' => true,
                ]);
            }
        }

        $request->session()->forget(['reset_email', 'unlock_email']);

        $request->session()->put('verified_reset_email', $email);

        return redirect()->route('new-password')->with(
            'success',
            $isUnlockFlow
                ? 'Verification successful. Please create a new password to finish unlocking your account.'
                : 'Code verified successfully!'
        );
    }

    public function showNewPassword()
    {
        if (!session('verified_reset_email')) {
            return redirect()->route('reset-password');
        }

        return view('customer.password_page.new_password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()]
        ]);

        $email = session('verified_reset_email');

        if (!$email) {
            return redirect()->route('reset-password');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('reset-password')
                ->withErrors(['email' => 'User not found.']);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'is_locked' => false,
            'login_attempts' => 0,
            'lockout_until' => null,
            'must_reset_password' => false,
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $request->session()->forget(['reset_email', 'unlock_email', 'verified_reset_email']);

        return redirect()->route('login')
            ->with('success', 'Password reset successfully! Please log in with your new password.');
    }

    public function showDeleteAccount()
    {
        return view('customer.delete_account', [
            'user' => Auth::user()
        ]);
    }
}
