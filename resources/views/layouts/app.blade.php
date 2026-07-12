<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'ERP TADCO')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #111827;
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 16px;
        }

        .sidebar .brand {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .sidebar .brand-subtitle {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 24px;
        }

        .sidebar .menu-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #9ca3af;
            margin: 18px 0 8px;
        }

        .sidebar a {
            color: #d1d5db;
            text-decoration: none;
            display: block;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .sidebar a:hover {
            background: #1f2937;
            color: #fff;
        }

        .sidebar a.active {
            background: #2563eb;
            color: #fff;
        }

        .main-content {
            margin-left: 260px;
            padding: 24px;
        }

        .topbar {
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .card {
            border-radius: 12px;
        }

        .table th {
            white-space: nowrap;
        }

        .auth-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        @media (max-width: 992px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="brand">ERP TADCO</div>
        <div class="brand-subtitle">Distribution Management System</div>

        <div class="menu-label">Main</div>

        <a href="{{ route('dashboard.index') }}" class="{{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
            Dashboard
        </a>

        @role('admin|sales')
            <div class="menu-label">Master Data</div>

            <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">
                Customer
            </a>

            <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
                Product
            </a>

            @role('admin')
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                    User Management
                </a>
            @endrole

            <a href="{{ route('imports.index') }}" class="{{ request()->routeIs('imports.*') ? 'active' : '' }}">
                Import Excel
            </a>

            <div class="menu-label">Operation</div>

            <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                Inventory
            </a>

            <a href="{{ route('delivery_orders.index') }}"
                class="{{ request()->routeIs('delivery_orders.*') ? 'active' : '' }}">
                Delivery Order
            </a>

            <a href="{{ route('shipments.index') }}" class="{{ request()->routeIs('shipments.*') ? 'active' : '' }}">
                Shipment
            </a>

            <div class="menu-label">Finance</div>

            <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                Invoice
            </a>

            <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">
                Payment
            </a>

            <div class="menu-label">Report</div>

            <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                Reports
            </a>
        @endrole
    </aside>

    <main class="main-content">
            <div class="topbar d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">@yield('page_title', 'ERP TADCO')</h4>
                    <small class="text-muted">@yield('page_subtitle', 'Sistem informasi distribusi PT TADCO')</small>
                </div>

                <div class="text-end d-flex align-items-center gap-3">
                    <div>
                        <small class="text-muted">Tanggal</small>
                        <div>{{ now()->format('d/m/Y') }}</div>
                    </div>

                    <div class="auth-actions">
                        @auth
                            <span class="text-muted small">
                                {{ Auth::user()->name }}
                            </span>

                            <form method="POST" action="{{ route('logout') }}" class="mb-0">
                                @csrf

                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    Logout
                                </button>
                            </form>
                        @endauth
                    </div>
                </div>
            </div>

            @yield('content')
        </main>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        @stack('scripts')
    </body>

    </html>
