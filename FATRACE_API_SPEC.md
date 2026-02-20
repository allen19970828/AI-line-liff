# 校園食材登錄平台 (FatRaceSchool) API 對接規格書

## 1. 概述

本文件記錄 `AI-line-liff` 專案如何與教育部「校園食材登錄平台」進行數據對接，以獲取學校的每日午餐菜單資訊。

## 2. 基礎資訊

- **官方入口**: `https://fatraceschool.k12ea.gov.tw/frontend/search.html`
- **對接方式**: RESTful HTTP Get (JSON 返回)
- **主要類別**: `App\Services\MealService::getMenuFromFatrace`

## 3. API 端點詳細說明

對接採用「兩段階段式」請求，先取得當日批次編號，再取得詳細菜色。

---

### STEP 1: 獲取當日餐點元數據 (Metadata)

透過學校代碼與日期，查詢該校當日是否有供餐，並取得關鍵的 `BatchDataId`。

- **URL**: `https://fatraceschool.k12ea.gov.tw/offered/meal`
- **Method**: `GET`
- **參數**:
    - `SchoolId`: 5 或 6 位數學校代碼（若首位為 0，需移除。如 `064616` 轉為 `64616`）。
    - `period`: 日期格式 `YYYY-MM-DD`。
    - `KitchenId`: 固定傳入 `all`。
    - `MenuType`: 固定傳入 `1` (代表午餐)。

- **關鍵返回欄位 (`data.0`)**:
    - `BatchDataId`: 後續查詢菜色所需的唯一編號。
    - `Calorie`: 當日總熱量。
    - `SchoolName`: 平台端紀錄的學校名稱。

---

### STEP 2: 獲取詳細菜色清單 (Dishes)

使用上一步取得的 `BatchDataId` 抓取具體的菜名與內容。

- **URL**: `https://fatraceschool.k12ea.gov.tw/dish`
- **Method**: `GET`
- **參數**:
    - `BatchDataId`: 由 STEP 1 取得。

- **主要返回內容 (`data`)**:
    - 陣列結構，包含：
        - `DishName`: 菜名。
        - `DishType`: 類別 (如：主食、主菜、副菜)。
        - `Ingredient`: 使用食材。

---

## 4. 異常處理與快取

1. **查無資料**: 若 STEP 1 返回空陣列，代表該校當日未登錄或不供餐，系統應回傳 `null`。
2. **格式轉換**: 獲取的原始資料會被重新封裝為系統內部的 `array` 格式，供 `GetDailyMenuTool` 轉化為 LINE Flex Message。
3. **快取策略**: 由於同一天菜單不會變動，生產環境中建議對相同 `SchoolCode` 與 `Date` 的結果進行 12 小時快取以減少外部請求。

## 6. 技術探勘與合規筆記 (Discovery & Compliance)

### 6.1 探勘背景

本專案使用的 API 並非官方正式對外宣告的 Open Data 介面，而是透過對官方查詢網頁進行 **網路偵查 (Network Inspection)** 所獲取的內部異步請求端點 (XHR)。

- **探勘手段**: 透過瀏覽器開發者工具觀察頁面在「不跳頁查詢」時的 Fetch/XHR 流量。
- **邏輯重組**: 解析出 `BatchDataId` 的鏈路依賴關係。

### 6.2 合規實務建議

由於對接的是非公開 API，為確保專案的長久運作與道德合規，實作上應遵守以下原則：

1. **極致快取**: 嚴禁高頻重複請求。應將查詢結果進行本地快取，避免增加政府伺服器負擔。
2. **來源尊重**: 在 UI 介面（如 LINE Flex Message）中必須保留指向官方網頁的連結。
3. **擴展建議**: 若未來系統規模擴大至全台等級，建議向教育部申請正式的 API 授權或存取官方 Open Data 資料集。

---

_最後更新日期: 2026-02-18_
