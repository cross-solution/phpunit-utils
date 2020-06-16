<?php

/**
 * CROSS PHPUnit Utils
 *
 * @see       https://github.com/cross-solution/phpunit-utils for the canonical source repository
 * @copyright https://github.com/cross-solution/phpunit-utils/blob/master/COPYRIGHT
 * @license   https://github.com/cross-solution/phpunit-utils/blob/master/LICENSE MIT
 */

declare(strict_types=1);

namespace Cross\TestUtils\Utils;

use Cross\TestUtils\Exception\InvalidUsageException;

/**
 * Creates object instances.
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
final class Instance
{

    /**
     * Create a \ReflectionClass instance for an object or FQCN.
     *
     * This method is mainly used by various traits,  to
     * allow creating reflection instances from an array specification
     * The sole purpose of this method is:
     * If an array is passed, the target object or FQCN is taken from
     * * the element with the key 'class' or
     * * the first element in the array
     *
     * @param  string|array|object  $fqcnOrObject
     *
     * @return \ReflectionClass
     */
    public static function reflection($fqcnOrObject): \ReflectionClass
    {
        if (is_array($fqcnOrObject)) {
            $fqcnOrObject = $fqcnOrObject['class'] ?? reset($fqcnOrObject);
        }

        return new \ReflectionClass($fqcnOrObject);
    }

    /**
     * Creates an object.
     *
     * if __$fqcn__ is a string and starts with "!", a \ReflectionClass
     * object is returned.
     *
     * if __$fqcn__ is an array, the first element is used as FQCN and
     * all other elements are used as constructor arguments - other
     * arguments passed in are ignored.
     *
     * @param  string|array $fqcn
     * @param  mixed ...$arguments
     *
     * @return object
     */
    public static function create($fqcn, ...$arguments): object
    {
        if (is_array($fqcn)) {
            $arguments = array_slice($fqcn, 1);
            $fqcn = reset($fqcn);
        }

        if (!is_string($fqcn)) {
            throw InvalidUsageException::fromClass(
                __CLASS__,
                'Expected a string as FQCN, but received %s',
                gettype($fqcn)
            );
        }

        if ('!' == $fqcn{0}) {
            return self::reflection(substr($fqcn, 1));
        }

        return new $fqcn(...$arguments);
    }

    /**
     * Creates an object.
     *
     * For each entry in __$arguments__ the following operations are made:
     *
     * * if the key is a string
     *   * if the value is a callable, it is called and the returned value
     *     used as argument value.
     *   * if the value is not a callable, the key is used as method name
     *     to call on the value - which should be an object or FQCN
     *
     *
     * * if the key is numeric
     *   * if value is a string starting with '@', the remaining string will be used:
     *     * as callable: the returned value is used as argument value
     *     * as method name to call on the __$context__ object to get the
     *       argument value - if __$context__ is not null
     *   * if the value is an array that has the key '@', the value of that key is treated
     *     * as callable: the returned value is used as argument value
     *     * as method name to call on the __$context__ object to get the
     *       argument value - if __$context__  is not null
     *
     * __NOTE__: Also private or protected method on the __$context__ can be used as
     *           method name, because reflection is used as last possibility.
     *
     * If none of the above leads to calling a callback, the value is taken as argument value
     * as is.
     *
     * You may pass the FQCN and the arguments as array to __$fqcn__
     * In that case - or if no arguments are needed - you may pass the context object
     * to __$arguments__
     *
     * @param  string|array $fqcn
     * @param  array|object $arguments
     * @param  object|null $context
     *
     * @return object
     */
    public static function withMappedArguments($fqcn, $arguments, ?object $context = null): object
    {
        if (is_object($arguments)) {
            $context   = $arguments;
            $arguments = [];
        }

        if (is_array($fqcn)) {
            $arguments = array_slice($fqcn, 1);
            $fqcn      = reset($fqcn);
        }

        $f = function ($value, $key) use ($context) {
            /** @var \ReflectionClass $reflection */
            static $reflection;

            switch (true) {
                // 'method' => object|FQCN|callable
                case is_string($key):
                    $callback = is_callable($value) ? $value : [$value, $key];
                    break;

                // '@function'
                case is_string($value) && '@' == $value{0}:
                    $callback = ltrim($value, '@');
                    break;

                // ['@' => callable]
                case is_array($value) && isset($value['@']):
                    $callback = $value['@'];
                    break;

                default:
                    return $value;
            }

            if (is_callable($callback)) {
                return $callback();
            }

            if (!$context) {
                return $value;
            }

            $contextCallback = [$context, $callback];

            if (is_callable($contextCallback)) {
                return $contextCallback();
            }

            if (!is_string($callback)) {
                return $value;
            }

            if (!$reflection) {
                $reflection = new \ReflectionClass($context);
            }

            if ($reflection->hasMethod($callback)) {
                $method = $reflection->getMethod($callback);
                $method->setAccessible(true);

                return $method->invoke($context);
            }

            return $value;
        };

        $arguments = array_map($f, $arguments, array_keys($arguments));

        return self::create($fqcn, ...$arguments);
    }
}
