<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Handle LIFF Login via ID Token
     */
    public function liffLogin(Request $request)
    {
        Log::debug('liffLogin endpoint hit', ['idToken_length' => strlen($request->input('idToken'))]);

        $request->validate([
            'idToken' => 'required|string',
        ]);

        $idToken = $request->input('idToken');
        $channelId = config('services.line.login_channel_id', env('LINE_LOGIN_CHANNEL_ID'));

        try {
            // 1. Verify ID Token with LINE
            $response = Http::asForm()->post('https://api.line.me/oauth2/v2.1/verify', [
                'id_token' => $idToken,
                'client_id' => $channelId,
            ]);

            if (!$response->successful()) {
                Log::error('LINE ID Token Verification Failed', ['response' => $response->json()]);

                return response()->json(['message' => 'Invalid ID Token'], 401);
            }

            $lineData = $response->json();
            $lineId = $lineData['sub'];
            $name = $lineData['name'];
            $picture = $lineData['picture'] ?? null;
            $email = $lineData['email'] ?? null;

            // 2. Mock Socialite User (To reuse Service Logic)
            $socialiteUser = new \Laravel\Socialite\Two\User;
            $socialiteUser->id = $lineId;
            $socialiteUser->name = $name;
            $socialiteUser->email = $email;
            $socialiteUser->avatar = $picture;
            $socialiteUser->token = $idToken; // Use ID Token as Access Token for now (or fetch real one if needed)
            // Note: Real access token exchange is skipped here for LIFF simplicity,
            // but we store id_token to keep constraints happy.

            // 3. Delegate to Service
            $service = app(\App\Services\LineIdentityService::class);
            $user = $service->handleSocialiteCallback($socialiteUser);

            // 4. Issue Sanctum Token
            // Revoke old tokens to be safe or keep them multiple devices support
            // $user->tokens()->delete();
            $token = $user->createToken('liff-token')->plainTextToken;

            // Determine Staff Meal Mode
            $staffMealMode = 'monthly';

            // Bypass global scopes because Auth::user() is not set during token issuance
            $staff = \App\Models\Staff::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->first();

            if ($staff && $staff->school_id) {
                $currentSemester = \App\Models\Semester::withoutGlobalScopes()
                    ->where('school_id', $staff->school_id)
                    ->current()
                    ->first();

                if ($currentSemester) {
                    $staffMealMode = $currentSemester->staff_meal_mode->value ?? 'monthly';
                    Log::debug("Setting staff_meal_mode to {$staffMealMode} for user {$user->id} (School: {$staff->school_id}, Semester: {$currentSemester->id})");
                } else {
                    Log::debug("No current semester found for user {$user->id} (School: {$staff->school_id})");
                }
            } else {
                Log::warning("User {$user->id} has no staff or school data");
            }

            // Also load school without global scopes for the response
            $school = \App\Models\School::withoutGlobalScopes()->find($user->school_id);

            return response()->json([
                'token' => $token,
                'user' => $user,
                'school_name' => $school?->name,
                'staff_meal_mode' => $staffMealMode,
            ]);

        } catch (\Exception $e) {
            Log::error('LIFF Login Exception', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }
}
