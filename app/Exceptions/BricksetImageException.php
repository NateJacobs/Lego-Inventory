<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a set's pictures cannot be fetched from Brickset — the set has
 * none, or the download itself failed. Separate from BricksetLookupException so
 * a backfill can report "found the set but not its picture" distinctly, and so
 * a missing picture never stops a set being catalogued.
 */
class BricksetImageException extends RuntimeException
{
}
