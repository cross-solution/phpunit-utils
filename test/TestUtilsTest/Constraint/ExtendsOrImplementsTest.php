<?php

/**
 * CROSS PHPUnit Utils
 *
 * @see       https://github.com/cross-solution/phpunit-utils for the canonical source repository
 * @copyright https://github.com/cross-solution/phpunit-utils/blob/master/COPYRIGHT
 * @license   https://github.com/cross-solution/phpunit-utils/blob/master/LICENSE MIT
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\Constraint;

use Cross\TestUtils\Constraint\ExtendsOrImplements;
use Cross\TestUtils\TestCase\TestDefaultAttributesTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \Cross\TestUtils\Constraint\ExtendsOrImplements
 *
 * @covers \Cross\TestUtils\Constraint\ExtendsOrImplements
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.Constraint
 * @group Cross.TestUtils.Constraint.ExtendsOrImplementsTest
 */
class ExtendsOrImplementsTest extends TestCase
{
    public function testCountReturnsExpectedValue()
    {
        $target = new ExtendsOrImplements(['one', 'two', 'three']);

        static::assertEquals(3, $target->count());
    }

    public function testToStringReturnsExpectedValue()
    {
        $target = new ExtendsOrImplements();

        static::assertEquals('extends or implements required classes and interfaces', $target->toString());
    }

    public function testEvaluateReturnsTrueIfClassesAreImplemented()
    {
        $subject = new class extends \ArrayObject {};

        $target = new ExtendsOrImplements([ \ArrayObject::class ]);

        static::assertTrue($target->evaluate($subject, '', true));
    }

    public function testEvaluateThrowsExceptionWithCorrectFailureDescription()
    {
        $class   = new class extends \ArrayObject {};
        $subject = new \ReflectionClass($class);

        $target = new ExtendsOrImplements([\Exception::class, \ArrayObject::class]);

        try {
            $target->evaluate($subject);
            static::fail('Expected exception of type ' . \PHPUnit\Framework\ExpectationFailedException::class . ' but none was thrown.');
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $message = $e->getMessage();

            static::assertStringContainsString('+ ' . \ArrayObject::class, $message);
            static::assertStringContainsString('- ' . \Exception::class, $message);
        }
    }

    public function testEvaluateThrowsExceptionWithCorrectDescriptionFromObject()
    {
        $class = new class extends \ArrayObject {};
        $target = new ExtendsOrImplements([\Exception::class]);

        $this->expectException(\PHPUnit\Framework\Exception::class);
        $this->expectExceptionMessage(get_class($class));

        $target->evaluate($class);
    }

    public function testEvaluateThrowsExceptionWithCorrectDescriptionFromString()
    {
        $subject = new class extends \ArrayObject {};
        $class   = get_class($subject);
        $target = new ExtendsOrImplements([\Exception::class]);

        $this->expectException(\PHPUnit\Framework\Exception::class);
        $this->expectExceptionMessage($class);

        $target->evaluate($class);
    }

}
