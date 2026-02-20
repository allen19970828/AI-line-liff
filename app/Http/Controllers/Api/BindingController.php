<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BindingController extends Controller
{
    /**
     * Bind User to School via Code
     * POST /api/bind
     */
    public function bind(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'real_name' => 'required|string',
            'staff_role' => 'required|string', // Keep role for initial setup
        ]);

        $code = trim($request->input('code'));
        $realName = trim($request->input('real_name'));
        $staffRole = $request->input('staff_role'); // 'teacher', 'admin', 'worker'
        $user = $request->user();

        // 1. Find School by Code
        $school = School::where('code', $code)->first();

        if (! $school) {
            return response()->json(['message' => '無效的學校代碼'], 404);
        }

        // 2. Update User & Link/Create Staff
        try {
            $user->school_id = $school->id;
            $user->save();

            // [New] Auto-Link or Create Staff
            $staff = \App\Models\Staff::where('school_id', $school->id)
                ->where('name', $realName)
                ->first();

            if ($staff) {
                // [Found] Link to user if not already linked (or overwrite if needed)
                if (! $staff->user_id) {
                    $staff->user_id = $user->id;
                    // Reactivate if resigning and coming back
                    $staff->is_active = true;
                    $staff->save();
                    Log::info("Binding User {$user->id} to existing Staff {$staff->id}");
                }
            } else {
                // [Not Found] Create new Staff
                // Map string role to Enum
                $roleEnum = match ($staffRole) {
                    'teacher' => \App\Enums\StaffRole::Teacher,
                    'admin' => \App\Enums\StaffRole::Admin,
                    'worker' => \App\Enums\StaffRole::Worker,
                    default => \App\Enums\StaffRole::Teacher,
                };

                $staff = \App\Models\Staff::create([
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'name' => $realName,
                    // 'stage' => \App\Enums\SemesterStage::Elementary, // Removed: Staff usually don't have stage, or provide default
                    'role' => $roleEnum,
                    'dietary_type' => \App\Enums\DietaryType::Meat, // Default
                    'is_meal_user' => true,
                    'is_active' => true,
                ]);
                Log::info("Created new Staff {$staff->id} for User {$user->id}");
            }

            // Reload user to get fresh data
            $user->load('school');

            return response()->json([
                'message' => '綁定成功！',
                'user' => $user,
                'school_name' => $school->name,
                'staff_status' => $staff->wasRecentlyCreated ? 'created' : 'linked',
            ]);

        } catch (\Exception $e) {
            Log::error('Binding Error', ['error' => $e->getMessage(), 'user_id' => $user->id, 'code' => $code]);

            return response()->json(['message' => '綁定失敗，請稍後再試。'], 500);
        }
    }

    /**
     * Unbind User from School (Resignation)
     * POST /api/unbind
     */
    public function unbind(Request $request)
    {
        $user = $request->user();

        try {
            // Find associated staff record
            $staff = \App\Models\Staff::where('user_id', $user->id)
                ->where('school_id', $user->school_id)
                ->first();

            if ($staff) {
                // Mark as inactive (Resigned)
                $staff->is_active = false;
                $staff->save();
                Log::info("Staff {$staff->id} marked as inactive (Resigned) by User {$user->id}");
            }

            // Unlink User
            $user->school_id = null;
            $user->save();

            return response()->json([
                'message' => '已解除綁定並標記離職',
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            Log::error('Unbinding Error: '.$e->getMessage());

            return response()->json(['message' => '解除綁定失敗'], 500);
        }
    }

    /**
     * Update Personal Settings
     * POST /api/settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'dietary_type' => 'required|string',
            'is_meal_user' => 'required|boolean',
        ]);

        $user = $request->user();

        try {
            $staff = \App\Models\Staff::where('user_id', $user->id)
                ->where('school_id', $user->school_id)
                ->firstOrFail();

            // Map string to Enum
            $dietaryType = match ($request->input('dietary_type')) {
                'meat' => \App\Enums\DietaryType::Meat,
                'vegan' => \App\Enums\DietaryType::Vegan,
                'ovo_lacto' => \App\Enums\DietaryType::OvoLacto,
                default => \App\Enums\DietaryType::Meat,
            };

            $staff->dietary_type = $dietaryType;
            $staff->is_meal_user = $request->input('is_meal_user');
            $staff->save();

            return response()->json([
                'message' => '個人設定已更新',
                'staff' => $staff,
            ]);

        } catch (\Exception $e) {
            Log::error('Settings Update Error: '.$e->getMessage());

            return response()->json(['message' => '設定更新失敗'], 500);
        }
    }

    /**
     * Get Personal Settings
     * GET /api/settings
     */
    public function getSettings(Request $request)
    {
        $user = $request->user();
        $staff = \App\Models\Staff::where('user_id', $user->id)
            ->where('school_id', $user->school_id)
            ->first();

        if (! $staff) {
            return response()->json(['message' => '找不到教職員資料'], 404);
        }

        return response()->json([
            'data' => $staff,
        ]);
    }
}
