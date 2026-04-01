{{-- ============================================================
     resources/views/pages/dashboard.blade.php
     Main SPA shell — Vue takes over from here.
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div id="app">

    {{-- ── TOAST NOTIFICATIONS ───────────────────────────────── --}}
    <div class="toast-wrap">
        <div v-for="(t, i) in toasts" :key="i" :class="['toast', t.type]">
            <i :class="t.icon"></i> @{{ t.msg }}
        </div>
    </div>

    <div class="shell">

        {{-- ════════════════════════════════════════════════════
             SIDEBAR
        ════════════════════════════════════════════════════ --}}
        <aside :class="['sidebar', { 'sb-open': sbOpen }]">

            <div class="sidebar-top">
                <div class="brand">
                    <div class="brand-mark">F</div>
                    <span class="brand-name">FinTask</span>
                </div>
                <div class="brand-sub">Finance Manager</div>
            </div>

            <nav class="nav">
                <div class="nav-label">Menu</div>
                <div v-for="n in navItems" :key="n.key"
                     :class="['nav-item', { active: page === n.key }]"
                     @click="go(n.key)">
                    <i :class="n.icon"></i>
                    @{{ n.label }}
                    <span v-if="n.key === 'tasks' && pendingCount > 0" class="nav-badge">
                        @{{ pendingCount }}
                    </span>
                </div>
            </nav>

            <div class="sidebar-foot">
                <div class="user-row">
                    <div class="avatar">@{{ initials }}</div>
                    <div class="user-meta">
                        <h5>@{{ userName }}</h5>
                        <p>Finance Tracker</p>
                    </div>
                </div>
            </div>

        </aside>

        {{-- ════════════════════════════════════════════════════
             MAIN CONTENT
        ════════════════════════════════════════════════════ --}}
        <main class="main">

            {{-- ── TOPBAR ──────────────────────────────────── --}}
            <div class="topbar">
                <div class="topbar-left">
                    <button class="mob-toggle" @click="sbOpen = !sbOpen">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>@{{ titles[page] }}</h2>
                        <p>@{{ subtitles[page] }}</p>
                    </div>
                </div>
                <div class="topbar-right">
                    <span class="date-pill">@{{ todayDisplay }}</span>
                    <div class="icon-btn">
                        <i class="fas fa-bell"></i>
                        <span v-if="pendingCount > 0" class="notif-dot"></span>
                    </div>
                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}" style="margin:0">
                        @csrf
                        <button type="submit" class="icon-btn" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- ════════════════════════════════════════════
                 DASHBOARD PAGE
            ════════════════════════════════════════════ --}}
            <div v-if="page === 'dashboard'" class="page">

                {{-- Stat Cards --}}
                <div class="stats-row">
                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,#059669,#34d399)"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:var(--green-bg);color:var(--green)">
                                <i class="fas fa-arrow-trend-down"></i>
                            </div>
                            <span class="stat-tag" style="background:var(--green-bg);color:var(--green)">Income</span>
                        </div>
                        <div class="stat-val">KES @{{ fmt(summary.income) }}</div>
                        <div class="stat-lbl">Total Income</div>
                    </div>

                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,#dc2626,#f87171)"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:var(--red-bg);color:var(--red)">
                                <i class="fas fa-arrow-trend-up"></i>
                            </div>
                            <span class="stat-tag" style="background:var(--red-bg);color:var(--red)">Expenses</span>
                        </div>
                        <div class="stat-val">KES @{{ fmt(summary.expenses) }}</div>
                        <div class="stat-lbl">Total Expenses</div>
                    </div>

                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,var(--accent),var(--accent2))"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:#fff3ed;color:var(--accent)">
                                <i class="fas fa-scale-balanced"></i>
                            </div>
                        </div>
                        <div class="stat-val">KES @{{ fmt(summary.income - summary.expenses) }}</div>
                        <div class="stat-lbl">Net Balance</div>
                    </div>

                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,var(--accent4),#818cf8)"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:#eef2ff;color:var(--accent4)">
                                <i class="fas fa-list-check"></i>
                            </div>
                        </div>
                        <div class="stat-val">@{{ doneCount }} / @{{ tasks.length }}</div>
                        <div class="stat-lbl">Tasks Done</div>
                    </div>
                </div>

                {{-- Recent Tasks + Transactions --}}
                <div class="two-col">
                    <div class="card">
                        <div class="card-hd">
                            <h3><i class="fas fa-check-circle"></i> Recent Tasks</h3>
                            <button class="btn btn-primary btn-sm" @click="go('tasks')">View All</button>
                        </div>
                        <div class="card-body">
                            <div v-if="!tasks.length" class="empty">
                                <i class="fas fa-clipboard-list"></i>
                                <h4>No tasks yet</h4>
                                <p>Create your first task</p>
                            </div>
                            <div v-else class="task-list">
                                <div v-for="t in tasks.slice(0, 5)" :key="t.id"
                                     :class="['task-item', { done: t.status === 'completed' }]">
                                    <div :class="['chk', { 'chk-on': t.status === 'completed' }]"
                                         @click="toggleTask(t)">
                                        <i v-if="t.status === 'completed'" class="fas fa-check"></i>
                                    </div>
                                    <div class="task-body">
                                        <div class="task-title">@{{ t.title }}</div>
                                        <div class="task-meta">
                                            <span><span :class="['pri', t.priority]"></span>@{{ t.priority }}</span>
                                            <span v-if="t.due_date">
                                                <i class="fas fa-calendar-days"></i>@{{ fmtDate(t.due_date) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-hd">
                            <h3><i class="fas fa-arrows-left-right"></i> Recent Transactions</h3>
                            <button class="btn btn-primary btn-sm" @click="go('finance')">View All</button>
                        </div>
                        <div class="card-body">
                            <div v-if="!transactions.length" class="empty">
                                <i class="fas fa-receipt"></i>
                                <h4>No transactions yet</h4>
                                <p>Add income or expenses</p>
                            </div>
                            <div v-else class="txn-list">
                                <div v-for="txn in transactions.slice(0, 5)" :key="txn.id" class="txn-item">
                                    <div :class="['txn-ico', txn.type]">
                                        <i :class="txn.type === 'income' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i>
                                    </div>
                                    <div class="txn-info">
                                        <div class="txn-desc">@{{ txn.description || txn.category }}</div>
                                        <div class="txn-cat">@{{ txn.category }}</div>
                                    </div>
                                    <div style="text-align:right">
                                        <div :class="['txn-amt', txn.type]">
                                            @{{ txn.type === 'income' ? '+' : '-' }} KES @{{ fmt(txn.amount) }}
                                        </div>
                                        <div class="txn-date">@{{ fmtDate(txn.date) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Category Breakdown + Quick Actions --}}
                <div class="two-col">
                    <div class="card">
                        <div class="card-hd">
                            <h3><i class="fas fa-chart-pie"></i> Spending Breakdown</h3>
                        </div>
                        <div class="card-body">
                            <div v-if="!summary.categories || !summary.categories.length" class="empty">
                                <i class="fas fa-chart-pie"></i>
                                <h4>No spending data</h4>
                                <p>Record expenses to see breakdown</p>
                            </div>
                            <div v-else class="cat-list">
                                <div v-for="(c, i) in summary.categories" :key="i" class="cat-item">
                                    <div class="cat-dot" :style="{ background: palette[i % palette.length] }"></div>
                                    <div class="cat-body">
                                        <div class="cat-row">
                                            <span>@{{ c.category }}</span>
                                            <span style="font-family:'DM Mono',monospace">KES @{{ fmt(c.total) }}</span>
                                        </div>
                                        <div class="cat-bar">
                                            <div class="cat-fill"
                                                 :style="{ width: catPct(c.total) + '%', background: palette[i % palette.length] }">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-hd">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="qa-list">
                                <button class="qa-btn" @click="openTaskModal()">
                                    <i class="fas fa-plus" style="color:var(--accent4)"></i> Add New Task
                                </button>
                                <button class="qa-btn" @click="openTxnModal('income')">
                                    <i class="fas fa-arrow-down" style="color:var(--green)"></i> Record Income
                                </button>
                                <button class="qa-btn" @click="openTxnModal('expense')">
                                    <i class="fas fa-arrow-up" style="color:var(--red)"></i> Record Expense
                                </button>
                                <button class="qa-btn" @click="go('report')">
                                    <i class="fas fa-chart-bar" style="color:var(--amber)"></i> View Daily Report
                                </button>
                                <button class="qa-btn" @click="go('budget')">
                                    <i class="fas fa-bullseye" style="color:var(--accent)"></i> Budget Tracker
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /dashboard --}}

            {{-- ════════════════════════════════════════════
                 TASKS PAGE
            ════════════════════════════════════════════ --}}
            <div v-if="page === 'tasks'" class="page">

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                    <div class="tabs">
                        <button :class="['tab', { on: tf === 'all' }]"       @click="tf = 'all'">All</button>
                        <button :class="['tab', { on: tf === 'pending' }]"   @click="tf = 'pending'">Pending</button>
                        <button :class="['tab', { on: tf === 'completed' }]" @click="tf = 'completed'">Done</button>
                    </div>
                    <div style="display:flex;gap:10px;align-items:center">
                        <div class="search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" class="fc" placeholder="Search tasks…"
                                   v-model="ts" style="width:210px;">
                        </div>
                        <button class="btn btn-primary" @click="openTaskModal()">
                            <i class="fas fa-plus"></i> New Task
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div v-if="loading" class="load-center"><div class="spin"></div></div>
                        <div v-else-if="!filteredTasks.length" class="empty">
                            <i class="fas fa-clipboard-check"></i>
                            <h4>No tasks found</h4>
                            <p>Try a different filter or create a new task</p>
                        </div>
                        <div v-else class="task-list">
                            <div v-for="t in filteredTasks" :key="t.id"
                                 :class="['task-item', { done: t.status === 'completed' }]">
                                <div :class="['chk', { 'chk-on': t.status === 'completed' }]"
                                     @click="toggleTask(t)">
                                    <i v-if="t.status === 'completed'" class="fas fa-check"></i>
                                </div>
                                <div class="task-body">
                                    <div class="task-title">@{{ t.title }}</div>
                                    <div class="task-meta">
                                        <span><span :class="['pri', t.priority]"></span>@{{ t.priority }}</span>
                                        <span v-if="t.due_date">
                                            <i class="fas fa-calendar-days"></i>@{{ fmtDate(t.due_date) }}
                                        </span>
                                        <span v-if="t.category">
                                            <i class="fas fa-tag"></i>@{{ t.category }}
                                        </span>
                                    </div>
                                </div>
                                <div class="task-acts">
                                    <button class="btn-ico ok" @click="toggleTask(t)">
                                        <i :class="t.status === 'pending' ? 'fas fa-check' : 'fas fa-rotate-left'"></i>
                                    </button>
                                    <button class="btn-ico" @click="editTask(t)">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn-ico del" @click="delTask(t.id)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /tasks --}}

            {{-- ════════════════════════════════════════════
                 FINANCE PAGE
            ════════════════════════════════════════════ --}}
            <div v-if="page === 'finance'" class="page">

                <div class="stats-row" style="grid-template-columns:repeat(3,1fr)">
                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,var(--green),#34d399)"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:var(--green-bg);color:var(--green)">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                        </div>
                        <div class="stat-val">KES @{{ fmt(summary.income) }}</div>
                        <div class="stat-lbl">Monthly Income</div>
                    </div>
                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,var(--red),#f87171)"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:var(--red-bg);color:var(--red)">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                        </div>
                        <div class="stat-val">KES @{{ fmt(summary.expenses) }}</div>
                        <div class="stat-lbl">Monthly Expenses</div>
                    </div>
                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,var(--blue),#60a5fa)"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:var(--blue-bg);color:var(--blue)">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                        </div>
                        <div class="stat-val">KES @{{ fmt(summary.income - summary.expenses) }}</div>
                        <div class="stat-lbl">Net Balance</div>
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                    <div class="tabs">
                        <button :class="['tab', { on: txf === 'all' }]"     @click="txf = 'all'">All</button>
                        <button :class="['tab', { on: txf === 'income' }]"  @click="txf = 'income'">Income</button>
                        <button :class="['tab', { on: txf === 'expense' }]" @click="txf = 'expense'">Expenses</button>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button class="btn btn-green" @click="openTxnModal('income')">
                            <i class="fas fa-plus"></i> Income
                        </button>
                        <button class="btn btn-red" @click="openTxnModal('expense')">
                            <i class="fas fa-minus"></i> Expense
                        </button>
                    </div>
                </div>

                <div class="two-col">
                    <div class="card">
                        <div class="card-hd"><h3><i class="fas fa-list"></i> Transactions</h3></div>
                        <div class="card-body">
                            <div v-if="!filteredTxns.length" class="empty">
                                <i class="fas fa-receipt"></i>
                                <h4>No transactions found</h4>
                                <p>Record your first transaction</p>
                            </div>
                            <div v-else class="txn-list">
                                <div v-for="txn in filteredTxns" :key="txn.id" class="txn-item">
                                    <div :class="['txn-ico', txn.type]">
                                        <i :class="catIcon(txn.category)"></i>
                                    </div>
                                    <div class="txn-info">
                                        <div class="txn-desc">@{{ txn.description || txn.category }}</div>
                                        <div class="txn-cat">@{{ txn.category }} · @{{ fmtDate(txn.date) }}</div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <div :class="['txn-amt', txn.type]">
                                            @{{ txn.type === 'income' ? '+' : '-' }} KES @{{ fmt(txn.amount) }}
                                        </div>
                                        <button class="btn-ico del" @click="delTxn(txn.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-hd"><h3><i class="fas fa-chart-pie"></i> By Category</h3></div>
                        <div class="card-body">
                            <div v-if="!summary.categories || !summary.categories.length" class="empty">
                                <i class="fas fa-chart-pie"></i>
                                <h4>No expense data</h4>
                                <p>Add expenses to see breakdown</p>
                            </div>
                            <div v-else class="cat-list">
                                <div v-for="(c, i) in summary.categories" :key="i" class="cat-item">
                                    <div class="cat-dot" :style="{ background: palette[i % palette.length] }"></div>
                                    <div class="cat-body">
                                        <div class="cat-row">
                                            <span>@{{ c.category }}</span>
                                            <span style="font-family:'DM Mono',monospace">
                                                KES @{{ fmt(c.total) }}
                                                <span style="color:var(--text3)">(@{{ catPct(c.total) }}%)</span>
                                            </span>
                                        </div>
                                        <div class="cat-bar">
                                            <div class="cat-fill"
                                                 :style="{ width: catPct(c.total) + '%', background: palette[i % palette.length] }">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /finance --}}

            {{-- ════════════════════════════════════════════
                 DAILY REPORT PAGE
            ════════════════════════════════════════════ --}}
            <div v-if="page === 'report'" class="page">

                <div style="margin-bottom:20px;">
                    <input type="date" class="fc" style="width:190px;"
                           v-model="rDate" @change="buildReport()">
                </div>

                <div class="report-row">
                    <div class="report-card">
                        <div class="rep-ico" style="background:var(--green-bg);color:var(--green)">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="rep-val">@{{ report.tasksDone }}</div>
                        <div class="rep-lbl">Tasks Completed Today</div>
                    </div>
                    <div class="report-card">
                        <div class="rep-ico" style="background:var(--red-bg);color:var(--red)">
                            <i class="fas fa-cart-shopping"></i>
                        </div>
                        <div class="rep-val">KES @{{ fmt(report.todayExp) }}</div>
                        <div class="rep-lbl">Money Spent Today</div>
                    </div>
                    <div class="report-card">
                        <div class="rep-ico" style="background:var(--blue-bg);color:var(--blue)">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="rep-val">KES @{{ fmt(report.remaining) }}</div>
                        <div class="rep-lbl">Remaining Budget</div>
                    </div>
                </div>

                <div class="two-col">
                    <div class="card">
                        <div class="card-hd">
                            <h3><i class="fas fa-clipboard-check"></i> Completed Tasks</h3>
                        </div>
                        <div class="card-body">
                            <div v-if="!report.doneTasks.length" class="empty">
                                <i class="fas fa-clipboard-check"></i>
                                <h4>No tasks completed today</h4>
                            </div>
                            <div v-else class="task-list">
                                <div v-for="(t, i) in report.doneTasks" :key="i" class="task-item done">
                                    <div class="chk chk-on"><i class="fas fa-check"></i></div>
                                    <div class="task-body">
                                        <div class="task-title">@{{ t.title }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-hd">
                            <h3><i class="fas fa-receipt"></i> Today's Expenses</h3>
                        </div>
                        <div class="card-body">
                            <div v-if="!report.todayExpList.length" class="empty">
                                <i class="fas fa-receipt"></i>
                                <h4>No expenses today</h4>
                                <p>Great job saving money!</p>
                            </div>
                            <div v-else class="txn-list">
                                <div v-for="(e, i) in report.todayExpList" :key="i" class="txn-item">
                                    <div class="txn-ico expense"><i :class="catIcon(e.category)"></i></div>
                                    <div class="txn-info">
                                        <div class="txn-desc">@{{ e.description || e.category }}</div>
                                        <div class="txn-cat">@{{ e.category }}</div>
                                    </div>
                                    <div class="txn-amt expense">- KES @{{ fmt(e.amount) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-top:20px;">
                    <div class="card-hd">
                        <h3><i class="fas fa-terminal"></i> Daily Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="terminal">
                            <div class="t-head">📊 Daily Report — @{{ rDate }}</div>
                            <div class="t-div">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>
                            <div>✅ Tasks completed: <span class="t-green">@{{ report.tasksDone }}</span></div>
                            <div>⏳ Tasks pending:   <span class="t-amber">@{{ report.tasksPending }}</span></div>
                            <div class="t-div">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>
                            <div class="t-green">💰 Today's Income:    KES @{{ fmt(report.todayInc) }}</div>
                            <div class="t-red">💸  Today's Expenses:  KES @{{ fmt(report.todayExp) }}</div>
                            <div class="t-div">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>
                            <div class="t-blue">📈 Monthly Income:    KES @{{ fmt(summary.income) }}</div>
                            <div class="t-amber">📉 Monthly Expenses:  KES @{{ fmt(summary.expenses) }}</div>
                            <div :style="{ color: report.remaining >= 0 ? '#34d399' : '#f87171' }">
                                💼 Remaining Budget:  KES @{{ fmt(report.remaining) }}
                            </div>
                            <div class="t-div">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>
                        </div>
                    </div>
                </div>

            </div>{{-- /report --}}

            {{-- ════════════════════════════════════════════
                 BUDGET TRACKER PAGE
            ════════════════════════════════════════════ --}}
            <div v-if="page === 'budget'" class="page">

                <div class="stats-row" style="grid-template-columns:repeat(3,1fr)">
                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,var(--green),#34d399)"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:var(--green-bg);color:var(--green)">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                        <div class="stat-val">KES @{{ fmt(summary.income) }}</div>
                        <div class="stat-lbl">Total Budget (Income)</div>
                    </div>
                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,var(--red),#f87171)"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:var(--red-bg);color:var(--red)">
                                <i class="fas fa-fire"></i>
                            </div>
                        </div>
                        <div class="stat-val">KES @{{ fmt(summary.expenses) }}</div>
                        <div class="stat-lbl">Spent So Far</div>
                    </div>
                    <div class="stat">
                        <div class="stat-stripe" style="background:linear-gradient(90deg,var(--accent),var(--accent2))"></div>
                        <div class="stat-hd">
                            <div class="stat-ico" style="background:#fff3ed;color:var(--accent)">
                                <i class="fas fa-shield-halved"></i>
                            </div>
                        </div>
                        <div class="stat-val">@{{ budgetPct }}%</div>
                        <div class="stat-lbl">Budget Used</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-hd">
                        <h3><i class="fas fa-chart-bar"></i> Budget Progress</h3>
                    </div>
                    <div class="card-body">
                        <div class="prog-wrap">
                            <div class="prog-hd">
                                <span>Overall Spending</span>
                                <span>@{{ budgetPct }}%</span>
                            </div>
                            <div class="prog-track">
                                <div class="prog-fill" :style="{
                                    width:  Math.min(budgetPct, 100) + '%',
                                    background: budgetPct > 90 ? 'var(--red)'
                                              : budgetPct > 70 ? 'var(--amber)'
                                              : 'var(--green)'
                                }"></div>
                            </div>
                            <div class="prog-foot">
                                <span>KES 0</span>
                                <span>KES @{{ fmt(summary.income) }}</span>
                            </div>
                        </div>

                        <div v-if="summary.categories && summary.categories.length">
                            <h4 style="font-size:13px;font-weight:700;margin-bottom:14px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;">
                                By Category
                            </h4>
                            <div class="cat-list">
                                <div v-for="(c, i) in summary.categories" :key="i" class="cat-item">
                                    <div class="cat-dot" :style="{ background: palette[i % palette.length] }"></div>
                                    <div class="cat-body">
                                        <div class="cat-row">
                                            <span>@{{ c.category }}</span>
                                            <span style="font-family:'DM Mono',monospace">
                                                KES @{{ fmt(c.total) }}
                                                <span style="color:var(--text3)">(@{{ catPct(c.total) }}%)</span>
                                            </span>
                                        </div>
                                        <div class="cat-bar">
                                            <div class="cat-fill"
                                                 :style="{ width: catPct(c.total) + '%', background: palette[i % palette.length] }">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="empty" style="padding:24px 0 4px">
                            <i class="fas fa-chart-bar"></i>
                            <h4>No spending data yet</h4>
                            <p>Add expenses to track your budget</p>
                        </div>
                    </div>
                </div>

            </div>{{-- /budget --}}

        </main>
    </div>{{-- /shell --}}

    {{-- ════════════════════════════════════════════════════
         TASK MODAL
    ════════════════════════════════════════════════════ --}}
    <div v-if="taskModal" class="overlay" @click.self="taskModal = false">
        <div class="modal">
            <div class="modal-hd">
                <h3>@{{ editingTask ? 'Edit Task' : 'New Task' }}</h3>
                <button class="modal-x" @click="taskModal = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-bd">
                <div class="fg">
                    <label>Title *</label>
                    <input type="text" class="fc" v-model="tForm.title"
                           placeholder="e.g. Pay rent, Save KES 5,000">
                </div>
                <div class="fg">
                    <label>Description</label>
                    <textarea class="fc" v-model="tForm.description"
                              placeholder="Optional details…"></textarea>
                </div>
                <div class="two-fg">
                    <div class="fg">
                        <label>Priority</label>
                        <select class="fc" v-model="tForm.priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Due Date</label>
                        <input type="date" class="fc" v-model="tForm.due_date">
                    </div>
                </div>
                <div class="fg" style="margin-bottom:0">
                    <label>Category</label>
                    <select class="fc" v-model="tForm.category">
                        <option value="general">General</option>
                        <option value="payment">Payment</option>
                        <option value="savings">Savings</option>
                        <option value="investment">Investment</option>
                        <option value="bill">Bill</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="modal-ft">
                <button class="btn btn-ghost" @click="taskModal = false">Cancel</button>
                <button class="btn btn-primary" @click="saveTask()" :disabled="!tForm.title">
                    <i class="fas fa-save"></i>
                    @{{ editingTask ? 'Update' : 'Create' }}
                </button>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════
         TRANSACTION MODAL
    ════════════════════════════════════════════════════ --}}
    <div v-if="txnModal" class="overlay" @click.self="txnModal = false">
        <div class="modal">
            <div class="modal-hd">
                <h3 :style="{ color: txnForm.type === 'income' ? 'var(--green)' : 'var(--red)' }">
                    <i :class="txnForm.type === 'income' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i>
                    Record @{{ txnForm.type === 'income' ? 'Income' : 'Expense' }}
                </h3>
                <button class="modal-x" @click="txnModal = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-bd">
                <div class="fg">
                    <label>Amount (KES) *</label>
                    <input type="number" class="fc" v-model="txnForm.amount"
                           placeholder="e.g. 5000" min="1" step="0.01">
                </div>
                <div class="two-fg">
                    <div class="fg">
                        <label>Category *</label>
                        <select class="fc" v-model="txnForm.category">
                            <option value="">Select…</option>
                            <template v-if="txnForm.type === 'expense'">
                                <option value="Food">🍔 Food</option>
                                <option value="Transport">🚌 Transport</option>
                                <option value="Bills">📄 Bills</option>
                                <option value="Rent">🏠 Rent</option>
                                <option value="Shopping">🛍️ Shopping</option>
                                <option value="Health">⚕️ Health</option>
                                <option value="Entertainment">🎬 Entertainment</option>
                                <option value="Education">📚 Education</option>
                                <option value="Investment">📈 Investment</option>
                                <option value="Other">📦 Other</option>
                            </template>
                            <template v-else>
                                <option value="Salary">💼 Salary</option>
                                <option value="Freelance">💻 Freelance</option>
                                <option value="Business">🏪 Business</option>
                                <option value="Investment">📈 Investment</option>
                                <option value="Gift">🎁 Gift</option>
                                <option value="Other">📦 Other</option>
                            </template>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Date *</label>
                        <input type="date" class="fc" v-model="txnForm.date">
                    </div>
                </div>
                <div class="fg" style="margin-bottom:0">
                    <label>Description</label>
                    <input type="text" class="fc" v-model="txnForm.description"
                           placeholder="e.g. Lunch at Java House">
                </div>
            </div>
            <div class="modal-ft">
                <button class="btn btn-ghost" @click="txnModal = false">Cancel</button>
                <button :class="['btn', txnForm.type === 'income' ? 'btn-green' : 'btn-red']"
                        @click="saveTxn()"
                        :disabled="!txnForm.amount || !txnForm.category || !txnForm.date">
                    <i class="fas fa-save"></i>
                    Record @{{ txnForm.type === 'income' ? 'Income' : 'Expense' }}
                </button>
            </div>
        </div>
    </div>

</div>{{-- #app --}}
@endsection