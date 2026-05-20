<?php

namespace App\Support;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class PublicCapturedTransactionInvoices
{
    public const FISCAL_START_DATE = '2026-04-01';
    public const START_INVOICE_NUMBER = 1001;
    private const PRICE_TOLERANCE_PAISE = 200;
    private const INVOICE_PRICE_OPTIONS_PAISE = [
        49900,
        76600,
        47100,
        58900,
        64800,
        37100,
        66600,
        48900,
        65000,
        50000,
        40000,
        39900,
        60000,
    ];

    public static function fiscalStart(): CarbonImmutable
    {
        return CarbonImmutable::create(2026, 4, 1, 0, 0, 0, 'Asia/Kolkata');
    }

    public static function eligibleQuery(): Builder
    {
        return Transaction::query()
            ->where('status', 'captured')
            ->where('amount_paise', '!=', 100)
            ->whereNotNull('user_id')
            ->whereHas('user');
    }

    public static function downloadableQuery(): Builder
    {
        return self::eligibleQuery()
            ->where(function (Builder $query): void {
                foreach (self::INVOICE_PRICE_OPTIONS_PAISE as $amountPaise) {
                    $query->orWhereBetween('amount_paise', [
                        $amountPaise - self::PRICE_TOLERANCE_PAISE,
                        $amountPaise + self::PRICE_TOLERANCE_PAISE,
                    ]);
                }
            });
    }

    public static function invoiceNumber(Transaction $transaction): string
    {
        $createdAt = $transaction->created_at
            ? $transaction->created_at->copy()->timezone('Asia/Kolkata')
            : now('Asia/Kolkata');

        if ($createdAt->lt(self::fiscalStart())) {
            return '0000-001-' . $transaction->id;
        }

        $sequence = (clone self::downloadableQuery())
            ->where('created_at', '>=', self::fiscalStart()->setTimezone('UTC'))
            ->where(function (Builder $query) use ($transaction): void {
                $query->where('created_at', '<', $transaction->created_at)
                    ->orWhere(function (Builder $query) use ($transaction): void {
                        $query->where('created_at', '=', $transaction->created_at)
                            ->where('id', '<=', (int) $transaction->id);
                    });
            })
            ->count();

        return (string) (self::START_INVOICE_NUMBER + max(0, $sequence - 1));
    }
}
