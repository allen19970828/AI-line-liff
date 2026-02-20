# LIFF 應用技術規格書 (LIFF Specification)

## 1. 介面架構 (Component System)

為了兼顧手機 WebView 的載入效能與程式碼的可維護性，LIFF 採用 **Blade 組件化** 拆分 UI，但保持 **邏輯集中式載入**。

### 1.1 UI 組件目錄

所有子頁面均存放於 `resources/views/liff/components/`：

- `dashboard.blade.php`: 主儀表板，顯示個人資訊與通知。
- `surveys.blade.php`: 問卷調查系統（支援 Pending/History 狀態）。
- `cancellation.blade.php`: 停餐申請系統（教職員/學生/團體）。
- `complaints.blade.php`: 午餐狀況回報（支援拍照上傳與歷史清單）。
- `settlement.blade.php`: 個人用餐明細與結算狀態（當月餐數統計）。
- `settings.blade.php`: 個人化設定。

### 1.2 載入策略 (Loading Strategy)

- **順序執行優先**：邏輯採用內嵌 JavaScript (Inline Script)，確保在「SDK 初始化」→「資料獲取」→「Alpine 啟動」之間具有明確的執行順序。
- **混合環境相容**：不依賴 Vite 動態模組，以解決在 ngrok 或特殊 Webview 環境下的混合內容 (Mixed Content) 限制。
- **外部資源**:
    - `LINE SDK`: `https://static.line-scdn.net/liff/edge/2/sdk.js`
    - `Axios`: `https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js`
    - `Alpine.js`: `https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js` (使用 `defer` 載入)

---

## 2. 狀態管理 (Alpine.js State)

系統使用單一的 `appData` store 管理全域狀態。

### 2.1 核心 Data Store

```javascript
{
    activeTab: 'dashboard',   // 當前切換的分頁
    loading: true,            // 全域載入狀態
    staff: null,              // 登入教職員資訊
    settings: { ... },        // 用戶設定 (通知開關等)
    surveys: { ... },         // 問卷清單數據
    cancellation: { ... },    // 停餐申請狀態機
    complaints: { ... },      // 回報單數據錄
    settlement: {             // 教職員個人結算數據
        yearMonth: '',
        daysCount: 0,
        amount: 0,
        dates: []             // 具體用餐日期清單
    }
}
```

---

## 3. 功能模組規範

### 3.1 停餐申請 (Meal Cancellation)

- **個人模組**：教職員本人或單一學生停餐。
- **團體模組**：班級活動（導師用）或跨班團體（行政用）。
- **驗證邏輯**：包含提前申請天數、每日截單時間檢查。

### 3.2 狀況回報 (Meal Complaints)

- **拍照上傳**：調用原生相機介面並將圖片 Base64 處理後透過 API 上傳。
- **異動追蹤**：整合後端 Activity Log 正確顯示處理狀態。

---

## 4. API 互動 (Backend Integration)

| 功能         | 方法 | 端點                     | 說明                       |
| :----------- | :--- | :----------------------- | :------------------------- |
| **初始化**   | GET  | `/api/liff/init`         | 獲取教職員、設定與初始數據 |
| **停餐申請** | POST | `/api/meal-cancellation` | 提交新的停餐單             |
| **問卷數據** | GET  | `/api/liff/surveys`      | 獲取待填寫與歷史問卷       |
| **提交回報** | POST | `/api/meal-complaints`   | 提交品質回報與照片         |

---

## 5. UI/UX 標準 (Premium Design)

- **微互動**：使用 `transition` 於分頁切換與按鈕 Click。
- **回饋機制**：所有 API 調用需伴隨按鈕 `loading` 狀態 (`setBtnLoading`)。
- **安全區域**：適配 iOS/Android 的 Safe Area Insets (`viewport-fit=cover`)。
