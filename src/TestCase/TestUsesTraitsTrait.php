<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license    MIT
 * @copyright  2013 - 2016 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

use Cross\TestUtils\Exception\InvalidUsageException;
use Cross\TestUtils\Utils\Target;

/**
 * Uses traits test.
 *
 * Classes (TestCases) using this trait can test wether the SUT uses required traits.
 *
 * Define the property $usesTraits as an array which holds all FQCN of the traits to test for.
 * The first entry (or the entry with the key 'target') will be used as the SUT.
 *
 * The SUT can also be provided via the methods 'getUsesTraitsTarget' or 'getTarget' or by properties
 * '$usesTraitsTarget' or '$target'.
 *
 *
 * @property string[]      $usesTraits
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait TestUsesTraitsTrait
{
    use AssertUsesTraitsTrait;

    /**
     * @testdox Uses required traits.
     * @coversNothing
     */
    public function testUsesTraits(): void
    {
        if (!property_exists($this, 'usesTraits') || !is_array($this->usesTraits)) {
            throw InvalidUsageException::fromTrait(
                __TRAIT__,
                __CLASS__,
                'Property "$usesTraits" is not defined or not an array.'
            );
        }

        $target = Target::get(
            $this,
            ['getUsesTraitsTarget', 'getTarget'],
            ['usesTraitsTarget', 'target'],
            'usesTraits'
        );

        static::assertUsesTraits($this->usesTraits, $target);
    }
}
