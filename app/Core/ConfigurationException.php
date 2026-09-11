<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * The environment is wrong, rather than the world.
 *
 * Kept apart from the RuntimeException a failed connection raises, because
 * the two want opposite handling and only one of them is safe to repeat.
 *
 * A connection failure carries the driver's message, which names the host,
 * the user and sometimes more; it is replaced with something generic before
 * anyone sees it. A configuration error is about a value the operator typed
 * themselves - a path that does not exist, a setting that contradicts another
 * - so its message contains nothing they did not already write down, and
 * showing it is the difference between "check the DB_* variables" and
 * knowing which one to check.
 *
 * That distinction matters most on a first deploy, where the environment is
 * exactly what is likely to be wrong and the person fixing it has no shell on
 * the machine.
 */
final class ConfigurationException extends RuntimeException
{
}
