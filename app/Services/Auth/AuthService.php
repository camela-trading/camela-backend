<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Notifications\AdminNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

class AuthService
{
    public function __construct(
        private AdminNotificationService $notificationService
    ) {}

    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw new \Exception("Invalid email or password.");
        }

        $user = User::where('email', $credentials['email'])->first();

        if (!$user?->is_active) {
            throw new \Exception('Your account has been disabled.');
        }

        $token = $user->createToken('camela-token')->plainTextToken;

        $user->update([
            'last_login_at' => now(),
        ]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    private function normalizeBaseUsername(string $value): string
    {
        $value = trim(mb_strtolower($value));

        // Replace non alphanumeric with underscore, then collapse underscores.
        $value = preg_replace('/[^a-z0-9]+/i', '_', $value) ?? '';
        $value = trim($value, '_');

        return $value ?: 'user';
    }

    private function generateUniqueUsername(?string $email, ?string $providedUsername): string
    {
        $base = null;

        if ($providedUsername !== null && $providedUsername !== '') {
            $base = $this->normalizeBaseUsername($providedUsername);
        } else {
            $email = $email ?? '';
            $basePart = explode('@', $email)[0] ?? '';
            $base = $this->normalizeBaseUsername($basePart);
        }

        $username = $base;
        $i = 2;

        while (User::where('username', $username)->exists()) {
            $username = $base . '-' . $i;
            $i++;
        }

        return $username;
    }

    public function register(array $data): array
    {
        $email = $data['email'] ?? '';
        $username = $this->generateUniqueUsername($email, $data['username'] ?? null);

        $user = User::create([
            'role_id' => 2, // CUSTOMER
            'name' => $data['name'],
            'username' => $username,
            'email' => $email,
            'password' => $data['password'],
        ]);

        $token = $user->createToken('camela-token')->plainTextToken;

        event(new Registered($user));
        $user->sendEmailVerificationNotification();

        $this->notificationService->notify(
            'notify_new_customer',
            'new_customer',
            'New Customer Registered',
            $user->name . ' just created a new account.',
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
            '/admin/customers'
        );

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function sendPasswordResetLink(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new \Exception(__($status));
        }
    }

    public function resetPassword(array $data): void
    {
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
            'password_confirmation' => $data['password_confirmation'] ?? $data['passwordConfirmation'] ?? null,
            'token' => $data['token'],
        ];

        $status = Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new \Exception(__($status));
        }
    }
}

