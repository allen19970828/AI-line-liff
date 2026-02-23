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

靜態屬性 `$navigationIcon`、`$navigationGroup` 以及一般屬性 `$view` 必須與基底類別（`Resource` 或 `Page`）的宣告 **完全匹配**。

```php
use BackedEnum;
use UnitEnum;

// 1. 圖標：必須包含 BackedEnum (靜態)
protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

// 2. 群組名稱：必須包含 UnitEnum (靜態)
protected static string | \UnitEnum | null $navigationGroup = '校園午餐管理';

// 3. 視圖路徑：在 Page 中是非靜態 (Non-static)
protected string $view = 'filament.pages.custom-page';

// 4. 標籤與排序：相對寬鬆，但建議加上型別標註
protected static ?string $navigationLabel = '學生資料管理';
protected static ?int $navigationSort = 1;
```

> [!CAUTION]
> **Page 類別中的 `$view` 絕不可加 `static`**。如果你看到 `Type of ...::$view must be non-static`，請立刻移除其 `static` 修飾詞。

> [!IMPORTANT]
> 若沒有在檔案開頭 `use UnitEnum;`，則必須寫成 `string|\UnitEnum|null`，否則會報「類別找不到」或型別不匹配。

## 3. 組件分家：Forms vs Schemas

v4 將「佈局元件」與「表單輸入元件」的命名空間拆開了。

### ✅ 修正方式

- **佈局元件 (Layout)**：`Grid`, `Section`, `Group`, `Split` 等，改從 `Filament\Schemas\Components` 引入。
- **輸入元件 (Inputs)**：`TextInput`, `Select`, `Toggle` 等，維持在 `Filament\Forms\Components`。
- **工具類 (Utilities)**：`Get`, `Set` 已移動至 `Filament\Schemas\Components\Utilities\Get`。

## 4. 全域 Actions 統一

v4 將過去分散各處的 Action 命名空間統一。即使是在 Table 內使用的 Actions，也已不再從 `Filament\Tables\Actions` 引用。

### 🚨 錯誤特徵

最常發生在複製舊版程式碼時，系統會報錯：

- `Class "Filament\Tables\Actions\EditAction" not found`
- `Class "Filament\Tables\Actions\DeleteAction" not found`

### ✅ 修正方式

- **統一路徑**：一律改用 **`Filament\Actions\...`**。
- **常用類別與新位置**：
    - `Filament\Actions\Action`
    - `Filament\Actions\CreateAction`
    - `Filament\Actions\EditAction`
    - `Filament\Actions\DeleteAction`
    - `Filament\Actions\DeleteBulkAction`
    - `Filament\Actions\BulkActionGroup`

---

## 5. 工具類別 (Utilities) 的新家

在 v4 中，用於閉包注入的 `Get` 與 `Set` 類別發生了遷移。

### ✅ 修正方式

若在 `visible()`, `disabled()`, `reactive()` 等閉包中使用型別提示，**必須**引用正確的類別：

- **`Get`**：`Filament\Schemas\Components\Utilities\Get`
- **`Set`**：`Filament\Schemas\Components\Utilities\Set`

```php
use Filament\Schemas\Components\Utilities\Get;

// ✅ 正確：明確指定 Get 類別
->visible(fn(Get $get) => in_array($get('type'), ['select', ...]))
```

> [!WARNING]
> 切勿將其型別提示為 `\Closure`，否則會拋出 `TypeError: Argument #1 ($get) must be of type Closure, Filament\Schemas\Components\Utilities\Get given`。這是因為 v4 注入的是一個 **Invokable Object** (可呼叫物件)，而非原生的閉包。

---

## 6. SelectFilter 與閉包注入 (DI)

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

## 7. 命名空間與目錄規範

- **目錄複數**：`App\Filament\Resources\Students` (複數)。
- **Namespace 校正**：確保目錄與 Namespace 完全對應，若有 `Student` (單數) 混用會導致 Autoload 失敗。

> [!TIP]
> 遇到不確定的方法簽章，直接到 `/vendor/filament/` 下 `grep` 該父類別的定義是最快的途徑。
