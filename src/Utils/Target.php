<?php
/**
 * CROSS PHPunit Utils
 *
 * @filesource
 * @copyright 2019 Cross Solution <https://www.cross-solution.de>
 * @license MIT
 */

declare(strict_types=1);

namespace Cross\TestUtils\Utils;

/**
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @todo write tests
 */
final class Target
{

    public static function get(
        object  $testcase,
        array   $methods,
        array   $properties,
        ?string $classesProperty = null,
        bool    $forceObject = false
    ) {
        $testcaseReflection = new \ReflectionClass($testcase);

        foreach ($methods as $method) {
            if ($testcaseReflection->hasMethod($method)) {
                $testcaseMethod = $testcaseReflection->getMethod($method);
                $testcaseMethod->setAccessible(true);

                return self::create($testcaseMethod->invoke($testcase), $forceObject);
            }
        }

        foreach ($properties as $property) {
            if ($testcaseReflection->hasProperty($property)) {
                $testcaseProperty = $testcaseReflection->getProperty($property);
                $testcaseProperty->setAccessible(true);

                return self::create($testcaseProperty->getValue($testcase), $forceObject);
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
                    $target = self::create($propertyValue['target'], $forceObject);
                    unset($propertyValue['target']);
                } else {
                    $target = self::create(array_shift($propertyValue), $forceObject);
                }

                $testcaseProperty->setValue($testcase, $propertyValue);

                return $target;
            }
        }

        throw new \PHPUnit_Framework_Exception('Could not find or create a target instance.');
    }

    /**
     * Creates an instance or returns FQCN
     *
     * @param  string|array $spec
     * @param  bool   $forceObject
     * @return string|object
     */
    private static function create($spec, bool $forceObject)
    {
        if (is_array($spec)) {
            $class = array_shift($spec);
            return new $class(...$spec);
        }

        if (is_string($spec)) {
            if (0 === strpos($spec, '!')) {
                return new \ReflectionClass(substr($spec, 1));
            }

            if ($forceObject) {
                return new $spec();
            }
        }

        return $spec;
    }
}
