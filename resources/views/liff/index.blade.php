<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>午餐智慧入口網</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
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

    {{-- Load external dependencies FIRST (must be available before inline script) --}}
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    {{-- LIFF Application Logic (Inline for stability) --}}
    <script>
        window.liffId = "{{ $liffId }}";

        // Use global axios and liff to avoid conflict with CDN or other Vite modules
        const axios = window.axios;
        const liff = window.liff;
        const liffId = window.liffId;

        // Custom Logger
        function log(msg, type = 'info') {
            console.log(`[${type.toUpperCase()}] ${msg}`);
        }

        /**
         * 圖片壓縮輔助函數
         */
        async function compressImage(file, { maxWidth = 1200, quality = 0.6 } = {}) {
            if (!file || !file.type.startsWith('image/')) return file;

            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (e) => {
                    const img = new Image();
                    img.src = e.target.result;
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;

                        if (width > maxWidth) {
                            height = (maxWidth / width) * height;
                            width = maxWidth;
                        }

                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob((blob) => {
                            if (!blob) {
                                resolve(file); // Fallback to original
                                return;
                            }
                            resolve(new File([blob], file.name, { type: 'image/jpeg' }));
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = () => resolve(file); // Fallback to original
                };
                reader.onerror = () => resolve(file); // Fallback to original
            });
        }

        // Global initialization
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

        window.setBtnLoading = function (btn, isLoading, text = '') {
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

        window.performLogin = function (btn) {
            if (btn) setBtnLoading(btn, true, '登入中...');
            liff.login();
        }

        async function handleAuth() {
            try {
                // Profile
                try {
                    const profile = await liff.getProfile();
                    const uName = document.getElementById('u-name');
                    const uAvatar = document.getElementById('u-avatar');
                    const userStatus = document.getElementById('user-status');

                    if (uName) uName.innerText = profile.displayName;
                    if (uAvatar) uAvatar.src = profile.pictureUrl;
                    if (userStatus) userStatus.classList.remove('hidden');
                } catch (e) { }

                // Token
                const idToken = liff.getIDToken();
                if (!idToken) throw new Error("No ID Token");

                const { data } = await axios.post('/api/auth/liff', { idToken });

                // Axios Config - Apply to global axios
                axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
                axios.defaults.headers.common['Accept'] = 'application/json';

                const user = data.user;
                // Dispatch event for Alpine
                window.dispatchEvent(new CustomEvent('auth-success', {
                    detail: {
                        staffMealMode: data.staff_meal_mode || 'monthly'
                    }
                }));

                document.getElementById('login-card').classList.add('hidden');
                document.getElementById('global-loading').classList.add('hidden');

                if (!user.school_id) {
                    // Bind
                    document.getElementById('binding-card').classList.remove('hidden');
                } else {
                    // Dashboard
                    document.getElementById('app-container').classList.remove('hidden');
                    const uSchool = document.getElementById('u-school');
                    if (data.school_name && uSchool) {
                        uSchool.innerText = data.school_name;
                    }

                    // [新增] 定點報到自動處理
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('action') === 'checkin' && urlParams.get('code')) {
                        try {
                            const checkInCode = urlParams.get('code');
                            const { data: ciRes } = await axios.post('/api/check-in', { code: checkInCode });
                            alert(`【報到成功】\n單位：${ciRes.school_name}\n時間：${ciRes.checked_at}`);

                            // 清除 URL 參數避免過期點擊
                            window.history.replaceState({}, document.title, window.location.pathname);
                        } catch (ciErr) {
                            alert(ciErr.response?.data?.message || '報到失敗');
                        }
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

        window.submitBinding = async function () {
            const codeInput = document.getElementById('bind-code');
            const nameInput = document.getElementById('real-name');
            const roleInput = document.getElementById('staff-role');
            const btn = document.getElementById('btn-bind');

            if (!codeInput || !nameInput || !roleInput || !btn) return;

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

        // Alpine.js Data Store
        window.appData = function () {
            return {
                liffId: window.liffId,
                view: 'dashboard',
                surveys: { pending: [], history: [] },
                settings: { dietary_type: 'meat', is_meal_user: true, staffMealMode: 'monthly' },
                cancellation: {
                    mainTab: 'staff',
                    studentTab: 'personal',
                    groupMode: 'class_activity',
                    permissions: {
                        canAccessGroup: false,
                        canClassActivity: false,
                        canCrossClass: false,
                    },
                    staff: null,
                    classrooms: [],
                    targets: [],
                    search: { classroom_id: '', seat_number: '' },
                    foundStudent: null,
                    searchResults: [],
                    result: null, // [新增] 存放申請結果
                    selectedClassroom: '',
                    selectedStudents: [],
                    studentSearchKeyword: '',
                    searchResults: [],
                    selectedClassForGroup: '',
                    classStudents: [],
                    groups: [],
                    showGroupManager: false,
                    editingGroup: null,
                    groupForm: {
                        name: '',
                        description: '',
                        students: [],
                        studentSearch: '',
                        searchResults: [],
                    },
                    startDate: (function () {
                        const d = new Date();
                        const offset = d.getTimezoneOffset() * 60000;
                        return new Date(d.getTime() - offset).toISOString().split('T')[0];
                    })(),
                    endDate: '',
                    leaveType: 'personal',
                    reason: '',
                    holidays: {}, // [新增] 假日清單 { '2024-04-04': '兒童節' }
                },

                complaints: {
                    history: [],
                    isReporting: false,
                    form: {
                        occurred_at: (function () {
                            const d = new Date();
                            const offset = d.getTimezoneOffset() * 60000;
                            return new Date(d.getTime() - offset).toISOString().split('T')[0];
                        })(),
                        classroom_id: '',
                        content: '',
                        photo: null,
                        photoPreview: null,
                    }
                },

                settlement: {
                    yearMonth: (function () {
                        const d = new Date();
                        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
                    })(),
                    daysCount: 0,
                    amount: 0,
                    dates: [],
                    isLoading: false
                },

                async init() {
                    // [新增] 監聽登入成功事件，更新設定
                    window.addEventListener('auth-success', (e) => {
                        this.settings.staffMealMode = e.detail.staffMealMode;
                    });

                    if (this.liffId) {
                        await this.fetchSurveys();
                        await this.fetchCancellationTargets();
                        await this.fetchHolidays();
                        await this.fetchSettlement();
                    }

                    // [新增] 監聽日期變更
                    this.$watch('cancellation.startDate', (val) => this.validateDate(val, 'start'));
                    this.$watch('cancellation.endDate', (val) => this.validateDate(val, 'end'));
                },

                // [新增] 抓取假日
                async fetchHolidays() {
                    try {
                        const { data } = await axios.get('/api/cancellation/holidays');
                        this.cancellation.holidays = data.holidays || {};

                        // [新增] 初始化驗證：檢查預設日期是否為假日
                        if (this.cancellation.startDate) {
                            this.validateDate(this.cancellation.startDate, 'start', true);
                        }
                    } catch (error) {
                        console.error('Fetch holidays error:', error);
                    }
                },

                // [新增] 取得假日顯示文字 (用於 UI 提示)
                getHolidayHint(dateStr) {
                    if (!dateStr || !this.cancellation.holidays) return null;
                    return this.cancellation.holidays[dateStr] || null;
                },

                // [新增] 日期驗證邏輯
                validateDate(dateStr, field, isInitial = false) {
                    if (!dateStr) return;

                    const holidayName = this.cancellation.holidays[dateStr];
                    if (holidayName) {
                        if (!isInitial) {
                            alert(`【提醒】${dateStr} 是 ${holidayName}，無須停餐！`);
                        }

                        // 重置日期 (使用 $nextTick 確保在手機端原生 Picker 關閉後執行)
                        this.$nextTick(() => {
                            if (field === 'start') this.cancellation.startDate = '';
                            if (field === 'end') this.cancellation.endDate = '';
                        });
                        return;
                    }
                },

                async fetchCancellationTargets() {
                    try {
                        const { data } = await axios.get('/api/cancellation/targets');
                        this.cancellation.targets = data.data;
                        this.cancellation.classrooms = data.classrooms || [];

                        if (data.permissions) {
                            this.cancellation.permissions = {
                                canAccessGroup: data.permissions.can_access_group,
                                canClassActivity: data.permissions.can_class_activity,
                                canCrossClass: data.permissions.can_cross_class,
                            };
                        }

                        const staff = data.data.find(t => t.type === 'staff');
                        if (staff) {
                            this.cancellation.staff = staff;
                        }
                    } catch (error) {
                        console.error('Fetch targets error:', error);
                    }
                },

                async fetchComplaints() {
                    try {
                        const { data } = await axios.get('/api/meal-complaints');
                        this.complaints.history = data.data;
                    } catch (err) {
                        console.error('Fetch complaints failed', err);
                    }
                },

                handleComplaintPhoto(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    this.complaints.form.photo = file;

                    const reader = new FileReader();
                    reader.onload = (f) => {
                        this.complaints.form.photoPreview = f.target.result;
                    };
                    reader.readAsDataURL(file);
                },

                async submitComplaint(btn) {
                    const form = this.complaints.form;
                    if (!form.content) return alert('請填寫狀況描述');
                    if (!form.occurred_at) return alert('請選擇發生日期');

                    try {
                        window.setBtnLoading(btn, true, '送出中...');

                        const formData = new FormData();
                        formData.append('occurred_at', form.occurred_at);
                        formData.append('classroom_id', form.classroom_id || '');
                        formData.append('content', form.content);
                        if (form.photo) {
                            log('Compressing photo...', 'info');
                            const compressed = await compressImage(form.photo);
                            log(`Photo compressed: ${Math.round(form.photo.size / 1024)}KB -> ${Math.round(compressed.size / 1024)}KB`, 'success');
                            formData.append('photo', compressed);
                        }

                        await axios.post('/api/meal-complaints', formData, {
                            headers: { 'Content-Type': 'multipart/form-data' }
                        });

                        alert('回報成功，感謝您的意見');

                        this.complaints.form.content = '';
                        this.complaints.form.photo = null;
                        this.complaints.form.photoPreview = null;
                        this.complaints.isReporting = false;

                        await this.fetchComplaints();

                    } catch (err) {
                        console.error('Submit complaint failed', err);
                        alert(err.response?.data?.message || '回報失敗，請重試');
                    } finally {
                        window.setBtnLoading(btn, false, '確認送出回報');
                    }
                },

                async searchStudent() {
                    try {
                        const { data } = await axios.post('/api/cancellation/search-student', this.cancellation.search);
                        this.cancellation.foundStudent = data.data;
                    } catch (error) {
                        console.error('Search student error:', error);
                        alert(error.response?.data?.message || '找不到學生');
                        this.cancellation.foundStudent = null;
                    }
                },

                async submitCancellation() {
                    if (!confirm('確定要送出停餐申請嗎？')) return;

                    // [新增] 二次確認日期
                    if (this.cancellation.holidays[this.cancellation.startDate]) {
                        return alert(`起始日為放假日，無法申請`);
                    }
                    if (this.cancellation.endDate && this.cancellation.holidays[this.cancellation.endDate]) {
                        return alert(`結束日為放假日，無法申請`);
                    }

                    try {
                        let targetId;
                        let type;

                        if (this.cancellation.mainTab === 'staff') {
                            if (!this.cancellation.staff) return alert('找不到教職員資料');
                            targetId = this.cancellation.staff.id;
                            type = 'staff';
                        } else {
                            if (!this.cancellation.foundStudent) return alert('請先搜尋學生');
                            targetId = this.cancellation.foundStudent.id;
                            type = 'student';
                        }

                        const { data } = await axios.post('/api/meal-cancellation', {
                            target_id: targetId,
                            type: type,
                            start_date: this.cancellation.startDate,
                            end_date: this.cancellation.endDate || this.cancellation.startDate,
                            leave_type: this.cancellation.leaveType,
                            reason: this.cancellation.reason
                        });

                        const count = data.count || 0;
                        const affected = data.affected_students || [];

                        this.cancellation.result = {
                            title: count > 0 ? '停餐申請成功！' : '停餐已確認！',
                            message: count > 0
                                ? `已為 ${count} 位學生建立申請。`
                                : `所選學生均已有停餐紀錄，毋需重複建立。`,
                            students: affected,
                            isGrouped: false
                        };
                        this.view = 'resultSummary';
                        window.scrollTo(0, 0);

                        this.cancellation.reason = '';
                        this.cancellation.foundStudent = null;
                        this.cancellation.search = { classroom_id: '', seat_number: '' };

                    } catch (error) {
                        console.error('Submit cancellation error:', error);
                        let msg = error.response?.data?.message || '申請失敗';
                        if (error.response?.data?.errors) {
                            msg += '\\n' + JSON.stringify(error.response.data.errors, null, 2);
                        }
                        alert(msg);
                    }
                },

                async submitClassActivity() {
                    if (!confirm('確定要送出班級活動停餐申請嗎？\\n將為整班學生建立停餐紀錄')) return;

                    try {
                        const { data } = await axios.post('/api/meal-cancellation/class-activity', {
                            classroom_id: this.cancellation.selectedClassroom,
                            start_date: this.cancellation.startDate,
                            end_date: this.cancellation.endDate || this.cancellation.startDate,
                            reason: this.cancellation.reason || '班級活動'
                        });

                        const count = data.count || 0;
                        const affected = data.affected_students || [];

                        // 設定結果資料並切換視圖
                        this.cancellation.result = {
                            title: count > 0 ? '班級活動停餐申請成功' : '班級活動停餐已確認',
                            message: count > 0
                                ? `已為 ${count} 位學生建立申請。`
                                : `所有搭伙學生均已有停餐紀錄，毋需重複建立。`,
                            students: affected,
                            isGrouped: false // 班級活動通常單一班級，不需要再分組顯示
                        };
                        this.view = 'resultSummary';
                        window.scrollTo(0, 0);

                        this.cancellation.selectedClassroom = '';
                        this.cancellation.reason = '';

                    } catch (error) {
                        console.error('Submit class activity error:', error);
                        let msg = error.response?.data?.message || '申請失敗';
                        if (error.response?.data?.errors) {
                            msg += '\\n' + JSON.stringify(error.response.data.errors, null, 2);
                        }
                        alert(msg);
                    }
                },

                async searchStudentForGroup() {
                    if (!this.cancellation.studentSearchKeyword || this.cancellation.studentSearchKeyword.length < 2) {
                        this.cancellation.searchResults = [];
                        return;
                    }

                    try {
                        const { data } = await axios.get('/api/students/search', {
                            params: { keyword: this.cancellation.studentSearchKeyword }
                        });
                        this.cancellation.searchResults = data.data || [];
                    } catch (error) {
                        console.error('Search students error:', error);
                        this.cancellation.searchResults = [];
                    }
                },

                addStudentToSelection(student) {
                    const exists = this.cancellation.selectedStudents.find(s => s.id === student.id);
                    if (exists) {
                        alert('此學生已在清單中');
                        return;
                    }

                    this.cancellation.selectedStudents.push({
                        id: student.id,
                        name: student.name,
                        class_name: student.class_name,
                        seat_number: student.seat_number
                    });

                    this.cancellation.studentSearchKeyword = '';
                    this.cancellation.searchResults = [];
                },

                removeStudentFromSelection(index) {
                    this.cancellation.selectedStudents.splice(index, 1);
                },

                async submitGroupCancellation() {
                    if (!confirm('確定要送出跨班團體停餐申請嗎？')) return;

                    try {
                        const { data } = await axios.post('/api/meal-cancellation/group', {
                            student_ids: this.cancellation.selectedStudents.map(s => s.id),
                            start_date: this.cancellation.startDate,
                            end_date: this.cancellation.endDate || this.cancellation.startDate,
                            reason: this.cancellation.reason || '團體活動'
                        });

                        const count = data.count || 0;
                        const affected = data.affected_students || [];

                        // 整理分組資料 (GroupBy Class Name)
                        const grouped = affected.reduce((acc, student) => {
                            const className = student.class_name || '未分班';
                            if (!acc[className]) acc[className] = [];
                            acc[className].push(student);
                            return acc;
                        }, {});

                        this.cancellation.result = {
                            title: count > 0 ? '跨班團體停餐申請成功' : '團體停餐已確認',
                            message: count > 0
                                ? `已為 ${count} 位學生建立申請。`
                                : `所選學生均已有停餐紀錄，毋需重複建立。`,
                            students: grouped,
                            isGrouped: true
                        };
                        this.view = 'resultSummary';
                        window.scrollTo(0, 0);

                        this.cancellation.selectedStudents = [];
                        this.cancellation.reason = '';

                    } catch (error) {
                        console.error('Submit group cancellation error:', error);
                        let msg = error.response?.data?.message || '申請失敗';
                        if (error.response?.data?.errors) {
                            msg += '\\n' + JSON.stringify(error.response.data.errors, null, 2);
                        }
                        alert(msg);
                    }
                },

                async fetchStudentsByClassForGroup() {
                    if (!this.cancellation.selectedClassForGroup) {
                        this.cancellation.classStudents = [];
                        return;
                    }

                    try {
                        const { data } = await axios.get('/api/students/search', {
                            params: { classroom_id: this.cancellation.selectedClassForGroup }
                        });
                        this.cancellation.classStudents = data.data || [];
                    } catch (error) {
                        console.error('Fetch class students error:', error);
                        alert('無法載入班級學生');
                    }
                },

                toggleStudentSelection(student) {
                    const index = this.cancellation.selectedStudents.findIndex(s => s.id === student.id);
                    if (index === -1) {
                        this.addStudentToSelection(student);
                    } else {
                        this.removeStudentFromSelection(index);
                    }
                },

                isStudentSelected(student) {
                    return this.cancellation.selectedStudents.some(s => s.id === student.id);
                },

                selectAllClassStudents() {
                    this.cancellation.classStudents.forEach(student => {
                        if (!this.isStudentSelected(student)) {
                            this.addStudentToSelection(student);
                        }
                    });
                },

                clearClassSelection() {
                    this.cancellation.classStudents.forEach(student => {
                        const index = this.cancellation.selectedStudents.findIndex(s => s.id === student.id);
                        if (index !== -1) {
                            this.removeStudentFromSelection(index);
                        }
                    });
                },

                async fetchGroups() {
                    try {
                        const { data } = await axios.get('/api/meal-cancellation-groups');
                        this.cancellation.groups = data.data || [];
                    } catch (error) {
                        console.error('Fetch groups error:', error);
                    }
                },

                openGroupManager() {
                    this.fetchGroups();
                    this.cancellation.showGroupManager = true;
                },

                closeGroupManager() {
                    this.cancellation.showGroupManager = false;
                    this.cancellation.editingGroup = null;
                },

                createGroup() {
                    this.cancellation.editingGroup = {};
                    this.cancellation.groupForm = {
                        name: '',
                        description: '',
                        students: [],
                        studentSearch: '',
                        searchResults: []
                    };
                },

                editGroup(group) {
                    this.cancellation.editingGroup = group;
                    this.cancellation.groupForm = {
                        name: group.name,
                        description: group.description,
                        students: JSON.parse(JSON.stringify(group.students)),
                        studentSearch: '',
                        searchResults: []
                    };
                },

                async saveGroup() {
                    const isNew = !this.cancellation.editingGroup.id;
                    const url = isNew ? '/api/meal-cancellation-groups' : `/api/meal-cancellation-groups/${this.cancellation.editingGroup.id}`;
                    const method = isNew ? 'post' : 'put';

                    try {
                        const payload = {
                            name: this.cancellation.groupForm.name,
                            description: this.cancellation.groupForm.description,
                            student_ids: this.cancellation.groupForm.students.map(s => s.id)
                        };

                        await axios[method](url, payload);

                        alert(isNew ? '群組已建立' : '群組已更新');
                        this.cancellation.editingGroup = null;
                        this.fetchGroups();

                    } catch (error) {
                        console.error('Save group error:', error);
                        let msg = error.response?.data?.message || '儲存失敗';
                        if (error.response?.data?.errors) {
                            msg += '\\n' + JSON.stringify(error.response.data.errors, null, 2);
                        }
                        alert(msg);
                    }
                },

                async deleteGroup(id) {
                    if (!confirm('確定要刪除此群組嗎？此動作無法復原。')) return;

                    try {
                        await axios.delete(`/api/meal-cancellation-groups/${id}`);
                        this.fetchGroups();
                        alert('群組已刪除');
                    } catch (error) {
                        console.error('Delete group error:', error);
                        alert('刪除失敗');
                    }
                },

                async searchStudentForGroupForm() {
                    if (!this.cancellation.groupForm.studentSearch || this.cancellation.groupForm.studentSearch.length < 2) {
                        this.cancellation.groupForm.searchResults = [];
                        return;
                    }

                    try {
                        const { data } = await axios.get('/api/students/search', {
                            params: { keyword: this.cancellation.groupForm.studentSearch }
                        });
                        const currentIds = this.cancellation.groupForm.students.map(s => s.id);
                        this.cancellation.groupForm.searchResults = (data.data || []).filter(s => !currentIds.includes(s.id));
                    } catch (error) {
                        console.error('Search students error:', error);
                    }
                },

                addStudentToGroupForm(student) {
                    this.cancellation.groupForm.students.push(student);
                    this.cancellation.groupForm.studentSearch = '';
                    this.cancellation.groupForm.searchResults = [];
                },

                removeStudentFromGroupForm(index) {
                    this.cancellation.groupForm.students.splice(index, 1);
                },

                applyGroupSelection(group) {
                    if (!group.students || group.students.length === 0) {
                        alert('此群組沒有成員');
                        return;
                    }

                    let addedCount = 0;
                    const currentIds = this.cancellation.selectedStudents.map(s => s.id);

                    group.students.forEach(student => {
                        if (!currentIds.includes(student.id)) {
                            this.cancellation.selectedStudents.push({
                                id: student.id,
                                name: student.name,
                                class_name: student.class_name,
                                seat_number: student.seat_number
                            });
                            addedCount++;
                        }
                    });
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
                            try {
                                const { data } = await axios.post('/api/check-in', { code: result.value });
                                alert(data.message);
                            } catch (apiErr) {
                                const msg = apiErr.response?.data?.message || '簽到失敗';
                                alert(msg);
                            }
                        }
                    } catch (e) {
                        console.error(e);
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
                },

                async fetchSettlement() {
                    this.settlement.isLoading = true;
                    try {
                        const { data } = await axios.get('/api/settlement/current', {
                            params: { year_month: this.settlement.yearMonth }
                        });
                        this.settlement.daysCount = data.days_count || 0;
                        this.settlement.amount = data.amount || 0;
                        this.settlement.dates = data.dates || [];
                    } catch (e) {
                        console.error('Fetch settlement failed', e);
                    } finally {
                        this.settlement.isLoading = false;
                    }
                }
            }
        };
    </script>

    {{-- Alpine.js - Load AFTER inline script defines appData --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
            <div id="user-status" class="hidden items-center gap-2">
                <img id="u-avatar" class="w-8 h-8 rounded-full border border-white/50" src="">
            </div>
        </div>
    </header>

    <!-- Main App Container -->
        <!-- Group Management Modal (Moved inside x-data scope) -->
        <div x-show="cancellation.showGroupManager" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] flex flex-col shadow-2xl overflow-hidden transform transition-all"
                @click.away="closeGroupManager()">

                <!-- Header -->
                <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800"
                        x-text="cancellation.editingGroup !== null ? (cancellation.editingGroup.id ? '編輯群組' : '建立新群組') : '我的群組管理'">
                    </h3>
                    <button @click="closeGroupManager()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-5">

                    <!-- 列表模式 -->
                    <div x-show="cancellation.editingGroup === null" class="space-y-4">
                        <div class="flex justify-end">
                            <button @click="createGroup()"
                                class="bg-purple-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-purple-200 active:scale-95 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                建立新群組
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="group in cancellation.groups" :key="group.id">
                                <div
                                    class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-bold text-gray-900 text-lg" x-text="group.name"></h4>
                                            <p class="text-sm text-gray-500 mt-1" x-text="group.description || '無描述'"></p>
                                            <div
                                                class="mt-2 text-xs bg-purple-50 text-purple-700 px-2 py-1 rounded-lg inline-block font-medium">
                                                <span x-text="group.student_count"></span> 位成員
                                            </div>
                                        </div>
                                        <div class="flex gap-2" x-show="group.can_edit">
                                            <button @click="editGroup(group)"
                                                class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click="deleteGroup(group.id)"
                                                class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="cancellation.groups.length === 0" class="text-center py-10 text-gray-400">
                                <p class="text-4xl mb-3">📁</p>
                                <p>尚無群組</p>
                            </div>
                        </div>
                    </div>

                    <!-- 編輯/新增模式 -->
                    <div x-show="cancellation.editingGroup !== null" class="space-y-5">
                        <!-- 基本資料 -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">群組名稱 <span
                                        class="text-red-500">*</span></label>
                                <input type="text" x-model="cancellation.groupForm.name" placeholder="例如：籃球校隊"
                                    class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">描述 (選填)</label>
                                <textarea x-model="cancellation.groupForm.description" placeholder="例如：代表學校參加縣市比賽..."
                                    rows="2"
                                    class="w-full bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 text-base"></textarea>
                            </div>
                        </div>

                        <!-- 成員管理 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">群組成員</label>

                            <!-- 搜尋學生 -->
                            <div class="relative mb-3">
                                <input type="text" x-model="cancellation.groupForm.studentSearch"
                                    @input.debounce.300ms="searchStudentForGroupForm()" placeholder="搜尋學生加入..."
                                    class="w-full bg-white border border-purple-200 rounded-xl px-4 py-2 pr-10 text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-100 transition-all">
                                <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>

                                <!-- 搜尋結果 -->
                                <div x-show="cancellation.groupForm.searchResults && cancellation.groupForm.searchResults.length > 0"
                                    class="absolute left-0 right-0 mt-1 bg-white border border-gray-100 rounded-xl shadow-xl max-h-48 overflow-y-auto z-10">
                                    <template x-for="student in cancellation.groupForm.searchResults" :key="student.id">
                                        <button @click="addStudentToGroupForm(student)" type="button"
                                            class="w-full px-4 py-2 text-left hover:bg-purple-50 text-sm border-b border-gray-50 last:border-0 flex justify-between items-center">
                                            <span>
                                                <span class="font-bold text-gray-800" x-text="student.name"></span>
                                                <span class="text-xs text-gray-500 ml-1"
                                                    x-text="student.class_name + ' · ' + student.seat_number + '號'"></span>
                                            </span>
                                            <span
                                                class="text-purple-600 text-xs font-bold px-2 py-0.5 bg-purple-100 rounded-full">加入</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- 學生列表 -->
                            <div
                                class="bg-slate-50 rounded-xl p-3 max-h-60 overflow-y-auto space-y-2 border border-slate-100">
                                <template x-for="(student, index) in cancellation.groupForm.students" :key="student.id">
                                    <div
                                        class="flex justify-between items-center bg-white rounded-lg px-3 py-2 shadow-sm border border-gray-100">
                                        <div>
                                            <p class="font-bold text-sm text-gray-800" x-text="student.name"></p>
                                            <p class="text-xs text-gray-500"
                                                x-text="student.class_name + ' · ' + student.seat_number + '號'"></p>
                                        </div>
                                        <button @click="removeStudentFromGroupForm(index)" type="button"
                                            class="text-red-400 hover:text-red-600 p-1">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <div x-show="cancellation.groupForm.students.length === 0"
                                    class="text-center py-4 text-xs text-gray-400">
                                    尚未加入任何成員
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="p-4 border-t border-gray-100 bg-gray-50 flex gap-3" x-show="cancellation.editingGroup !== null">
                    <button @click="cancellation.editingGroup = null"
                        class="flex-1 py-3 rounded-xl border border-gray-300 text-gray-600 font-bold bg-white active:scale-95 transition-all">取消</button>
                    <button @click="saveGroup()"
                        :disabled="!cancellation.groupForm.name || cancellation.groupForm.students.length === 0"
                        class="flex-1 py-3 rounded-xl bg-purple-600 text-white font-bold shadow-lg shadow-purple-200 active:scale-95 disabled:opacity-50 disabled:active:scale-100 transition-all">
                        儲存
                    </button>
                </div>
            </div>
        </div>

        <!-- Result Summary View (Moved inside x-data scope) -->
        <div x-show="view === 'resultSummary'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div
                class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] flex flex-col shadow-2xl overflow-hidden transform transition-all">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800" x-text="cancellation.result?.title"></h3>
                    <button @click="view = 'dashboard'; cancellation.result = null"
                        class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-5">
                    <p class="text-gray-600 mb-4" x-text="cancellation.result?.message"></p>
                </div>

                <!-- Footer Buttons -->
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    <button @click="view = 'dashboard'; cancellation.result = null"
                        class="w-full py-3 bg-gray-100 text-gray-700 rounded-xl font-medium active:scale-95 transition-transform hover:bg-gray-200">
                        返回首頁
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Login Button (Visible if not logged in) -->
    <div id="login-card"
        class="hidden absolute top-0 left-0 w-full h-full bg-gray-50 items-center justify-center p-4 z-50">
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
        class="hidden absolute top-0 left-0 w-full h-full bg-gray-50 items-center justify-center p-6 z-40">
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
    <div id="global-loading" class="fixed inset-0 bg-white z-60 flex flex-col items-center justify-center">
        <div class="animate-spin w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full mb-4"></div>
        <p class="text-gray-500 text-sm font-medium tracking-wide">系統連線中...</p>
    </div>

    <footer
        class="text-center py-4 text-[10px] text-gray-300 fixed bottom-0 w-full bg-gray-50/90 backdrop-blur-sm z-10 safe-bottom">
        Powered by AI Lunch System V2
    </footer>

</body>

</html>
