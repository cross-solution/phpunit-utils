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
 * @property object|string $target
 * @property string[]      $usesTraits
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait TestUsesTraitsTrait
{
    use AssertUsesTraitsTrait, GetTargetInstanceTrait;

    /**
     * @testdox Uses required traits.
     * @coversNothing
     */
    public function testUsesTraits(): void
    {
        if (!property_exists($this, 'usesTraits')) {
            throw new \PHPUnit_Framework_Exception(__TRAIT__ . ': ' . get_class($this)
                                                   . ' must define the property "usesTraits".');
        }

        if (!is_array($this->usesTraits)) {
            throw new \PHPUnit_Framework_Exception(
                __TRAIT__ . ': ' . get_class($this)
                . ': Property "usesTraits" must be an array.'
            );
        }

        $target = $this->getTargetInstance(
            ['getUsesTraitsTarget', 'getTarget'],
            ['usesTraitsTarget', 'target'],
            'usesTraits'
        );

        static::assertUsesTraits($this->usesTraits, $target);
    }
}
