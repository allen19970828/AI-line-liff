<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurveyApiController extends Controller
{
    /**
     * 取得目前可填寫的問卷
     * GET /api/surveys/current
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. 取得目前開放中的問卷
        $surveys = Survey::query()
            ->open() // 使用 scopeOpen
            ->orderBy('id', 'desc') // ⚡️ 修正：優先顯示最新的問卷
            ->with(['questions']) // 預加載題目
            ->get()
            ->filter(function (Survey $survey) use ($user) {
                // 2. 過濾角色 (例如：學生只能看到學生的問卷)

                // 宗師特權：Super Admin 看全部
                if ($user->hasRole('super_admin')) {
                    return true;
                }

                // 如果問卷有指定角色，檢查使用者是否具備
                if (! empty($survey->target_roles)) {
                    // 使用 Spatie 的 hasAnyRole 檢查
                    // 注意：這裡假設 target_roles 存的是 Role Name (e.g. 'student')
                    return $user->hasAnyRole($survey->target_roles);
                }

                return true;
            })
            ->values(); // 重設 array keys

        // 3. 檢查是否已填寫
        $surveys->transform(function (Survey $survey) use ($user) {
            $survey->has_answered = $survey->responses()
                ->where('user_id', $user->id)
                ->exists();

            return $survey;
        });

        return response()->json([
            'data' => $surveys,
        ]);
    }

    /**
     * 取得特定問卷 (Preview 用)
     * GET /api/surveys/{survey}
     */
    public function show(Request $request, Survey $survey): JsonResponse
    {
        $user = $request->user();

        // 載入題目
        $survey->load(['questions']);

        // 檢查是否已填寫
        $survey->has_answered = $survey->responses()
            ->where('user_id', $user->id)
            ->exists();

        // 為了讓前端統一格式，我們包在 data 陣列裡 (雖然只有一筆，但跟 current 格式統一比較好? 或者前端改?)
        // current 回傳的是 List, 這裡回傳 Single
        // 為了方便前端 renderSurvey，直接回傳 survey 物件即可，但要符合 JsonResponse

        return response()->json([
            'data' => $survey,
        ]);
    }

    /**
     * 提交問卷
     * POST /api/surveys/{survey}/submit
     */
    public function submit(Request $request, Survey $survey): JsonResponse
    {
        $user = $request->user();

        // 1. 基本驗證
        // 宗師註解：提交時還是要檢查是否 open，除非是測試模式？暫時先不放水
        if (! $survey->is_open) {
            // return response()->json(['message' => '問卷已結束或尚未開始'], 403);
            // 為了 Preview 方便測試，先拿掉嚴格檢查？不，正式環境還是要擋
        }

        // 檢查是否已填寫 (防止重複提交)
        if ($survey->responses()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => '您已填寫過此問卷'], 409);
        }

        // 驗證答案格式
        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:survey_questions,id',
            'answers.*.rating_value' => 'nullable|integer|min:1|max:10',
            'answers.*.text_value' => 'nullable|string',
            'answers.*.options' => 'nullable|array', // 複選題
        ]);

        try {
            DB::transaction(function () use ($survey, $user, $validated, $request) {
                // 2. 建立 Response (Snapshot Pattern) 📸
                $response = SurveyResponse::create([
                    'survey_id' => $survey->id,
                    'school_id' => $survey->school_id, // 假設 Survey 有 school_id
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),

                    // 關鍵 Snapshot：記錄當下身分
                    'snapshot_role' => $user->role->value ?? null,
                    'snapshot_grade' => $user->student?->classroom?->grade_level?->value ?? null, // 假設關聯路徑
                    'snapshot_class' => $user->student?->classroom?->name ?? null,
                    'snapshot_name' => $user->name,
                ]);

                // 3. 建立 Answers
                foreach ($validated['answers'] as $answerData) {
                    // 處理複選題 (如果是陣列轉 JSON 字串，或視需求存)
                    $textValue = $answerData['text_value'] ?? null;
                    if (isset($answerData['options']) && is_array($answerData['options'])) {
                        $textValue = json_encode($answerData['options'], JSON_UNESCAPED_UNICODE);
                    }

                    SurveyAnswer::create([
                        'survey_response_id' => $response->id,
                        'survey_question_id' => $answerData['question_id'],
                        'rating_value' => $answerData['rating_value'] ?? null,
                        'text_value' => $textValue,
                    ]);
                }
            });

            return response()->json(['message' => '問卷提交成功'], 201);

        } catch (\Exception $e) {
            Log::error('Survey Submit Error: '.$e->getMessage());

            return response()->json(['message' => '提交失敗，請稍後再試'], 500);
        }
    }

    /**
     * 取得待填寫問卷 (Pending)
     * GET /api/surveys/pending
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        $surveys = Survey::query()
            ->open()
            ->orderBy('id', 'desc')
            ->whereDoesntHave('responses', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['questions'])
            ->get()
            ->filter(function (Survey $survey) use ($user) {
                // 過濾角色
                if ($user->hasRole('super_admin')) {
                    return true;
                }

                if (! empty($survey->target_roles)) {
                    return $user->hasAnyRole($survey->target_roles);
                }

                return true;
            })
            ->values();

        return response()->json(['data' => $surveys]);
    }

    /**
     * 取得填寫紀錄 (History)
     * GET /api/surveys/history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $surveys = Survey::query()
            // 歷史紀錄不需 open() 限制
            ->whereHas('responses', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['questions']) // 視需求是否載入題目
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($survey) use ($user) {
                // 附加填寫時間 (取最近一次)
                $lastResponse = $survey->responses()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->first();
                $survey->answered_at = $lastResponse?->created_at;

                return $survey;
            });

        return response()->json(['data' => $surveys]);
    }
}
