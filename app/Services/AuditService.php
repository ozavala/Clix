<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuditService
{
    public function logActivity(string $action, string $model, $modelId, array $changes = [], array $metadata = []): void
    {
        $user = Auth::user();
        $userId = $user ? $user->user_id : null;
        $userName = $user ? $user->full_name : 'System';

        $logData = [
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'user_id' => $userId,
            'user_name' => $userName,
            'changes' => $changes,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ];

        Log::channel('audit')->info('Activity logged', $logData);
    }

    public function logPaymentActivity($payment): void
    {
        $payable = $payment->payable;
        $payableType = class_basename($payable);
        
        $this->logActivity(
            'payment_received',
            $payableType,
            $payable->id,
            [
                'payment_amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date,
                'payable_status_before' => $payable->getOriginal('status'),
                'payable_status_after' => $payable->status
            ],
            [
                'payable_total_amount' => $payable->total_amount,
                'payable_amount_due' => $payable->amount_due
            ]
        );
    }

    public function logStatusChange($model, string $oldStatus, string $newStatus): void
    {
        $this->logActivity(
            'status_changed',
            class_basename($model),
            $model->id,
            [
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]
        );
    }

    public function logInventoryChange($product, $warehouse, int $oldQuantity, int $newQuantity, string $reason): void
    {
        $this->logActivity(
            'inventory_changed',
            'Product',
            $product->id,
            [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'quantity_change' => $newQuantity - $oldQuantity,
                'reason' => $reason
            ],
            [
                'product_name' => $product->name,
                'product_sku' => $product->sku
            ]
        );
    }

    public function logUserLogin(User $user, bool $success, string $reason = null): void
    {
        $this->logActivity(
            $success ? 'login_success' : 'login_failed',
            'User',
            $user->id,
            [
                'success' => $success,
                'reason' => $reason
            ],
            [
                'email' => $user->email,
                'ip_address' => request()->ip()
            ]
        );
    }

    public function logDataExport(string $model, array $filters, int $recordCount): void
    {
        $this->logActivity(
            'data_exported',
            $model,
            null,
            [
                'filters' => $filters,
                'record_count' => $recordCount
            ]
        );
    }

    public function logReportGenerated(string $reportType, array $parameters, array $results): void
    {
        $this->logActivity(
            'report_generated',
            'Report',
            null,
            [
                'report_type' => $reportType,
                'parameters' => $parameters,
                'result_summary' => [
                    'total_records' => count($results),
                    'generated_at' => now()->toISOString()
                ]
            ]
        );
    }

    public function getAuditTrail(string $model, $modelId, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = Log::channel('audit')->where('model', $model)->where('model_id', $modelId);
        
        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }
        
        return $query->orderBy('timestamp', 'desc')->get()->toArray();
    }

    public function getUserActivity(User $user, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = Log::channel('audit')->where('user_id', $user->id);
        
        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }
        
        return $query->orderBy('timestamp', 'desc')->get()->toArray();
    }
} 