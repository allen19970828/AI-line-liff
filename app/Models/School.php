<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // [關鍵] 引入軟刪除心法

class School extends Model
{
    use HasFactory, SoftDeletes; // [關鍵] 啟用心法

    /**
     * [第一要素：防護罩]
     * $fillable (可批量賦值屬性)
     * 只有寫在這裡的欄位，才允許透過 create() 或 update() 直接注入。
     * 這是防止「隔空打牛」（惡意修改不該改的欄位）的關鍵防禦。
     */
    protected $fillable = [
        'code',
        'name',
        'tax_id',
        'city',
        'district',
        'address',
        'phone',
        'website',
        'is_active',
        // 注意：id, created_at, updated_at, deleted_at 不需要寫在這裡，系統會自動處理
    ];

    /**
     * [第二要素：易容術]
     * $casts (型別轉換)
     * 資料庫裡的 tinyint(1) 或 string，拿出來時要自動變成 PHP 的格式。
     */
    protected $casts = [
        'is_active' => 'boolean', // 讓它在 PHP 裡變成 true/false，而不是 1/0
        // 若你有用 jsonb，記得加 'options' => 'array',
    ];

    /**
     * [第三要素：人際關係]
     * Relationships
     * 雖然你斬斷了 owner_id，但學校與人依然有緣。
     * 這裡定義學校與用戶的關係：一所學校可以有多個用戶（老師、家長）。
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
