<?php
/**
 * CROSS PHPunit Utils
 *
 * @filesource
 * @copyright 2019 Cross Solution <https://www.cross-solution.de>
 * @license MIT
 */

declare(strict_types=1);

namespace Cross\TestUtils\Exception;

/**
 * Exception thrown if a helper trait is used the wrong way
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @todo write tests
 */
class InvalidUsageException extends \PHPUnit_Framework_Exception implements ExceptionInterface
{
    use TemplatedMessageExceptionTrait;
}
