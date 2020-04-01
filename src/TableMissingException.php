<?php

declare(strict_types=1);

namespace bizley\migration;

use Exception;

/**
 * Exception to be thrown when requested DB table does not exist.
 */
class TableMissingException extends Exception
{
}
