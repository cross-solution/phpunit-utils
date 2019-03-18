<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license    MIT
 * @copyright  2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

use Cross\TestUtils\Constraint\DefaultAttributesValues;

/**
 * Provide methods for default attributes values assertion.
 *
 * @see    DefaultAttributesValues
 * @method static void assertThat($value, \PHPUnit_Framework_Constraint $constraint, $message = '')
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait AssertDefaultAttributesValuesTrait
{
    /**
     * Assert that an object defines expected attributes and they have the expected value..
     *
     * @param array $defaultAttributes propertyName => value pairs
     * @param object|\ReflectionClass|string   $objectOrClass
     * @param string   $message
     *
     * @throws \PHPUnit_Framework_Exception
     */
    public static function assertDefaultAttributesValues(
        iterable $defaultAttributes,
        $objectOrClass,
        string $message = ''
    ): void {
        static::assertThat($objectOrClass, static::defaultAttributesValues($defaultAttributes), $message);
    }

    /**
     * Creates and returns an DefaultAttributesValues constraint.
     *
     * @param iterable $defaultAttributes
     *
     * @return DefaultAttributesValues
     */
    public static function defaultAttributesValues(iterable $defaultAttributes): DefaultAttributesValues
    {
        return new DefaultAttributesValues($defaultAttributes);
    }
}
