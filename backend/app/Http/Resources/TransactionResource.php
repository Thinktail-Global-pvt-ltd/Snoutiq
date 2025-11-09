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
            'status' => $this->status,
            'type' => $this->type,
            'paymentMethod' => $this->payment_method,
            'reference' => $this->reference,
            'createdAt' => optional($this->created_at)->toIso8601String(),
        ];
    }
}

