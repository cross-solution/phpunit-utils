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

use Cross\TestUtils\TestCase\AssertDefaultAttributesValuesTrait;
use Cross\TestUtils\TestCase\GetTargetInstanceTrait;
use Cross\TestUtils\TestCase\TestDefaultAttributesTrait;
use Cross\TestUtils\TestCase\TestUsesTraitsTrait;

/**
 * Tests for \Cross\TestUtils\TestCase\TestDefaultAttributesTrait
 * 
 * @covers \Cross\TestUtils\TestCase\TestDefaultAttributesTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.TestDefaultAttributesTrait
 */
class TestDefaultAttributesTraitTest extends \PHPUnit_Framework_TestCase
{
    use TestUsesTraitsTrait;

    private $usesTraits = [ 'target' => TestDefaultAttributesTrait::class, AssertDefaultAttributesValuesTrait::class, GetTargetInstanceTrait::class ];

    public function testThrowsExceptionIfPropertyDoesNotExist()
    {
        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage('must define');

        $target = new class { use TestDefaultAttributesTrait; };

        $target->testDefaultAttributes();
    }

    public function testThrowsExceptionIfPropertyIsNotAnArray()
    {
        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage('be an array');

        $target = new class { use TestDefaultAttributesTrait; public $defaultAttributes = 'string'; };

        $target->testDefaultAttributes();
    }

    public function testCallsExpectedMethods()
    {
        $obj = new \stdClass;

        $target = new class($obj)
        {
            use TestDefaultAttributesTrait;

            public function __construct($obj) {
                $this->target = $obj;
            }

            public $target;

            public $defaultAttributes = ['one', 'two'];

            public $getTargetInstanceArgs;

            public static $assertDefaultAttributesValuesArgs;

            public function getTargetInstance()
            {
                $this->getTargetInstanceArgs = func_get_args();
                return $this->target;
            }

            public static function assertDefaultAttributesValues()
            {
                static::$assertDefaultAttributesValuesArgs = func_get_args();
            }
        };


        $target->testDefaultAttributes();

        static::assertEquals(
            [
                ['getDefaultAttributesTarget', 'getTarget'],
                ['defaultAttributesTarget', 'target'],
                'defaultAttributes'
            ],
            $target->getTargetInstanceArgs
        );

        static::assertEquals(
            [
                $target->defaultAttributes,
                $obj,
            ],
            $target::$assertDefaultAttributesValuesArgs

        );


    }
}
