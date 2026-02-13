# AI-line-liff 專案技術規格書 (Project Specification)

## 1. 專案概述

本專案是一個基於 Laravel 12 開發的 **AI 驅動學校午餐管理系統**。系統整合了 LINE 生態圈（LIFF 與 Bot）以及先進的 AI 技術（Prism），提供學校管理員、教職員與學生一個高效、透明的午餐管理平台。系統具備強大的**多租戶（Multi-tenancy）**架構，支援多所學校同時運行且資料完全隔離。

## 2. 核心技術棧

- **後端框架**: Laravel 12
- **後台管理**: Filament v4 (TALL Stack: Tailwind CSS, Alpine.js, Laravel, Livewire)
- **資料庫**: PostgreSQL (支援 pgvector)
- **AI 整合**: Laravel AI, Prism (Echolabs), MCP (Model Context Protocol)
- **LINE 整合**: LINE Bot SDK, Socialite (LINE Provider), LIFF
- **權限管理**: Filament Shield (基於 Spatie Permission)
- **工具與品質**: Laravel Pail, Laravel Pint, Pest (測試)

## 3. 核心功能與架構

### 3.1 多租戶架構 (Multi-tenancy)

- **學校隔離**: 透過 `BelongsToSchool` Trait 實現 Global Scope 隔離。所有模型（如學期、用戶、學生等）均會自動過濾所屬學校的資料。
- **權限分級**: 支援超級管理員（可切換學校）與學校管理員（僅限自身學校資料）。

### 3.2 學期與午餐管理

- **學期設定**: 支援國小、幼兒園、國中等不同學制的學期設定。
- **餐費規則**: 精細的餐費拆解（食材費、點心費等）與退費機制。
- **行事曆管理**: 處理補課、放假、活動不供餐等例外日期。
- **水果日計畫**: 支援設定特定星期為水果日。

### 3.3 AI 與 MCP 整合

- **AI 助手**: 整合 Prism 提供智慧化的數據分析與決策支援。
- **MCP 伺服器**: 透過 `Laravel\Mcp` 暴露專屬工具與資源，讓 AI 代理（如 Antigravity）能更深層地理解並操作系統業務邏輯。

### 3.4 LINE 生態整合

- **LINE Login**: 使用者透過 LINE 帳號一鍵登入，簡化身份校驗。
- **LIFF 介面**: 提供學生與教職員在行動端的操作介面（如訂餐、請假、查看菜單）。
- **LINE Bot**: 透過機器人主動推播通知（如訂單確認、餘額不足提醒）。

## 4. 核心資料模型

| 模型                  | 說明                                         |
| :-------------------- | :------------------------------------------- |
| **School**            | 學校基本資訊，包含統編、地址、代碼等。       |
| **Semester**          | 學期設定，包含起訖日期、餐費規則、學制部別。 |
| **User**              | 系統用戶，綁定所屬學校，並具備不同權限角色。 |
| **SemesterException** | 學期例外日期設定（放假、補課）。             |
| **Student**           | 學生資料，與家長（User）及學期相關聯。       |

## 5. 安全與權限設計

- **身份驗證**: 以 LINE Login 為主，並為超級管理員保留傳統登入入口。
- **權限控管**: 嚴格的 RBAC (Role-Based Access Control)，確保敏感數據僅授權人員可見。
- **操作日誌**: 整合 Filament Activity Log，紀錄所有關鍵操作，具備完整的審計追蹤能力。

## 6. 未來擴展方向

- **AI 自動排餐**: 根據營養指標與預算自動生成菜單。
- **智慧庫存預測**: 結合歷史數據預測食材消耗。
- **多語系支持**: 目前以繁體中文為主，架構上已預留本地化擴展空間。
