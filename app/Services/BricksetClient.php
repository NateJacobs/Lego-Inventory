<?php

namespace App\Services;

use App\Exceptions\BricksetLookupException;
use NateJacobs\MurstenTrack\Resources\Set as BrickSetSearch;

/**
 * Builds a Brickset client with its API key where the package can find it.
 *
 * MurstenTrack reads the key with getenv('MURSTEN_TRACK_KEY') inside its own
 * Request class and offers no way to pass one in. That works locally and fails
 * on a deployed server for the same reason the BrickLink credentials did:
 * `config:cache` stops Laravel loading the .env file at all, so getenv()
 * returns false and the package rejects the request as "Missing Brickset API
 * key" while the key sits in .env untouched.
 *
 * BricklinkClient could hand its credentials to the client object. There is
 * nowhere to hand these, so read the key from the config — which is cached
 * correctly — and put it into the process environment the package reads.
 */
class BricksetClient
{
    public static function isConfigured(): bool
    {
        return filled(config('services.brickset.key'));
    }

    /**
     * @throws BricksetLookupException When the API key is not configured.
     */
    public static function make(): BrickSetSearch
    {
        $key = config('services.brickset.key');

        if (blank($key)) {
            throw new BricksetLookupException(
                'The Brickset API key is not configured, so sets cannot be looked up. '
                .'Set MURSTEN_TRACK_KEY in the environment.'
            );
        }

        // getenv() reads the process environment, and a cached config means the
        // .env file was never loaded into it.
        if (getenv('MURSTEN_TRACK_KEY') !== $key) {
            putenv('MURSTEN_TRACK_KEY='.$key);
        }

        return new BrickSetSearch;
    }
}
