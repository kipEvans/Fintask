/* ============================================================
   FinTask — Vue 3 Application
   File: public/js/fintask.js
   ============================================================ */

const { createApp, ref, computed, onMounted } = Vue;

createApp({
    setup() {

        /* ── STATE ─────────────────────────────────────────────── */
        const page    = ref('dashboard');
        const sbOpen  = ref(false);
        const loading = ref(false);
        const toasts  = ref([]);

        // User (populated from meta tag set by Laravel)
        const userName = ref(
            document.querySelector('meta[name="user-name"]')?.content || 'User'
        );

        // Tasks
        const tasks       = ref([]);
        const tf          = ref('all');   // task filter
        const ts          = ref('');      // task search
        const taskModal   = ref(false);
        const editingTask = ref(null);
        const tForm       = ref({
            title: '', description: '', priority: 'medium',
            due_date: '', category: 'general'
        });

        // Transactions
        const transactions = ref([]);
        const txf          = ref('all');
        const txnModal     = ref(false);
        const txnForm      = ref({
            amount: '', type: 'expense', category: '',
            description: '', date: todayStr()
        });

        // Summary / Report
        const summary = ref({ income: 0, expenses: 0, categories: [] });
        const rDate   = ref(todayStr());
        const report  = ref({
            tasksDone: 0, tasksPending: 0,
            todayInc: 0, todayExp: 0, remaining: 0,
            doneTasks: [], todayExpList: []
        });

        /* ── CONFIG ────────────────────────────────────────────── */
        const palette = [
            '#e85d26','#2a9d8f','#6366f1','#f0a500',
            '#dc2626','#059669','#0891b2','#7c3aed',
            '#db2777','#65a30d'
        ];

        const navItems = [
            { key: 'dashboard', icon: 'fas fa-th-large',    label: 'Dashboard' },
            { key: 'tasks',     icon: 'fas fa-check-circle', label: 'Tasks' },
            { key: 'finance',   icon: 'fas fa-coins',        label: 'Finance' },
            { key: 'report',    icon: 'fas fa-chart-bar',    label: 'Daily Report' },
            { key: 'budget',    icon: 'fas fa-bullseye',     label: 'Budget Tracker' },
        ];

        const titles = {
            dashboard: 'Dashboard',
            tasks:     'Task Management',
            finance:   'Finance Tracker',
            report:    'Daily Report',
            budget:    'Budget Tracker',
        };

        const subtitles = {
            dashboard: 'Overview of your finances & tasks',
            tasks:     'Manage your financial tasks',
            finance:   'Track income and expenses',
            report:    'Your daily financial summary',
            budget:    'Monitor your spending',
        };

        /* ── COMPUTED ──────────────────────────────────────────── */
        const initials = computed(() =>
            userName.value.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
        );

        const todayDisplay = computed(() =>
            new Date().toLocaleDateString('en-US', {
                weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
            })
        );

        const pendingCount = computed(() =>
            tasks.value.filter(t => t.status === 'pending').length
        );

        const doneCount = computed(() =>
            tasks.value.filter(t => t.status === 'completed').length
        );

        const budgetPct = computed(() => {
            if (!summary.value.income) return 0;
            return Math.round((summary.value.expenses / summary.value.income) * 100);
        });

        const filteredTasks = computed(() => {
            let r = [...tasks.value];
            if (tf.value !== 'all') r = r.filter(t => t.status === tf.value);
            if (ts.value) {
                const q = ts.value.toLowerCase();
                r = r.filter(t =>
                    t.title.toLowerCase().includes(q) ||
                    (t.description && t.description.toLowerCase().includes(q))
                );
            }
            return r;
        });

        const filteredTxns = computed(() =>
            txf.value === 'all'
                ? transactions.value
                : transactions.value.filter(t => t.type === txf.value)
        );

        /* ── HELPERS ───────────────────────────────────────────── */
        function todayStr() {
            return new Date().toISOString().split('T')[0];
        }

        function fmt(n) {
            if (!n && n !== 0) return '0';
            return Number(n).toLocaleString('en-US');
        }

        function fmtDate(d) {
            if (!d) return '';
            return new Date(d + 'T00:00:00').toLocaleDateString('en-US', {
                month: 'short', day: 'numeric'
            });
        }

        function catPct(total) {
            if (!summary.value.expenses) return 0;
            return Math.round((total / summary.value.expenses) * 100);
        }

        function catIcon(cat) {
            const m = {
                Food: 'fas fa-utensils', Transport: 'fas fa-bus',
                Bills: 'fas fa-file-invoice', Rent: 'fas fa-house',
                Shopping: 'fas fa-bag-shopping', Health: 'fas fa-heart-pulse',
                Entertainment: 'fas fa-film', Education: 'fas fa-graduation-cap',
                Investment: 'fas fa-chart-line', Salary: 'fas fa-briefcase',
                Freelance: 'fas fa-laptop', Business: 'fas fa-store',
                Gift: 'fas fa-gift', Other: 'fas fa-tag',
                savings: 'fas fa-piggy-bank', payment: 'fas fa-credit-card',
                bill: 'fas fa-file-invoice-dollar', general: 'fas fa-clipboard',
            };
            return m[cat] || 'fas fa-tag';
        }

        function toast(msg, type = 'ok') {
            const icons = {
                ok:   'fas fa-check-circle',
                err:  'fas fa-exclamation-circle',
                info: 'fas fa-info-circle'
            };
            const t = { msg, type, icon: icons[type] || icons.info };
            toasts.value.push(t);
            setTimeout(() => {
                const i = toasts.value.indexOf(t);
                if (i > -1) toasts.value.splice(i, 1);
            }, 3200);
        }

        function go(p) {
            page.value = p;
            sbOpen.value = false;
            if (p === 'report') buildReport();
        }

        /* ── API HELPERS ───────────────────────────────────────── */
        function getHeaders() {
            const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
            const tokenMeta = document.querySelector('meta[name="auth-token"]');
            return {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  csrfMeta ? csrfMeta.content : '',
                'Authorization': tokenMeta ? `Bearer ${tokenMeta.content}` : '',
            };
        }

        async function api(method, url, data = null) {
            const opts = {
                method,
                headers: getHeaders(),
            };
            if (data) opts.body = JSON.stringify(data);

            const res = await fetch(url, opts);

            if (res.status === 401) {
                // Token expired — redirect to login
                window.location.href = '/login';
                return null;
            }

            const json = await res.json();

            if (!res.ok) {
                const msg = json.message || Object.values(json.errors || {})[0]?.[0] || 'Request failed';
                throw new Error(msg);
            }

            return json;
        }

        /* ── TASKS API ─────────────────────────────────────────── */
        async function fetchTasks() {
            try {
                loading.value = true;
                const res = await api('GET', '/api/tasks');
                if (res) {
                    // Laravel paginator returns data.data; flat array returns data
                    tasks.value = Array.isArray(res.data)
                        ? res.data
                        : (res.data?.data || []);
                }
            } catch (e) {
                toast('Failed to load tasks', 'err');
            } finally {
                loading.value = false;
            }
        }

        function openTaskModal() {
            editingTask.value = null;
            tForm.value = {
                title: '', description: '', priority: 'medium',
                due_date: '', category: 'general'
            };
            taskModal.value = true;
        }

        function editTask(t) {
            editingTask.value = t;
            tForm.value = {
                title:       t.title,
                description: t.description || '',
                priority:    t.priority,
                due_date:    t.due_date ? t.due_date.split('T')[0] : '',
                category:    t.category || 'general',
            };
            taskModal.value = true;
        }

        async function saveTask() {
            try {
                if (editingTask.value) {
                    const res = await api('PUT', `/api/tasks/${editingTask.value.id}`, tForm.value);
                    if (res) {
                        Object.assign(editingTask.value, res.data);
                        toast('Task updated ✏️');
                    }
                } else {
                    const res = await api('POST', '/api/tasks', tForm.value);
                    if (res) {
                        tasks.value.unshift(res.data);
                        toast('Task created 🎯');
                    }
                }
                taskModal.value = false;
            } catch (e) {
                toast(e.message || 'Failed to save task', 'err');
            }
        }

        async function toggleTask(t) {
            try {
                const newStatus = t.status === 'pending' ? 'completed' : 'pending';
                const res = await api('PUT', `/api/tasks/${t.id}`, { status: newStatus });
                if (res) {
                    t.status       = res.data.status;
                    t.completed_at = res.data.completed_at;
                    toast(newStatus === 'completed' ? 'Task completed! 🎉' : 'Task reopened', 'info');
                    buildReport();
                }
            } catch (e) {
                toast('Failed to update task', 'err');
            }
        }

        async function delTask(id) {
            if (!confirm('Delete this task?')) return;
            try {
                await api('DELETE', `/api/tasks/${id}`);
                tasks.value = tasks.value.filter(t => t.id !== id);
                toast('Task deleted', 'info');
            } catch (e) {
                toast('Failed to delete task', 'err');
            }
        }

        /* ── TRANSACTIONS API ──────────────────────────────────── */
        async function fetchTransactions() {
            try {
                const res = await api('GET', '/api/transactions');
                if (res) {
                    transactions.value = Array.isArray(res.data)
                        ? res.data
                        : (res.data?.data || []);
                    recalcSummary();
                }
            } catch (e) {
                toast('Failed to load transactions', 'err');
            }
        }

        function openTxnModal(type) {
            txnForm.value = {
                amount: '', type, category: '',
                description: '', date: todayStr()
            };
            txnModal.value = true;
        }

        async function saveTxn() {
            try {
                const res = await api('POST', '/api/transactions', txnForm.value);
                if (res) {
                    transactions.value.unshift(res.data);
                    recalcSummary();
                    buildReport();
                    toast(txnForm.value.type === 'income' ? 'Income recorded 💰' : 'Expense recorded 📝');
                    txnModal.value = false;
                }
            } catch (e) {
                toast(e.message || 'Failed to save transaction', 'err');
            }
        }

        async function delTxn(id) {
            if (!confirm('Delete this transaction?')) return;
            try {
                await api('DELETE', `/api/transactions/${id}`);
                transactions.value = transactions.value.filter(t => t.id !== id);
                recalcSummary();
                buildReport();
                toast('Transaction deleted', 'info');
            } catch (e) {
                toast('Failed to delete transaction', 'err');
            }
        }

        /* ── LOCAL SUMMARY CALC ────────────────────────────────── */
        // Called after every transaction change for instant UI feedback.
        // The real source of truth is the Laravel API summary endpoint.
        function recalcSummary() {
            const income   = transactions.value
                .filter(t => t.type === 'income')
                .reduce((s, t) => s + Number(t.amount), 0);

            const expenses = transactions.value
                .filter(t => t.type === 'expense')
                .reduce((s, t) => s + Number(t.amount), 0);

            const catMap = {};
            transactions.value
                .filter(t => t.type === 'expense')
                .forEach(t => {
                    catMap[t.category] = (catMap[t.category] || 0) + Number(t.amount);
                });

            const categories = Object.entries(catMap)
                .map(([category, total]) => ({ category, total }))
                .sort((a, b) => b.total - a.total);

            summary.value = { income, expenses, categories };
        }

        /* ── REPORT (CLIENT-SIDE) ──────────────────────────────── */
        // For a production build wire this to GET /api/reports/daily?date=X
        function buildReport() {
            const d          = rDate.value;
            const doneTasks  = tasks.value.filter(t =>
                t.status === 'completed' &&
                (t.completed_at || '').startsWith(d)
            );
            const pending     = tasks.value.filter(t => t.status === 'pending');
            const todayTxns   = transactions.value.filter(t => t.date === d);
            const todayInc    = todayTxns.filter(t => t.type === 'income').reduce((s, t) => s + Number(t.amount), 0);
            const todayExpList = todayTxns.filter(t => t.type === 'expense');
            const todayExp    = todayExpList.reduce((s, t) => s + Number(t.amount), 0);

            report.value = {
                tasksDone:    doneTasks.length,
                tasksPending: pending.length,
                todayInc, todayExp,
                remaining:    summary.value.income - summary.value.expenses,
                doneTasks,
                todayExpList,
            };
        }

        /* ── INIT ──────────────────────────────────────────────── */
        onMounted(async () => {
            await fetchTasks();
            await fetchTransactions();
            buildReport();
        });

        /* ── EXPOSE ────────────────────────────────────────────── */
        return {
            page, sbOpen, loading, toasts, userName,
            tasks, tf, ts, taskModal, editingTask, tForm,
            transactions, txf, txnModal, txnForm,
            summary, rDate, report,
            palette, navItems, titles, subtitles,
            initials, todayDisplay, pendingCount, doneCount,
            budgetPct, filteredTasks, filteredTxns,
            fmt, fmtDate, catPct, catIcon, go,
            openTaskModal, editTask, saveTask, toggleTask, delTask,
            openTxnModal, saveTxn, delTxn, buildReport,
        };
    }
}).mount('#app');