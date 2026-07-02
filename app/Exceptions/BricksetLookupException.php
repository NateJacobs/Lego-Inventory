<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a set cannot be looked up on Brickset while cataloging it, so the
 * user gets a clear message instead of a downstream "cannot use object as
 * array" or NOT NULL database error.
 */
class BricksetLookupException extends RuntimeException
{
}
