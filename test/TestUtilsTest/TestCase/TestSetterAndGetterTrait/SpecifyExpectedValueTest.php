<?php

/**
 * phpunit-utils
 *
 * @filesource
 * @copyright 2020 CROSS Solution <https://www.cross-solution.de>
 * @license MIT
 */

declare(strict_types=1);
namespace Cross\TestUtilsTest\TestCase\TestSetterAndGetterTrait;

use PHPUnit\Framework\TestCase;
use Cross\TestUtils\TestCase\TestSetterAndGetterTrait;

/**
 * Testcase for \Cross\TestUtils\TestCase\TestSetterAndGetterTrait
 *
 * @covers \Cross\TestUtils\TestCase\TestSetterAndGetterTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.TestSetterAndGetterTrait
 */
class SpecifyExpectedValueTest extends TestCase
{
    public function setUp(): void
    {
        $dummy = new class
        {
            public $attr;
            public $expect = 'expected';

            public function setAttr($v)
            {
                $this->attr = $v;
                return $this->expect;
            }

            public function getAttr()
            {
                return $this->expect;
            }
        };

        $this->target = new class($dummy)
        {
            use TestSetterAndGetterTrait;

            public $target;
            public $testSetterAndGetter;
            public static $result;

            public function __construct($dummy)
            {
                $this->target = $dummy;
                static::$result = null;
            }

            public static function assertEquals($expect)
            {
                static::$result = $expect;
            }

            public static function assertSame($expect)
            {
                static::$result = $expect;
            }
        };
    }

    /**
     * @testWith    [true, "a string"]
     *              [true, [1,2,3]]
     *              [true, "stdClass", "object"]
     *
     */
    public function testSpecifyingExpectedGetterValues($expect, $value, $type = null)
    {
        $this->target->target->expect = $expect;
        $this->target->testSetterAndGetter(
            'attr',
            ['value' . ($type ? "_$type" : '') => $value, 'expect' => $expect]
        );

        static::assertEquals($expect, $this->target::$result);
    }

    /**
     * @testWith    [true]
     */
    public function testSpecifiyingExpectedSetterValues($expect, $type = null)
    {
        if ($type === 'object') {
            $expect = new $expect();
        }
        $this->target->target->expect = $expect;
        $this->target->testSetterAndGetter(
            'attr',
            ['getter' => false, 'value' => 'irrelevant', 'setter_value' => $expect]
        );

        static::assertEquals($expect, $this->target::$result);
    }
}
