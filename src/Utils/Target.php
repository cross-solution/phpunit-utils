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

/**
 * Retrieves the SUT for a test from various sources of a context class.
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
final class Target
{
    /**
     * Get the SUT from various sources of a context class.
     *
     * __$methods__:
     *   An array of method names. If one of that methods exists in the
     *   context class, it is called
     *   The methods can return one of the following:
     *
     *   * an object: is used as SUT
     *   * an array:  {@link Instance::withMappedArguments()} is called with
     *     the array and the context class, its return value is
     *     returned as SUT.
     *   * a string:  is returned to use as the FQCN od the SUT,
     *     unless $forceObject is true - then it's treated like
     *     above.
     *
     * __$properties__:
     *   An array of property names. If one of that property exists in the
     *   context class, its value is used the same way as the return value
     *   above.
     *
     * __$classesProperty__:
     *   If the context class defines a class list array, you can
     *   specify its name here, and if the SUT is not found yet, it will
     *   take the element with the key 'target' or the first element and treat
     *   it like a property value described above.
     *   The element will be unset after the SUT creation.
     *
     * @param  object       $testcase        Context class
     * @param  array        $methods
     * @param  array        $properties
     * @param  string|null  $classesProperty
     * @param  bool         $forceObject     If true, an object is returned at any case
     *
     * @return string|object
     */
    public static function get(
        object  $testcase,
        array   $methods,
        array   $properties,
        ?string $classesProperty = null,
        bool    $forceObject = false
    ) {
        $testcaseReflection = new \ReflectionClass($testcase);

        $createTarget = function ($target) use ($testcase, $forceObject) {
            if (is_object($target)) {
                return $target;
            }

            if (!$forceObject && is_string($target) && '!' != $target{0}) {
                return $target;
            }

            return Instance::withMappedArguments($target, $testcase);
        };

        foreach ($methods as $method) {
            if ($testcaseReflection->hasMethod($method)) {
                $testcaseMethod = $testcaseReflection->getMethod($method);
                $testcaseMethod->setAccessible(true);

                return $createTarget($testcaseMethod->invoke($testcase));
            }
        }

        foreach ($properties as $property) {
            if ($testcaseReflection->hasProperty($property)) {
                $testcaseProperty = $testcaseReflection->getProperty($property);
                $testcaseProperty->setAccessible(true);

                return $createTarget($testcaseProperty->getValue($testcase));
            }
        }

        if ($classesProperty
            && $testcaseReflection->hasProperty($classesProperty)
        ) {
            $testcaseProperty = $testcaseReflection->getProperty($classesProperty);
            $testcaseProperty->setAccessible(true);

            $propertyValue = $testcaseProperty->getValue($testcase);

            if (is_array($propertyValue) && count($propertyValue)) {
                if (isset($propertyValue['target'])) {
                    $target = $createTarget($propertyValue['target']);
                    unset($propertyValue['target']);
                } else {
                    $target = $createTarget(array_shift($propertyValue));
                }

                $testcaseProperty->setValue($testcase, $propertyValue);

                return $target;
            }
        }

        throw new \PHPUnit\Framework\Exception('Could not find or create a target instance.');
    }
}
