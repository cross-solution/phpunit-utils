<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license MIT
 * @copyright  2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

use Cross\TestUtils\Exception\InvalidUsageException;
use Cross\TestUtils\Utils\Target;

/**
 * Tests the target for default attribute values.
 *
 * Define the property $defaultAttributes as an array of propertyName => value pairs.
 * The first entry (propertyName = 0) will be used as the SUT.
 *
 * The SUT can also be provided via the methods 'getDefaultAttributesTarget' or 'getTarget' or by properties
 * '$defaultAttributesTarget' or '$target'.
 *
 * @property array $defaultAttributes
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @deprecated Testing internal object state is bad practice.
 *             (see: https://thephp.cc/news/2019/02/help-my-tests-stopped-working#assertions-and-non-public-attributes)
 */
trait TestDefaultAttributesTrait
{
    use AssertDefaultAttributesValuesTrait;

    /**
     * @testdox Defines correct default attribute values.
     * @coversNothing
     */
    public function testDefaultAttributes(): void
    {
        if (!property_exists($this, 'defaultAttributes') || !is_array($this->defaultAttributes)) {
            throw InvalidUsageException::fromTrait(
                __TRAIT__,
                __CLASS__,
                'The property "$defaultAttributes" is not defined or not an array.'
            );
        }

        $target = Target::get(
            $this,
            ['getDefaultAttributesTarget', 'getTarget'],
            ['defaultAttributesTarget', 'target'],
            'defaultAttributes'
        );

        static::assertDefaultAttributesValues($this->defaultAttributes, $target);
    }
}
