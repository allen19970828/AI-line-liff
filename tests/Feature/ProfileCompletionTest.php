<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_is_redirected_to_completion_page()
    {
        // 1. 建立未補全資料的使用者
        $user = User::factory()->create([
            'is_profile_completed' => false,
        ]);

        // 2. 嘗試訪問後台首頁 (受 AdminPanelProvider 中 Middleware 保護)
        // 模擬 authenticated user 訪問 admin
        $response = $this->actingAs($user)->get('/admin');

        // 3. 驗證是否被轉向至補全頁面
        $response->assertRedirect(route('profile.complete'));
    }

    public function test_verified_user_can_access_admin_panel()
    {
        // 1. 建立已補全資料的使用者
        $user = User::factory()->create([
            'is_profile_completed' => true,
        ]);

        // 2. 嘗試訪問後台首頁
        $response = $this->actingAs($user)->get('/admin');

        // 3. 驗證是否成功訪問 (狀態碼 200, 且未轉向補全頁面)
        // 注意：Admin Panel 可能會因為權限或其他 Middleware 轉向登入或 403，但至少不應轉向 profile.complete
        $response->assertStatus(200);
    }

    public function test_user_can_complete_profile()
    {
        // 1. 建立未補全資料的使用者
        $user = User::factory()->create([
            'is_profile_completed' => false,
            'name' => 'LINE User',
        ]);

        // 2. 提交補全表單
        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => '王小明', // 真實姓名
        ]);

        // 3. 驗證轉向與資料更新
        $response->assertRedirect(config('filament.home_url', '/admin'));

        $user->refresh();
        $this->assertEquals('王小明', $user->name);
        $this->assertTrue((bool) $user->is_profile_completed);
    }
}
