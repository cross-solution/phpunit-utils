<?php

/**
 * CROSS PHPUnit Utils
 *
 * @see       https://github.com/cross-solution/phpunit-utils for the canonical source repository
 * @copyright https://github.com/cross-solution/phpunit-utils/blob/master/COPYRIGHT
 * @license   https://github.com/cross-solution/phpunit-utils/blob/master/LICENSE MIT
 */

declare(strict_types=1);

namespace Cross\TestUtils\Exception;

/**
 * Exception thrown if a helper trait is used the wrong way
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
class InvalidUsageException extends \PHPUnit\Framework\Exception implements ExceptionInterface
{
    use TemplatedMessageExceptionTrait;
}
