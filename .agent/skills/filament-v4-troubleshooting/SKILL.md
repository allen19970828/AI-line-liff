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

### 🚨 錯誤特徵

系統報錯：

- `Class "Filament\Forms\Components\Section" not found`
- `Class "Filament\Forms\Components\Group" not found`

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

## 8. 路由命名一致性 (Routing Consistency)

在將 Filament 的功能（如列印、導出）從 Livewire 重構為獨立 Controller 以提升穩定性時，常會遇到路由名稱不匹配的問題。

### 🚨 錯誤特徵

`Symfony\Component\Routing\Exception\RouteNotFoundException: Route [admin.print.xxx] not defined.`

### ✅ 修正方式

- **檢查標點符號**：仔細檢查 `routes/web.php` 中定義的 `->name()` 是否與 Blade 視圖或 Controller 中 `route()` 函式呼叫的字串完全一致。常見錯誤是點號 (`.`) 與連字號 (`-`) 混用。
- **命名規範建議**：在 Filament 專案中，建議遵循「點號層級命名」（Dot notation），例如使用 `admin.print.lunch-fee-notification.render`。這與 Filament 內部的命名習慣較為接近，且具備更好的語意層級。

```php
// ✅ 推薦方法：routes/web.php
Route::post('/admin/print/render', PrintController::class)
    ->middleware(['auth'])
    ->name('admin.print.lunch-fee-notification.render');

// ✅ Blade 視圖連結
<form action="{{ route('admin.print.lunch-fee-notification.render') }}" method="POST">
```

## 9. 穩定性重構：從 Livewire 轉向 Controller (Stable Operations)

當 Filament 頁面中的某些操作（如生成大型 PDF、複雜報表、批量列印）在 Livewire 中頻繁報 500 錯誤或狀態迷失時，應考慮「弱化 Livewire」的重構策略。

### 🚨 錯誤特徵

- `Livewire\Exceptions\ComponentNotFoundException`
- 頁面重新載入後 `$wire` 資料丟失。
- 異步請求逾時或高負載下的不可靠行為。

### ✅ 修正方式

使用 **Filament Page (輸入介面) + Backend Controller (執行介面)** 的架構：

1. **視圖層**：使用標準 HTML `<form>` 並將 `target="_blank"` 設為新視窗。
2. **資料傳遞**：將 Livewire 的資料 JSON 化後透過 `hidden input` 傳遞。
3. **後端處理**：建立專用的 `__invoke` Controller 接收 POST 請求並執行邏輯。

```blade
<!-- ✅ 穩定模式：resources/views/filament/pages/xxx.blade.php -->
<form action="{{ route('admin.print.render') }}" method="POST" target="_blank">
    @csrf
    <input type="hidden" name="data" :value="JSON.stringify($wire.data)">

    <x-filament::button type="submit">
        立即執行 (穩定模式)
    </x-filament::button>
</form>
```

```php
// ✅ 後端：app/Http/Controllers/Admin/PrintController.php
public function __invoke(Request $request)
{
    $data = json_decode($request->input('data'), true);
    // 執行複雜邏輯並回傳 View 或 FileStream
}
```

> [!TIP]
> 這種方式能徹底規避 Livewire 的 Lifecycle 複雜性，特別適合「一次性、高耗時、需要開啟新視窗」的操作。

> [!TIP]
> 遇到不確定的方法簽章，直接到 `/vendor/filament/` 下 `grep` 該父類別的定義是最快的途徑。
