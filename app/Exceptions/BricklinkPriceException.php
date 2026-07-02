<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a BrickLink price lookup fails, so that the queued
 * RefreshCatalogItemPrice job retries it and the "Update Bricklink Prices"
 * Nova action can report the failure rather than silently skipping it.
 */
class BricklinkPriceException extends RuntimeException
{
}
