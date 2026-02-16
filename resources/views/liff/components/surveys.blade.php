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
