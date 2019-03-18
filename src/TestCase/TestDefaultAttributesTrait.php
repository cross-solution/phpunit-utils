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
 */
trait TestDefaultAttributesTrait
{
    use AssertDefaultAttributesValuesTrait;
    use GetTargetInstanceTrait;

    /**
     * @testdox Defines correct default attribute values.
     * @coversNothing
     */
    public function testDefaultAttributes(): void
    {
        $errTmpl = __TRAIT__ . ': ' . get_class($this);

        if (!property_exists($this, 'defaultAttributes')) {
            throw new \PHPUnit_Framework_Exception($errTmpl . ' must define the property "defaultAttributes".');
        }

        if (!is_array($this->defaultAttributes)) {
            throw new \PHPUnit_Framework_Exception($errTmpl . ': Property "defaultAttributes" must be an array');
        }

        $target = $this->getTargetInstance(
            ['getDefaultAttributesTarget', 'getTarget'],
            ['defaultAttributesTarget', 'target'],
            'defaultAttributes'
        );

        static::assertDefaultAttributesValues($this->defaultAttributes, $target);
    }
}
