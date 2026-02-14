---
name: filament-activity-log-setup
description: 快速為新的 Filament Model 增加異動紀錄 (Activity Log) 的標準流程。
---

# Filament Activity Log 設定心法

當專案中新增了一個 Model（例如：`LunchOrder` 訂餐單），請遵循以下三個步驟來裝備「全紀錄心法」：

## 1. 在 Model 注入心法

在 `app/Models/LunchOrder.php` 中引入 Trait 並實作配置：

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LunchOrder extends Model {
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()    // 紀錄所有可填寫欄位
            ->logOnlyDirty()   // 只紀錄有變動的部分
            ->dontSubmitEmptyLogs(); // 如果沒變動就不紀錄
    }
}
```

## 2. 建立紀錄頁面

在對應 Resource 的 Pages 目錄下建立專屬頁面。
路徑：`app/Filament/Resources/LunchOrderResource/Pages/ListLunchOrderActivities.php`

```php
namespace App\Filament\Resources\LunchOrderResource\Pages;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListLunchOrderActivities extends ListActivities {
    protected static string $resource = LunchOrderResource::class;
}
```

## 3. 註冊路由與按鈕

在 `app/Filament/Resources/LunchOrderResource.php` 中進行最後的聯動：

### A. 註冊路由 (getPages)

```php
public static function getPages(): array {
    return [
        // ... 其他路由
        'activities' => Pages\ListLunchOrderActivities::route('/{record}/activities'),
    ];
}
```

### B. 增加按鈕 (table actions)

```php
->actions([
    \Filament\Actions\Action::make('activities')
        ->label('異動紀錄')
        ->icon('heroicon-m-clock')
        ->color('info')
        ->url(fn ($record) => static::getUrl('activities', ['record' => $record])),
    // ... 其他動作
])
```

> [!TIP]
> 記得確認 `heroicon-m-clock` 是否存在，若版本不同可改用 `heroicon-o-clock`。
