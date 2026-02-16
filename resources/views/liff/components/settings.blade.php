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
                <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
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
