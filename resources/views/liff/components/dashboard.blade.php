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
            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center mb-3 text-emerald-600">
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
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mb-3 text-orange-600">
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

        <!-- Meal Complaint Card -->
        <div @click="view = 'complaints'; fetchComplaints()"
            class="bg-white p-5 rounded-2xl shadow-sm border border-amber-100 active:scale-95 transition-transform cursor-pointer">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center mb-3 text-amber-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="font-bold text-gray-800">問題回報</h3>
            <p class="text-xs text-gray-400 mt-1">食安反映/意見單</p>
        </div>
    </div>
</div>
