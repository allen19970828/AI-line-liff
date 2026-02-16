---
name: multi-tenancy-troubleshooting
description: 處理 Laravel 多租戶架構（Multi-tenancy）中 Global Scope 與資料完整性衝突的排錯指南。
---

# Multi-tenancy Troubleshooting (多租戶隔離排錯心法)

本心法旨在解決多租戶架構中常見的「結界衝突」現象，特別是當系統使用 Global Scope 隔離資料，且涉及 OAuth/LIFF 登入等複雜流程時。

## 核心心法

### 1. 破陣術：`withoutGlobalScopes()` 的使用時機

**現象**：

- 資料庫明明有資料，但 Eloquent 查詢回傳 `null`。
- 嘗試插入資料（Insert）報 `Unique violation` 重複錯誤，但 `Model::where(...)` 卻找不到紀錄。

**口訣**：

> 「明明有卻看不見，必是結界遮了眼。」

**處理邏輯**：
在進行**身分查核、帳號綁定、唯一性校驗**時，必須使用 `withoutGlobalScopes()` 跳過租戶隔離限制，確保能跨租戶（或看見無租戶歸屬的）紀錄。

```php
// 範例：尋找現有 LINE 帳號，必須無視學校隔離
$account = LineAccount::withoutGlobalScopes()
    ->where('line_user_id', $id)
    ->first();
```

### 2. 柔化術：初次登入的 Nullable 策略

**現象**：

- `school_id` 設為 `NOT NULL`，導致 OAuth 首次登入時（用戶尚未選擇學校）無法建立關聯帳號。
- 500 錯誤報 `null value violates not-null constraint`。

**口訣**：

> 「新芽尚無歸宗地，土讓需得留分隙。」

**處理邏輯**：

1. **資料庫端**：租戶外鍵（如 `school_id`）在「過渡期模型」應設為 `nullable()`。
2. **流程端**：透過 `is_profile_completed` 等旗標擋關，強制用戶進補全頁面。
3. **安全端**：`BelongsToSchool` 護法依然生效，`null` 值資料會被 Global Scope 自動過濾，不會外洩給其他學校。

### 3. 歸宗術：自動填入的優先級

**現象**：

- 在 `creating` 事件中自動代入 `auth()->user()->school_id` 時報錯或代入錯誤值。

**口訣**：

> 「自行動手先歸宗，防呆護法兩不誤。」

**處理邏輯**：

1. 在 Service 層建立物件時，若已知學校，應**顯式賦值** (`'school_id' => $user->school_id`)。
2. `BelongsToSchool` 護法應檢查 `if ($model->school_id)` 是否已有值，避免覆蓋掉顯式設定的值。

## 適用情境

- **身分驗證 (Auth)**: LINE Login, Socialite 回傳處理。
- **後台管理 (Filament)**: 管理員跨校操作或上帝視角 (Super Admin) 切換。
- **異步處理 (Queue)**: 排程任務中缺乏 Auth Context 時的學校歸屬。

---

_弟子 Antigravity 整理，宗師法眼見證。_
