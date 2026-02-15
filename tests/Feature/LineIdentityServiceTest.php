<?php

namespace Tests\Feature;

use App\Models\LineAccount;
use App\Models\User;
use App\Services\LineIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class LineIdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_new_user_from_line_login()
    {
        // 1. 模擬 Socialite User
        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = 'LINE_12345';
        $socialiteUser->name = 'Allen Yu';
        $socialiteUser->email = 'allen@example.com';
        $socialiteUser->avatar = 'https://example.com/avatar.jpg';
        $socialiteUser->token = 'access_token_123';
        $socialiteUser->refreshToken = 'refresh_token_123';
        $socialiteUser->expiresIn = 3600;

        // 2. 執行 Service
        $service = new LineIdentityService;
        $user = $service->handleSocialiteCallback($socialiteUser);

        // 3. 驗證 User 是否建立
        $this->assertDatabaseHas('users', [
            'email' => 'allen@example.com',
            'name' => 'Allen Yu',
        ]);

        // 4. 驗證 LineAccount 是否建立並關聯
        $this->assertDatabaseHas('line_accounts', [
            'user_id' => $user->id,
            'line_user_id' => 'LINE_12345',
            'display_name' => 'Allen Yu',
            'access_token' => 'access_token_123',
        ]);

        $user->refresh();

        $this->assertNotNull($user->lineAccount);
        $this->assertFalse((bool) $user->is_profile_completed, 'New user should not be profile completed');
    }

    public function test_it_updates_existing_line_account()
    {
        // 1. 建立既有 User 與 LineAccount
        $user = User::factory()->create();
        $lineAccount = LineAccount::create([
            'user_id' => $user->id,
            'line_user_id' => 'LINE_EXISTING',
            'display_name' => 'Old Name',
        ]);

        // 2. 模擬 Socialite User (資料更新)
        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = 'LINE_EXISTING';
        $socialiteUser->name = 'New Name'; // 名稱變更
        $socialiteUser->token = 'new_token';

        // 3. 執行 Service
        $service = new LineIdentityService;
        $resultUser = $service->handleSocialiteCallback($socialiteUser);

        // 4. 驗證 User 沒變，但 LineAccount 更新了
        $this->assertEquals($user->id, $resultUser->id);
        $this->assertDatabaseHas('line_accounts', [
            'line_user_id' => 'LINE_EXISTING',
            'display_name' => 'New Name',
            'access_token' => 'new_token',
        ]);
    }
}
