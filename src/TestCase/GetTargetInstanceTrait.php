<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @copyright 2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

/**
 * Helper trait to get target class names or create target instances from various sources
 * of the TestCase class.
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait GetTargetInstanceTrait
{

    /**
     * Get a target from various sources on the TestCase class.
     *
     * $methods:
     *      An array of method names. For each name, is is checked, wether a method with that name exists,
     *      and if it is so, it will be called and the return value of that method will be returned as target.
     *
     * $properties
     *      An array of property names. For each name, it is checked, wether a property with that name exists.
     *      If there is one, its value will be returned as target.
     *
     * $classesProperty
     *      The name of an property holding an array of class names
     *      (or at least a class name/object in the key "target" or 0)
     *      If such a property exists, it is an array and has at least one item, then
     *      * the value of the key "target" is returned as target. The key will be unset.
     *      * the value of the first item is returned as target. The key will be unset.
     *
     * You can specify the target in the following ways:
     *
     * * A string: Assumed to be a class name and is returned as is,
     *             unless $forceObject is true, then the class will be instantiated.
     *             If the string starts with "!", an instance of \ReflectionClass for that class will be returned.
     * * an array: The first item must be the class name, all successive items are uses as constructor
     *             arguments. An instance is returned.
     *
     *
     * @param array       $methods
     * @param array       $properties
     * @param null|string $classesProperty
     * @param bool        $forceObject
     *
     * @return \ReflectionClass|object|string
     * @throws \PHPUnit_Framework_Exception
     */
    private function getTargetInstance(
        array $methods,
        array $properties,
        ?string $classesProperty = null,
        bool $forceObject = false
    ) {
        $createTarget = function ($spec) use ($forceObject) {

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
        };

        foreach ($methods as $method) {
            if (method_exists($this, $method)) {
                return $createTarget($this->$method());
            }
        }

        foreach ($properties as $property) {
            if (property_exists($this, $property)) {
                return $createTarget($this->$property);
            }
        }

        if ($classesProperty
            && property_exists($this, $classesProperty)
            && is_array($this->$classesProperty)
            && count($this->$classesProperty)
        ) {
            if (isset($this->{$classesProperty}['target'])) {
                $target = $createTarget($this->{$classesProperty}['target']);
                unset($this->{$classesProperty}['target']);

                return $target;
            }

            return $createTarget(array_shift($this->$classesProperty));
        }

        throw new \PHPUnit_Framework_Exception(__TRAIT__ . ': '
            . get_class($this) . ': Could not find or create a target instance.');
    }
}
