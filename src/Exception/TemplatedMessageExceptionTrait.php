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
 * Boilerplate for an exception with templated message.
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @todo write tests
 */
trait TemplatedMessageExceptionTrait
{
    public static function create(...$args): \Exception
    {
        $ex      = end($args) instanceof \Throwable ? array_pop($args) : null;
        $message = sprintf(...$args);

        return new static($message, 0, $ex);
    }

    public static function fromTrait(string $trait, string $class, string $message, ...$args): \Exception
    {
        if (count($args)) {
            $message = sprintf($message, ...$args);
        }

        return static::create('%s: %s: %s', $trait, $class, $message);
    }
}
