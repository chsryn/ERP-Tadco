<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'ERP TADCO')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @stack('styles')

    <style>
        :root {
            --app-navy: #071437;
            --app-navy-soft: #252F4A;
            --app-bg: #F1F1F4;
            --app-text: #071437;
            --app-muted: #99A1B7;
            --app-border: #DBDFE9;
            --app-blue: #1B84FF;
            --app-orange: #F6C000;
            --app-shadow: rgba(0, 0, 0, 0.03) 0px 3px 4px 0px;
        }

        * {
            letter-spacing: 0;
        }

        body {
            min-height: 100vh;
            background: var(--app-navy);
            color: var(--app-text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 13px;
        }

        .app-shell {
            min-height: 100vh;
            padding-left: 280px;
            background: var(--app-navy);
            transition: padding-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 20;
            width: 280px;
            min-height: 100vh;
            background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            color: #cbd5e1;
            display: flex;
            flex-direction: column;
            padding: 24px 16px 20px;
            box-shadow: 0 0 50px rgba(15, 23, 42, 0.18);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-x: hidden;
        }

        /* Ponytail: collapsed = icon-only 72px, no extra JS libs */
        .app-shell.collapsed {
            padding-left: 72px;
        }

        .sidebar.collapsed {
            width: 72px;
            padding-left: 8px;
            padding-right: 8px;
        }

        .sidebar.collapsed .brand-logo {
            max-height: 36px;
        }

        .sidebar.collapsed .sidebar-link,
        .sidebar.collapsed .sidebar-group-toggle {
            justify-content: center;
            padding-left: 8px;
            padding-right: 8px;
            gap: 0;
        }

        .sidebar.collapsed .sidebar-link span,
        .sidebar.collapsed .sidebar-group-toggle > span > span,
        .sidebar.collapsed .sidebar-group-toggle .bi-chevron-down,
        .sidebar.collapsed .sidebar-footer .overflow-hidden,
        .sidebar.collapsed .sidebar-footer small {
            display: none !important;
        }

        .sidebar.collapsed .sidebar-submenu {
            display: none !important;
        }

        .sidebar.collapsed .sidebar-footer {
            padding: 10px 6px;
            display: flex;
            justify-content: center;
        }

        .sidebar-toggle {
            width: 40px;
            height: 40px;
            border: 1px solid rgba(219, 223, 233, 0.7);
            border-radius: 999px;
            background: #fff;
            color: var(--app-text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            opacity: 0.55;
            transition: background 0.2s ease, transform 0.2s ease, border-color 0.2s ease, opacity 0.2s ease;
        }

        .sidebar-toggle:hover {
            background: #f8fafc;
            border-color: var(--app-blue);
            color: var(--app-blue);
            opacity: 1;
        }

        .sidebar-toggle i {
            transition: transform 0.3s ease;
        }

        .app-shell.collapsed .sidebar-toggle i {
            transform: scaleX(-1);
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(7, 20, 55, 0.45);
            backdrop-filter: blur(2px);
            z-index: 19;
        }

        .sidebar-backdrop.show {
            display: block;
        }

        /* .sidebar .brand {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 18px;
        } */

            .sidebar .brand {
            display: flex;
            flex-direction: column; /* Mengubah arah elemen menjadi atas-bawah */
            align-items: center; /* Menempatkan logo dan teks tepat di tengah */
            justify-content: center;
            text-align: center;
            padding-bottom: 1px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 18px;
        }

        /* .brand-logo {
            max-height: 50px;
            width: auto;
            max-width: 100%;
            display: block;
            filter: none;
        } */
            .brand-logo {
            max-height: 80px; /* Memperbesar ukuran logo (sebelumnya 50px) */
            width: auto;
            max-width: 100%;
            display: block;
            filter: none;
        }

        .sidebar-nav {
            flex: 1;
            padding-left: 0;
            padding-right: 0;
            padding-bottom: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-footer {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 18px;
            margin: 18px 0 0;
            padding: 14px 12px;
        }

        .sidebar-link,
        .sidebar-footer button,
        .sidebar-group-toggle {
            width: 100%;
            min-height: 40px;
            border: 0;
            background: transparent;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            position: relative;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-group-toggle {
            justify-content: space-between;
            cursor: pointer;
            background: transparent;
        }

        .sidebar-footer button {
            border-radius: 8px;
            text-align: left;
            min-height: 40px;
        }

        .sidebar-link:hover,
        .sidebar-footer button:hover,
        .sidebar-group-toggle:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }

        .sidebar-link:hover i,
        .sidebar-group-toggle:hover i {
            color: var(--app-blue);
        }

        /* Top level direct active link */
        .sidebar-link.active {
            background: rgba(27, 132, 255, 0.13);
            color: #ffffff;
            font-weight: 600;
            border-left: 4px solid var(--app-blue);
        }

        /* Group toggle header active (expanded state) */
        .sidebar-group-toggle.active {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-weight: 600;
            border-left: 4px solid transparent;
        }

        .sidebar-group {
            margin-top: 4px;
        }

        .sidebar-submenu {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height 0.28s ease, opacity 0.28s ease;
            padding-left: 0;
            margin-top: 2px;
        }

        .sidebar-submenu.open {
            max-height: 420px;
            opacity: 1;
        }

        .sidebar-submenu .sidebar-link {
            padding: 8px 16px 8px 40px;
            font-size: 12.8px;
            background: transparent;
            border-radius: 8px;
            margin-bottom: 2px;
            color: #94a3b8;
            border-left: 4px solid transparent;
        }

        .sidebar-submenu .sidebar-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }

        .sidebar-submenu .sidebar-link.active {
            background: rgba(27, 132, 255, 0.15);
            color: #ffffff;
            font-weight: 600;
            border-left: 4px solid var(--app-blue);
        }

        .sidebar-group-toggle .bi-chevron-down {
            transition: transform 0.2s ease;
            color: #94a3b8;
        }

        .sidebar-group-toggle.active .bi-chevron-down {
            transform: rotate(180deg);
            color: var(--app-blue);
        }

        .sidebar-link i,
        .sidebar-group-toggle i,
        .sidebar-footer i {
            width: 18px;
            font-size: 15px;
            text-align: center;
            color: #94a3b8;
        }

        .sidebar-link.active i,
        .sidebar-group-toggle.active > span > i {
            color: var(--app-blue);
        }

        .main-content {
            min-height: 100vh;
            background: var(--app-bg);
            border-top-left-radius: 24px;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 15;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 18px 32px;
            background: rgba(248, 249, 250, 0.86);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.7);
        }

        .search-pill {
            position: relative;
            width: min(340px, 100%);
            height: 44px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border-radius: 999px;
            padding: 0 16px;
            color: var(--app-muted);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(219, 223, 233, 0.6);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .search-pill:focus-within {
            border-color: var(--app-blue);
            box-shadow: 0 0 0 3px rgba(27, 132, 255, 0.15);
        }

        .search-pill input {
            border: 0;
            outline: 0;
            width: 100%;
            color: var(--app-text);
            background: transparent;
            font-size: 13px;
        }

        .search-pill .search-clear-btn {
            border: 0;
            background: transparent;
            padding: 0;
            color: var(--app-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .search-pill .search-clear-btn:hover {
            color: var(--app-text);
        }

        .search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: max(100%, 380px);
            max-height: 420px;
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid rgba(219, 223, 233, 0.9);
            border-radius: 16px;
            box-shadow: 0 16px 36px rgba(7, 20, 55, 0.12);
            z-index: 1050;
            padding: 8px 0;
            display: none;
            scrollbar-width: thin;
        }

        .search-dropdown-section {
            padding-bottom: 4px;
        }

        .search-dropdown-header {
            padding: 8px 16px 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--app-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
        }

        .search-dropdown-section:first-child .search-dropdown-header {
            border-top: 0;
        }

        .search-dropdown-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 16px;
            color: var(--app-text);
            text-decoration: none;
            transition: background 0.15s ease;
            cursor: pointer;
        }

        .search-dropdown-item:hover,
        .search-dropdown-item.active {
            background: #f1f5f9;
            color: var(--app-blue);
        }

        .search-dropdown-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .search-dropdown-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .search-dropdown-icon.customer-icon {
            background: rgba(27, 132, 255, 0.1);
            color: var(--app-blue);
        }

        .search-dropdown-icon.product-icon {
            background: rgba(246, 192, 0, 0.12);
            color: #d97706;
        }

        .search-dropdown-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .search-dropdown-title {
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }

        .search-dropdown-subtitle {
            font-size: 11.5px;
            color: var(--app-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
        }

        .search-dropdown-empty {
            padding: 24px 16px;
            text-align: center;
            color: var(--app-muted);
            font-size: 13px;
        }

        .search-dropdown-empty i {
            font-size: 24px;
            display: block;
            margin-bottom: 6px;
            opacity: 0.6;
        }

        .search-dropdown-footer {
            padding: 8px 16px;
            font-size: 11.5px;
            text-align: center;
            color: var(--app-muted);
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-date {
            color: var(--app-muted);
            font-size: 12px;
            line-height: 1.2;
            text-align: right;
            min-width: 120px;
        }

        .topbar-date strong {
            display: block;
            color: var(--app-text);
            font-size: 13px;
            font-weight: 700;
            margin-top: 3px;
        }

        .topbar .topbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            color: var(--app-text);
        }

        .topbar .topbar-brand span {
            font-size: 14px;
            color: var(--app-muted);
        }

        .footer-panel {
            margin-top: 24px;
            background: #fff;
            border-radius: 24px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border: 1px solid #eef0f3;
            box-shadow: var(--app-shadow);
        }

        .footer-panel p {
            margin: 0;
            color: var(--app-muted);
            font-size: 13px;
        }

        .footer-links {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            align-items: center;
        }

        .footer-links a {
            color: var(--app-blue);
            text-decoration: none;
            font-size: 13px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .icon-button {
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #4b5563;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .icon-button .notification-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #b91c1c;
            border: 2px solid var(--app-bg);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            background: #323536;
            border: 2px solid #fff;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .content-canvas {
            padding: 24px 32px 56px;
            max-width: 1600px;
        }

        .page-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .page-heading h4,
        .page-heading h5 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            line-height: 28px;
        }

        .page-heading small {
            display: block;
            margin-top: 4px;
            font-size: 13px;
        }

        .page-heading small,
        .topbar small,
        .text-muted {
            color: var(--app-muted) !important;
        }

        .card {
            border: 0;
            border-radius: 8.125px;
            box-shadow: var(--app-shadow);
            background: #fff;
        }

        .card .card-body {
            padding: 24px;
        }

        .btn {
            border-radius: 6.175px;
            font-weight: 500;
            font-size: 13px;
        }

        .btn-primary {
            background: var(--app-blue);
            border-color: var(--app-blue);
            box-shadow: var(--app-shadow);
        }

        .btn-dark {
            background: var(--app-navy);
            border-color: var(--app-navy);
        }

        .form-control,
        .form-select {
            border-color: #e5e7eb;
            border-radius: 6.175px;
            min-height: 42px;
            font-size: 13px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--app-blue);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.12);
        }

        .table {
            margin-bottom: 0;
            color: var(--app-text);
        }

        .table th {
            white-space: nowrap;
            border-bottom: 0;
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            background: #f9fafb;
            padding: 13px 18px;
        }

        .table td {
            border-color: #f3f4f6;
            padding: 15px 18px;
            vertical-align: middle;
        }

        .table-bordered>:not(caption)>*>* {
            border-width: 0 0 1px;
        }

        .table-hover tbody tr:hover {
            background-color: #fbfcfd;
        }

        .table-dark {
            --bs-table-bg: #fbfcfd;
            --bs-table-color: #6b7280;
            --bs-table-border-color: #f3f4f6;
        }

        .badge {
            border-radius: 999px;
            padding: 6px 10px;
            font-weight: 700;
            font-size: 11px;
        }

        .bg-success {
            background-color: #d1fae5 !important;
            color: #047857 !important;
        }

        .bg-secondary {
            background-color: #f3f4f6 !important;
            color: #6b7280 !important;
        }

        .bg-warning {
            background-color: #fef3c7 !important;
            color: #b45309 !important;
        }

        .bg-danger {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
        }

        .alert {
            border: 0;
            border-radius: 18px;
            box-shadow: var(--app-shadow);
        }

        .pagination {
            gap: 4px;
        }

        .page-link {
            border-radius: 8px;
            border-color: #e5e7eb;
            color: #6b7280;
        }

        .active>.page-link,
        .page-link.active {
            background: #fff;
            color: var(--app-text);
            border-color: #e5e7eb;
        }

        .metric-card {
            min-height: 108px;
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 22px;
            overflow: hidden;
            position: relative;
            background: #fff;
            border: 1px solid rgba(229, 231, 235, 0.95);
        }

        .metric-card>div:last-child {
            flex: 1 1 auto;
            min-width: 0;
        }

        .metric-card::after {
            content: "";
            position: absolute;
            right: -24px;
            top: -24px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: var(--metric-soft, #eff6ff);
            opacity: 0.5;
        }

        .metric-icon {
            width: 56px;
            height: 56px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--metric-color, var(--app-blue));
            background: var(--metric-bg, #eff6ff);
            font-size: 24px;
            flex: 0 0 auto;
        }

        .metric-label {
            color: var(--app-muted);
            font-weight: 500;
            margin-bottom: 2px;
            font-size: 13px;
        }

        .metric-value {
            font-size: 30px;
            line-height: 36px;
            color: var(--app-text);
            font-weight: 500;
        }

        .metric-trend {
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .chart-card {
            padding: 28px;
        }

        .soft-panel {
            background: #fff;
            border-radius: 22px;
            box-shadow: var(--app-shadow);
            overflow: hidden;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 1px solid #eef0f3;
            border-radius: 16px;
            color: var(--app-text);
            text-decoration: none;
            background: #fff;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .quick-action:hover {
            color: var(--app-text);
            border-color: rgba(37, 99, 235, 0.22);
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.08);
            transform: translateY(-1px);
        }

        .quick-action-icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            color: var(--metric-color, var(--app-blue));
            background: var(--metric-bg, #eff6ff);
            flex: 0 0 auto;
        }

        .donut {
            --percent: 75;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: conic-gradient(var(--app-blue) calc(var(--percent) * 1%), #e5e7eb 0);
            display: grid;
            place-items: center;
            margin: 8px auto 24px;
        }

        .donut::before {
            content: "";
            width: 124px;
            height: 124px;
            border-radius: 50%;
            background: #fff;
            position: absolute;
        }

        .donut-inner {
            position: relative;
            text-align: center;
            z-index: 1;
        }

        .bar-chart {
            height: 192px;
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 14px;
            padding: 0 8px;
        }

        .bar-day {
            flex: 1;
            min-width: 38px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }

        .bar-pair {
            flex: 1;
            width: 100%;
            display: flex;
            align-items: end;
            justify-content: center;
            gap: 4px;
        }

        .bar {
            width: min(30px, 45%);
            border-radius: 8px 8px 0 0;
        }

        .bar.inbound {
            background: var(--app-blue);
        }

        .bar.outbound {
            background: var(--app-orange);
        }

        .bar-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--app-muted);
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            display: inline-block;
        }

        @media (max-width: 992px) {
            .app-shell {
                padding-left: 0 !important;
            }

            .app-shell.collapsed {
                padding-left: 0 !important;
            }

            .sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                width: 280px;
                min-height: 100vh;
                transform: translateX(0);
                padding-bottom: 20px;
            }

            .sidebar.collapsed {
                transform: translateX(-100%);
                width: 280px;
            }

            .app-shell:not(.collapsed) .sidebar-backdrop {
                display: block;
            }

            .sidebar .brand {
                height: auto;
            }

            .sidebar-nav {
                padding: 0 16px;
            }

            .sidebar-link,
            .sidebar-link.active {
                border-radius: 999px;
            }

            .sidebar-link.active::before,
            .sidebar-link.active::after {
                display: none;
            }

            .main-content {
                border-radius: 32px 32px 0 0;
            }

            .topbar,
            .content-canvas {
                padding-left: 18px;
                padding-right: 18px;
            }

            .topbar {
                position: static;
                flex-wrap: wrap;
            }

            .topbar-date {
                display: none;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .search-pill {
                width: 100%;
            }
        }
    </style>
    <link href="{{ asset('css/metronic.css') }}" rel="stylesheet">
</head>

<body>
    <div class="app-shell" id="appShell">
        <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
        <aside class="sidebar" id="appSidebar" aria-label="Sidebar navigasi">
            <div class="brand">
                <img src="{{ asset('images/Tadco-TP2.png') }}" alt="TADCO ERP" class="brand-logo">
                <!-- <span style="color:#94a3b8; font-size:13px; font-weight:500; white-space:nowrap;">Admin Dashboard</span> -->
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboard.index') }}"
                    class="sidebar-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>

                @role('admin|sales')
                    <div class="sidebar-group">
                        <button type="button"
                            class="sidebar-group-toggle {{ request()->routeIs('inventory.*') || request()->routeIs('delivery_orders.*') || request()->routeIs('shipments.*') ? 'active' : '' }}"
                            data-group="inventory-group">
                            <span>
                                <i class="bi bi-boxes"></i>
                                <span>Inventory</span>
                            </span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="sidebar-submenu {{ request()->routeIs('inventory.*') || request()->routeIs('delivery_orders.*') || request()->routeIs('shipments.*') ? 'open' : '' }}"
                            id="inventory-group">
                            <a href="{{ route('inventory.index') }}"
                                class="sidebar-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                                <i class="bi bi-stack"></i>
                                <span>Inventory</span>
                            </a>
                            <a href="{{ route('delivery_orders.index') }}"
                                class="sidebar-link {{ request()->routeIs('delivery_orders.*') ? 'active' : '' }}">
                                <i class="bi bi-receipt"></i>
                                <span>Delivery Orders</span>
                            </a>
                            <a href="{{ route('shipments.index') }}"
                                class="sidebar-link {{ request()->routeIs('shipments.*') ? 'active' : '' }}">
                                <i class="bi bi-truck"></i>
                                <span>Shipments</span>
                            </a>
                        </div>
                    </div>

                    <div class="sidebar-group">
                        <button type="button"
                            class="sidebar-group-toggle {{ request()->routeIs('customers.*') || request()->routeIs('products.*') ? 'active' : '' }}"
                            data-group="master-group">
                            <span>
                                <i class="bi bi-folder-fill"></i>
                                <span>Master Data</span>
                            </span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="sidebar-submenu {{ request()->routeIs('customers.*') || request()->routeIs('products.*') ? 'open' : '' }}"
                            id="master-group">
                            <a href="{{ route('customers.index') }}"
                                class="sidebar-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                <i class="bi bi-people-fill"></i>
                                <span>Customers</span>
                            </a>
                            <a href="{{ route('products.index') }}"
                                class="sidebar-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                                <i class="bi bi-box-seam-fill"></i>
                                <span>Products</span>
                            </a>
                        </div>
                    </div>

                    <div class="sidebar-group">
                        <button type="button"
                            class="sidebar-group-toggle {{ request()->routeIs('invoices.*') || request()->routeIs('payments.*') ? 'active' : '' }}"
                            data-group="finance-group">
                            <span>
                                <i class="bi bi-currency-dollar"></i>
                                <span>Finance</span>
                            </span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="sidebar-submenu {{ request()->routeIs('invoices.*') || request()->routeIs('payments.*') ? 'open' : '' }}"
                            id="finance-group">
                            <a href="{{ route('invoices.index') }}"
                                class="sidebar-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                                <i class="bi bi-cash-stack"></i>
                                <span>Invoices</span>
                            </a>
                            <a href="{{ route('payments.index') }}"
                                class="sidebar-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                                <i class="bi bi-credit-card"></i>
                                <span>Payments</span>
                            </a>
                        </div>
                    </div>

                    <div class="sidebar-group">
                        <button type="button"
                            class="sidebar-group-toggle {{ request()->routeIs('users.*') || request()->routeIs('imports.*') || request()->routeIs('reports.*') ? 'active' : '' }}"
                            data-group="settings-group">
                            <span>
                                <i class="bi bi-gear-fill"></i>
                                <span>Settings</span>
                            </span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="sidebar-submenu {{ request()->routeIs('users.*') || request()->routeIs('imports.*') || request()->routeIs('reports.*') ? 'open' : '' }}"
                            id="settings-group">
                            @role('admin')
                                <a href="{{ route('users.index') }}"
                                    class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <i class="bi bi-person-gear"></i>
                                    <span>User Management</span>
                                </a>
                            @endrole
                            <a href="{{ route('imports.index') }}"
                                class="sidebar-link {{ request()->routeIs('imports.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-arrow-up"></i>
                                <span>Import Excel</span>
                            </a>
                            <a href="{{ route('reports.index') }}"
                                class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                                <i class="bi bi-bar-chart-fill"></i>
                                <span>Reports</span>
                            </a>
                        </div>
                    </div>
                @endrole
            </nav>

            <div class="sidebar-footer">
                @auth
                <div class="d-flex align-items-center gap-3">
                    <div class="user-avatar" style="width: 36px; height: 36px; font-size: 13px;">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <small class="d-block text-muted mb-0" style="font-size: 11px;">Selamat datang,</small>
                        <strong class="d-block text-white text-truncate" style="font-size: 13px;">{{ Auth::user()->name }}</strong>
                    </div>
                </div>
                @endauth
            </div>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width:0;">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle"
                        aria-label="Tutup sidebar" aria-expanded="true" aria-controls="appSidebar"
                        title="Toggle sidebar (Ctrl+B)">
                        <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                    </button>
                    <div class="search-pill flex-grow-1" id="globalSearchPill" style="max-width: 420px;">
                    <i class="bi bi-search text-muted" id="globalSearchIcon"></i>
                    <div class="spinner-border spinner-border-sm text-primary d-none" id="globalSearchSpinner" role="status" style="width: 14px; height: 14px;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <input type="text" id="globalSearchInput" placeholder="Cari Customer atau Produk..." autocomplete="off">
                    <button type="button" class="search-clear-btn d-none" id="globalSearchClear" title="Clear">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>

                    <div class="search-dropdown" id="globalSearchDropdown"></div>
                    </div>
                </div>

                <div class="topbar-actions">
                    <div class="topbar-date">
                        Hari ini
                        <strong>{{ now()->format('d M Y') }}</strong>
                    </div>

                    <div class="dropdown d-inline-block">
                        <button class="icon-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                            @auth
                                @if(auth()->user()->unreadNotifications->count() > 0)
                                    <span class="notification-dot"></span>
                                @endif
                            @endauth
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4 mt-2" style="width: 320px;">
                            <li><h6 class="dropdown-header fw-bold text-dark fs-6 py-2">Notifikasi</h6></li>
                            @auth
                                @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                                    <li>
                                        <a class="dropdown-item py-2" href="#">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="bi bi-exclamation-circle-fill text-warning mt-1"></i>
                                                <div>
                                                    <small class="d-block text-dark fw-medium text-wrap">{{ $notification->data['message'] ?? 'Pemberitahuan baru' }}</small>
                                                    <small class="text-muted" style="font-size: 10px;">{{ $notification->created_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                @empty
                                    <li><span class="dropdown-item text-muted small text-center py-3">Belum ada notifikasi baru.</span></li>
                                @endforelse
                            @endauth
                        </ul>
                    </div>

                    <div class="dropdown d-inline-block ms-1">
                        @auth
                        <div class="user-avatar" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ Auth::user()->name }}">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4 mt-2 pb-2">
                            <li class="px-3 py-2 text-center border-bottom mb-2">
                                <strong class="d-block text-dark">{{ Auth::user()->name }}</strong>
                                <small class="text-muted">{{ Auth::user()->email }}</small>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="mb-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger fw-semibold d-flex align-items-center gap-2">
                                        <i class="bi bi-box-arrow-right"></i> Keluar Sistem
                                    </button>
                                </form>
                            </li>
                        </ul>
                        @endauth
                    </div>
                </div>
            </div>

            <div class="content-canvas">
                <div class="page-heading">
                    <div>
                        <h4>@yield('page_title', 'ERP TADCO')</h4>
                        <small>@yield('page_subtitle', 'Sistem informasi distribusi PT TADCO')</small>
                    </div>

                    @yield('page_action')
                </div>

                @yield('content')

                <div class="footer-panel">
                    <p>ERP TADCO © {{ date('Y') }}. Kelola inventaris, transaksi, dan laporan dengan mudah.</p>
                    <div class="footer-links">
                        <a href="{{ route('dashboard.index') }}">Dashboard</a>
                        <a href="{{ route('reports.index') }}">Reports</a>
                        <a href="{{ route('imports.index') }}">Import Excel</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle: expand/collapse — ponytail: boolean + localStorage, no lib
        (() => {
            const shell = document.getElementById('appShell');
            const sidebar = document.getElementById('appSidebar');
            const btn = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!shell || !sidebar || !btn) return;

            const mq = window.matchMedia('(max-width: 992px)');
            const isMobile = () => mq.matches;

            const apply = (collapsed, persist = true) => {
                shell.classList.toggle('collapsed', collapsed);
                sidebar.classList.toggle('collapsed', collapsed);
                btn.setAttribute('aria-expanded', String(!collapsed));
                btn.setAttribute('aria-label', collapsed ? 'Buka sidebar' : 'Tutup sidebar');
                btn.title = collapsed ? 'Buka sidebar (Ctrl+B)' : 'Tutup sidebar (Ctrl+B)';
                if (backdrop) backdrop.classList.toggle('show', !collapsed && isMobile());
                if (persist) try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch(e) {}
                // Tooltip ringan saat collapsed desktop: tampilkan title pada link
                document.querySelectorAll('.sidebar-link, .sidebar-group-toggle').forEach(el => {
                    if (collapsed && !isMobile()) {
                        const txt = el.querySelector('span span') || el.querySelector('span');
                        if (txt && !el.getAttribute('title')) el.setAttribute('title', txt.textContent.trim());
                    } else if (!collapsed) {
                        // keep original title if any
                    }
                });
            };

            let initial = false;
            try { initial = localStorage.getItem('sidebarCollapsed') === '1'; } catch(e) {}
            // Mobile default: hidden (collapsed=true means drawer closed)
            if (isMobile()) initial = true;
            apply(initial, false);

            const toggleSidebar = () => {
                const nowCollapsed = shell.classList.contains('collapsed');
                // Auto-expand saat klik submenu dalam keadaan collapsed desktop
                apply(!nowCollapsed);
            };

            btn.addEventListener('click', toggleSidebar);
            if (backdrop) backdrop.addEventListener('click', () => apply(true));

            // Ctrl+B shortcut + Escape untuk tutup di mobile
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
                    e.preventDefault();
                    toggleSidebar();
                }
                if (e.key === 'Escape' && isMobile() && !shell.classList.contains('collapsed')) {
                    apply(true);
                }
            });

            // Saat resize crossing breakpoint, sesuaikan
            mq.addEventListener('change', () => {
                if (isMobile()) apply(true, false);
            });

            // Expose for debugging
            window.toggleSidebar = toggleSidebar;
        })();

        document.querySelectorAll('.sidebar-group-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                // Jika sidebar collapsed di desktop, expand dulu
                const shell = document.getElementById('appShell');
                if (shell && shell.classList.contains('collapsed') && !window.matchMedia('(max-width: 992px)').matches) {
                    shell.classList.remove('collapsed');
                    document.getElementById('appSidebar')?.classList.remove('collapsed');
                    document.getElementById('sidebarToggle')?.setAttribute('aria-expanded', 'true');
                    try { localStorage.setItem('sidebarCollapsed', '0'); } catch(e) {}
                }
                // 1. Tutup semua submenu yang sedang terbuka (kecuali yang sedang di-klik)
                document.querySelectorAll('.sidebar-group-toggle').forEach(otherToggle => {
                    if (otherToggle !== toggle) {
                        otherToggle.classList.remove('active');
                        const otherSubmenu = document.getElementById(otherToggle.dataset.group);
                        if (otherSubmenu) otherSubmenu.classList.remove('open');
                    }
                });

                // 2. Buka/Tutup submenu yang di-klik
                const groupId = toggle.dataset.group;
                const submenu = document.getElementById(groupId);
                const isOpen = submenu.classList.toggle('open');
                toggle.classList.toggle('active', isOpen);
            });
        });

        // Global Search Pill Live Autocomplete
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('globalSearchInput');
            const searchDropdown = document.getElementById('globalSearchDropdown');
            const searchSpinner = document.getElementById('globalSearchSpinner');
            const searchIcon = document.getElementById('globalSearchIcon');
            const searchClear = document.getElementById('globalSearchClear');
            const searchPill = document.getElementById('globalSearchPill');

            if (!searchInput || !searchDropdown) return;

            let debounceTimer = null;
            let selectedIndex = -1;

            const escapeHtml = (str) => {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const performSearch = (query) => {
                if (query.trim().length < 2) {
                    searchDropdown.style.display = 'none';
                    searchDropdown.innerHTML = '';
                    searchSpinner.classList.add('d-none');
                    searchIcon.classList.remove('d-none');
                    return;
                }

                searchIcon.classList.add('d-none');
                searchSpinner.classList.remove('d-none');

                fetch(`{{ route('search.global') }}?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        searchSpinner.classList.add('d-none');
                        searchIcon.classList.remove('d-none');
                        renderDropdown(data);
                    })
                    .catch(err => {
                        console.error('Global search error:', err);
                        searchSpinner.classList.add('d-none');
                        searchIcon.classList.remove('d-none');
                    });
            };

            const renderDropdown = (data) => {
                selectedIndex = -1;
                if (!data || data.total === 0) {
                    searchDropdown.innerHTML = `
                        <div class="search-dropdown-empty">
                            <i class="bi bi-search-heart"></i>
                            Tidak ada customer atau produk yang cocok dengan "<strong>${escapeHtml(data.query)}</strong>".
                        </div>`;
                    searchDropdown.style.display = 'block';
                    return;
                }

                let html = '';

                if (data.customers && data.customers.length > 0) {
                    html += `
                        <div class="search-dropdown-section">
                            <div class="search-dropdown-header">
                                <i class="bi bi-people-fill"></i> Customer (${data.customers.length})
                            </div>`;
                    data.customers.forEach(c => {
                        html += `
                            <a href="${c.url}" class="search-dropdown-item search-item">
                                <div class="search-dropdown-item-left">
                                    <div class="search-dropdown-icon customer-icon">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <div class="search-dropdown-text">
                                        <div class="search-dropdown-title">${escapeHtml(c.name)} <span class="badge bg-secondary ms-1">${escapeHtml(c.code)}</span></div>
                                        <div class="search-dropdown-subtitle">${escapeHtml(c.subtitle)}</div>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
                            </a>`;
                    });
                    html += `</div>`;
                }

                if (data.products && data.products.length > 0) {
                    html += `
                        <div class="search-dropdown-section">
                            <div class="search-dropdown-header">
                                <i class="bi bi-box-seam-fill"></i> Produk (${data.products.length})
                            </div>`;
                    data.products.forEach(p => {
                        html += `
                            <a href="${p.url}" class="search-dropdown-item search-item">
                                <div class="search-dropdown-item-left">
                                    <div class="search-dropdown-icon product-icon">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="search-dropdown-text">
                                        <div class="search-dropdown-title">${escapeHtml(p.name)} <span class="badge bg-secondary ms-1">${escapeHtml(p.code)}</span></div>
                                        <div class="search-dropdown-subtitle">${escapeHtml(p.subtitle)}</div>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
                            </a>`;
                    });
                    html += `</div>`;
                }

                html += `<div class="search-dropdown-footer">Ditemukan ${data.total} hasil pencarian</div>`;

                searchDropdown.innerHTML = html;
                searchDropdown.style.display = 'block';
            };

            searchInput.addEventListener('input', (e) => {
                const val = e.target.value;
                if (val.trim().length > 0) {
                    searchClear.classList.remove('d-none');
                } else {
                    searchClear.classList.add('d-none');
                    searchDropdown.style.display = 'none';
                }

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    performSearch(val);
                }, 300);
            });

            searchInput.addEventListener('focus', () => {
                if (searchInput.value.trim().length >= 2 && searchDropdown.children.length > 0) {
                    searchDropdown.style.display = 'block';
                }
            });

            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchClear.classList.add('d-none');
                searchDropdown.style.display = 'none';
                searchDropdown.innerHTML = '';
                searchInput.focus();
            });

            // Keyboard navigation (Arrow keys, Esc, Enter)
            searchInput.addEventListener('keydown', (e) => {
                const items = searchDropdown.querySelectorAll('.search-item');
                if (!items || items.length === 0 || searchDropdown.style.display === 'none') {
                    if (e.key === 'Escape') {
                        searchDropdown.style.display = 'none';
                    }
                    return;
                }

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = (selectedIndex + 1) % items.length;
                    updateActiveItem(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                    updateActiveItem(items);
                } else if (e.key === 'Enter') {
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        e.preventDefault();
                        items[selectedIndex].click();
                    }
                } else if (e.key === 'Escape') {
                    searchDropdown.style.display = 'none';
                }
            });

            const updateActiveItem = (items) => {
                items.forEach((item, index) => {
                    if (index === selectedIndex) {
                        item.classList.add('active');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('active');
                    }
                });
            };

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (searchPill && !searchPill.contains(e.target)) {
                    searchDropdown.style.display = 'none';
                }
            });
        });
    </script>

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mencegah double submit pada semua form di aplikasi
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    // Jika form memiliki class 'no-block' (untuk kasus khusus), biarkan saja
                    if (this.classList.contains('no-block')) return;

                    // Cari tombol submit di dalam form ini
                    const submitBtn = this.querySelector('button[type="submit"], input[type="submit"]');
                    
                    if (submitBtn) {
                        // Cek apakah form valid secara HTML5
                        if (this.checkValidity()) {
                            // Tambahkan animasi loading dan disable tombolnya
                            const originalText = submitBtn.innerHTML;
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memproses...';
                            
                            // Kembalikan tombol jika ternyata halaman tidak berpindah setelah 10 detik (misal error di background)
                            setTimeout(() => {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }, 10000);
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>
