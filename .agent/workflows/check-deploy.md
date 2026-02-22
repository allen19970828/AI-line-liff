---
description: 執行遠端部署檢修 SOP，檢查環境變數、磁碟掛載與資料庫狀態。
---

// turbo-all

1. 執行核心檢修指令

```bash
php artisan app:self-healing
```

2. 檢查資料庫遷移狀態

```bash
php artisan migrate:status
```

3. 檢查環境變數檔案與關鍵配置
   查看 [zeabur_env_vars.md](file:///Users/allenyu/.gemini/antigravity/brain/304bbdaf-1762-4a86-b387-5d750d15a1c0/zeabur_env_vars.md) 確認配置項是否與伺服器同步。

4. 檢查軟連結狀態 (手動驗證)

```bash
ls -la public/storage
```

5. 回報結果
   總結目前的系統狀態，並列出任何仍缺失的環境變數或異常。
