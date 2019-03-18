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

use Cross\TestUtils\TestCase\AssertUsesTraitsTrait;
use Cross\TestUtils\TestCase\GetTargetInstanceTrait;
use Cross\TestUtils\TestCase\TestUsesTraitsTrait;

/**
 * Tests for \Cross\TestUtils\TestCase\TestUsesTraitsTrait
 * 
 * @covers \Cross\TestUtils\TestCase\TestUsesTraitsTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.TestUsesTraitsTrait
 */
class TestUsesTraitsTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testUsesCorrectTraits()
    {
        $target = new \ReflectionClass(TestUsesTraitsTrait::class);

        static::assertEquals(
            [AssertUsesTraitsTrait::class, GetTargetInstanceTrait::class],
            $target->getTraitNames()
        );
    }

    public function testThrowsExceptionIfPropertyDoesNotExist()
    {
        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage('must define');

        $target = new class { use TestUsesTraitsTrait; };

        $target->testUsesTraits();
    }

    public function testThrowsExceptionIfPropertyIsNotAnArray()
    {
        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage('be an array');

        $target = new class { use TestUsesTraitsTrait; public $usesTraits = 'string'; };

        $target->testUsesTraits();
    }

    public function testCallsExpectedMethods()
    {
        $obj = new \stdClass;

        $target = new class($obj)
        {
            use TestUsesTraitsTrait;

            public function __construct($obj) {
                $this->target = $obj;
            }

            public $target;

            public $usesTraits = ['one', 'two'];

            public $getTargetInstanceArgs;

            public static $assertUsesTraitsArgs;

            public function getTargetInstance()
            {
                $this->getTargetInstanceArgs = func_get_args();
                return $this->target;
            }

            public static function assertUsesTraits()
            {
                static::$assertUsesTraitsArgs = func_get_args();
            }
        };


        $target->testUsesTraits();

        static::assertEquals(
            [
                ['getUsesTraitsTarget', 'getTarget'],
                ['usesTraitsTarget', 'target'],
                'usesTraits'
            ],
            $target->getTargetInstanceArgs
        );

        static::assertEquals(
            [
                $target->usesTraits,
                $obj,
            ],
            $target::$assertUsesTraitsArgs

        );


    }
}
