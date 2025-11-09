<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ClinicResource extends JsonResource
{
    /**
     * @param  \App\Models\Clinic  $resource
     */
    public function toArray($request): array
    {
        $lastActiveAt = $this->last_transaction_at ?? null;
        if ($lastActiveAt && ! $lastActiveAt instanceof Carbon) {
            $lastActiveAt = Carbon::parse($lastActiveAt);
        }

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'location' => trim(collect([$this->city, $this->state])->filter()->implode(', ')) ?: null,
            'createdAt' => optional($this->created_at)->toIso8601String(),
            'stats' => [
                'totalTransactions' => (int) ($this->total_transactions ?? 0),
                'lifetimeRevenuePaise' => (int) ($this->lifetime_revenue_paise ?? 0),
                'lastActiveAt' => $lastActiveAt?->toIso8601String(),
            ],
        ];
    }
}
