<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Exception;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request)
    {
        try {

            $result = $this->authService->login(
                $request->validated()
            );

            return response()->json([

                'success' => true,

                'message' => 'Login successful.',

                'token' => $result['token'],

                'user' => new UserResource($result['user'])

            ]);

        } catch (Exception $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()

            ], 401);

        }
    }

    public function register(RegisterRequest $request)
    {
        try {

            $result = $this->authService->register(
                $request->validated()
            );

            return response()->json([

                'success' => true,

                'message' => 'Registration successful.',

                'token' => $result['token'],

                'user' => new UserResource($result['user'])

            ], 201);

        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()

            ], 500);

        }
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email is already verified.',
            ]);
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        if ($user->email !== $request->string('email')->toString()) {
            return response()->json([
                'success' => false,
                'message' => 'Email address does not match your account.',
            ], 422);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent successfully.',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->authService->sendPasswordResetLink($request->string('email')->toString());
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent successfully.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->authService->resetPassword($data);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password has been reset successfully.',
        ]);
    }
}
