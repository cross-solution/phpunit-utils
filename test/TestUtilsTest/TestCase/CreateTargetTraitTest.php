<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license MIT
 * @copyright 2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\TestCase;

use Cross\TestUtils\TestCase\CreateTargetTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests for \Cross\TestUtils\TestCase\CreateTargetTrait
 * 
 * @covers \Cross\TestUtils\TestCase\CreateTargetTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.CreateTargetTrait
 */
class CreateTargetTraitTest extends \PHPUnit_Framework_TestCase
{
    private function getConcreteTrait() : object
    {
        return new class
        {
            use CreateTargetTrait;

            public function prophesize($class)
            {
                return new class($class) extends ObjectProphecy
                {
                    public $name;
                    public $called;

                    public function __construct($class)
                    {
                        $this->name = $class;
                    }

                    public function __call($method, array $args)
                    {
                        $this->called[$method][] = $args;
                        return $this;
                    }

                    public function willBeConstructedWith(array $arguments = null)
                    {
                        $this->called[__FUNCTION__][] = $arguments;
                        return $this;
                    }

                    public function reveal()
                    {
                        $this->called[__FUNCTION__][] = [];
                        return $this;
                    }
                };
            }
        };
    }

    public function testCreateTarget()
    {
        $target = $this->getConcreteTrait();

        $actual = $target->createTarget(\stdClass::class);

        static::assertInstanceOf(\stdClass::class, $actual);

        /* @var \ArrayObject $actual */
        $value = ['test' => 'array'];
        $actual = $target->createTarget(\ArrayObject::class, $value, \ArrayObject::ARRAY_AS_PROPS);

        static::assertInstanceOf(\ArrayObject::class, $actual);
        static::assertEquals($value, iterator_to_array($actual));
        static::assertEquals(\ArrayObject::ARRAY_AS_PROPS, $actual->getFlags());
    }

    public function testCreateReflectionClass()
    {
        /* @var \ReflectionClass $actual */
        $target = $this->getConcreteTrait();
        $actual = $target->createTargetReflection(\stdClass::class);

        static::assertInstanceOf(\ReflectionClass::class, $actual);
        static::assertEquals(\stdClass::class, $actual->getName());
    }

    public function testCreateProphecyFromStringWithoutArguments()
    {
        $target = $this->getConcreteTrait();

        $actual = $target->createTargetProphecy(\stdClass::class);

        static::assertEquals(\stdClass::class, $actual->name);
        static::assertNull($actual->called);
    }

    public function testCreateProphecyFromArray()
    {
        $target = $this->getConcreteTrait();

        $args = ['one', 'two'];
        $actual = $target->createTargetProphecy(array_merge([\stdClass::class], $args));

        static::assertArrayHasKey('willBeConstructedWith', $actual->called);
        static::assertEquals($args, $actual->called['willBeConstructedWith'][0]);
    }

    public function testCreateProphecyWithMethodProphecies()
    {
        $target = $this->getConcreteTrait();

        $prophecies = [
            ['method1', 'willReturn' => true],
            ['method2' => ['one', 'two'], 'shouldBeCalled']
        ];

        $actual = $target->createTargetProphecy(\stdClass::class, $prophecies);

        $expect = [
            'method1' => [[]],
            'willReturn' => [[true]],
            'method2' => [['one', 'two']],
            'shouldBeCalled' => [[]],
        ];

        static::assertEquals($expect, $actual->called);
    }

    public function testCreateDouble()
    {
        $target = $this->getConcreteTrait();

        $actual = $target->createTargetDouble(\stdClass::class);

        static::assertArrayHasKey('reveal', $actual->called);
    }
}
