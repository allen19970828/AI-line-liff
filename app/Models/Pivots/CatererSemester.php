<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CatererSemester extends Pivot
{
    // 宗師指點：因為你的 Migration 有寫 $table->id()，所以這是一個有主鍵的 Pivot
    // 但通常 Laravel 的 Pivot 預設是不遞增的，不過為了保險起見，可以宣告一下
    public $incrementing = true;

    protected $table = 'caterer_semester';

    // 宗師指點：這就是為什麼非建這個 Model 不可的原因！
    // 這一行讓你在取用 $caterer->pivot->service_meals 時，自動拿到 Array，不用自己 json_decode
    protected $casts = [
        'service_meals' => 'array',
    ];
}
