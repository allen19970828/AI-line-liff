// Use global axios and liff to avoid conflict with CDN or other Vite modules
const axios = window.axios;
const liff = window.liff;

const liffId = window.liffId;

// Custom Logger
function log(msg, type = "info") {
    console.log(`[${type.toUpperCase()}] ${msg}`);
}

// Global initialization
document.addEventListener("DOMContentLoaded", async () => {
    log("DOM Ready. Calling initLIFF...");

    if (CSS.supports("padding-top: env(safe-area-inset-top)")) {
        log("Safe Area supported", "success");
    }

    if (!liffId) {
        log("Error: LIFF ID missing", "error");
        return;
    }
    await initLIFF();
});

// --- Helper: Button Loading State ---
const SPINNER_SVG = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;

window.setBtnLoading = function (btn, isLoading, text = "") {
    if (isLoading) {
        btn.disabled = true;
        btn.dataset.originalText = btn.innerText;
        btn.innerHTML = `${SPINNER_SVG} <span>${text}</span>`;
    } else {
        btn.disabled = false;
        btn.innerHTML = `<span>${btn.dataset.originalText || text}</span>`;
    }
};

async function initLIFF() {
    try {
        await liff.init({ liffId: liffId });
        log("LIFF Inited");

        if (!liff.isLoggedIn()) {
            log("Not logged in", "error");
            document.getElementById("global-loading").classList.add("hidden");
            document.getElementById("login-card").classList.remove("hidden");
        } else {
            log("Logged in");
            await handleAuth();
        }
    } catch (err) {
        log("Init Fatal: " + err.message, "error");
        alert("初始化失敗: " + err.message);
    }
}

window.performLogin = function (btn) {
    if (btn) setBtnLoading(btn, true, "登入中...");
    liff.login();
};

async function handleAuth() {
    try {
        // Profile
        try {
            const profile = await liff.getProfile();
            const uName = document.getElementById("u-name");
            const uAvatar = document.getElementById("u-avatar");
            const userStatus = document.getElementById("user-status");

            if (uName) uName.innerText = profile.displayName;
            if (uAvatar) uAvatar.src = profile.pictureUrl;
            if (userStatus) userStatus.classList.remove("hidden");
        } catch (e) {}

        // Token
        const idToken = liff.getIDToken();
        if (!idToken) throw new Error("No ID Token");

        const { data } = await axios.post("/api/auth/liff", { idToken });

        // Axios Config - Apply to global axios
        axios.defaults.headers.common["Authorization"] = `Bearer ${data.token}`;
        axios.defaults.headers.common["Accept"] = "application/json";
        axios.defaults.headers.common["ngrok-skip-browser-warning"] = "69420";

        const user = data.user;
        document.getElementById("login-card").classList.add("hidden");
        document.getElementById("global-loading").classList.add("hidden");

        if (!user.school_id) {
            // Bind
            document.getElementById("binding-card").classList.remove("hidden");
        } else {
            // Dashboard
            document.getElementById("app-container").classList.remove("hidden");
            const uSchool = document.getElementById("u-school");
            if (data.school_name && uSchool) {
                uSchool.innerText = data.school_name;
            }
        }
    } catch (err) {
        console.error(err);
        if (
            err.response &&
            (err.response.status === 401 || err.response.status === 500)
        ) {
            liff.logout();
            location.reload();
            return;
        }
        alert("驗證失敗");
        document.getElementById("global-loading").classList.add("hidden");
        document.getElementById("login-card").classList.remove("hidden");
    }
}

window.submitBinding = async function () {
    const codeInput = document.getElementById("bind-code");
    const nameInput = document.getElementById("real-name");
    const roleInput = document.getElementById("staff-role");
    const btn = document.getElementById("btn-bind");

    if (!codeInput || !nameInput || !roleInput || !btn) return;

    const code = codeInput.value.trim();
    const realName = nameInput.value.trim();
    const staffRole = roleInput.value;

    if (!code || !realName) return alert("請填寫完整資訊");

    try {
        setBtnLoading(btn, true, "綁定中...");
        const { data } = await axios.post("/api/bind", {
            code,
            real_name: realName,
            staff_role: staffRole,
        });

        alert(`綁定成功！歡迎加入 ${data.school_name}`);
        location.reload();
    } catch (err) {
        alert(err.response?.data?.message || "綁定失敗");
        setBtnLoading(btn, false);
    }
};

// Register with Alpine if available, otherwise set on window
const appDataStore = function () {
    return {
        liffId: window.liffId, // Ensure it's inside the object
        view: "dashboard",
        surveys: { pending: [], history: [] },
        settings: { dietary_type: "meat", is_meal_user: true },
        cancellation: {
            mainTab: "staff",
            studentTab: "personal",
            groupMode: "class_activity",
            permissions: {
                canAccessGroup: false,
                canClassActivity: false,
                canCrossClass: false,
            },
            staff: null,
            classrooms: [],
            targets: [],
            search: { classroom_id: "", seat_number: "" },
            foundStudent: null,
            selectedClassroom: "",
            selectedStudents: [],
            studentSearchKeyword: "",
            searchResults: [],
            selectedClassForGroup: "",
            classStudents: [],
            groups: [],
            showGroupManager: false,
            editingGroup: null,
            groupForm: {
                name: "",
                description: "",
                students: [],
                studentSearch: "",
                searchResults: [],
            },
            startDate: (function () {
                const d = new Date();
                const offset = d.getTimezoneOffset() * 60000;
                return new Date(d.getTime() - offset)
                    .toISOString()
                    .split("T")[0];
            })(),
            endDate: "",
            leaveType: "personal",
            reason: "",
        },

        complaints: {
            history: [],
            isReporting: false,
            form: {
                occurred_at: (function () {
                    const d = new Date();
                    const offset = d.getTimezoneOffset() * 60000;
                    return new Date(d.getTime() - offset)
                        .toISOString()
                        .split("T")[0];
                })(),
                classroom_id: "",
                content: "",
                photo: null,
                photoPreview: null,
            },
        },

        async init() {
            // Note: initLIFF is also called on DOMContentLoaded
            // This init() is for Alpine component logic
            if (this.liffId) {
                await this.fetchSurveys();
                await this.fetchCancellationTargets();
            }
        },

        async fetchCancellationTargets() {
            try {
                const { data } = await axios.get("/api/cancellation/targets");
                this.cancellation.targets = data.data;
                this.cancellation.classrooms = data.classrooms || [];

                if (data.permissions) {
                    this.cancellation.permissions = {
                        canAccessGroup: data.permissions.can_access_group,
                        canClassActivity: data.permissions.can_class_activity,
                        canCrossClass: data.permissions.can_cross_class,
                    };
                }

                const staff = data.data.find((t) => t.type === "staff");
                if (staff) {
                    this.cancellation.staff = staff;
                }
            } catch (error) {
                console.error("Fetch targets error:", error);
                // Silent failure for init to avoid annoying alerts on every reload
            }
        },

        async fetchComplaints() {
            try {
                const { data } = await axios.get("/api/meal-complaints");
                this.complaints.history = data.data;
            } catch (err) {
                console.error("Fetch complaints failed", err);
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
            if (!form.content) return alert("請填寫狀況描述");
            if (!form.occurred_at) return alert("請選擇發生日期");

            try {
                window.setBtnLoading(btn, true, "送出中...");

                const formData = new FormData();
                formData.append("occurred_at", form.occurred_at);
                formData.append("classroom_id", form.classroom_id || "");
                formData.append("content", form.content);
                if (form.photo) {
                    formData.append("photo", form.photo);
                }

                await axios.post("/api/meal-complaints", formData, {
                    headers: { "Content-Type": "multipart/form-data" },
                });

                alert("回報成功，感謝您的意見");

                this.complaints.form.content = "";
                this.complaints.form.photo = null;
                this.complaints.form.photoPreview = null;
                this.complaints.isReporting = false;

                await this.fetchComplaints();
            } catch (err) {
                console.error("Submit complaint failed", err);
                alert(err.response?.data?.message || "回報失敗，請重試");
            } finally {
                window.setBtnLoading(btn, false, "確認送出回報");
            }
        },

        async searchStudent() {
            try {
                const { data } = await axios.post(
                    "/api/cancellation/search-student",
                    this.cancellation.search,
                );
                this.cancellation.foundStudent = data.data;
            } catch (error) {
                console.error("Search student error:", error);
                alert(error.response?.data?.message || "找不到學生");
                this.cancellation.foundStudent = null;
            }
        },

        async submitCancellation() {
            if (!confirm("確定要送出停餐申請嗎？")) return;

            try {
                let targetId;
                let type;

                if (this.cancellation.mainTab === "staff") {
                    if (!this.cancellation.staff)
                        return alert("找不到教職員資料");
                    targetId = this.cancellation.staff.id;
                    type = "staff";
                } else {
                    if (!this.cancellation.foundStudent)
                        return alert("請先搜尋學生");
                    targetId = this.cancellation.foundStudent.id;
                    type = "student";
                }

                const { data } = await axios.post("/api/meal-cancellation", {
                    target_id: targetId,
                    type: type,
                    start_date: this.cancellation.startDate,
                    end_date:
                        this.cancellation.endDate ||
                        this.cancellation.startDate,
                    leave_type: this.cancellation.leaveType,
                    reason: this.cancellation.reason,
                });

                alert("停餐申請成功！\n" + (data.message || "已送出申請"));
                this.view = "dashboard";

                this.cancellation.reason = "";
                this.cancellation.foundStudent = null;
                this.cancellation.search = {
                    classroom_id: "",
                    seat_number: "",
                };
            } catch (error) {
                console.error("Submit cancellation error:", error);
                let msg = error.response?.data?.message || "申請失敗";
                if (error.response?.data?.errors) {
                    msg +=
                        "\n" +
                        JSON.stringify(error.response.data.errors, null, 2);
                }
                alert(msg);
            }
        },

        async submitClassActivity() {
            if (
                !confirm(
                    "確定要送出班級活動停餐申請嗎？\n將為整班學生建立停餐紀錄",
                )
            )
                return;

            try {
                const { data } = await axios.post(
                    "/api/meal-cancellation/class-activity",
                    {
                        classroom_id: this.cancellation.selectedClassroom,
                        start_date: this.cancellation.startDate,
                        end_date:
                            this.cancellation.endDate ||
                            this.cancellation.startDate,
                        reason: this.cancellation.reason || "班級活動",
                    },
                );

                alert(
                    `班級活動停餐申請成功！\n已建立 ${data.count || 0} 筆申請`,
                );
                this.view = "dashboard";

                this.cancellation.selectedClassroom = "";
                this.cancellation.reason = "";
            } catch (error) {
                console.error("Submit class activity error:", error);
                let msg = error.response?.data?.message || "申請失敗";
                if (error.response?.data?.errors) {
                    msg +=
                        "\n" +
                        JSON.stringify(error.response.data.errors, null, 2);
                }
                alert(msg);
            }
        },

        async searchStudentForGroup() {
            if (
                !this.cancellation.studentSearchKeyword ||
                this.cancellation.studentSearchKeyword.length < 2
            ) {
                this.cancellation.searchResults = [];
                return;
            }

            try {
                const { data } = await axios.get("/api/students/search", {
                    params: { keyword: this.cancellation.studentSearchKeyword },
                });
                this.cancellation.searchResults = data.data || [];
            } catch (error) {
                console.error("Search students error:", error);
                this.cancellation.searchResults = [];
            }
        },

        addStudentToSelection(student) {
            const exists = this.cancellation.selectedStudents.find(
                (s) => s.id === student.id,
            );
            if (exists) {
                alert("此學生已在清單中");
                return;
            }

            this.cancellation.selectedStudents.push({
                id: student.id,
                name: student.name,
                class_name: student.class_name,
                seat_number: student.seat_number,
            });

            this.cancellation.studentSearchKeyword = "";
            this.cancellation.searchResults = [];
        },

        removeStudentFromSelection(index) {
            this.cancellation.selectedStudents.splice(index, 1);
        },

        async submitGroupCancellation() {
            if (!confirm("確定要送出跨班團體停餐申請嗎？")) return;

            try {
                const { data } = await axios.post(
                    "/api/meal-cancellation/group",
                    {
                        student_ids: this.cancellation.selectedStudents.map(
                            (s) => s.id,
                        ),
                        start_date: this.cancellation.startDate,
                        end_date:
                            this.cancellation.endDate ||
                            this.cancellation.startDate,
                        reason: this.cancellation.reason || "團體活動",
                    },
                );

                alert(
                    `跨班團體停餐申請成功！\n已建立 ${data.count || 0} 筆申請`,
                );
                this.view = "dashboard";

                this.cancellation.selectedStudents = [];
                this.cancellation.reason = "";
            } catch (error) {
                console.error("Submit group cancellation error:", error);
                let msg = error.response?.data?.message || "申請失敗";
                if (error.response?.data?.errors) {
                    msg +=
                        "\n" +
                        JSON.stringify(error.response.data.errors, null, 2);
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
                const { data } = await axios.get("/api/students/search", {
                    params: {
                        classroom_id: this.cancellation.selectedClassForGroup,
                    },
                });
                this.cancellation.classStudents = data.data || [];
            } catch (error) {
                console.error("Fetch class students error:", error);
                alert("無法載入班級學生");
            }
        },

        toggleStudentSelection(student) {
            const index = this.cancellation.selectedStudents.findIndex(
                (s) => s.id === student.id,
            );
            if (index === -1) {
                this.addStudentToSelection(student);
            } else {
                this.removeStudentFromSelection(index);
            }
        },

        isStudentSelected(student) {
            return this.cancellation.selectedStudents.some(
                (s) => s.id === student.id,
            );
        },

        selectAllClassStudents() {
            this.cancellation.classStudents.forEach((student) => {
                if (!this.isStudentSelected(student)) {
                    this.addStudentToSelection(student);
                }
            });
        },

        clearClassSelection() {
            this.cancellation.classStudents.forEach((student) => {
                const index = this.cancellation.selectedStudents.findIndex(
                    (s) => s.id === student.id,
                );
                if (index !== -1) {
                    this.removeStudentFromSelection(index);
                }
            });
        },

        async fetchGroups() {
            try {
                const { data } = await axios.get(
                    "/api/meal-cancellation-groups",
                );
                this.cancellation.groups = data.data || [];
            } catch (error) {
                console.error("Fetch groups error:", error);
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
                name: "",
                description: "",
                students: [],
                studentSearch: "",
                searchResults: [],
            };
        },

        editGroup(group) {
            this.cancellation.editingGroup = group;
            this.cancellation.groupForm = {
                name: group.name,
                description: group.description,
                students: JSON.parse(JSON.stringify(group.students)),
                studentSearch: "",
                searchResults: [],
            };
        },

        async saveGroup() {
            const isNew = !this.cancellation.editingGroup.id;
            const url = isNew
                ? "/api/meal-cancellation-groups"
                : `/api/meal-cancellation-groups/${this.cancellation.editingGroup.id}`;
            const method = isNew ? "post" : "put";

            try {
                const payload = {
                    name: this.cancellation.groupForm.name,
                    description: this.cancellation.groupForm.description,
                    student_ids: this.cancellation.groupForm.students.map(
                        (s) => s.id,
                    ),
                };

                await axios[method](url, payload);

                alert(isNew ? "群組已建立" : "群組已更新");
                this.cancellation.editingGroup = null;
                this.fetchGroups();
            } catch (error) {
                console.error("Save group error:", error);
                let msg = error.response?.data?.message || "儲存失敗";
                if (error.response?.data?.errors) {
                    msg +=
                        "\n" +
                        JSON.stringify(error.response.data.errors, null, 2);
                }
                alert(msg);
            }
        },

        async deleteGroup(id) {
            if (!confirm("確定要刪除此群組嗎？此動作無法復原。")) return;

            try {
                await axios.delete(`/api/meal-cancellation-groups/${id}`);
                this.fetchGroups();
                alert("群組已刪除");
            } catch (error) {
                console.error("Delete group error:", error);
                alert("刪除失敗");
            }
        },

        async searchStudentForGroupForm() {
            if (
                !this.cancellation.groupForm.studentSearch ||
                this.cancellation.groupForm.studentSearch.length < 2
            ) {
                this.cancellation.groupForm.searchResults = [];
                return;
            }

            try {
                const { data } = await axios.get("/api/students/search", {
                    params: {
                        keyword: this.cancellation.groupForm.studentSearch,
                    },
                });
                const currentIds = this.cancellation.groupForm.students.map(
                    (s) => s.id,
                );
                this.cancellation.groupForm.searchResults = (
                    data.data || []
                ).filter((s) => !currentIds.includes(s.id));
            } catch (error) {
                console.error("Search students error:", error);
            }
        },

        addStudentToGroupForm(student) {
            this.cancellation.groupForm.students.push(student);
            this.cancellation.groupForm.studentSearch = "";
            this.cancellation.groupForm.searchResults = [];
        },

        removeStudentFromGroupForm(index) {
            this.cancellation.groupForm.students.splice(index, 1);
        },

        applyGroupSelection(group) {
            if (!group.students || group.students.length === 0) {
                alert("此群組沒有成員");
                return;
            }

            let addedCount = 0;
            const currentIds = this.cancellation.selectedStudents.map(
                (s) => s.id,
            );

            group.students.forEach((student) => {
                if (!currentIds.includes(student.id)) {
                    this.cancellation.selectedStudents.push({
                        id: student.id,
                        name: student.name,
                        class_name: student.class_name,
                        seat_number: student.seat_number,
                    });
                    addedCount++;
                }
            });
        },

        async fetchSurveys() {
            try {
                const [pRes, hRes] = await Promise.all([
                    axios.get("/api/surveys/pending"),
                    axios.get("/api/surveys/history"),
                ]);
                this.surveys.pending = pRes.data.data || [];
                this.surveys.history = hRes.data.data || [];
            } catch (e) {
                console.error(e);
            }
        },

        async fetchSettings() {
            try {
                const { data } = await axios.get("/api/settings");
                this.settings.dietary_type = data.data.dietary_type;
                this.settings.is_meal_user = !!data.data.is_meal_user;
            } catch (e) {
                console.error(e);
            }
        },

        async saveSettings() {
            try {
                await axios.post("/api/settings", this.settings);
                alert("設定已儲存");
                this.view = "dashboard";
            } catch (e) {
                alert("更新失敗");
            }
        },

        async scanQrCode() {
            if (!liff.isInClient()) {
                alert("請在 LINE App 中開啟以使用掃碼功能");
                return;
            }
            try {
                const result = await liff.scanCodeV2();
                if (result.value) {
                    try {
                        const { data } = await axios.post("/api/check-in", {
                            code: result.value,
                        });
                        alert(data.message);
                    } catch (apiErr) {
                        const msg =
                            apiErr.response?.data?.message || "簽到失敗";
                        alert(msg);
                    }
                }
            } catch (e) {
                console.error(e);
                if (e.message !== "User cancelled the scanning.") {
                    alert("掃描無法啟動: " + (e.message || e));
                }
            }
        },

        async resign() {
            if (
                confirm(
                    "確定要從目前學校離職嗎？\n此動作將使您的帳號狀態變為「離職」，且無法再查看學校資訊。",
                )
            ) {
                try {
                    await axios.post("/api/unbind");
                    alert("已離職並解除綁定");
                    location.reload();
                } catch (e) {
                    alert("操作失敗");
                }
            }
        },
    };
};

// Global Export
window.appData = appDataStore;

// Alpine check
if (window.Alpine) {
    window.Alpine.data("appData", appDataStore);
} else {
    document.addEventListener("alpine:init", () => {
        window.Alpine.data("appData", appDataStore);
    });
}
