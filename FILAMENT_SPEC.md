# Filament 後台技術規格書 (Filament Specification)

## 1. 資源與管理 (Resources)

### 1.1 LunchSettlementResource (午餐結算管理)

- **基礎表格**：顯示教職員每月的結算總額、天數與狀態（草稿、已發送、已支付）。
- **批次動作**：支援「一鍵發送 LINE 通知」與「批次更新付款狀態」。
- **自動化補償**：提供「手動觸發月結算」按鈕，呼叫 `LunchSettlementService`。

---

## 2. 統計與視覺化 (Analytics & Visualization)

### 2.1 DailyDiningPage (每日用餐統計頁面)

這是一個自定義頁面，用於補足單純月結算表的資訊密度問題。

#### A. 視覺組件：日期看板 (Dining Heatmap)

- **UI 結構**：CSS Grid 渲染當月 1-31 號方塊。
- **顏色邏輯**：
    - 灰色：無資料/放假日。
    - 淺綠色：有用餐紀錄，人數偏少。
    - 深綠色：有用餐紀錄，人數達標。
- **互動機制**：點擊方塊後，下方表格自動過濾為該日名單。

#### B. 資料列表：用餐明細 (Dining Details Table)

- **欄位**：教職員姓名、部別、職稱、簽到時間。
- **過濾器**：支援按日期、部別 (國小/幼兒園)、姓名關鍵字搜尋。

---

## 3. 背景與權限 (Context & Permissions)

- **多租戶支援 (Multi-tenancy)**：所有數據必須嚴格遵守 `school_id` 過濾。
- **預載優化 (Eager Loading)**：在統計頁面必須預載 `staff` 與 `user` 關聯，避免 N+1 問題。
- **權限控制**：僅限 `admin` 或具備特定權限的行政人員存取統計分頁。
