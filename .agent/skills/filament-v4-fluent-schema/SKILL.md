---
name: filament-v4-fluent-schema
description: Filament v4 推薦的 Schema 配置流 (Fluent API) 標準實作規範。
---

# Filament v4 Schema 配置流 (Fluent API) 心法

當專案中需要建立新的表單（Form）或頁面佈局（Schema）時，請捨棄傳統的陣列回傳模式，改用「配置流」模式，以達到最佳的擴充性與可讀性。

## 1. 建立 Schema 配置類別

在 `app/Filament/Resources/[ResourceName]/Schemas/` 目錄下建立一個配置類。
路徑範例：`app/Filament/Resources/Order/Schemas/OrderForm.php`

```php
namespace App\Filament\Resources\Order\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === 左側主戰場 ===
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('基本資料')
                            ->schema([
                                TextInput::make('order_no')->required(),
                                // ...
                            ]),
                    ]),

                // === 右側側翼 ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('狀態設定')
                            ->schema([
                                // ...
                            ]),
                    ]),
            ])
            ->columns(3); // 宗師指點：直接在此定義佈局重力
    }
}
```

## 2. 在 Resource 中掛載

在對應的 `Resource.php` 中使用 `configure` 方法來發動陣法：

```php
use App\Filament\Resources\Order\Schemas\OrderForm;
use Filament\Schemas\Schema;

public static function form(Schema $schema): Schema
{
    return OrderForm::configure($schema);
}
```

## 3. 核心優勢 (宗師點評)

- **階層分明**：透過 `->components()` 與 `->columns()`，佈局邏輯與欄位內容分離，結構更清晰。
- **鏈式呼叫**：允許在 Resource 端或配置類端隨時鏈接更多的屬性（如 `->live()`, `->disabled()`）。
- **三位一體**：列表 (List)、建立 (Create)、編輯 (Edit) 均能共享同一套配置邏輯，維護成本極低。

> [!IMPORTANT]
> 在 v4 系統中，請務必確認 `use Filament\Schemas\Schema;` 與 `use Filament\Schemas\Components\...;` 引用正確，避免混用舊版的 `Forms` 命名空間。
