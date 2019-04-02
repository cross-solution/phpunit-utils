<?php
/**
 * CROSS PHPunit Utils
 *
 * @filesource
 * @copyright 2019 Cross Solution <https://www.cross-solution.de>
 * @license MIT
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\Utils;

use Cross\TestUtils\Utils\Target;

/**
 * Tests for \Cross\TestUtils\Utils\Target
 *
 * @covers \Cross\TestUtils\Utils\Target
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.Utils
 * @group Cross.TestUtils.Utils.Target
 */
class TargetTest extends \PHPUnit_Framework_TestCase
{

    public function testGetTargetInstanceFromMethod()
    {
        $target = new class
        {
            public function getTestTarget() { return 'TestTarget'; }
        };

        $actual = Target::get($target, ['getTestTarget'], ['testTarget']);

        static::assertEquals('TestTarget', $actual);
    }

    public function testGetTargetInstanceFromProperty()
    {
        $target = new class
        {
            public $testTarget = 'TestTarget';
        };

        $actual = Target::get($target, ['getTestTarget'], ['testTarget']);

        static::assertEquals('TestTarget', $actual);
    }

    public function testGetTargetInstanceFromClassesPropertyTargetKey()
    {
        $target = new class
        {
            public $classes = ['target' => 'TestTarget', 'other', 'values'];
        };

        $actual = Target::get($target, [], [], 'classes');

        static::assertEquals('TestTarget', $actual);
        static::assertEquals( ['other', 'values'], $target->classes, 'Target specification was not unset!');
    }

    public function testGetTargetInstanceFromClassesPropertyFirstItem()
    {
        $target = new class
        {
            public $classes = ['TestTarget', 'other', 'values'];
            public function test()
            {
                return $this->getTargetInstance([], [], 'classes');
            }
        };

        $actual = Target::get($target, [], [], 'classes');

        static::assertEquals('TestTarget', $actual);
        static::assertEquals( ['other', 'values'], $target->classes, 'Target specification was not unset!');
    }

    public function testExceptionIsThrownIfNoTargetWasFound()
    {
        $target = new class {};

        $this->expectException(\PHPUnit_Framework_Exception::class);
        $this->expectExceptionMessage('find or create');

        Target::get($target, [], []);
    }

    public function testGetTargetInstanceReflection()
    {
        $target = new class
        {
            public $testTarget = '!\stdClass';
        };

        $actual = Target::get($target, ['getTestTarget'], ['testTarget']);

        static::assertInstanceOf(\ReflectionClass::class, $actual);
    }

    public function testGetTargetInstanceFromArraySpec()
    {
        $classObj = new class('') { public $arg; public function __construct($arg) { $this->arg = $arg;}};
        $class = get_class($classObj);

        $target = new class($class)
        {
            public $class;
            public function getTestTarget() { return [$this->class, 'arg']; }
            public function __construct($class) {
                $this->class = $class;
            }
        };

        $actual = Target::get($target, ['getTestTarget'], ['testTarget']);

        static::assertInstanceOf($class, $actual);
    }

    public function testGetTargetIfAlreadyObject()
    {
        $object = new \stdClass;
        $target = new class
        {
            public $object;
            public function getTarget()
            {
                return $this->object;
            }
        };
        $target->object = $object;

        $actual = Target::get($target, ['getTarget'], []);

        static::assertSame($object, $actual);
    }

    public function testGetTargetInstanceForcedObject()
    {
        $target = new class
        {
            public $testTarget = \stdClass::class;
        };

        $actual = Target::get($target, ['getTestTarget'], ['testTarget'], '', true);

        static::assertInstanceOf(\stdClass::class, $actual);
    }
}
