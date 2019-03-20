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

use Cross\TestUtils\Exception\InvalidUsageException;
use Cross\TestUtils\Utils\Target;

/**
 * Inheritance test.
 *
 * Classes (TestCases) uses this trait can assert inheritance and interface implementations.
 *
 * Define the property $inheritance as an array which holds all FQCN of the traits to test for.
 * The first entry (or the entry with the key 'target') will be used as the SUT.
 *
 * The SUT can also be provided via the methods 'getInheritanceTarget' or 'getTarget' or by properties
 * '$inheritanceTarget' or '$target'.
 *
 *
 * @property string[] $inheritance
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait TestInheritanceTrait
{
    use AssertInheritanceTrait;

    /**
     * @testdox Extends correct parent and implements required interfaces.
     * @coversNothing
     */
    public function testInheritance(): void
    {
        if (!property_exists($this, 'inheritance') || !is_array($this->inheritance)) {
            throw InvalidUsageException::fromTrait(
                __TRAIT__,
                __CLASS__,
                'Property "$inheritance" is not defined or is not an array.'
            );
        }

        $target = Target::get(
            $this,
            ['getInheritanceTarget', 'getTarget'],
            ['inheritanceTarget', 'target'],
            'inheritance'
        );

        static::assertInheritance($this->inheritance, $target);
    }
}
