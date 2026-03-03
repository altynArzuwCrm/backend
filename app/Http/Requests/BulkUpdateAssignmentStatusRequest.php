<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация запроса массового обновления статуса назначений по списку заказов.
 */
class BulkUpdateAssignmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|exists:orders,id',
            'status' => 'required|string|in:pending,in_progress,cancelled,under_review,approved',
        ];
    }
}
