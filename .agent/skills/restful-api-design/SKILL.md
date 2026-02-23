---
name: restful-api-design
description: 專案專屬的 RESTful API 設計與實作標準規範。當需要建立、修改 API 路由或 Controller 時應啟用此規範。
---

# RESTful API 設計規範 (AI-Lunch 專案標準)

本規範旨在統一 AI-Lunch 專案中所有 API 的路徑命名、HTTP 動詞使用、回應結構以及錯誤處理，確保系統具備良好的可預測性、一致性，並利於 AI 工具 (AI Tools) 與 LIFF 前端的介接。

## 1. 路由命名 (Routing)

### 資源化路徑

- **使用複數名詞**：URL 應該代表資源 (Resource)，而非動作。
    - 正確：`GET /api/v1/students`
    - 錯誤：`GET /api/v1/getStudents`
- **全小寫與 Kebab-case**：路徑中的多個單字應以連字號分隔。
    - 正確：`GET /api/v1/meal-cancellations`
    - 錯誤：`GET /api/v1/mealCancellations`
- **禁止在 URL 中包含動詞**：動詞應由 HTTP Method 表達。
    - 正確：`POST /api/v1/bindings` (建立綁定)
    - 錯誤：`POST /api/v1/bind`

## 2. HTTP 動詞使用 (HTTP Methods)

| 動詞       | 用途                     | 成功回應碼              | 說明                                                 |
| :--------- | :----------------------- | :---------------------- | :--------------------------------------------------- |
| **GET**    | 讀取資源或列表           | 200 OK                  | 必須具備等冪性 (Idempotent)，不應修改伺服器狀態。    |
| **POST**   | 建立新資源或執行特殊動作 | 201 Created / 200 OK    | 用於新增資料或觸發非 CRUD 的複合動作 (如：`login`)。 |
| **PUT**    | 替換/更新完整資源        | 200 OK                  | 用於覆蓋整筆資料。                                   |
| **PATCH**  | 部分更新資源內容         | 200 OK                  | **(優先建議)** 只修改特定欄位。                      |
| **DELETE** | 刪除/撤銷資源            | 204 No Content / 200 OK | 刪除指定資源。                                       |

## 3. 版本控制 (Versioning)

- 所有的 API 路由必須加上版本前綴，預設為 `v1`。
- 範例：`Route::prefix('v1')->group(...)`。

## 4. 回應格式 (Response Structure)

一致的回應格式有助於前端與 AI 工具穩定解析。

### 成功回應 (Standard Envelope)

```json
{
    "status": "success",
    "data": { ... },
    "meta": { "total": 100 } // 選填，用於分頁或額外資訊
}
```

### 錯誤回應 (Error Envelope)

```json
{
    "status": "error",
    "message": "人類可讀的錯誤描述",
    "code": "SPECIFIC_ERROR_CODE", // 選填
    "errors": { ... } // 針對 422 驗證錯誤的詳細清單
}
```

## 5. 常見狀態碼與適用情境

- **200 OK**：處理成功。
- **201 Created**：成功建立資源 (POST 常用)。
- **400 Bad Request**：一般的參數錯誤或邏輯不合規範 (不含驗證失敗)。
- **401 Unauthorized**：未登入或 Token 過期。
- **403 Forbidden**：權限不足，禁止存取。
- **404 Not Found**：找不到資源。
- **422 Unprocessable Entity**：Laravel 預設的驗證失敗 (ValidationError) 專用代碼。
- **429 Too Many Requests**：達到速率限制 (Rate Limit)。
- **500 Internal Server Error**：伺服器內部非預期崩潰。

## 6. 企業級進階規範 (Enterprise Standards)

### 6.1 分頁、篩選與排序 (Filtering, Sorting & Pagination)

- **統一分頁參數**：使用 `page` 與 `per_page`。
    - `GET /api/v1/students?page=1&per_page=15`
- **分頁回應結構**：
    ```json
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150
    }
    ```
- **統一篩選參數**：建議使用 `filter[field]`。
- **統一排序參數**：使用 `sort` 參數，前綴 `-` 代表降冪。
    - `GET /api/v1/students?sort=-created_at`

### 6.2 速率限制 (Throttling)

- 所有的 API 必須套用 `throttle` 中介層。
- 對於登入、綁定等敏感操作，應設置嚴格的頻率限制。

### 6.3 安全性與資料隱私

- **顯式指定內容類型**：要求客戶端發送 `Accept: application/json`。
- **敏感資料隱藏**：確保 API Resource 中使用 `hidden` 或篩選欄位，嚴禁洩漏密碼、Token 或機敏個人資訊。

### 6.4 HATEOAS (資源自我探索)

- 在回應中盡可能包含相關資源的連結 (`links`)，降低前端組裝網址的負擔。
    ```json
    "links": {
        "self": "/api/v1/students/123",
        "classroom": "/api/v1/classrooms/45"
    }
    ```

### 6.5 文件化標準

- 使用貼近程式碼的標註 (如 OpenAPI/Swagger) 或自動化工具生成 API 文件。

## 7. AI 工具相容性建議

- **明確的 Property Description**：在 Controller 方法中加入詳細的 PHPDoc，說明每個欄位的內容，以便 `mcp-development` 技能提取為 AI Tool 描述。
- **邏輯抽離**：將判斷邏輯保留在 Service，Controller 僅處理 Request 驗證與 Response 封裝。

## 8. 重構核對清單 (Refactoring Checklist)

當您在維護現有 API 時，請檢查：

- [ ] URL 裡是否還有 `get`, `update`, `delete` 等動詞？ (應移除)
- [ ] 是否正確使用了 `GET` 查詢與 `PATCH` 更新？
- [ ] 錯誤回應是否有回傳正確的 HTTP 狀態碼 (而非一律 200)？
- [ ] 分頁結構是否統一使用 `meta` 欄位？
- [ ] 是否已針對敏感欄位進行過濾？
- [ ] 資源是否使用了複數命名？

## 9. AI-First 與未來擴充性 (AI-First & AI-Ready)

為了確保 API 能被大型語言模型 (LLM) 準確理解並高效調用 (Function Calling)，必須遵循以下「AI 友善」原則：

### 9.1 語意化欄位命名 (Semantic Naming)

- **避免縮寫**：AI 對全稱的理解遠勝於縮寫。
    - 正確：`is_automatic_settlement`
    - 錯誤：`is_auto_setl`
- **自帶 Context**：欄位名稱應能自我解釋，減少 AI 猜測的成本。

### 9.2 模式描述 (Schema & Descriptions)

- **PHPDoc 強化**：在 Controller 方法與 DTO (Data Transfer Objects) 中，必須為每個輸入參數加上屬性描述，這會直接影響 AI Tool 的生成品質。
- **類型約束**：明確標註 `string`, `int`, `boolean` 以及可能的 `enum` 值。

### 9.3 錯誤引導 (Reasoning Support in Errors)

- 當 AI 調用失敗時，錯誤訊息應具備「可偵錯性」，告知 AI 應如何修正參數。
    - 範例：`"message": "學生 ID 無效，請確認該學生是否確實隸屬於您的管理班級中。"`

### 9.4 狀態追蹤 (Async AI Tasks)

- 對於耗時的 AI 運算 (如生成長篇報告)，應採用非同步設計：
    1. POST 建立任務並回傳 `202 Accepted` 與 `task_id`。
    2. 提供 GET 查詢任務狀態。
    3. 完成後提供結果或下載連結。

### 9.5 元數據與追蹤 (Metadata & Tracing)

- 在回應中包含 `X-Request-Id` 或自訂追蹤碼，方便 AI 客戶端在遇到問題時提供參考 ID 給人類開發者。

## 10. 重構核對清單 (Refactoring Checklist)

當您在維護現有 API 時，請檢查：

- [ ] URL 裡是否還有 `get`, `update`, `delete` 等動詞？ (應移除)
- [ ] 是否正確使用了 `GET` 查詢與 `PATCH` 更新？
- [ ] 錯誤回應是否有回傳正確的 HTTP 狀態碼 (而非一律 200)？
- [ ] 分頁結構是否統一使用 `meta` 欄位？
- [ ] 是否已針對敏感欄位進行過濾？
- [ ] 資源是否使用了複數命名？
- [ ] **(AI 檢查)** 欄位名稱是否具備足夠的語意化？
- [ ] **(AI 檢查)** 方法是否有對應的 PHPDoc 描述供 Tool 調用？
