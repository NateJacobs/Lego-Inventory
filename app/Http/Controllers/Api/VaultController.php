<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectionLog;
use App\Services\CollectionValuation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The collection's value for vault.ngtj.com: totals only. Reached with a token that can
 * only `vault:read` (see `php artisan vault:token`). Amounts are whole cents.
 *
 * The market value is the conservative "used" BrickLink figure; the new and retail
 * figures come along as alternatives.
 */
class VaultController extends Controller
{
    public function summary(CollectionValuation $valuation): JsonResponse
    {
        return response()->json([
            'schema_version' => 1,
            'source' => 'mursten',
            'as_of' => today()->toDateString(),
            'currency' => 'USD',
            'market_value_cents' => self::cents($valuation->usedValue()),
            'cost_basis_cents' => self::cents($valuation->costBasis()),
            'item_count' => $valuation->totalSets(),
            'valuation_basis' => 'BrickLink used prices, refreshed monthly; bulk and orders at value',
            'alt_values' => [
                'new' => self::cents($valuation->newValue()),
                'retail' => self::cents($valuation->retailValue()),
            ],
        ]);
    }

    /** The monthly collection log. Cost was never logged, so it is left empty. */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $points = CollectionLog::query()
            ->when($validated['from'] ?? null, fn ($q, $from) => $q->whereDate('date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($q, $to) => $q->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->get()
            ->map(fn (CollectionLog $log): array => [
                'date' => $log->date->toDateString(),
                'market_value_cents' => self::cents((float) $log->used_value),
                'cost_basis_cents' => null,
                'item_count' => (int) $log->total_sets,
            ]);

        return response()->json([
            'schema_version' => 1,
            'source' => 'mursten',
            'currency' => 'USD',
            'points' => $points,
        ]);
    }

    private static function cents(float $dollars): int
    {
        return (int) round($dollars * 100);
    }
}
