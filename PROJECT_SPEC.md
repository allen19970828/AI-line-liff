# AI-line-liff 專案技術規格書 (Project Specification)

## 1. 專案概述

本專案是一個基於 Laravel 12 開發的 **AI 驅動學校午餐管理系統**。系統整合了 LINE 生態圈（LIFF 與 Bot）以及先進的 AI 技術（Prism），提供學校管理員、教職員與學生一個高效、透明的午餐管理平台。系統具備強大的**多租戶（Multi-tenancy）**架構，支援多所學校同時運行且資料完全隔離。

## 2. 核心技術棧

- **後端框架**: Laravel 12
- **後台管理**: Filament v4 (TALL Stack)
- **資料庫**: PostgreSQL (支援 pgvector)
- **身份與安全**:
    - **Filament Breezy**: 提供個人資料官理、2FA 二階段驗證、頭像上傳。
    - **Filament Shield**: 基於 Spatie Permission 的權限管理。
    - **Auto Logout**: 閒置自動登出機制。
- **日誌追蹤**:
    - **Filament Activity Log**: 模型異動紀錄（pxlrbt/filament-activity log）。
    - **Authentication Log**: 使用者登入/登出紀錄。
    - **Laravel Log**: 系統日誌查看器（storage/logs）。
- **AI 整合**: Laravel AI, Prism (Echolabs), MCP (Model Context Protocol)
    - **主要引擎**: Google Gemini 2.5 Flash Lite (提供極低延遲與高精準工具調用)。
    - **開發備援**: Groq (Llama 3.3), Ollama (地端 TAIDE/Qwen)。
- **LINE 整合**: LINE Bot SDK, Socialite (LINE Provider), LIFF
- **工具與品質**: Laravel Pail, Laravel Pint, Pest (測試)

## 3. 核心功能與架構

### 3.1 多租戶架構 (Multi-tenancy)

- **學校隔離**: 透過 `BelongsToSchool` Trait 實現 Global Scope 隔離。所有模型（如學期、用戶、學生等）均會自動過濾所屬學校的資料。
- **權限分級**: 支援超級管理員（可切換學校）與學校管理員（僅限自身學校資料）。

### 3.2 安全與審計 (Security & Auditing)

- **身份驗證**: 整合 LINE Login，超級管理員具備傳統登入與 **2FA (二階段驗證)** 保護。
- **閒置保護**: 若使用者長時間未操作，系統將自動導向登出頁面以策安全。
- **全方位日誌**:
    - **模型層級**: 紀錄資料的新增、修改、刪除（誰在何時改了什麼）。
    - **存取層級**: 紀錄所有登入、失敗嘗試以及裝置資訊。
    - **系統層級**: 可直接在後台查閱 Laravel log 檔案，方便快速除錯。

### 3.3 學期與午餐管理

- **學期設定**: 支援國小、幼兒園、國中等不同學制的學期設定。
- **餐費規則**: 精細的餐費拆解（食材費、點心費等）與退費機制。
- **行事曆管理**: 處理補課、放假、活動不供餐等例外日期。
- **水果日計畫**: 支援設定特定星期為水果日。

### 3.4 AI 智能代理 (LunchAgent)

系統搭載了基於 MCP 協議的 `LunchAgent`，能理解自然語言並操作以下工具：

- **停餐管理 (`cancel_meal`)**: 自動根據身分（老師/家長）處理個人、班級或團體申請。
- **查詢停餐 (`list_cancellations`)**: 即時產出格式化的停餐名單。
- **查詢學生 (`get_student_info`)**: 對接 `StudentService` 查詢學生在學狀態與午餐訂閱情況。
- **查詢菜單 (`get_daily_menu`)**: 透過 `MealService` 實時對接「校園食材登錄平台」獲取真實菜色。

### 3.5 外部 API 整合

- **校園食材登錄平台 (FatRaceSchool)**:
    - 透過隱藏 API 獲取全台學校的每日配餐資料。
    - 詳細規格請參閱 [FATRACE_API_SPEC.md](./FATRACE_API_SPEC.md)。
- **行事曆管理**: 處理補課、放假、活動不供餐等例外日期。
- **水果日計畫**: 支援設定特定星期為水果日。

### 3.6 AI 與 MCP 整合

- **AI 助手**: 整合 Prism 提供智慧化的數據分析與決策支援。
- **MCP 伺服器**: 透過 `Laravel\Mcp` 暴露專屬工具與資源，讓 AI 代理（如 Antigravity）能更深層地理解並操作系統業務邏輯。

### 3.4 LINE 生態整合 (Deep Dive)

- **統一身份認證 (Unified Identity)**: 系統以 LINE ID 作為唯一且絕對的 User ID。除超級管理員外，廢除傳統密碼登入，所有角色均透過 LINE Login 進入系統。
- **LINE Login (Socialite)**:
    - **後台**: 整合 Laravel Socialite，管理員可透過 LINE 一鍵登入 Filament 控制面板。
    - **前台 (LIFF)**: 採用原生 LIFF SDK (CDN 版本) 開發，前端獲取 `idToken` 後傳送至後端 `/api/auth/liff` 進行驗證。
- **身分自動補全 (Profile Completion)**:
    - 首次登入的 LINE 用戶會被標記為 `is_profile_completed = false`。
    - 系統會強制引導用戶至資料補全頁面選擇「學校」與「角色」（學生/教職員），完成「認祖歸宗」流程。
- **多型停餐申請 (Polymorphic Meal Cancellation)**:
    - 停餐機制採用多型關聯 (`cancellable`)，同時支援學生與教職員透過 LIFF 提交申請。
    - 申請流程：LIFF 提交 -> 管理員核准 -> 觸發退費或通知流程。
- **LINE Bot 通知**: 整合 LINE Messaging API，針對審核結果、學期異動、餐費通知進行主動推播。

### 3.5 停餐與回報系統 (Meal Management)

- **彈性停餐**: 支援教職員個人、班級活動與跨班團體（MealCancellationGroup）停餐申請。
- **午餐狀況回報**: 提供即時的午餐品質與異常狀況回報機制（MealComplaint），支援現場拍照。
- **前端實作**: 詳細架構請參閱 [LIFF 技術規格書](./LIFF_SPEC.md)。

## 4. 核心資料模型

| 模型                      | 說明                                                                           |
| :------------------------ | :----------------------------------------------------------------------------- |
| **School**                | 學校基本資訊，為多租戶架構的核心根節點。                                       |
| **Semester**              | 學期設定，包含起訖日期、餐費規則、學制部別（支援多租戶隔離）。                 |
| **User**                  | 系統用戶，綁定學校，具備角色與權限（支援多租戶隔離）。                         |
| **LineAccount**           | 存放 LINE 授權資訊（UID、Token、頭像），與 User 一對一綁定（支援多租戶隔離）。 |
| **MealCancelRequest**     | **多型模型**，紀錄學生或教職員的停餐申請與審核狀態（支援多租戶隔離）。         |
| **MealCancellationGroup** | **團體停餐管理**，用於申請跨班級的團體活動停餐。                               |
| **MealComplaint**         | **午餐狀況回報**，紀錄時間、地點、狀況內容及照片證明。                         |
| **Student**               | 學生資料，與家長（User）及學期相關聯（支援多租戶隔離）。                       |

## 5. 安全與權限設計

- **結界機制 (Multi-tenancy isolation)**:
    - 所有核心模型均導入 `BelongsToSchool` 護法。
    - 透過 Global Scope 確保 A 校管理員絕對無法讀取 B 校資料。
    - 在身分驗證（如 LINE Login）等跨租戶場景，使用 `withoutGlobalScopes()` 確保身分識別不因隔離而失效。
- **身分驗證**: 以 LINE Login 為主。
- **權限控管**: 嚴格的 RBAC，結合 Filament Shield 進行細粒度的按鈕與頁面授權。
- **操作日誌**: 整合 Filament Activity Log 與 Authentication Log，達成「萬事皆有痕，凡走過必留下紀錄」。

## 7. 開發環境與部署 (Development & Deployment)

### 7.1 Docker 化開發 (Laravel Sail)

本專案全面採用 **Laravel Sail (Docker)** 作為標準開發環境。這確保了從本地開發到 Zeabur 雲端部署的高度一致性。

- **啟動環境**: `./vendor/bin/sail up -d`
- **資料庫整合**: 內建 PostgreSQL 18、Redis 與 Mailpit (郵件調試)。
- **前端編譯**: `sail npm run dev` 或 `sail npm run build`。
- **資料庫遷移**: `sail artisan migrate --seed`。

### 7.2 部署指引 (Zeabur)

- **自動化部署**: 系統整合 GitHub Action 與 Zeabur，推送至 `main` 分支即觸發自動建置。
- **環境變數**: 部署後需在 Zeabur 後台配置 `APP_KEY`、`LINE_CHANNEL_ID` 等必要密鑰。
- **靜態資源**: 部署流程會自動執行編譯，免手動上傳 `public/build`。
