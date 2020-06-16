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
 * Boilerplate for an exception with templated message.
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait TemplatedMessageExceptionTrait
{
    /**
     * Creates an exception with templated message.
     *
     * Pass in arguments as you would to _sprintf_:
     * The first argument is the template string, the following
     * arguments are the replacements.
     *
     * If the last argument passed is an instanceof \Throwable, it is used
     * as previous exception argument to the \Exception constructor.
     *
     * @param  array     $args
     *
     * @return \Exception
     */
    public static function create(...$args): \Exception
    {
        $ex      = end($args) instanceof \Throwable ? array_pop($args) : null;
        $message = sprintf(...$args);

        return new static($message, 0, $ex);
    }

    /**
     * Creates an exception with templated message prepended by the class name.
     *
     * @param  string     $class
     * @param  string     $message
     * @param  array      ...$args
     *
     * @return \Exception
     */
    public static function fromClass(string $class, string $message, ...$args): \Exception
    {
        $message = '%s: ' . $message;

        return static::create($message, $class, ...$args);
    }

    /**
     * Creates an exception with templated message prepended by trait and class name.
     *
     * @param  string     $trait
     * @param  string     $class
     * @param  string     $message
     * @param  array      ...$args
     *
     * @return \Exception
     */
    public static function fromTrait(string $trait, string $class, string $message, ...$args): \Exception
    {
        $message = '%s: %s: ' . $message;

        return static::create($message, $trait, $class, ...$args);
    }
}
