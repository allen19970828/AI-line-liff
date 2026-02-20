<!-- Settlement View -->
<div x-show="view === 'settlement'" x-transition:enter="transition ease-out duration-300" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <button @click="view = 'dashboard'"
            class="p-2 -ml-2 text-gray-400 hover:text-gray-600 active:scale-90 transition-transform">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <h2 class="text-xl font-bold text-gray-800">月結與用餐明細</h2>
    </div>

    <!-- Month Selector (Minimalist) -->
    <div class="flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
        <button
            @click="const d = new Date(settlement.yearMonth + '-01'); d.setMonth(d.getMonth() - 1); settlement.yearMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); fetchSettlement()"
            class="p-2 text-gray-500 hover:text-emerald-600 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <span class="text-lg font-bold text-gray-700" x-text="settlement.yearMonth"></span>
        <button
            @click="const d = new Date(settlement.yearMonth + '-01'); d.setMonth(d.getMonth() + 1); settlement.yearMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); fetchSettlement()"
            class="p-2 text-gray-500 hover:text-emerald-600 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 gap-4">
        <div
            class="bg-emerald-600 p-6 rounded-3xl text-white shadow-lg shadow-emerald-100 flex flex-col justify-between overflow-hidden relative">
            <p class="text-sm font-medium opacity-80 mb-1">用餐次數</p>
            <div>
                <span class="text-3xl font-bold" x-text="settlement.daysCount"></span>
                <span class="text-sm ml-1">次</span>
            </div>
        </div>
        <div
            class="bg-blue-600 p-6 rounded-3xl text-white shadow-lg shadow-blue-100 flex flex-col justify-between overflow-hidden relative">
            <p class="text-sm font-medium opacity-80 mb-1">結算金額</p>
            <div>
                <span class="text-sm mr-1">$</span>
                <span class="text-3xl font-bold" x-text="settlement.amount"></span>
            </div>
        </div>
    </div>

    <!-- Dining Dates List -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 00-2 2z" />
                </svg>
                具體用餐日期
            </h3>
        </div>

        <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
            <template x-if="settlement.isLoading">
                <div class="p-10 text-center text-gray-400 space-y-3">
                    <div
                        class="animate-spin w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full mx-auto">
                    </div>
                    <p class="text-xs">載入數據中...</p>
                </div>
            </template>

            <template x-if="!settlement.isLoading && settlement.dates.length === 0">
                <div class="p-10 text-center text-gray-400">
                    <p class="text-4xl mb-3">🍱</p>
                    <p class="text-sm">本月尚無用餐紀錄</p>
                </div>
            </template>

            <template x-for="date in settlement.dates" :key="date">
                <div class="px-6 py-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <span class="font-medium text-gray-700" x-text="date"></span>
                    </div>
                    <span class="text-xs text-emerald-600 font-bold bg-emerald-50 px-2 py-1 rounded-lg">已簽到</span>
                </div>
            </template>
        </div>
    </div>
</div>
