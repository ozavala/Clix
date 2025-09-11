<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function salesByPeriod(Request $request)
    {
        $period = $request->input('period', 'last_30_days');

        list($startDate, $endDate) = $this->getDateRange($request);

        $salesData = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total_sales'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return view('reports.sales', compact('salesData', 'period'));
    }

    public function salesByProduct(Request $request)
    {
        $period = $request->input('period', 'last_30_days');

        list($startDate, $endDate) = $this->getDateRange($request);

        $productSalesData = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.product_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('products.name as product_name', DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_sales'))
            ->groupBy('products.name')
            ->orderByDesc('total_sales')
            ->get();

        return view('reports.sales-by-product', compact('productSalesData', 'period'));
    }

    public function salesByCustomer(Request $request)
    {
        $period = $request->input('period', 'last_30_days');

        list($startDate, $endDate) = $this->getDateRange($request);

        $customerSalesData = DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.customer_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('customers.company_name as customer_name', DB::raw('SUM(orders.total_amount) as total_sales'))
            ->groupBy('customers.company_name')
            ->orderByDesc('total_sales')
            ->get();

        return view('reports.sales-by-customer', compact('customerSalesData', 'period'));
    }

    public function salesByCategory(Request $request)
    {
        $period = $request->input('period', 'last_30_days');

        list($startDate, $endDate) = $this->getDateRange($request);

        $categorySalesData = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.product_id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.category_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('product_categories.name as category_name', DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_sales'))
            ->groupBy('product_categories.name')
            ->orderByDesc('total_sales')
            ->get();

        return view('reports.sales-by-category', compact('categorySalesData', 'period'));
    }

    public function salesByEmployee(Request $request)
    {
        $period = $request->input('period', 'last_30_days');

        list($startDate, $endDate) = $this->getDateRange($request);

        $employeeSalesData = DB::table('orders')
            ->join('crm_users', 'orders.created_by_user_id', '=', 'crm_users.user_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('crm_users.full_name as employee_name', DB::raw('SUM(orders.total_amount) as total_sales'))
            ->groupBy('employee_name')
            ->orderByDesc('total_sales')
            ->get();

        return view('reports.sales-by-employee', compact('employeeSalesData', 'period'));
    }

    private function getDateRange(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            return [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
        }

        $period = $request->input('period', 'last_30_days');

        switch ($period) {
            case 'last_7_days':
                return [now()->subDays(6)->startOfDay(), now()->endOfDay()];
            case 'last_30_days':
                return [now()->subDays(29)->startOfDay(), now()->endOfDay()];
            case 'last_90_days':
                return [now()->subDays(89)->startOfDay(), now()->endOfDay()];
            default:
                return [now()->subDays(29)->startOfDay(), now()->endOfDay()];
        }
    }

}