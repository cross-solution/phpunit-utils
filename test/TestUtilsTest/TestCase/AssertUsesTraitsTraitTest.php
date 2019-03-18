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

use Cross\TestUtils\Constraint\UsesTraits;
use Cross\TestUtils\TestCase\AssertUsesTraitsTrait;

/**
 * Tests for \Cross\TestUtils\TestCase\AssertUsesTraitsTrait
 * 
 * @covers \Cross\TestUtils\TestCase\AssertUsesTraitsTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.AssertUsesTraitsTrait
 */
class AssertUsesTraitsTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testAssertMethodCallsAssertThat()
    {
        $target = new class
        {
            use AssertUsesTraitsTrait;

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

        $usesTraitss = [
            'class', 'trait'
        ];

        $message = 'test';

        $object = new \stdClass;

        $target::assertUsesTraits($usesTraitss, $object, $message);

        static::assertSame($object, $target::$object);
        static::assertEquals($message, $target::$message);
        static::assertInstanceOf(UsesTraits::class, $target::$constraint);
        static::assertAttributeEquals($usesTraitss, 'expectedTraits', $target::$constraint);
    }
}
