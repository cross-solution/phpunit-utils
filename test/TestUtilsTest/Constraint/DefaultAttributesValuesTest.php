<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license MIT
 * @copyright 2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\Constraint;

use Cross\TestUtils\Constraint\DefaultAttributesValues;

/**
 * Tests for \Cross\TestUtils\Constraint\DefaultAttributesValues
 * 
 * @covers \Cross\TestUtils\Constraint\DefaultAttributesValues
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.Constraint
 * @group Cross.TestUtils.Constraint.DefaultAttributesValues
 */
class DefaultAttributesValuesTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateInstanceSetsDefaultAttributes()
    {
        $attr = [
            'testAttr' => 'testAttrValue',
        ];

        $target = new DefaultAttributesValues($attr);

        static::assertAttributeEquals($attr, 'defaultAttributes', $target);
    }

    public function testCountReturnsExpectedValue()
    {
        $target = new DefaultAttributesValues(['one', 'two', 'three']);

        static::assertEquals(3, $target->count());
    }

    public function testToStringReturnsExpectedValue()
    {
        $target = new DefaultAttributesValues();

        static::assertEquals('has expected default attributes and its values.', $target->toString());
    }

    public function testEvaluateReturnsTrueIfDefaultAttributesAreCorrect()
    {
        $subject = new class
        {
            public $nullValue;
            public $strValue = 'strValue';
        };

        $target = new DefaultAttributesValues(['nullValue', 'strValue' => 'strValue']);

        static::assertTrue($target->evaluate(new \ReflectionClass($subject), '', true));
    }

    public function testEvaluateThrowsExceptionWithCorrectFailureDescription()
    {
        $subject = new class
        {
            public $nullValue = 'is not null';
            public $boolValue = true;
            public $array = ['second' => 'first'];
            public $strValue = 'exactMatch';
            public $correctValue = 'correct';
        };

        $target = new DefaultAttributesValues([
            'mustBeHere',
            'nullValue',
            'boolValue' => false,
            'array' => ['first' => 'second'],
            'strValue' => 'exactmatch',
            'correctValue' => 'correct',
        ]);

        try {
            $target->evaluate(get_class($subject));
            static::fail('Expected exception of type ' . \PHPUnit_Framework_ExpectationFailedException::class . ' but none was thrown.');
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $message = $e->getMessage();
            $format = "\n - %-25s: %s";

            static::assertContains(sprintf($format, 'mustBeHere', 'Attribute is not defined'), $message);
            static::assertContains(sprintf($format, 'nullValue', "Failed asserting that 'is not null' is identical to null"), $message);
            static::assertContains(sprintf($format, 'boolValue', "Failed asserting that true is identical to false"), $message);
            static::assertContains(sprintf($format, 'array', 'Failed asserting that Array'), $message);
            static::assertContains(sprintf($format, 'strValue', 'Failed asserting that two strings'), $message);
            static::assertContains('Expected: exactmatch', $message);
            static::assertContains('Actual  : exactMatch', $message);
            static::assertContains('+ correctValue', $message);
        }
    }

    public function testEvaluateThrowsExceptionWithCorrectFailureDescriptionFromReflectionClass()
    {
        $subject = new \ReflectionClass(\stdClass::class);

        $target = new DefaultAttributesValues(['mustBeHere']);

        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage($subject->getName());

        $target->evaluate($subject);
    }

    public function testEvaluateThrowsExceptionWithCorrectFailureDescriptionFromObject()
    {
        $subject = new class {};
        $class   = get_class($subject);
        $target = new DefaultAttributesValues(['mustBeHere']);

        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage($class);

        $target->evaluate($subject);
    }

}
