# 工讀生 LINE 打卡系統規格書 (Work-Study Punch Specification)

## 1. 功能概述 (Overview)

本模組旨在提供工讀生一個基於 LINE LIFF 的「簽到/簽退」系統。不同於教職員午餐簽到，此系統強調 **「上班會話 (Attendance Session)」**、**「工時計算」** 以及 **「物理位置驗證」**。

## 2. 資料模型 (Database Schema)

### 2.1 AttendanceRecord (出勤紀錄)

採用「一對時間戳記」架構，而非單點紀錄，以利工時計算。

| 欄位名          | 型別      | 說明                                                                 |
| :-------------- | :-------- | :------------------------------------------------------------------- |
| `id`            | BigInt    | Primary Key                                                          |
| `school_id`     | ForeignID | 多租戶關聯                                                           |
| `user_id`       | ForeignID | 工讀生用戶 ID (綁定 LineAccount)                                     |
| `clock_in_at`   | DateTime  | 簽到時間                                                             |
| `clock_out_at`  | DateTime  | 簽退時間 (可為 null，代表尚未下班)                                   |
| `clock_in_ip`   | String    | 簽到時的 IP 地址                                                     |
| `clock_out_ip`  | String    | 簽退時的 IP 地址                                                     |
| `location_data` | JSON      | 儲存簽到/退時的 GPS 經緯度與精準度                                   |
| `status`        | Enum      | `active` (上班中), `completed` (已結案), `exception` (忘記簽退/異常) |
| `total_minutes` | Integer   | 自動計算的總工時 (分)                                                |

---

## 3. UI/UX 流程 (User Flow)

### 3.1 LIFF 入口

- **首頁按鈕**：顯示當前狀態（「點擊上班」或「進行中：已上班 02:30」）。
- **打卡動作**：
    1. 點擊「上班/下班」。
    2. 觸發 `liff.getLocation()` 獲取位置。
    3. 呼叫相機掃描現場 **固定 QR Code**。
    4. 成功後顯示 Flex Message 通知。

---

## 4. API 規格 (API Integration)

### 4.1 提交打卡 (PUNCH)

- **方法**：`POST`
- **端點**：`/api/liff/attendance/punch`
- **參數**：
    - `code`: 現場 QR Code 內容。
    - `lat`: 緯度。
    - `lng`: 經度。
    - `type`: `auto` (系統自動判定簽到或簽退)。

### 4.2 獲取當前狀態 (STATUS)

- **方法**：`GET`
- **端點**：`/api/liff/attendance/current`
- **回應**：顯示是否正在上班中、開始時間、累積工時。

---

## 5. 安全與防範機制 (Security & Anti-cheating)

### 5.1 雙重屬地驗證

- **地理圍欄 (Geofencing)**：座標必須與學校中心點距離 < 200 公尺 (可調整)。
- **物理特徵碼**：QR Code 包含學校專屬 `checkin_code`，防止在校外偽造地點。

### 5.2 異常處理

- **自動結案**：若簽到超過 12 小時未簽退，系統自動標記為 `exception` 並發送 LINE 通知。
- **補卡機制**：工讀生可提出「補卡申請」，由行政管理員在 Filament 後台審核修正。

---

## 6. 管理介面 (Filament Admin)

- **AttendanceResource**：管理員可查閱所有出勤明細、GPS 座標、導出 Excel。
- **SalaryCalculator**：根據 `$navigationSort` 設定的時薪，一鍵產出工讀金領清單。
