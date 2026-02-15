<?php

namespace App\Services;

class ClassroomService
{
    /**
     * 獲取班級名稱權重 (核心演算法)
     * 用於 Model saving 時計算 sort_order
     */
    public static function getClassNameWeight(?string $name): int
    {
        if (empty($name)) {
            return 999;
        }

        // 1. 預處理：移除干擾字元，轉大寫
        // 移除 "班"、"年" (防止有人打 "一年甲")、空格
        $cleanName = str_replace(['班', '年', ' '], '', $name);
        $cleanName = strtoupper($cleanName);

        // 2. 優先判斷：純數字 (1, 2, 10...)
        // 數字班通常權重最小，排最前面
        if (is_numeric($cleanName)) {
            return (int) $cleanName;
        }

        // 3. 英文單字判斷 (A, B, C...)
        // 放在 50~99 區段
        if (preg_match('/^[A-Z]$/', $cleanName)) {
            return 50 + (ord($cleanName) - ord('A'));
        }

        // 4. 查表法 (Map Lookup)
        // 這裡包含：天干、八德、中文數字、特殊班
        $map = self::getWeightMap();

        // 4-1. 精確比對 (Exact Match) - 最快
        if (isset($map[$cleanName])) {
            return $map[$cleanName];
        }

        // 4-2. 模糊掃描 (Fuzzy Scan) - 容錯
        // 例如輸入 "一年甲"，上面沒比對到，這裡掃描是否包含 "甲"
        foreach ($map as $key => $weight) {
            // 如果班名中包含 Key (例如 "一年甲" 包含 "甲")
            // mb_strpos 支援中文
            if (mb_strpos($cleanName, (string) $key) !== false) {
                return $weight;
            }
        }

        // 5. 真的不認識，排最後
        return 999;
    }

    /**
     * 建構權重對照表 (權重分層設計)
     * 使用 static cache 避免重複建構陣列
     */
    private static function getWeightMap(): array
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $weights = [];

        // [Group 1] 天干 (100 ~ 199)
        // 甲=101, 乙=102...
        $stems = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $base = 100;
        foreach ($stems as $i => $char) {
            $weights[$char] = $base + $i + 1;
        }

        // [Group 2] 八德與儒家思想 (200 ~ 299)
        // 這是台灣最常見的混亂來源，包含 忠孝仁愛信義和平...
        $virtues = [
            '忠',
            '孝',
            '仁',
            '愛',
            '信',
            '義',
            '和',
            '平',
            '公',
            '誠',
            '勤',
            '毅',
            '溫',
            '良',
            '恭',
            '儉',
            '讓',
            '禮',
            '智',
            '勇',
            '真',
            '善',
            '美',
        ];
        $base = 200;
        foreach ($virtues as $i => $char) {
            $weights[$char] = $base + $i + 1;
        }

        // [Group 3] 中文數字 (300 ~ 399)
        // 處理 "一班" (非 '1') 的情況
        $cnNumbers = [
            '一',
            '二',
            '三',
            '四',
            '五',
            '六',
            '七',
            '八',
            '九',
            '十',
            '十一',
            '十二',
            '十三',
            '十四',
            '十五',
        ];
        $base = 300;
        foreach ($cnNumbers as $i => $char) {
            $weights[$char] = $base + $i + 1;
        }

        // [Group 4] 幼兒園與特殊班 (800 ~ 899)
        // 通常排在最後面
        $special = [
            '寶貝' => 801,
            '幼' => 802,
            '小' => 803,
            '中' => 804,
            '大' => 805, // 小班/中班/大班
            '太陽' => 810,
            '月亮' => 811,
            '星星' => 812,
            '彩虹' => 813,
            '綿羊' => 814,
            '體育' => 880,
            '特教' => 881,
            '資源' => 882,
            '巡迴' => 883,
        ];

        // 合併陣列
        $map = $weights + $special;

        return $map;
    }
}
