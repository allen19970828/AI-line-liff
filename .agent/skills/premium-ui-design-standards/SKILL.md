---
name: premium-ui-design-standards
description: 定義專案中的「精品級」UI/UX 設計標準，包含 Filament 後台與 LIFF 前端的具體美學規範與技術實作。
---

# 精品級 UI/UX 設計標準 (Premium UI/UX Design Standards)

本規範旨在確保所有新專案、新功能均能維持「高品質」、「有質感」且「直覺」的使用者體驗。

## 1. Filament 後台設計心法 (Admin Aesthetics)

在 Filament Resource 中，嚴禁直接平舖欄位。必須透過「資訊架構」營造專業感。

### 1a. 區塊化與導航 (Sections & Icons)

- **必須使用 Section**：將相關欄位群組化，並賦予明確的 `icon()` 與 `description()`。
- **情感導引**：Label 應使用台灣習慣之稱呼（如：學制/部別），Helper Text 應提供「心法級」提示而非冗贅描述。

### 1b. 高級布局規範 (Layout & Grid)

- **2:1 非線性布局**：主戰場（佔 2/3）放置核心資訊，側翼（佔 1/3）放置狀態、計價、時間戳等次要資訊。
- **格柵系統**：利用 `Grid` 讓表單在一行內呈現 2~3 個緊密相關的欄位（如：學期代碼與學制）。

### 1c. 代碼架構 (Schema/Table Separation)

- **分離原則**：遵循「劍刃 (Schema)」與「劍脊 (Table)」分離模式，將 `form` 與 `table` 的定義抽離至單獨類別（如 `SemesterForm.php`），保持 Resource 類別乾淨。

---

## 2. LIFF/移動端美學標準 (Mobile UI Standards)

移動端界面必須給人「輕量」、「呼吸感」與「精品」的視覺印象。

### 2a. 現代色彩與材質 (Colors & Texture)

- **毛玻璃效果 (Glassmorphism)**：固定式組件（如導航列）應使用 `bg-xxx/90` 搭配 `backdrop-blur-sm`。
- **背景層次**：底層使用 `bg-gray-50` 搭配卡片式的白色背景，營造深度感。

### 2b. 圓角與間距 (Geometry & Spacing)

- **高圓角規範**：卡片與按鈕應使用 `rounded-2xl` 或 `rounded-3xl`，避免 90 度死角。
- **按鈕質感**：使用 `py-3` 或 `py-4` 的大按鈕，確保點擊舒適度。

### 2c. 手機適配與細節 (Mobile Optimization)

- **Safe Area**：必須在底部加入 `safe-bottom` 類別，避免內容被操作條遮擋。
- **微動畫 (Transitions)**：狀態轉場應有微小的淡入淡出或 Skeleton Loading 防止畫面閃爍。

---

## 3. 技術實作準則 (Implementation Rules)

- **Alpine.js 驅動**：所有 UI 狀態變更（顯示/隱藏、動態列表）優先使用 Alpine.js 原生驅動。
- **Z-Index 規範**：Loading 遮罩應設在最高層（如 `z-60`），彈窗設在 `z-50`。
- **SEO 與語意化**：確保隱藏的 UI 元素初始狀態為 `hidden`，並在揭露時賦予正確的佈局屬性。

## 4. 關鍵寫作風格 (Workflow Guidelines)

- **宗師註解**：在代碼中加入帶有情感的註解（如：`// 宗師指點：確保不會撈到別校的學期`），提煉業務邏輯的核心價值。
- **直攻精華**：API 回傳結構必須極簡，僅保留 UI 渲染所需的欄位。
