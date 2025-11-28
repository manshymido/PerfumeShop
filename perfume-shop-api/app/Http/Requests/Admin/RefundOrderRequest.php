<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RefundOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orderId = $this->route('id');
        $orderModel = \App\Models\Order::find($orderId);

        $rules = [
            'amount' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($orderModel) {
            $rules['amount'][] = 'max:' . $orderModel->total;
        }

        return $rules;
    }
}

