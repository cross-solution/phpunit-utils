<?php

/**
 * CROSS PHPUnit Utils
 *
 * @see       https://github.com/cross-solution/phpunit-utils for the canonical source repository
 * @copyright https://github.com/cross-solution/phpunit-utils/blob/master/COPYRIGHT
 * @license   https://github.com/cross-solution/phpunit-utils/blob/master/LICENSE MIT
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

use Cross\TestUtils\Constraint\UsesTraits;

/**
 * Trait to be used to easily assert the usage of specific traits of a target class in a test case.
 *
 * @method static void assertThat($value, \PHPUnit_Framework_Constraint $constraint, $message='')
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait AssertUsesTraitsTrait
{

    /**
     * Asserts that a class uses expected traits.
     *
     * @param iterable         $traits        Trait names to check against.
     * @param string|object             $objectOrClass The target instance or class name
     * @param string                    $message       Failure message.
     */
    public static function assertUsesTraits(iterable $traits, $objectOrClass, string $message = ''): void
    {
        static::assertThat($objectOrClass, static::usesTraits($traits), $message);
    }

    /**
     * Creates and returns an UsesTraits constraint.
     *
     * @param iterable $traits
     *
     * @return UsesTraits
     */
    public static function usesTraits(iterable $traits): UsesTraits
    {
        return new UsesTraits($traits);
    }
}
