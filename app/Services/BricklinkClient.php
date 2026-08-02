<?php

namespace App\Services;

use App\Exceptions\BricklinkPriceException;
use NateJacobs\MurstenStock\Client as BLClient;

/**
 * Builds an authenticated BrickLink client.
 *
 * The credentials used to be read with getenv() at each of the three call
 * sites. That works locally but not on a deployed server: `config:cache` stops
 * Laravel loading the .env file at all, so getenv() returned false and every
 * request went out signed with empty credentials — failing in a way that looked
 * like BrickLink rejecting the account rather than a misconfiguration.
 */
class BricklinkClient
{
    /**
     * @return list<string> The credentials that are missing.
     */
    public static function missingCredentials(): array
    {
        return array_keys(array_filter(
            self::credentials(),
            fn ($value): bool => blank($value),
        ));
    }

    public static function isConfigured(): bool
    {
        return self::missingCredentials() === [];
    }

    /**
     * @throws BricklinkPriceException When any credential is missing.
     */
    public static function make(): BLClient
    {
        $missing = self::missingCredentials();

        if ($missing !== []) {
            throw new BricklinkPriceException(
                'BrickLink credentials are not configured: missing '.implode(', ', $missing).'.'
            );
        }

        $client = new BLClient;
        $client->setAuth(self::credentials());

        return $client;
    }

    /**
     * @return array<string, string|null>
     */
    protected static function credentials(): array
    {
        return [
            'consumer_key' => config('services.bricklink.consumer_key'),
            'consumer_secret' => config('services.bricklink.consumer_secret'),
            'token' => config('services.bricklink.token'),
            'token_secret' => config('services.bricklink.token_secret'),
        ];
    }
}
