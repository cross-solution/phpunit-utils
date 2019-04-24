<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license MIT
 * @copyright  2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\TestCase;

use Cross\TestUtils\Constraint\ExtendsOrImplements;
use Cross\TestUtils\TestCase\AssertInheritanceTrait;

use PHPUnit\Framework\TestCase;

/**
 * Tests for \Cross\TestUtils\TestCase\AssertInheritanceTrait
 *
 * @covers \Cross\TestUtils\TestCase\AssertInheritanceTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.AssertInheritanceTrait
 */
class AssertInheritanceTraitTest extends TestCase
{
    public function testAssertMethodCallsAssertThat()
    {
        $target = new class
        {
            use AssertInheritanceTrait;

            public static $object;
            public static $constraint;
            public static $message;

            public static function assertThat($object, $constraint, $message = '')
            {
                static::$object = $object;
                static::$constraint = $constraint;
                static::$message = $message;
            }
        };

        $inheritances = [];

        $message = 'test';

        $object = new \stdClass;

        $target::assertInheritance($inheritances, $object, $message);

        static::assertSame($object, $target::$object);
        static::assertEquals($message, $target::$message);
        static::assertInstanceOf(ExtendsOrImplements::class, $target::$constraint);
    }

    public function testAssertMethodPassesCorrectValueOfInheritances()
    {
        $target = new class
        {
            use AssertInheritanceTrait;

            public static $inheritances;

            public static function extendsOrImplements(iterable $parentsAndInterfaces): ExtendsOrImplements
            {
                static::$inheritances = $parentsAndInterfaces;
                return new ExtendsOrImplements($parentsAndInterfaces);
            }

            public static function assertThat($object, $constraints, $message = '')
            {

            }
        };

        $inheritances = ['class', 'trait'];

        $target->assertInheritance($inheritances, new \stdClass);

        static::assertEquals($inheritances, $target::$inheritances);
    }
}
