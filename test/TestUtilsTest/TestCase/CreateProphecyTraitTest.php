<?php

/**
 * CROSS PHPUnit Utils
 *
 * @see       https://github.com/cross-solution/phpunit-utils for the canonical source repository
 * @copyright https://github.com/cross-solution/phpunit-utils/blob/master/COPYRIGHT
 * @license   https://github.com/cross-solution/phpunit-utils/blob/master/LICENSE MIT
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\TestCase;

use Cross\TestUtils\Exception\InvalidUsageException;
use Cross\TestUtils\TestCase\CreateProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests for \Cross\TestUtils\TestCase\CreateProphecyTrait
 *
 * @covers \Cross\TestUtils\TestCase\CreateProphecyTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.CreateProphecyTrait
 */
class CreateProphecyTraitTest extends TestCase
{
    private $target;

    protected function setUp(): void
    {
        $this->target = new class
        {
            use CreateProphecyTrait;

            public $prophecy;

            public function prophesize($class)
            {
                $this->prophecy = new class($class) extends ObjectProphecy
                {
                    public $class;
                    public $called = [];

                    public function __construct($class)
                    {
                        $this->class = $class;
                    }

                    public function __call($method, $args)
                    {
                        $this->called[$method][] = $args;
                        return $this;
                    }

                    public function willExtend($class)
                    {
                        return $this->__call(__FUNCTION__, [$class]);
                    }

                    public function willImplement($interface)
                    {
                        return $this->__call(__FUNCTION__, [$interface]);
                    }

                    public function willBeConstructedWith(
                        array $arguments = null
                    ) {
                        return $this->__call(__FUNCTION__, [$arguments]);
                    }

                    public function reveal()
                    {
                        return $this->__call(__FUNCTION__, []);
                    }
                };

                return $this->prophecy;
            }
        };
    }

    public function testThrowsExceptionIfInvalidSpecificationIsPassed()
    {
        $this->expectException(InvalidUsageException::class);
        $this->expectExceptionMessage('Expected string or array');

        $this->target->createProphecy(new \stdClass);
    }

    public function testThrowsExceptionIfFqcnNotFound()
    {
        $this->expectException(InvalidUsageException::class);
        $this->expectExceptionMessage('No FQCN found');

        $this->target->createProphecy([]);
    }

    public function testCreateSimpleProphecy()
    {
        $prophecy = $this->target->createProphecy(\stdClass::class);

        static::assertEquals(\stdClass::class, $prophecy->class);
    }

    public function testCreateProphecyFromSimpleArray()
    {
        $prophecy = $this->target->createProphecy([\stdClass::class, 'arg1']);

        static::assertArrayHasKey('willBeConstructedWith', $prophecy->called);
        static::assertEquals([[1 => 'arg1']], $prophecy->called['willBeConstructedWith'][0]);
    }

    public function testCreateProphecyFromArrayWithArguments()
    {
        $prophecy = $this->target->createProphecy([\stdClass::class, 'arguments' => ['arg1']]);

        static::assertArrayHasKey('willBeConstructedWith', $prophecy->called);
        static::assertEquals([['arg1']], $prophecy->called['willBeConstructedWith'][0]);
    }

    public function testCreateProphecyThatExtendsAndImplements()
    {
        $prophecy = $this->target->createProphecy([
            \stdClass::class,
            'extends' => \ArrayObject::class,
            'implements' => [\Serializable::class, \Countable::class]
        ]);

        static::assertArrayHasKey('willExtend', $prophecy->called);
        static::assertArrayHasKey('willImplement', $prophecy->called);
        static::assertEquals([\ArrayObject::class], $prophecy->called['willExtend'][0]);
        static::assertEquals([[\Serializable::class], [\Countable::class]], $prophecy->called['willImplement']);
    }

    public function testCreateProphecyWithMethodPromises()
    {
        $prophecies = [
            ['method1', 'willReturn' => 'test'],
            ['method2', 'other' => ['arg1', 'arg2']]
        ];

        $prophecy = $this->target->createProphecy(\stdClass::class, $prophecies);

        static::assertArrayHasKey('method1', $prophecy->called);
        static::assertArrayHasKey('willReturn', $prophecy->called);
        static::assertEquals(['test'], $prophecy->called['willReturn'][0]);

        static::assertArrayHasKey('method2', $prophecy->called);
        static::assertArrayHasKey('other', $prophecy->called);
        static::assertEquals(['arg1', 'arg2'], $prophecy->called['other'][0]);
    }

    public function testCreateDouble()
    {
        $prophecy = $this->target->createDouble(\stdClass::class);

        static::assertArrayHasKey('reveal', $prophecy->called);
    }
}
