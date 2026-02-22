<!-- Meal Backfill (用餐補登) View -->
<div x-show="view === 'meal-backfill'" x-transition:enter="transition ease-out duration-300" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <button @click="view = 'dashboard'"
            class="p-2 -ml-2 text-gray-400 hover:text-gray-600 active:scale-90 transition-transform">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <h2 class="text-xl font-bold text-gray-800">用餐補登</h2>
    </div>

    <!-- Month Selector -->
    <div class="flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
        <button
            @click="const d = new Date(backfill.yearMonth + '-01'); d.setMonth(d.getMonth() - 1); backfill.yearMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); fetchBackfillCalendar()"
            class="p-2 text-gray-500 hover:text-indigo-600 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <span class="text-lg font-bold text-gray-700" x-text="backfill.yearMonth"></span>
        <button
            @click="const d = new Date(backfill.yearMonth + '-01'); d.setMonth(d.getMonth() + 1); backfill.yearMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); fetchBackfillCalendar()"
            class="p-2 text-gray-500 hover:text-indigo-600 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-indigo-600 p-4 rounded-2xl text-white shadow-lg shadow-indigo-100 text-center">
            <p class="text-xs font-medium opacity-80 mb-1">已選天數</p>
            <span class="text-2xl font-bold" x-text="backfill.selectedDates.length"></span>
        </div>
        <div class="bg-emerald-600 p-4 rounded-2xl text-white shadow-lg shadow-emerald-100 text-center">
            <p class="text-xs font-medium opacity-80 mb-1">預估金額</p>
            <span class="text-2xl font-bold" x-text="'$' + (backfill.selectedDates.length * backfill.mealPrice)"></span>
        </div>
        <div class="bg-amber-500 p-4 rounded-2xl text-white shadow-lg shadow-amber-100 text-center">
            <p class="text-xs font-medium opacity-80 mb-1">異動數</p>
            <span class="text-2xl font-bold" x-text="backfill.changesCount"></span>
        </div>
    </div>

    <!-- Locked Warning -->
    <template x-if="backfill.isLocked">
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <p class="text-sm text-red-700 font-medium">本月已結算鎖定，無法修改</p>
        </div>
    </template>

    <!-- Calendar Grid -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                點選日期補登用餐
            </h3>
            <p class="text-xs text-gray-400 mt-1">綠色＝已登記 | 灰色＝可補登 | 暗色＝不可選</p>
        </div>

        <!-- Loading -->
        <template x-if="backfill.isLoading">
            <div class="p-10 text-center text-gray-400 space-y-3">
                <div class="animate-spin w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full mx-auto">
                </div>
                <p class="text-xs">載入月曆中...</p>
            </div>
        </template>

        <!-- Calendar -->
        <template x-if="!backfill.isLoading">
            <div class="p-4">
                <!-- Day of week headers -->
                <div class="grid grid-cols-7 gap-1 mb-2">
                    <template x-for="day in ['一', '二', '三', '四', '五', '六', '日']" :key="day">
                        <div class="text-center text-xs font-bold text-gray-400 py-1" x-text="day"></div>
                    </template>
                </div>

                <!-- Calendar cells -->
                <div class="grid grid-cols-7 gap-1">
                    <!-- Empty cells for offset -->
                    <template x-for="i in backfill.firstDayOffset" :key="'empty-' + i">
                        <div class="aspect-square"></div>
                    </template>

                    <!-- Date cells -->
                    <template x-for="dayInfo in backfill.calendarDays" :key="dayInfo.date">
                        <button @click="toggleBackfillDate(dayInfo.date)"
                            :disabled="dayInfo.is_day_off || dayInfo.is_future || backfill.isLocked" :class="{
                                'bg-emerald-500 text-white shadow-md shadow-emerald-200 scale-105': backfill.selectedDates.includes(dayInfo.date),
                                'bg-gray-100 text-gray-700 hover:bg-indigo-100 hover:text-indigo-700 active:scale-90': !backfill.selectedDates.includes(dayInfo.date) && !dayInfo.is_day_off && !dayInfo.is_future,
                                'bg-gray-50 text-gray-300 cursor-not-allowed': dayInfo.is_day_off || dayInfo.is_future,
                            }"
                            class="aspect-square rounded-xl flex flex-col items-center justify-center text-sm font-bold transition-all duration-200 relative">
                            <span x-text="dayInfo.date.split('-')[2].replace(/^0/, '')"></span>
                            <!-- QR badge for non-backfill check-ins -->
                            <template x-if="backfill.qrDates.includes(dayInfo.date)">
                                <span
                                    class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-blue-500 rounded-full border border-white"></span>
                            </template>
                        </button>
                    </template>
                </div>

                <!-- Legend -->
                <div class="flex items-center gap-4 mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded bg-emerald-500"></div>
                        <span>已登記</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded bg-gray-100"></div>
                        <span>可補登</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                        <span>QR簽到</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Save Button -->
    <template x-if="backfill.changesCount > 0 && !backfill.isLocked">
        <button @click="submitBackfill()" :disabled="backfill.isSubmitting"
            class="w-full py-4 bg-linear-to-r from-indigo-600 to-purple-600 text-white font-bold text-lg rounded-2xl shadow-lg shadow-indigo-200 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
            <template x-if="backfill.isSubmitting">
                <div class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></div>
            </template>
            <template x-if="!backfill.isSubmitting">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </template>
            <span x-text="backfill.isSubmitting ? '儲存中...' : '儲存變更 (' + backfill.changesCount + ' 筆)'"></span>
        </button>
    </template>

    <!-- Success Message -->
    <template x-if="backfill.successMessage">
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-center"
            x-transition:enter="transition ease-out duration-300"
            x-init="setTimeout(() => backfill.successMessage = '', 3000)">
            <p class="text-sm text-emerald-700 font-medium" x-text="backfill.successMessage"></p>
        </div>
    </template>
</div>