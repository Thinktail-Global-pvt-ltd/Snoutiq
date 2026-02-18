<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @param  \App\Models\Transaction  $resource
     */
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'clinic' => $this->whenLoaded('clinic', function () {
                return [
                    'id' => (string) $this->clinic->id,
                    'name' => $this->clinic->name,
                ];
            }),
            'amountPaise' => (int) $this->amount_paise,
            'actualAmountPaidByConsumerPaise' => $this->actual_amount_paid_by_consumer_paise !== null
                ? (int) $this->actual_amount_paid_by_consumer_paise
                : null,
            'paymentToSnoutiqPaise' => $this->payment_to_snoutiq_paise !== null
                ? (int) $this->payment_to_snoutiq_paise
                : null,
            'paymentToDoctorPaise' => $this->payment_to_doctor_paise !== null
                ? (int) $this->payment_to_doctor_paise
                : null,
            'status' => $this->status,
            'type' => $this->type,
            'paymentMethod' => $this->payment_method,
            'reference' => $this->reference,
            'createdAt' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
