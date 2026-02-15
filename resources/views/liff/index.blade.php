<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>午餐智慧入口網</title>
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

    {{-- JS via Vite (Logic Only) --}}
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

        /* Toggle Switch */
        /* Tailwind Custom Forms or simple CSS */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #10B981;
        }

        .toggle-checkbox:checked+.toggle-label {
            background-color: #10B981;
        }
    </style>
</head>

<body class="bg-gray-50 text-slate-800 min-h-screen flex flex-col">

    <!-- Header (Safe Layout) -->
    <header class="bg-emerald-600 text-white shadow-md z-30 safe-top">
        <div class="px-6 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight">智慧午餐系統</h1>
                <p class="text-emerald-100 text-xs mt-0.5" id="u-school">載入中...</p>
            </div>
            <!-- User Status (Collapsed in Header) -->
            <div id="user-status" class="flex items-center gap-2 hidden">
                <img id="u-avatar" class="w-8 h-8 rounded-full border border-white/50" src="">
            </div>
        </div>
    </header>

    <!-- Main App Container -->
    <main id="app-container" class="hidden flex-1 px-4 py-6 pb-20 safe-bottom overflow-y-auto" x-data="appData()">

        <!-- Dashboard View -->
        <div x-show="view === 'dashboard'" x-transition:enter="transition ease-out duration-300" class="space-y-6">
            <!-- Greeting -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-800">早安，<span id="u-name">老師</span> 👋</h2>
            </div>

            <!-- Feature Grid -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Survey Card -->
                <div @click="view = 'surveys'; fetchSurveys()"
                    class="bg-white p-5 rounded-2xl shadow-sm border border-emerald-100 active:scale-95 transition-transform cursor-pointer">
                    <div
                        class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center mb-3 text-emerald-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800">滿意度調查</h3>
                    <p class="text-xs text-gray-400 mt-1">累積點數回饋</p>
                </div>

                <!-- QR Check-in Card -->
                <div @click="scanQrCode()"
                    class="bg-white p-5 rounded-2xl shadow-sm border border-blue-100 active:scale-95 transition-transform cursor-pointer">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mb-3 text-blue-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4h2v-4zM6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800">用餐簽到</h3>
                    <p class="text-xs text-gray-400 mt-1">掃碼快速報到</p>
                </div>

                <!-- Settings Card -->
                <div @click="view = 'settings'; fetchSettings()"
                    class="bg-white p-5 rounded-2xl shadow-sm border border-orange-100 active:scale-95 transition-transform cursor-pointer">
                    <div
                        class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mb-3 text-orange-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800">個人設定</h3>
                    <p class="text-xs text-gray-400 mt-1">飲食偏好設定</p>
                </div>

                <!-- Meal Cancellation Card -->
                <div @click="view = 'cancellation'; fetchCancellationTargets()"
                    class="bg-white p-5 rounded-2xl shadow-sm border border-red-100 active:scale-95 transition-transform cursor-pointer">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mb-3 text-red-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800">停餐申請</h3>
                    <p class="text-xs text-gray-400 mt-1">請假/取消用餐</p>
                </div>
            </div>
        </div>

        <!-- Surveys View -->
        <div x-show="view === 'surveys'" x-cloak class="space-y-4">
            <div class="flex items-center mb-4">
                <button @click="view = 'dashboard'" class="p-2 -ml-2 text-gray-500 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h2 class="text-xl font-bold ml-1">滿意度調查</h2>
            </div>

            <!-- Tabs Logic -->
            <div x-data="{ activeTab: 'pending' }">
                <div class="bg-gray-100 rounded-lg p-1 flex mb-4">
                    <button @click="activeTab = 'pending'"
                        :class="activeTab === 'pending' ? 'bg-white shadow-sm text-emerald-700 font-bold' : 'text-gray-400'"
                        class="flex-1 py-2 rounded-md text-sm transition-all">待填寫</button>
                    <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'bg-white shadow-sm text-emerald-700 font-bold' : 'text-gray-400'"
                        class="flex-1 py-2 rounded-md text-sm transition-all">歷史紀錄</button>
                </div>

                <div x-show="activeTab === 'pending'" class="space-y-3">
                    <template x-for="survey in surveys.pending" :key="survey.id">
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-lg" x-text="survey.title"></h3>
                            <a :href="'/liff/survey/' + survey.id"
                                class="block mt-3 text-center bg-emerald-600 text-white py-2 rounded-lg font-bold">立即填寫</a>
                        </div>
                    </template>
                    <div x-show="surveys.pending.length === 0" class="text-center py-10 text-gray-400">目前沒有待填寫的問卷</div>
                </div>

                <div x-show="activeTab === 'history'" class="space-y-3" x-cloak>
                    <template x-for="survey in surveys.history" :key="survey.id">
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 opacity-75">
                            <h3 class="font-bold text-gray-800" x-text="survey.title"></h3>
                            <span class="text-xs text-gray-400"
                                x-text="'完成於 ' + new Date(survey.answered_at).toLocaleDateString()"></span>
                        </div>
                    </template>
                    <div x-show="surveys.history.length === 0" class="text-center py-10 text-gray-400">尚無歷史紀錄</div>
                </div>
            </div>
        </div>

        <!-- Cancellation View -->
        <div x-show="view === 'cancellation'" x-cloak class="space-y-4">
            <div class="flex items-center mb-4">
                <button @click="view = 'dashboard'" class="p-2 -ml-2 text-gray-500 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h2 class="text-xl font-bold ml-1">停餐申請</h2>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-5">
                <!-- Target Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">停餐對象</label>
                    <select x-model="cancellation.targetIndex"
                        class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base focus:border-red-500 focus:ring-4 focus:ring-red-500/10 outline-none appearance-none">
                        <template x-for="(target, index) in cancellation.targets" :key="target.id + target.type">
                            <option :value="index" x-text="target.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Date Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">停餐日期</label>
                    <input type="date" x-model="cancellation.date"
                        class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base focus:border-red-500 focus:ring-4 focus:ring-red-500/10 outline-none">
                </div>

                <!-- Reason Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">停餐原因</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="cancellation.leave_type = 'personal'"
                            :class="cancellation.leave_type === 'personal' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-gray-50 border-gray-200'"
                            class="py-3 rounded-xl border-2 font-bold transition-all">事假 / 其他</button>
                        <button @click="cancellation.leave_type = 'sick'"
                            :class="cancellation.leave_type === 'sick' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-gray-50 border-gray-200'"
                            class="py-3 rounded-xl border-2 font-bold transition-all">病假</button>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">備註說明 (選填)</label>
                    <input type="text" x-model="cancellation.reason" placeholder="請輸入請假事由..."
                        class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base focus:border-red-500 focus:ring-4 focus:ring-red-500/10 outline-none">
                </div>

                <button @click="submitCancellation()"
                    class="w-full bg-red-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-red-200 active:scale-95">
                    送出申請
                </button>
            </div>

            <p class="text-center text-xs text-gray-400 mt-4">注意：當日停餐請於上午 9:00 前完成申請</p>
        </div>

        <!-- Settings View -->
        <div x-show="view === 'settings'" x-cloak class="space-y-4">
            <div class="flex items-center mb-4">
                <button @click="view = 'dashboard'" class="p-2 -ml-2 text-gray-500 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h2 class="text-xl font-bold ml-1">個人設定</h2>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                <!-- Dietary -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">飲食習慣</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="settings.dietary_type = 'meat'"
                            :class="settings.dietary_type === 'meat' ? 'bg-emerald-100 border-emerald-500 text-emerald-700' : 'bg-gray-50 border-gray-200'"
                            class="py-3 rounded-xl border-2 font-bold transition-all">🍖 葷食</button>
                        <button @click="settings.dietary_type = 'vegan'"
                            :class="settings.dietary_type === 'vegan' ? 'bg-emerald-100 border-emerald-500 text-emerald-700' : 'bg-gray-50 border-gray-200'"
                            class="py-3 rounded-xl border-2 font-bold transition-all">🥦 素食</button>
                    </div>
                </div>

                <!-- Meal User -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">是否用餐</label>
                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded-xl border border-gray-200">
                        <span class="text-gray-600">參與學校午餐</span>
                        <div
                            class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" name="toggle" id="toggle" x-model="settings.is_meal_user"
                                class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer border-gray-300" />
                            <label for="toggle"
                                class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                        </div>
                    </div>
                </div>

                <button @click="saveSettings()"
                    class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-200 active:scale-95">
                    儲存變更
                </button>
            </div>

            <div class="pt-6 border-t border-gray-100">
                <button @click="resign()"
                    class="w-full text-red-500 text-sm py-3 font-medium bg-red-50 rounded-xl hover:bg-red-100">
                    從本校離職 (解除綁定)
                </button>
            </div>
        </div>

    </main>

    <!-- Login Button (Visible if not logged in) -->
    <div id="login-card"
        class="hidden absolute top-0 left-0 w-full h-full bg-gray-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-100 text-center w-full max-w-sm">
            <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                    </path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">歡迎使用午餐系統</h3>
            <p class="text-gray-500 mb-8">請先登入 LINE 以存取您的服務</p>
            <button onclick="performLogin(this)"
                class="w-full bg-[#06C755] hover:bg-[#05b34c] text-white font-bold py-4 rounded-xl transition shadow-lg shadow-green-200 active:scale-95 flex justify-center items-center gap-2 text-lg">
                <span>LINE 登入</span>
            </button>
        </div>
    </div>

    <!-- Binding Card (Visible if no school) -->
    <div id="binding-card"
        class="hidden absolute top-0 left-0 w-full h-full bg-gray-50 flex items-center justify-center p-6 z-40">
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-gray-100 text-center w-full max-w-sm space-y-8">
            <div
                class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-blue-100">
                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-1 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                    </path>
                </svg>
            </div>

            <div class="space-y-2">
                <h3 class="text-2xl font-bold text-gray-800 tracking-tight">學校/單位綁定</h3>
                <p class="text-gray-500 text-sm">歡迎加入！請輸入您的學校代碼</p>
            </div>

            <div class="space-y-5">
                <div class="relative">
                    <input type="text" id="bind-code"
                        class="w-full bg-slate-50 border border-gray-200 rounded-2xl px-4 py-4 text-center text-xl tracking-widest font-mono uppercase placeholder:text-sm placeholder:tracking-normal placeholder:font-sans focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none"
                        placeholder="學校代碼 (例如：TEST001)">
                </div>

                <div class="relative">
                    <input type="text" id="real-name"
                        class="w-full bg-slate-50 border border-gray-200 rounded-2xl px-4 py-4 text-center text-lg placeholder:text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none"
                        placeholder="請輸入您的真實姓名">
                </div>

                <div class="grid grid-cols-1">
                    <div class="relative">
                        <select id="staff-role"
                            class="w-full bg-slate-50 border border-gray-200 rounded-2xl px-4 py-4 text-center text-base focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none appearance-none">
                            <option value="teacher">👩‍🏫 教師</option>
                            <option value="admin">💼 行政</option>
                            <option value="worker">🛠️ 職工</option>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button onclick="submitBinding()" id="btn-bind"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-2xl transition-all shadow-lg shadow-blue-200 hover:shadow-blue-300 active:scale-[0.98] flex justify-center items-center gap-2 text-lg">
                    <span>確認綁定</span>
                </button>
                <p class="text-xs text-gray-400 mt-4">不知道代碼？請洽詢管理單位</p>
            </div>
        </div>
    </div>

    <!-- Global Loading -->
    <div id="global-loading" class="fixed inset-0 bg-white z-[60] flex flex-col items-center justify-center">
        <div class="animate-spin w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full mb-4"></div>
        <p class="text-gray-500 text-sm font-medium tracking-wide">系統連線中...</p>
    </div>

    <footer
        class="text-center py-4 text-[10px] text-gray-300 fixed bottom-0 w-full bg-gray-50/90 backdrop-blur-sm z-10 safe-bottom">
        Powered by AI Lunch System V2
    </footer>

    <script>
        const liffId = "{{ $liffId }}";

        // Custom Logger (Simplified for production)
        function log(msg, type = 'info') {
            console.log(`[${type.toUpperCase()}] ${msg}`);
        }

        document.addEventListener('DOMContentLoaded', async () => {
            log('DOM Ready. Calling initLIFF...');

            if (CSS.supports('padding-top: env(safe-area-inset-top)')) {
                log('Safe Area supported', 'success');
            }

            if (!liffId) {
                log('Error: LIFF ID missing', 'error');
                return;
            }
            await initLIFF();
        });

        // --- Helper: Button Loading State ---
        const SPINNER_SVG = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;

        function setBtnLoading(btn, isLoading, text = '') {
            if (isLoading) {
                btn.disabled = true;
                btn.dataset.originalText = btn.innerText;
                btn.innerHTML = `${SPINNER_SVG} <span>${text}</span>`;
            } else {
                btn.disabled = false;
                btn.innerHTML = `<span>${btn.dataset.originalText || text}</span>`;
            }
        }

        async function initLIFF() {
            try {
                await liff.init({ liffId: liffId });
                log('LIFF Inited');

                if (!liff.isLoggedIn()) {
                    log('Not logged in', 'error');
                    document.getElementById('global-loading').classList.add('hidden');
                    document.getElementById('login-card').classList.remove('hidden');
                } else {
                    log('Logged in');
                    await handleAuth();
                }

            } catch (err) {
                log('Init Fatal: ' + err.message, 'error');
                alert('初始化失敗: ' + err.message);
            }
        }

        function performLogin(btn) {
            if (btn) setBtnLoading(btn, true, '登入中...');
            liff.login();
        }

        async function handleAuth() {
            try {
                // Profile
                try {
                    const profile = await liff.getProfile();
                    document.getElementById('u-name').innerText = profile.displayName;
                    document.getElementById('u-avatar').src = profile.pictureUrl;
                    document.getElementById('user-status').classList.remove('hidden');
                } catch (e) { }

                // Token
                const idToken = liff.getIDToken();
                if (!idToken) throw new Error("No ID Token");

                const { data } = await axios.post('/api/auth/liff', { idToken });

                // Axios Config
                axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
                axios.defaults.headers.common['Accept'] = 'application/json';
                axios.defaults.headers.common['ngrok-skip-browser-warning'] = '69420';

                const user = data.user;
                document.getElementById('login-card').classList.add('hidden');
                document.getElementById('global-loading').classList.add('hidden');

                if (!user.school_id) {
                    // Bind
                    document.getElementById('binding-card').classList.remove('hidden');
                } else {
                    // Dashboard
                    document.getElementById('app-container').classList.remove('hidden');
                    if (data.school_name) {
                        document.getElementById('u-school').innerText = data.school_name;
                    }
                }

            } catch (err) {
                console.error(err);
                if (err.response && (err.response.status === 401 || err.response.status === 500)) {
                    liff.logout();
                    location.reload();
                    return;
                }
                alert('驗證失敗');
                document.getElementById('global-loading').classList.add('hidden');
                document.getElementById('login-card').classList.remove('hidden');
            }
        }

        async function submitBinding() {
            const codeInput = document.getElementById('bind-code');
            const nameInput = document.getElementById('real-name');
            const roleInput = document.getElementById('staff-role');
            const btn = document.getElementById('btn-bind');

            const code = codeInput.value.trim();
            const realName = nameInput.value.trim();
            const staffRole = roleInput.value;

            if (!code || !realName) return alert('請填寫完整資訊');

            try {
                setBtnLoading(btn, true, '綁定中...');
                const { data } = await axios.post('/api/bind', {
                    code,
                    real_name: realName,
                    staff_role: staffRole
                });

                alert(`綁定成功！歡迎加入 ${data.school_name}`);
                location.reload();

            } catch (err) {
                alert(err.response?.data?.message || '綁定失敗');
                setBtnLoading(btn, false);
            }
        }

        function appData() {
            return {
                view: 'dashboard',
                surveys: { pending: [], history: [] },
                settings: { dietary_type: 'meat', is_meal_user: true },
                cancellation: {
                    targets: [],
                    targetIndex: 0,
                    date: new Date().toISOString().split('T')[0],
                    leave_type: 'personal',
                    reason: '' // Optional
                },

                async fetchCancellationTargets() {
                    try {
                        const { data } = await axios.get('/api/cancellation/targets');
                        this.cancellation.targets = data.data;
                        if (this.cancellation.targets.length > 0) {
                            this.cancellation.targetIndex = 0;
                        }
                    } catch (e) {
                        console.error(e);
                        alert('無法載入停餐對象');
                    }
                },

                async submitCancellation() {
                    const target = this.cancellation.targets[this.cancellation.targetIndex];
                    if (!target) return alert('請選擇對象');

                    if (!confirm(`確定要為「${target.name}」申請 ${this.cancellation.date} 停餐嗎？`)) return;

                    try {
                        await axios.post('/api/meal-cancellation', {
                            target_id: target.id,
                            type: target.type,
                            date: this.cancellation.date,
                            leave_type: this.cancellation.leave_type,
                            reason: this.cancellation.reason
                        });
                        alert('申請已送出');
                        this.view = 'dashboard';
                    } catch (e) {
                        alert(e.response?.data?.message || '申請失敗');
                    }
                },

                async fetchSurveys() {
                    try {
                        const [pRes, hRes] = await Promise.all([
                            axios.get('/api/surveys/pending'),
                            axios.get('/api/surveys/history')
                        ]);
                        this.surveys.pending = pRes.data.data || [];
                        this.surveys.history = hRes.data.data || [];
                    } catch (e) {
                        console.error(e);
                        alert('載入問卷失敗');
                    }
                },

                async fetchSettings() {
                    try {
                        const { data } = await axios.get('/api/settings');
                        this.settings.dietary_type = data.data.dietary_type;
                        this.settings.is_meal_user = !!data.data.is_meal_user;
                    } catch (e) {
                        console.error(e);
                    }
                },

                async saveSettings() {
                    try {
                        await axios.post('/api/settings', this.settings);
                        alert('設定已儲存');
                        this.view = 'dashboard';
                    } catch (e) {
                        alert('更新失敗');
                    }
                },

                async scanQrCode() {
                    if (!liff.isInClient()) {
                        alert('請在 LINE App 中開啟以使用掃碼功能');
                        return;
                    }
                    try {
                        const result = await liff.scanCodeV2();
                        if (result.value) {
                            // Call API
                            // Show loading if needed?
                            // For better UX, we can just alert the result directly
                            try {
                                const { data } = await axios.post('/api/check-in', { code: result.value });
                                alert(data.message); // Success message
                            } catch (apiErr) {
                                // Show API error message
                                const msg = apiErr.response?.data?.message || '簽到失敗';
                                alert(msg);
                            }
                        }
                    } catch (e) {
                        console.error(e);
                        // This catches scan errors (e.g. user cancelled)
                        // Don't alert if user just cancelled
                        if (e.message !== 'User cancelled the scanning.') {
                            alert('掃描無法啟動: ' + (e.message || e));
                        }
                    }
                },

                async resign() {
                    if (confirm('確定要從目前學校離職嗎？\n此動作將使您的帳號狀態變為「離職」，且無法再查看學校資訊。')) {
                        try {
                            await axios.post('/api/unbind');
                            alert('已離職並解除綁定');
                            location.reload();
                        } catch (e) {
                            alert('操作失敗');
                        }
                    }
                }
            }
        }
    </script>
</body>

</html>
