---
name: traditional-chinese-implementation-plan
description: 實作計畫 (Implementation Plan) 必須使用繁體中文呈現。
---

# 繁體中文實作計畫準則 (Traditional Chinese Implementation Plan Guidelines)

在本專案中，所有的實作計畫 (`implementation_plan.md`) 必須嚴格遵守以下準則：

## 1. 核心要求

- **一律使用繁體中文 (Traditional Chinese)** 撰寫計畫內容。
- 計畫的目標描述、變更說明、驗證步驟等，均必須以繁體中文表達。

## 2. 術語使用

- **程式碼相關專有名詞**：可保留英文（如 Class, Model, Controller, Migration, JSONB, nullable 等）。
- **描述性文字**：應全數轉換為繁體中文（如「建立」、「修改」、「刪除」、「連動驗證」、「多租戶隔離」等）。

## 3. 格式規範

- **目標描述 (Goal Description)**：清晰說明本次變更的武學脈絡。
- **建議變更 (Proposed Changes)**：分組說明修改內容，註解部分應保持中英並重或全中文。
- **驗證計畫 (Verification Plan)**：提供具體的測試與驗證步驟說明。

## 4. 實施範例

```markdown
# [目標描述]

重構廠商管理系統，引入多租戶隔離與自定義樞紐模型。

## 建議變更

### 廠商系統

#### [NEW] Caterer.php

- 建立廠商主模型。
- 與 `Semester` 建立多對多關聯。
  ...
```
