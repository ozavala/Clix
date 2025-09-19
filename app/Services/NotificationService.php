<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function sendLowStockAlert(Product $product): void
    {
        $warehouses = $product->warehouses;
        $lowStockWarehouses = [];

        foreach ($warehouses as $warehouse) {
            $stock = $warehouse->pivot->quantity;
            $reorderPoint = $product->reorder_point ?? 10;

            if ($stock <= $reorderPoint) {
                $lowStockWarehouses[] = [
                    'warehouse' => $warehouse->name,
                    'stock' => $stock,
                    'reorder_point' => $reorderPoint
                ];
            }
        }

        if (!empty($lowStockWarehouses)) {
            // Enviar notificación a administradores
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($admins as $admin) {
                // Aquí se implementaría el envío de email o notificación
                // Mail::to($admin->email)->send(new LowStockAlert($product, $lowStockWarehouses));
            }
        }
    }

    public function sendOverdueInvoiceAlert(Invoice $invoice): void
    {
        if ($invoice->status === 'sent' && $invoice->due_date < now()) {
            $customer = $invoice->customer;
            
            // Notificar al cliente
            // Mail::to($customer->email)->send(new OverdueInvoiceReminder($invoice));
            
            // Notificar al equipo de ventas
            $salesTeam = User::whereHas('roles', function ($query) {
                $query->where('name', 'sales');
            })->get();

            foreach ($salesTeam as $member) {
                // Mail::to($member->email)->send(new OverdueInvoiceAlert($invoice));
            }
        }
    }

    public function sendPurchaseOrderStatusUpdate(PurchaseOrder $purchaseOrder, string $oldStatus, string $newStatus): void
    {
        $supplier = $purchaseOrder->supplier;
        
        // Notificar al proveedor sobre cambios de estado
        if (in_array($newStatus, ['confirmed', 'dispatched', 'received'])) {
            // Mail::to($supplier->email)->send(new PurchaseOrderStatusUpdate($purchaseOrder, $oldStatus, $newStatus));
        }
    }

    public function sendPaymentReceivedAlert($payment): void
    {
        $payable = $payment->payable;
        
        if ($payable instanceof Invoice) {
            $customer = $payable->customer;
            // Mail::to($customer->email)->send(new PaymentReceived($payment));
        } elseif ($payable instanceof PurchaseOrder) {
            $supplier = $payable->supplier;
            // Mail::to($supplier->email)->send(new PaymentReceived($payment));
        }
    }

    public function sendOrderStatusUpdate(Order $order, string $oldStatus, string $newStatus): void
    {
        $customer = $order->customer;
        
        // Notificar al cliente sobre cambios de estado
        if (in_array($newStatus, ['processing', 'shipped', 'delivered'])) {
            // Mail::to($customer->email)->send(new OrderStatusUpdate($order, $oldStatus, $newStatus));
        }
    }

    public function sendQuotationExpiryAlert(): void
    {
        $expiringQuotations = Quotation::where('status', 'sent')
            ->where('expiry_date', '<=', now()->addDays(3))
            ->where('expiry_date', '>', now())
            ->get();

        foreach ($expiringQuotations as $quotation) {
            $customer = $quotation->customer;
            // Mail::to($customer->email)->send(new QuotationExpiryReminder($quotation));
        }
    }

    public function sendDailyReport(): void
    {
        $today = now()->format('Y-m-d');
        
        $stats = [
            'new_orders' => Order::whereDate('created_at', $today)->count(),
            'new_invoices' => Invoice::whereDate('created_at', $today)->count(),
            'payments_received' => Payment::whereDate('created_at', $today)->sum('amount'),
            'low_stock_products' => Product::whereHas('warehouses', function ($query) {
                $query->whereRaw('quantity <= reorder_point');
            })->count(),
        ];

        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        foreach ($admins as $admin) {
            // Mail::to($admin->email)->send(new DailyReport($stats));
        }
    }

    public function sendInventoryValueAlert(): void
    {
        $products = Product::all();
        $totalInventoryValue = $products->sum(function ($product) {
            return $product->warehouses->sum(function ($warehouse) use ($product) {
                return $warehouse->pivot->quantity * $product->cost;
            });
        });

        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        foreach ($admins as $admin) {
            // Mail::to($admin->email)->send(new InventoryValueAlert($totalInventoryValue));
        }
    }
} 