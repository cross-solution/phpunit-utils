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
class UsesTraitsTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateInstanceSetsParentsAndInterfaces()
    {
        $traits = [
            AssertUsesTraitsTrait::class,
        ];

        $target = new UsesTraits($traits);

        static::assertAttributeEquals($traits, 'expectedTraits', $target);
    }

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
            static::fail('Expected exception of type ' . \PHPUnit_Framework_ExpectationFailedException::class . ' but none was thrown.');
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $message = $e->getMessage();

            static::assertContains('+ ' . AssertUsesTraitsTrait::class, $message);
            static::assertContains('- nonExistentTrait', $message);
        }
    }

    public function testEvaluateThrowsExceptionWithCorrectDescriptionFromString()
    {
        $subject = new class {};
        $class   = get_class($subject);
        $target = new UsesTraits([AssertUsesTraitsTrait::class]);

        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage($class);

        $target->evaluate($class);
    }

    public function testEvaluateThrowsExceptionWithCorrectDescriptionFromObject()
    {
        $subject = new class extends \ArrayObject {};
        $class   = get_class($subject);
        $target = new UsesTraits([AssertUsesTraitsTrait::class]);

        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage($class);

        $target->evaluate($subject);
    }

}
