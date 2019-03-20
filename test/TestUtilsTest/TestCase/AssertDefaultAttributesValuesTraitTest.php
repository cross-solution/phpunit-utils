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

use Cross\TestUtils\Constraint\DefaultAttributesValues;
use Cross\TestUtils\TestCase\AssertDefaultAttributesValuesTrait;

/**
 * Tests for \Cross\TestUtils\TestCase\AssertDefaultAttributesValuesTrait
 *
 * @covers \Cross\TestUtils\TestCase\AssertDefaultAttributesValuesTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.AssertDefaultAttributesValuesTrait
 */
class AssertDefaultAttributesValuesTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testAssertMethodCallsAssertThat()
    {
        $target = new class
        {
            use AssertDefaultAttributesValuesTrait;

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

        $attr = [
            'attr' => 'value',
        ];

        $message = 'test';

        $object = new \stdClass;

        $target::assertDefaultAttributesValues($attr, $object, $message);

        static::assertSame($object, $target::$object);
        static::assertEquals($message, $target::$message);
        static::assertInstanceOf(DefaultAttributesValues::class, $target::$constraint);
        static::assertAttributeEquals($attr, 'defaultAttributes', $target::$constraint);
    }
}
