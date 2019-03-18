<?php
/**
 * Cross PHPUnit Utils
 *
 * @filesource
 * @license MIT
 * @copyright  2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\TestCase;

use Cross\TestUtils\TestCase\GetTargetInstanceTrait;

/**
 * Tests for \Cross\TestUtils\TestCase\GetTargetInstanceTrait
 * 
 * @covers \Cross\TestUtils\TestCase\GetTargetInstanceTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.GetTargetInstanceTrait
 */
class GetTargetInstanceTraitTest extends \PHPUnit_Framework_TestCase
{

    public function testGetTargetInstanceFromMethod()
    {
        $target = new class
        {
            use GetTargetInstanceTrait;
            public function getTestTarget() { return 'TestTarget'; }
            public function test()
            {
                return $this->getTargetInstance(['getTestTarget'], ['testTarget']);
            }
        };

        $actual = $target->test();

        static::assertEquals('TestTarget', $actual);
    }

    public function testGetTargetInstanceFromProperty()
    {
        $target = new class
        {
            use GetTargetInstanceTrait;
            public $testTarget = 'TestTarget';
            public function test()
            {
                return $this->getTargetInstance(['getTestTarget'], ['testTarget']);
            }
        };

        $actual = $target->test();

        static::assertEquals('TestTarget', $actual);
    }

    public function testGetTargetInstanceFromClassesPropertyTargetKey()
    {
        $target = new class
        {
            use GetTargetInstanceTrait;
            public $classes = ['target' => 'TestTarget', 'other', 'values'];
            public function test()
            {
                return $this->getTargetInstance([], [], 'classes');
            }
        };

        $actual = $target->test();

        static::assertEquals('TestTarget', $actual);
        static::assertEquals( ['other', 'values'], $target->classes, 'Target specification was not unset!');
    }

    public function testGetTargetInstanceFromClassesPropertyFirstItem()
    {
        $target = new class
        {
            use GetTargetInstanceTrait;
            public $classes = ['TestTarget', 'other', 'values'];
            public function test()
            {
                return $this->getTargetInstance([], [], 'classes');
            }
        };

        $actual = $target->test();

        static::assertEquals('TestTarget', $actual);
        static::assertEquals( ['other', 'values'], $target->classes, 'Target specification was not unset!');
    }

    public function testExceptionIsThrownIfNoTargetWasFound()
    {
        $target = new class { use GetTargetInstanceTrait; public function test() { return $this->getTargetInstance([],[]);}};

        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage('find or create');

        $target->test();
    }

    public function testGetTargetInstanceReflection()
    {
        $target = new class
        {
            use GetTargetInstanceTrait;
            public $testTarget = '!\stdClass';
            public function test()
            {
                return $this->getTargetInstance(['getTestTarget'], ['testTarget']);
            }
        };

        $actual = $target->test();

        static::assertInstanceOf(\ReflectionClass::class, $actual);
    }

    public function testGetTargetInstanceFromArraySpec()
    {
        $classObj = new class('') { public $arg; public function __construct($arg) { $this->arg = $arg;}};
        $class = get_class($classObj);

        $target = new class($class)
        {
            use GetTargetInstanceTrait;
            public $class;
            public function getTestTarget() { return [$this->class, 'arg']; }
            public function __construct($class) {
                $this->class = $class;
            }
            public function test()
            {
                return $this->getTargetInstance(['getTestTarget'], ['testTarget']);
            }
        };

        $actual = $target->test();

        static::assertInstanceOf($class, $actual);
    }

    public function testGetTargetInstanceForcedObject()
    {
        $target = new class
        {
            use GetTargetInstanceTrait;
            public $testTarget = \stdClass::class;
            public function test()
            {
                return $this->getTargetInstance(['getTestTarget'], ['testTarget'], '', true);
            }
        };

        $actual = $target->test();

        static::assertInstanceOf(\stdClass::class, $actual);
    }
}
