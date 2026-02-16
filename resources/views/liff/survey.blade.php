<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>滿意度調查</title>
    {{-- Force Inline CSS for Ngrok Mobile Compatibility --}}
    @php
        $manifestPath = public_path('build/manifest.json');
        $cssLoaded = false;
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['resources/css/app.css']['file'])) {
                $cssFile = public_path('build/' . $manifest['resources/css/app.css']['file']);
                if (file_exists($cssFile)) {
                    echo '<style>' . file_get_contents($cssFile) . '</style>';
                    $cssLoaded = true;
                }
            }
        }
    @endphp

    @if(!$cssLoaded)
        @vite(['resources/css/app.css'])
    @endif

    {{-- JS via Vite --}}
    @vite(['resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Instrument Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .safe-top {
            padding-top: env(safe-area-inset-top);
        }

        .safe-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
    </style>
</head>

<body class="bg-gray-50 text-slate-800 min-h-screen flex flex-col" x-data="surveyData()">

    <!-- Header -->
    <header class="bg-emerald-600 text-white shadow-md z-30 safe-top sticky top-0">
        <div class="px-4 py-3 flex items-center gap-3">
            <a href="/liff" class="p-1 -ml-1 text-emerald-100 hover:text-white">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-lg font-bold tracking-tight truncate" x-text="survey?.title || '載入中...'">滿意度調查</h1>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 px-4 py-6 pb-32 safe-bottom overflow-y-auto space-y-6">

        <!-- Loading State -->
        <div x-show="loading" class="flex flex-col items-center justify-center py-20">
            <div class="animate-spin w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full mb-4">
            </div>
            <p class="text-gray-500 text-sm">正在載入問卷...</p>
        </div>

        <!-- Error State -->
        <div x-show="!loading && error" x-cloak class="bg-red-50 p-6 rounded-2xl border border-red-100 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-red-800 mb-2">無法載入問卷</h3>
            <p class="text-red-600 text-sm mb-6" x-text="error"></p>
            <a href="/liff"
                class="inline-block bg-white border border-red-200 text-red-600 px-6 py-2 rounded-xl font-bold shadow-sm">返回首頁</a>
        </div>

        <!-- Already Answered State -->
        <div x-show="!loading && survey?.has_answered" x-cloak
            class="bg-emerald-50 p-6 rounded-2xl border border-emerald-100 text-center">
            <div
                class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4 text-emerald-600">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-emerald-800 mb-2">感謝您的填寫</h3>
            <p class="text-emerald-600 text-sm mb-6">您已完成此份問卷，感謝您的寶貴意見。</p>
            <a href="/liff"
                class="inline-block bg-emerald-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg shadow-emerald-200">返回首頁</a>
        </div>

        <!-- Survey Form -->
        <div x-show="!loading && survey && !survey.has_answered" x-cloak class="space-y-6">

            <!-- Description -->
            <div x-show="survey?.description"
                class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 prose prose-sm text-gray-600">
                <p x-text="survey?.description"></p>
            </div>

            <!-- Questions -->
            <template x-for="(question, index) in survey?.questions" :key="question.id">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                    <!-- Question Title -->
                    <div class="flex gap-3">
                        <span
                            class="shrink-0 w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-xs font-bold"
                            x-text="index + 1"></span>
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg leading-snug">
                                <span x-text="question.label"></span>
                                <span x-show="question.is_required" class="text-red-500 ml-1">*</span>
                            </h3>
                        </div>
                    </div>

                    <!-- Input: Text / Textarea -->
                    <div x-show="['text', 'textarea'].includes(question.type)">
                        <textarea x-model="answers[question.id].text_value" rows="3" placeholder="請輸入您的回答..."
                            class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 outline-none"></textarea>
                    </div>

                    <!-- Input: Rating 5 (Stars) -->
                    <div x-show="question.type === 'rating_5'">
                        <div class="flex justify-center items-center gap-2">
                            <template x-for="n in 5" :key="n">
                                <button @click="answers[question.id].rating_value = n"
                                    class="p-1 transition-all transform active:scale-125"
                                    :class="answers[question.id].rating_value >= n ? 'text-amber-400' : 'text-gray-300'">
                                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-2 px-6">
                            <span>非常不滿意</span>
                            <span>非常滿意</span>
                        </div>
                    </div>

                    <!-- Input: Rating 10 -->
                    <div x-show="question.type === 'rating_10'">
                        <div class="flex flex-wrap gap-2 justify-center">
                            <template x-for="n in 10" :key="n">
                                <button @click="answers[question.id].rating_value = n"
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                                    :class="answers[question.id].rating_value === n ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 scale-110' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                                    <span x-text="n"></span>
                                </button>
                            </template>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-2 px-2">
                            <span>1分</span>
                            <span>10分</span>
                        </div>
                    </div>

                    <!-- Input: Single Choice (Radio / Select) -->
                    <div x-show="['radio', 'select'].includes(question.type) && question.options" class="space-y-2">
                        <template x-for="option in question.options" :key="option">
                            <label
                                class="flex items-center gap-3 p-3 rounded-xl border border-transparent hover:bg-gray-50 transition-colors cursor-pointer"
                                :class="answers[question.id].text_value === option ? 'bg-emerald-50 border-emerald-200' : ''">
                                <input type="radio" :name="'q_' + question.id" :value="option"
                                    x-model="answers[question.id].text_value"
                                    class="w-5 h-5 text-emerald-600 border-gray-300 focus:ring-emerald-500">
                                <span class="text-gray-700" x-text="option"></span>
                            </label>
                        </template>
                    </div>

                    <!-- Input: Multiple Choice (Checkbox) -->
                    <div x-show="question.type === 'checkbox' && question.options" class="space-y-2">
                        <template x-for="option in question.options" :key="option">
                            <label
                                class="flex items-center gap-3 p-3 rounded-xl border border-transparent hover:bg-gray-50 transition-colors cursor-pointer"
                                :class="(answers[question.id].options || []).includes(option) ? 'bg-emerald-50 border-emerald-200' : ''">
                                <input type="checkbox" :value="option" x-model="answers[question.id].options"
                                    class="w-5 h-5 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                <span class="text-gray-700" x-text="option"></span>
                            </label>
                        </template>
                    </div>

                </div>
            </template>

            <!-- Submit Button -->
            <button @click="submitSurvey()" id="btn-submit"
                class="w-full bg-emerald-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-emerald-200 active:scale-95 text-lg">
                提交問卷
            </button>
        </div>

    </main>

    <!-- Global Loading Overlay -->
    <div id="auth-loading" class="fixed inset-0 bg-white z-60 flex flex-col items-center justify-center">
        <div class="animate-spin w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full mb-4"></div>
        <p class="text-gray-500 text-sm font-medium tracking-wide">驗證身分中...</p>
    </div>

    <!-- Login Required Card -->
    <div id="login-card" class="hidden fixed inset-0 bg-gray-50 z-50 items-center justify-center p-6">
        <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-100 text-center w-full max-w-sm">
            <h3 class="text-xl font-bold text-gray-800 mb-2">需要登入</h3>
            <p class="text-gray-500 mb-6">請先登入 LINE 以填寫問卷</p>
            <button onclick="liff.login()" class="w-full bg-[#06C755] text-white font-bold py-3 rounded-xl">LINE
                登入</button>
        </div>
    </div>

    <script>
        const liffId = "{{ $liffId }}";
        const surveyId = "{{ $surveyId }}";

        function surveyData() {
            return {
                loading: true,
                error: null,
                survey: null,
                answers: {},

                async init() {
                    if (!liffId) {
                        this.error = '系統設定錯誤 (Missing LIFF ID)';
                        this.loading = false;
                        document.getElementById('auth-loading').classList.add('hidden');
                        return;
                    }

                    try {
                        await liff.init({ liffId: liffId });
                        if (!liff.isLoggedIn()) {
                            document.getElementById('auth-loading').classList.add('hidden');
                            document.getElementById('login-card').classList.remove('hidden');
                            return;
                        }

                        // Auth & Fetch
                        const idToken = liff.getIDToken();
                        const { data: authData } = await axios.post('/api/auth/liff', { idToken });

                        axios.defaults.headers.common['Authorization'] = `Bearer ${authData.token}`;
                        axios.defaults.headers.common['Accept'] = 'application/json';
                        axios.defaults.headers.common['ngrok-skip-browser-warning'] = '69420';

                        await this.fetchSurvey();

                    } catch (e) {
                        console.error(e);
                        this.error = '驗證或連線失敗';
                    } finally {
                        this.loading = false;
                        document.getElementById('auth-loading').classList.add('hidden');
                    }
                },

                async fetchSurvey() {
                    try {
                        const { data } = await axios.get(`/api/surveys/${surveyId}`);
                        this.survey = data.data;

                        // Initialize answer structure
                        this.survey.questions.forEach(q => {
                            this.answers[q.id] = {
                                question_id: q.id,
                                rating_value: null,
                                text_value: '',
                                options: [] // for multiple choice
                            };
                        });

                    } catch (e) {
                        console.error(e);
                        if (e.response?.status === 404) {
                            this.error = '問卷不存在或已過期';
                        } else {
                            this.error = e.response?.data?.message || '無法載入問卷內容';
                        }
                    }
                },

                async submitSurvey() {
                    // Validation
                    for (const q of this.survey.questions) {
                        if (q.is_required) {
                            const ans = this.answers[q.id];
                            let filled = false;

                            if (q.question_type === 'text') filled = !!ans.text_value.trim();
                            else if (q.question_type === 'rating') filled = !!ans.rating_value;
                            else if (q.question_type === 'single_choice') filled = !!ans.text_value;
                            else if (q.question_type === 'multiple_choice') filled = ans.options.length > 0;

                            if (!filled) {
                                alert(`請回答第 ${this.survey.questions.indexOf(q) + 1} 題`);
                                return;
                            }
                        }
                    }

                    if (!confirm('確定要提交問卷嗎？提交後無法修改。')) return;

                    const btn = document.getElementById('btn-submit');
                    btn.disabled = true;
                    btn.innerText = '提交中...';

                    // Transform answers for API
                    const payload = {
                        answers: Object.values(this.answers).map(a => ({
                            question_id: a.question_id,
                            rating_value: a.rating_value,
                            text_value: a.text_value,
                            options: a.options
                        }))
                    };

                    try {
                        await axios.post(`/api/surveys/${surveyId}/submit`, payload);
                        alert('提交成功！');
                        this.survey.has_answered = true;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } catch (e) {
                        alert(e.response?.data?.message || '提交失敗，請稍後再試');
                        btn.disabled = false;
                        btn.innerText = '提交問卷';
                    }
                }
            }
        }
    </script>
</body>

</html>
