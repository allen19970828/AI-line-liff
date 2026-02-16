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

        <!-- [主 Tab] 教職員 vs 學生 -->
        <div class="flex p-1 bg-gray-100 rounded-xl">
            <button @click="cancellation.mainTab = 'staff'"
                :class="cancellation.mainTab === 'staff' ? 'bg-white shadow text-red-600 font-bold' : 'text-gray-400'"
                class="flex-1 py-3 rounded-lg text-sm transition-all">🧑‍🏫 教職員</button>
            <button @click="cancellation.mainTab = 'student'"
                :class="cancellation.mainTab === 'student' ? 'bg-white shadow text-red-600 font-bold' : 'text-gray-400'"
                class="flex-1 py-3 rounded-lg text-sm transition-all">👨‍🎓 學生</button>
        </div>

        <!-- [主 Tab 1] 教職員停餐 -->
        <div x-show="cancellation.mainTab === 'staff'" class="space-y-4">
            <!-- 教職員資訊卡片 -->
            <div class="text-center py-4 bg-red-50 rounded-xl border border-red-100">
                <div
                    class="w-16 h-16 bg-white rounded-full mx-auto mb-2 flex items-center justify-center text-2xl shadow-sm">
                    🧑‍🏫
                </div>
                <p class="font-bold text-gray-800" x-text="cancellation.staff?.name || '載入中...'">
                </p>
                <p class="text-xs text-gray-500">為自己申請停餐</p>
            </div>

            <!-- 日期選擇（起訖） -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">開始日期</label>
                    <input type="date" x-model="cancellation.startDate" :min="new Date().toISOString().split('T')[0]"
                        class="w-full bg-slate-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">結束日期</label>
                    <input type="date" x-model="cancellation.endDate" :min="cancellation.startDate"
                        class="w-full bg-slate-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none">
                </div>
            </div>

            <!-- 假別選擇 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">停餐原因</label>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="cancellation.leaveType = 'personal'"
                        :class="cancellation.leaveType === 'personal' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-gray-50 border-gray-200'"
                        class="py-3 rounded-xl border-2 font-bold transition-all">事假</button>
                    <button @click="cancellation.leaveType = 'sick'"
                        :class="cancellation.leaveType === 'sick' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-gray-50 border-gray-200'"
                        class="py-3 rounded-xl border-2 font-bold transition-all">病假</button>
                </div>
            </div>

            <!-- 備註 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">備註說明 (選填)</label>
                <input type="text" x-model="cancellation.reason" placeholder="請輸入請假事由..."
                    class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none">
            </div>

            <!-- 送出按鈕 -->
            <button @click="submitCancellation()" :disabled="!cancellation.startDate"
                class="w-full bg-red-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-red-200 active:scale-95 disabled:opacity-50 disabled:active:scale-100">
                送出申請
            </button>
        </div>

        <!-- [主 Tab 2] 學生停餐 -->
        <div x-show="cancellation.mainTab === 'student'">

            <!-- [子 Tab] 個人 vs 團體 -->
            <div class="flex p-1 bg-emerald-50 rounded-xl mb-4">
                <button @click="cancellation.studentTab = 'personal'"
                    :class="cancellation.studentTab === 'personal' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-gray-500'"
                    class="flex-1 py-2 rounded-lg text-xs transition-all">個人停餐</button>
                <button @click="cancellation.studentTab = 'group'"
                    :class="cancellation.studentTab === 'group' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-gray-500'"
                    class="flex-1 py-2 rounded-lg text-xs transition-all"
                    x-show="cancellation.permissions.canAccessGroup">團體停餐</button>
            </div>

            <!-- [子 Tab 1] 個人停餐 -->
            <div x-show="cancellation.studentTab === 'personal'" class="space-y-4">
                <!-- 搜尋學生 -->
                <div class="grid grid-cols-3 gap-2">
                    <select x-model="cancellation.search.classroom_id"
                        class="col-span-2 bg-slate-50 border border-gray-200 rounded-lg px-2 py-2 text-sm outline-none">
                        <option value="">選擇班級</option>
                        <template x-for="c in cancellation.classrooms" :key="c.id">
                            <option :value="c.id" x-text="c.name"></option>
                        </template>
                    </select>
                    <input type="number" x-model="cancellation.search.seat_number" placeholder="座號"
                        class="bg-slate-50 border border-gray-200 rounded-lg px-2 py-2 text-sm outline-none">
                </div>

                <button @click="searchStudent()"
                    :disabled="!cancellation.search.classroom_id || !cancellation.search.seat_number"
                    class="w-full bg-gray-100 text-gray-900 border border-gray-300 py-3 rounded-xl text-base font-bold shadow-sm active:scale-95 disabled:opacity-50 disabled:active:scale-100 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    搜尋學生
                </button>

                <!-- 搜尋結果 -->
                <div x-show="cancellation.foundStudent"
                    class="bg-emerald-50 p-4 rounded-xl border border-emerald-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-emerald-600 font-bold"
                            x-text="cancellation.foundStudent?.class_name + ' ' + cancellation.foundStudent?.seat_number + '號'">
                        </p>
                        <p class="font-bold text-gray-800 text-lg" x-text="cancellation.foundStudent?.name"></p>
                    </div>
                    <div class="text-emerald-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- 日期選擇 -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">開始日期</label>
                        <input type="date" x-model="cancellation.startDate"
                            :min="new Date().toISOString().split('T')[0]"
                            class="w-full bg-slate-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">結束日期</label>
                        <input type="date" x-model="cancellation.endDate" :min="cancellation.startDate"
                            class="w-full bg-slate-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    </div>
                </div>

                <!-- 假別選擇 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">停餐原因</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="cancellation.leaveType = 'personal'"
                            :class="cancellation.leaveType === 'personal' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-gray-50 border-gray-200'"
                            class="py-3 rounded-xl border-2 font-bold transition-all">事假</button>
                        <button @click="cancellation.leaveType = 'sick'"
                            :class="cancellation.leaveType === 'sick' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-gray-50 border-gray-200'"
                            class="py-3 rounded-xl border-2 font-bold transition-all">病假</button>
                        <button @click="cancellation.leaveType = 'administrative'"
                            :class="cancellation.leaveType === 'administrative' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-gray-50 border-gray-200'"
                            class="py-3 rounded-xl border-2 font-bold transition-all">公假</button>
                    </div>
                </div>

                <!-- 備註 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">備註說明 (選填)</label>
                    <input type="text" x-model="cancellation.reason" placeholder="請輸入請假事由..."
                        class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base">
                </div>

                <!-- 送出按鈕 -->
                <button @click="submitCancellation()" :disabled="!cancellation.foundStudent || !cancellation.startDate"
                    class="w-full bg-red-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-red-200 active:scale-95 disabled:opacity-50 disabled:active:scale-100">
                    送出申請
                </button>
            </div>

            <!-- [子 Tab 2] 團體停餐 -->
            <div x-show="cancellation.studentTab === 'group'" class="space-y-4">

                <!-- [模式切換] 班級活動 vs 跨班團體 -->
                <div class="grid grid-cols-2 gap-3">
                    <button @click="cancellation.groupMode = 'class_activity'"
                        x-show="cancellation.permissions.canClassActivity"
                        :class="cancellation.groupMode === 'class_activity' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'bg-gray-50 border-gray-200'"
                        class="p-4 rounded-2xl border-2 font-bold transition-all">
                        <div class="text-3xl mb-2">🏫</div>
                        <div class="text-sm">班級活動</div>
                    </button>

                    <button @click="cancellation.groupMode = 'cross_class'"
                        x-show="cancellation.permissions.canCrossClass"
                        :class="cancellation.groupMode === 'cross_class' ? 'bg-purple-50 border-purple-500 text-purple-700' : 'bg-gray-50 border-gray-200'"
                        class="p-4 rounded-2xl border-2 font-bold transition-all">
                        <div class="text-3xl mb-2">🌐</div>
                        <div class="text-sm">跨班團體</div>
                    </button>
                </div>

                <!-- [模式 1] 班級活動停餐 -->
                <div x-show="cancellation.groupMode === 'class_activity'" class="space-y-4">
                    <div class="bg-blue-50 p-3 rounded-xl border border-blue-100">
                        <p class="text-xs text-blue-700 font-medium">批次建立整班學生的停餐申請（例如：校外教學、班級活動）
                        </p>
                    </div>

                    <!-- 選擇班級 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">選擇班級</label>
                        <select x-model="cancellation.selectedClassroom"
                            class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base">
                            <option value="">請選擇班級</option>
                            <template x-for="c in cancellation.classrooms" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- 日期選擇 -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">開始日期</label>
                            <input type="date" x-model="cancellation.startDate"
                                :min="new Date().toISOString().split('T')[0]"
                                class="w-full bg-slate-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">結束日期</label>
                            <input type="date" x-model="cancellation.endDate" :min="cancellation.startDate"
                                class="w-full bg-slate-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                        </div>
                    </div>

                    <!-- 活動理由 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">活動理由</label>
                        <input type="text" x-model="cancellation.reason" placeholder="例如：校外教學、班級旅遊..."
                            class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base">
                    </div>

                    <!-- 送出按鈕 -->
                    <button @click="submitClassActivity()"
                        :disabled="!cancellation.selectedClassroom || !cancellation.startDate"
                        class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-50 disabled:active:scale-100">
                        送出班級活動停餐
                    </button>
                </div>

                <!-- [模式 2] 跨班團體停餐 -->
                <div x-show="cancellation.groupMode === 'cross_class'" class="space-y-4">
                    <div class="bg-purple-50 p-3 rounded-xl border border-purple-100">
                        <p class="text-xs text-purple-700 font-medium">多選學生（可跨班級）建立停餐申請（例如：校隊比賽、運動會集訓）
                        </p>
                    </div>

                    <!-- 班級選取 (購物車模式) -->
                    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                        <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                            <span>🏫 從班級選取</span>
                            <span class="text-xs font-normal text-gray-400">(點選加入清單)</span>
                        </label>
                        <div class="mb-3">
                            <select x-model="cancellation.selectedClassForGroup"
                                @change="fetchStudentsByClassForGroup()"
                                class="w-full bg-slate-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none transition-all">
                                <option value="">請選擇班級...</option>
                                <template x-for="c in cancellation.classrooms" :key="c.id">
                                    <option :value="c.id" x-text="c.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- 班級學生列表 -->
                        <div x-show="cancellation.classStudents.length > 0" x-transition>
                            <div class="flex justify-between items-center mb-2 px-1">
                                <span class="text-xs text-gray-500"
                                    x-text="'共 ' + cancellation.classStudents.length + ' 位學生'"></span>
                                <div class="flex gap-3">
                                    <button @click="selectAllClassStudents()"
                                        class="text-xs text-purple-600 font-bold hover:text-purple-800">全選</button>
                                    <button @click="clearClassSelection()"
                                        class="text-xs text-gray-400 hover:text-gray-600">清除選取</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto p-1">
                                <template x-for="student in cancellation.classStudents" :key="student.id">
                                    <button @click="toggleStudentSelection(student)"
                                        :class="isStudentSelected(student) ? 'bg-purple-600 text-white border-purple-600 shadow-purple-200' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-purple-300'"
                                        class="px-2 py-2 rounded-lg border text-sm font-medium transition-all text-center relative overflow-hidden shadow-sm active:scale-95">
                                        <span x-text="student.name" class="block truncate"></span>
                                        <span x-text="student.seat_number + '號'"
                                            class="text-[10px] opacity-70 block mt-0.5"></span>

                                        <!-- Checkmark -->
                                        <div x-show="isStudentSelected(student)" class="absolute top-1 right-1">
                                            <svg class="w-3 h-3 text-white opacity-80" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-show="cancellation.selectedClassForGroup && cancellation.classStudents.length === 0"
                            class="text-center py-4 text-xs text-gray-400">
                            此班級尚無資料
                        </div>
                    </div>

                    <!-- 搜尋學生 -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-medium text-gray-700">搜尋並新增學生</label>
                            <div class="flex gap-2">
                                <button @click="openGroupManager()"
                                    class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-lg font-bold hover:bg-purple-200 transition-colors">
                                    群組管理
                                </button>
                                <div class="relative" x-data="{ open: false }">
                                    <button
                                        @click="open = !open; if(open && cancellation.groups.length === 0) fetchGroups()"
                                        class="text-xs bg-purple-600 text-white px-2 py-1 rounded-lg font-bold hover:bg-purple-700 transition-colors flex items-center gap-1">
                                        <span>📂 從群組選擇</span>
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <!-- 群組選擇下拉 -->
                                    <div x-show="open" @click.away="open = false"
                                        class="absolute right-0 mt-1 w-48 bg-white rounded-xl shadow-xl border border-gray-100 z-20 py-1"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100">
                                        <template x-for="group in cancellation.groups" :key="group.id">
                                            <button @click="applyGroupSelection(group); open = false"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700 transition-colors">
                                                <span x-text="group.name" class="font-bold"></span>
                                                <span class="text-xs text-gray-400 ml-1"
                                                    x-text="'(' + group.student_count + '人)'"></span>
                                            </button>
                                        </template>
                                        <div x-show="cancellation.groups.length === 0"
                                            class="px-4 py-3 text-xs text-gray-400 text-center">
                                            尚無群組<br>請先建立群組
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="text" x-model="cancellation.studentSearchKeyword"
                                @input.debounce.300ms="searchStudentForGroup()" placeholder="輸入班級或學生姓名搜尋..."
                                class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 pr-10 text-base">
                            <svg class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- 搜尋結果下拉 -->
                        <div x-show="cancellation.searchResults && cancellation.searchResults.length > 0"
                            class="mt-2 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="student in cancellation.searchResults" :key="student.id">
                                <button @click="addStudentToSelection(student)" type="button"
                                    class="w-full px-4 py-3 text-left hover:bg-purple-50 border-b border-gray-100 last:border-0 transition-colors">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900" x-text="student.name">
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <span x-text="student.class_name"></span> · 座號 <span
                                                    x-text="student.seat_number"></span>
                                            </p>
                                        </div>
                                        <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 已選學生清單 -->
                    <div x-show="cancellation.selectedStudents.length > 0">
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-medium text-gray-700">
                                已選擇 <span class="text-purple-600 font-bold"
                                    x-text="cancellation.selectedStudents.length"></span> 位學生
                            </label>
                            <button @click="cancellation.selectedStudents = []" type="button"
                                class="text-xs text-red-600 hover:text-red-700 font-medium">
                                清空全部
                            </button>
                        </div>
                        <div class="bg-purple-50 rounded-xl p-3 max-h-48 overflow-y-auto space-y-2">
                            <template x-for="(student, index) in cancellation.selectedStudents" :key="student.id">
                                <div class="flex justify-between items-center bg-white rounded-lg px-3 py-2 shadow-sm">
                                    <div class="flex-1">
                                        <p class="font-medium text-sm text-gray-900" x-text="student.name"></p>
                                        <p class="text-xs text-gray-500">
                                            <span x-text="student.class_name"></span> · 座號 <span
                                                x-text="student.seat_number"></span>
                                        </p>
                                    </div>
                                    <button @click="removeStudentFromSelection(index)" type="button"
                                        class="ml-2 p-1 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 顯示提示（無學生選擇時） -->
                    <div x-show="cancellation.selectedStudents.length === 0" class="text-center py-8 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="text-sm">請使用上方搜尋框新增學生</p>
                    </div>

                    <!-- 日期選擇 -->
                    <div class="grid grid-cols-2 gap-3" x-show="cancellation.selectedStudents.length > 0">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">開始日期</label>
                            <input type="date" x-model="cancellation.startDate"
                                :min="new Date().toISOString().split('T')[0]"
                                class="w-full bg-slate-50 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">結束日期 (選填)</label>
                            <input type="date" x-model="cancellation.endDate" :min="cancellation.startDate"
                                class="w-full bg-slate-50 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>

                    <!-- 停餐原因 -->
                    <div x-show="cancellation.selectedStudents.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">活動名稱/原因</label>
                        <input type="text" x-model="cancellation.reason" placeholder="例如：全國籃球賽、校慶排練..."
                            class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base">
                    </div>

                    <!-- 送出按鈕 -->
                    <button @click="submitGroupCancellation()"
                        :disabled="cancellation.selectedStudents.length === 0 || !cancellation.startDate"
                        class="w-full bg-purple-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-purple-200 active:scale-95 disabled:opacity-50 disabled:active:scale-100">
                        送出團體停餐申請
                    </button>
                </div>
            </div>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-4">注意：停餐申請需符合學期設定的提前天數規則</p>
</div>
