<!-- Meal Complaints View -->
<div x-show="view === 'complaints'" x-cloak class="space-y-4 pb-10">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <button @click="view = 'dashboard'" class="p-2 -ml-2 text-gray-500 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <h2 class="text-xl font-bold ml-1">問題回報</h2>
        </div>
        <button x-show="!complaints.isReporting"
            @click="complaints.isReporting = true; complaints.form.classroom_id = (cancellation.classrooms[0]?.id || '')"
            class="bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-amber-100 active:scale-95 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            我要回報
        </button>
    </div>

    <!-- Reporting Form -->
    <div x-show="complaints.isReporting" class="bg-white p-6 rounded-2xl shadow-sm border border-amber-100 space-y-5"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4">
        <div class="flex justify-between items-center mb-2">
            <h3 class="font-bold text-gray-800">提交新意見/問題</h3>
            <button @click="complaints.isReporting = false" class="text-gray-400 text-sm">取消</button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">發生日期</label>
                <input type="date" x-model="complaints.form.occurred_at"
                    class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">發生地點/班級</label>
                <select x-model="complaints.form.classroom_id"
                    class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base appearance-none">
                    <option value="">全校性問題</option>
                    <template x-for="cls in cancellation.classrooms" :key="cls.id">
                        <option :value="cls.id" x-text="cls.class_name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">狀況描述</label>
                <textarea x-model="complaints.form.content" placeholder="請描述發現的問題 (如：異物、口味過重、份量不足...)" rows="4"
                    class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">證據照片 (選填)</label>
                <div class="mt-1 flex items-center gap-4">
                    <label
                        class="flex flex-col items-center justify-center w-24 h-24 border-2 border-dashed border-gray-300 rounded-2xl cursor-pointer hover:bg-gray-50 transition-colors">
                        <template x-if="!complaints.form.photoPreview">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-400">
                                <svg class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p class="text-[10px]">上傳照片</p>
                            </div>
                        </template>
                        <template x-if="complaints.form.photoPreview">
                            <img :src="complaints.form.photoPreview" class="w-full h-full object-cover rounded-2xl">
                        </template>
                        <input type="file" class="hidden" @change="handleComplaintPhoto" accept="image/*">
                    </label>
                    <div x-show="complaints.form.photoPreview" class="flex-1">
                        <button @click="complaints.form.photo = null; complaints.form.photoPreview = null"
                            class="text-xs text-red-500 font-bold">移除照片</button>
                    </div>
                </div>
            </div>
        </div>

        <button @click="submitComplaint($event.target)"
            class="w-full bg-amber-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-amber-100 active:scale-95 flex justify-center items-center gap-2">
            確認送出回報
        </button>
    </div>

    <!-- History List -->
    <div x-show="!complaints.isReporting" class="space-y-4">
        <h3 class="font-bold text-gray-500 text-sm px-1">回報紀錄</h3>

        <div class="space-y-3">
            <template x-for="item in complaints.history" :key="item.id">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-md" :class="{
                                                'bg-red-50 text-red-600': item.status === 'pending',
                                                'bg-amber-50 text-amber-600': item.status === 'processing',
                                                'bg-emerald-50 text-emerald-600': item.status === 'resolved'
                                            }" x-text="item.status_text"></span>
                                <span class="text-xs text-gray-400" x-text="item.occurred_at"></span>
                            </div>
                            <p class="text-sm font-bold text-gray-800" x-text="item.classroom_name"></p>
                        </div>
                        <template x-if="item.photo_url">
                            <img :src="item.photo_url"
                                class="w-12 h-12 rounded-lg object-cover border border-gray-100 shadow-sm"
                                @click="window.open(item.photo_url)">
                        </template>
                    </div>

                    <p class="text-sm text-gray-600 leading-relaxed" x-text="item.content"></p>

                    <template x-if="item.caterer_reply">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-xs font-bold text-gray-800 mb-1 flex items-center gap-1">
                                <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                廠商回覆：
                            </p>
                            <p class="text-xs text-gray-500" x-text="item.caterer_reply"></p>
                        </div>
                    </template>
                </div>
            </template>

            <div x-show="complaints.history.length === 0" class="text-center py-12 text-gray-400">
                <p class="text-4xl mb-3">📝</p>
                <p>尚無任何回報紀錄</p>
            </div>
        </div>
    </div>
</div>
