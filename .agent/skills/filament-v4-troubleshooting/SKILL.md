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

## 2. 導航屬性強型別 (PHP Invariant Properties)

PHP 8.3+ 與 Filament v4 對屬性型別要求嚴格一致。

### 🚨 錯誤特徵

`Type of ...::$navigationGroup must be UnitEnum|string|null`

### ✅ 修正方式

必須明確標註聯集型別（Union Types）：

```php
use BackedEnum;
use UnitEnum;

protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
protected static string | UnitEnum | null $navigationGroup = '學籍與補助';
protected static ?string $navigationLabel = '學生資料管理';
protected static ?int $navigationSort = 1;
```

## 3. 組件分家：Forms vs Schemas

v4 將「佈局元件」與「表單輸入元件」的命名空間拆開了。

### ✅ 修正方式

- **佈局元件 (Layout)**：`Grid`, `Section`, `Group`, `Split` 等，改從 `Filament\Schemas\Components` 引入。
- **輸入元件 (Inputs)**：`TextInput`, `Select`, `Toggle` 等，維持在 `Filament\Forms\Components`。
- **工具類 (Utilities)**：`Get`, `Set` 已移動至 `Filament\Schemas\Components\Utilities\Get`。

## 4. 全域 Actions 統一

v4 的橫向改革，將各處的 Action 命名空間統一。

### ✅ 修正方式

- 移除 `Filament\Tables\Actions\...` 引用。
- 改用 **`Filament\Actions\...`** (包含 `CreateAction`, `EditAction`, `DeleteAction`, `DeleteBulkAction` 等)。

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
