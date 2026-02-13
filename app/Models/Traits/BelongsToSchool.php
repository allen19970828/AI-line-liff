<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * 系統核心：全效多租戶護法 (Ultimate Multi-tenancy Trait)
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        // [第一式：自動歸宗] 寫入時自動帶入
        static::creating(function (Model $model) {
            // 若欄位已有值（例如 Super Admin 幫某校建資料），則不覆蓋
            if ($model->school_id) {
                return;
            }

            // 若是當前登入者有歸屬學校，自動帶入
            if (auth()->check() && auth()->user()->school_id) {
                $model->school_id = auth()->user()->school_id;
            }

            // 宗師防呆：若最後 school_id 仍是 null（例如 Super Admin 忘了選學校），
            // 資料庫層級的 Not Null Constraint 會擋下，這裡就不多做檢查。
        });

        // [第二式：結界張開] 查詢時強制隔離
        static::addGlobalScope('school_scope', function (Builder $builder) {
            // 1. 命令行/排程任務：視為上帝視角，不做限制
            if (App::runningInConsole()) {
                return;
            }

            $tableName = $builder->getModel()->getTable();

            // 2. 未登入者 (Guest)：
            // 危險！預設不給看任何資料，防止前台頁面資料外洩。
            // 若是公開頁面，請在 Controller 使用 ->withoutGlobalScope('school_scope') 自行處理。
            if (! auth()->check()) {
                // 宗師修正：與其 return 放行，不如 block 到底
                $builder->whereRaw('1 = 0');

                return;
            }

            /** @var \App\Models\User $user */
            $user = auth()->user();

            // 3. 上帝視角 (Super Admin)：
            // 呼叫 User Model 的方法，解耦權限邏輯
            if ($user->canBypassSchoolScope()) {
                return;
            }

            // 4. 凡夫俗子：只能看自家的
            if ($user->school_id) {
                $builder->where("{$tableName}.school_id", $user->school_id);
            } else {
                // 5. 孤魂野鬼 (已登入但無學校)：什麼都看不到
                $builder->whereRaw('1 = 0');
            }
        });
    }

    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}
