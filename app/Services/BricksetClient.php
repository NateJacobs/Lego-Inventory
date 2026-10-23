<?php

namespace App\Services;

use App\Exceptions\BricksetLookupException;
use NateJacobs\MurstenTrack\Resources\Set as BrickSetSearch;
use NateJacobs\MurstenTrack\Responses\Set as BrickSetResponse;

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

    /**
     * The one set Brickset holds under this number, e.g. "71053-9".
     *
     * getSets only returns an array when it finds something; on failure it
     * hands back a ResponseException, an ErrorException, or null. Turning all
     * of that into one exception here keeps every caller from having to tell
     * those four shapes apart.
     *
     * @throws BricksetLookupException
     */
    public static function findSet(string $itemNumber): BrickSetResponse
    {
        $response = self::make()->getSets(['setNumber' => $itemNumber]);

        if (is_array($response) && isset($response[0])) {
            return $response[0];
        }

        if (self::matchedNothing($response)) {
            throw new BricksetLookupException(
                "Brickset has no set numbered {$itemNumber}."
            );
        }

        throw new BricksetLookupException(
            "Could not load set {$itemNumber} from Brickset: "
            .($response instanceof \Throwable ? $response->getMessage() : 'the response could not be read').'.'
        );
    }

    /**
     * Whether Brickset answered but matched no set.
     *
     * MurstenTrack's createReturnObject() builds its result inside a foreach
     * over the sets it was given, so a search that matched none never
     * initialises that variable and `return $sets` reads an undefined one.
     * Locally that is a warning and the method returns null; with warnings
     * promoted to exceptions, as on a deployed server, it comes back as an
     * ErrorException reading "Undefined variable $sets". Neither says what
     * actually happened, so recognise both and say it plainly — a set number
     * that predates this catalogue, or a book Brickset files differently, is
     * not a broken API.
     */
    protected static function matchedNothing(mixed $response): bool
    {
        if ($response === null) {
            return true;
        }

        return $response instanceof \Throwable
            && (str_contains($response->getMessage(), 'Undefined variable $sets')
                || str_contains($response->getMessage(), 'Undefined array key "sets"'));
    }
}
