<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license MIT
 * @copyright 2019 Cross Solution <http://cross-solution.de>
 */

/** */
namespace Cross\TestUtilsTest\Constraint;

use Cross\TestUtils\Constraint\UsesTraits;
use Cross\TestUtils\TestCase\AssertUsesTraitsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \Cross\TestUtils\Constraint\UsesTraits
 *
 * @covers \Cross\TestUtils\Constraint\UsesTraits
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.Constraint
 * @group Cross.TestUtils.Constraint.UsesTraitsTrait
 */
class UsesTraitsTest extends TestCase
{
    public function testCountReturnsExpectedValue()
    {
        $target = new UsesTraits(['one', 'two', 'three']);

        static::assertEquals(3, $target->count());
    }

    public function testToStringReturnsExpectedValue()
    {
        $target = new UsesTraits();

        static::assertEquals('uses required traits', $target->toString());
    }

    public function testEvaluateReturnsTrueIfClassesAreImplemented()
    {
        $subject = new class { use AssertUsesTraitsTrait; };

        $target = new UsesTraits([ AssertUsesTraitsTrait::class ]);

        static::assertTrue($target->evaluate($subject, '', true));
    }

    public function testEvaluateThrowsExceptionWithCorrectFailureDescription()
    {
        $class   = new class { use AssertUsesTraitsTrait; };
        $subject = new \ReflectionClass($class);

        $target = new UsesTraits(['nonExistentTrait', AssertUsesTraitsTrait::class]);

        try {
            $target->evaluate($subject);
            static::fail('Expected exception of type ' . \PHPUnit\Framework\ExpectationFailedException::class . ' but none was thrown.');
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $message = $e->getMessage();

            static::assertStringContainsString('+ ' . AssertUsesTraitsTrait::class, $message);
            static::assertStringContainsString('- nonExistentTrait', $message);
        }
    }

    public function testEvaluateThrowsExceptionWithCorrectDescriptionFromString()
    {
        $subject = new class {};
        $class   = get_class($subject);
        $target = new UsesTraits([AssertUsesTraitsTrait::class]);

        $this->expectException(\PHPUnit\Framework\Exception::class);
        $this->expectExceptionMessage($class);

        $target->evaluate($class);
    }

    public function testEvaluateThrowsExceptionWithCorrectDescriptionFromObject()
    {
        $subject = new class extends \ArrayObject {};
        $class   = get_class($subject);
        $target = new UsesTraits([AssertUsesTraitsTrait::class]);

        $this->expectException(\PHPUnit\Framework\Exception::class);
        $this->expectExceptionMessage($class);

        $target->evaluate($subject);
    }

}
