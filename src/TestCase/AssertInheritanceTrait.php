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

use Cross\TestUtils\Constraint\ExtendsOrImplements;

/**
 * Provide methods for inheritance assertion.
 *
 * @see    ExtendsOrImplements
 *
 * @method static void assertThat($value, \PHPUnit_Framework_Constraint $constraint, $message='')
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait AssertInheritanceTrait
{
    /**
     * Assert that an object extends or implements specific classes resp. interfaces.
     *
     * @param iterable $parentsAndInterfaces
     * @param object|\ReflectionClass|string   $objectOrClass
     * @param string   $message
     */
    public static function assertInheritance(
        iterable $parentsAndInterfaces,
        $objectOrClass,
        string $message = ''
    ): void {
        static::assertThat($objectOrClass, static::extendsOrImplements($parentsAndInterfaces), $message);
    }

    /**
     * Creates and returns an ExtendsOrImplements constraint.
     *
     * @param iterable $parentsAndInterfaces
     *
     * @return ExtendsOrImplements
     */
    public static function extendsOrImplements(iterable $parentsAndInterfaces): ExtendsOrImplements
    {
        return new ExtendsOrImplements($parentsAndInterfaces);
    }
}
