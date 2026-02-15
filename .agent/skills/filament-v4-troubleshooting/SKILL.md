---
name: filament-v4-troubleshooting
description: 當 Filament 報錯或語法不相容時，檢查是否符合 v4 的全新規格。
---

# Filament v4 故障排除心法

當在升級或新開發 Filament v4 專案遇到 `FatalError`、`null` 注入報錯或「類別找不到」時，請優先啟動此檢查清單。

## 1. 核心 API 變更：從 Form 到 Schema

在 v4 中，`Resource` 與 `RelationManager` 的表單定義已從 `Form` 遷移至 `Schema`。

### 🚨 錯誤特徵

`Could not check compatibility... because class Filament\Forms\Form is not available` 或方法簽章不匹配。

### ✅ 修正方式

- **簽章更換**：將 `form(Form $form)` 改為 `form(Schema $schema): Schema`。
- **引用路徑**：`use Filament\Schemas\Schema;`
- **關鍵方法**：使用 `->components([...])` 取代舊有的 `->schema([...])`。

> [!CAUTION]
> 在 v4 中，如果你看到原本程式碼使用 `Schema` 而不是 `Form`，請**不要隨意改回 `Form`**。這通常意味著該 Resource 已經對齊了 v4 的全新架構。

## 2. 導航屬性強型別與協變性 (Type Invariance)

PHP 8.3+ 與 Filament v4 對於繼承的靜態屬性有嚴格的型別一致性要求。

### 🚨 錯誤特徵

`FatalError: Type of App\Filament\Resources\...\$navigationGroup must be UnitEnum|string|null`

### ✅ 修正方式

靜態屬性 `$navigationIcon`、`$navigationGroup` 等必須與基底類別 `Filament\Resources\Resource` 的宣告 **完全匹配**。

```php
use BackedEnum;
use UnitEnum;

// 1. 圖標：必須包含 BackedEnum
protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

// 2. 群組名稱：必須包含 UnitEnum (或是補上 \UnitEnum 全域命名空間)
protected static string | \UnitEnum | null $navigationGroup = '校園午餐管理';

// 3. 標籤與排序：相對寬鬆，但建議加上型別標註
protected static ?string $navigationLabel = '學生資料管理';
protected static ?int $navigationSort = 1;
```

> [!IMPORTANT]
> 若沒有在檔案開頭 `use UnitEnum;`，則必須寫成 `string|\UnitEnum|null`，否則會報「類別找不到」或型別不匹配。

## 3. 組件分家：Forms vs Schemas

v4 將「佈局元件」與「表單輸入元件」的命名空間拆開了。

### ✅ 修正方式

- **佈局元件 (Layout)**：`Grid`, `Section`, `Group`, `Split` 等，改從 `Filament\Schemas\Components` 引入。
- **輸入元件 (Inputs)**：`TextInput`, `Select`, `Toggle` 等，維持在 `Filament\Forms\Components`。
- **工具類 (Utilities)**：`Get`, `Set` 已移動至 `Filament\Schemas\Components\Utilities\Get`。

## 4. 全域 Actions 統一

v4 的橫向改革，將各處的 Action 命名空間統一。

### ✅ 修正方式

- **不再區分來源**：移除所有 `Filament\Tables\Actions\...` 或 `Filament\Resources\Pages\Actions\...` 引用。
- **全域唯一路徑**：一律改用 **`Filament\Actions\...`**。
- **常用類別**：
    - `Action`
    - `CreateAction`
    - `EditAction`
    - `DeleteAction`
    - `DeleteBulkAction`
    - `BulkActionGroup`

## 5. SelectFilter 與閉包注入 (DI)

### 🚨 錯誤特徵

`Call to a member function where() on null` (發生在 `relationship` 閉包內)。

### ✅ 修正方式

Filament v4 的 `evaluate()` 系統需要明確的 **型別提示** 或 **參數名稱**：

```php
use Illuminate\Database\Eloquent\Builder;

// ❌ 錯誤：fn($q) 導致注入 null
// ✅ 正確：fn(Builder $query)
->relationship('classroom', 'name', fn(Builder $query) => $query->where(...))
```

## 6. 命名空間與目錄規範

- **目錄複數**：`App\Filament\Resources\Students` (複數)。
- **Namespace 校正**：確保目錄與 Namespace 完全對應，若有 `Student` (單數) 混用會導致 Autoload 失敗。

> [!TIP]
> 遇到不確定的方法簽章，直接到 `/vendor/filament/` 下 `grep` 該父類別的定義是最快的途徑。
