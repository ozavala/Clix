<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            Dashboard
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard') }}">Home</a>
                </li>
                @can('view-admin-section')
                <li class="nav-item dropdown">
                    <a id="navbarDropdownAdmin" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Admin
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownAdmin">
                        @can('edit-settings')
                        <a class="dropdown-item" href="{{ route('settings.edit') }}">
                            Settings
                        </a>
                        <a class="dropdown-item" href="{{ route('tenants.select') }}">
                            Tenants
                        </a>
                        @endcan
                        <a class="dropdown-item" href="{{ route('tax-settings.index') }}">
                            Tax Settings
                        </a>
                        <a class="dropdown-item" href="{{ route('crm-users.index') }}">
                            CRM Users
                        </a>
                        @can('view-roles')
                        <a class="dropdown-item" href="{{ route('user-roles.index') }}">
                            User Roles
                        </a>
                        @endcan
                        @can('view-permissions')
                        <a class="dropdown-item" href="{{ route('permissions.index') }}">
                            Permissions
                        </a>
                        @endcan
                        <a class="dropdown-item" href="{{ route('addresses.index') }}">
                            Addresses
                        </a>
                    </div>
                </li>
                @endcan
                <li class="nav-item dropdown">
                    <a id="navbarDropdownSales" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Sales
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownSales">
                        <a class="dropdown-item" href="{{ route('customers.index') }}">
                            Customers
                        </a>
                        <a class="dropdown-item" href="{{ route('contacts.index') }}">
                            Contacts
                        </a>
                        <a class="dropdown-item" href="{{ route('leads.index') }}">
                            Leads
                        </a>
                        <a class="dropdown-item" href="{{ route('opportunities.index') }}">
                            Opportunities
                        </a>
                        <a class="dropdown-item" href="{{ route('quotations.index') }}">
                            Quotations
                        </a>
                        <a class="dropdown-item" href="{{ route('orders.index') }}">
                            Sales Orders
                        </a>
                        <a class="dropdown-item" href="{{ route('invoices.index') }}">
                            Invoices
                        </a>
                        <a class="dropdown-item" href="{{ route('marketing-campaigns.index') }}">
                            Marketing Campaigns
                        </a>
                        <a class="dropdown-item" href="{{ route('email-templates.index') }}">
                            Email Templates
                        </a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a id="navbarDropdownPurchasing" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Purchasing
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownPurchasing">
                        <a class="dropdown-item" href="{{ route('suppliers.index') }}">
                            Suppliers
                        </a>
                        <a class="dropdown-item" href="{{ route('purchase-orders.index') }}">
                            Purchase Orders
                        </a>
                        <a class="dropdown-item" href="{{ route('bills.index') }}">
                            Bills
                        </a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a id="navbarDropdownInventory" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Inventory
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownInventory">
                        <a class="dropdown-item" href="{{ route('products.index') }}">
                            Products/Services
                        </a>
                        <a class="dropdown-item" href="{{ route('product-categories.index') }}">
                            Product Categories
                        </a>
                        <a class="dropdown-item" href="{{ route('product-features.index') }}">
                            Product Features
                        </a>
                        <a class="dropdown-item" href="{{ route('warehouses.index') }}">
                            Warehouses
                        </a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a id="navbarDropdownAccounting" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Accounting
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownAccounting">
                        <a class="dropdown-item" href="{{ route('payments.index') }}">
                            Payments
                        </a>
                        <a class="dropdown-item" href="{{ route('journal-entries.index') }}">
                            Journal Entries
                        </a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a id="navbarDropdownReports" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Reports
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownReports">
                        <a class="dropdown-item" href="{{ route('reports.sales') }}">
                            Sales by Period
                        </a>
                        <a class="dropdown-item" href="{{ route('reports.sales-by-product') }}">
                            Sales by Product
                        </a>
                        <a class="dropdown-item" href="{{ route('reports.sales-by-customer') }}">
                            Sales by Customer
                        </a>
                        <a class="dropdown-item" href="{{ route('reports.sales-by-employee') }}">
                            Sales by Employee
                        </a>
                        <a class="dropdown-item" href="{{ route('reports.sales-by-category') }}">
                            Sales by Category
                        </a>
                        <a class="dropdown-item" href="{{ route('reports.profit_and_loss') }}">
                            Profit and Loss
                        </a>
                        <a class="dropdown-item" href="{{ route('reports.tax-balance') }}">
                            Tax Balance Report
                        </a>
                    </div>
                </li>
            </ul>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- Tenant Switcher -->
                @auth
                    @if(isset($hasMultipleTenants) && $hasMultipleTenants)
                    <li class="nav-item dropdown">
                        <a id="navbarDropdownTenant" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            @if($currentTenant)
                                <span class="badge bg-primary">{{ $currentTenant->name }}</span>
                            @else
                                {{ __('Select Tenant') }}
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownTenant">
                            @foreach($userTenants as $tenant)
                                <a class="dropdown-item {{ $currentTenant && $currentTenant->id === $tenant->id ? 'active' : '' }}" 
                                   href="{{ route('tenants.switch', $tenant) }}">
                                    {{ $tenant->name }}
                                    @if($currentTenant && $currentTenant->id === $tenant->id)
                                        <i class="fas fa-check ms-2"></i>
                                    @endif
                                </a>
                            @endforeach
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('tenants.select') }}">
                                <i class="fas fa-cog me-1"></i> {{ __('Manage Tenants') }}
                            </a>
                        </div>
                    </li>
                    @elseif($currentTenant)
                    <li class="nav-item">
                        <span class="nav-link">
                            <span class="badge bg-secondary">{{ $currentTenant->name }}</span>
                        </span>
                    </li>
                    @endif
                @endauth
                
                <!-- Authentication Links -->
                <li class="nav-item dropdown">
                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre="">
                        {{ Auth::user()->full_name }}
                    </a>

                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            Profile
                        </a>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                            onclick="event.preventDefault();
                                document.getElementById('logout-form').submit();">
                            Log Out
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>