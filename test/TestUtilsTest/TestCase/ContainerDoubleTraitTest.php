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
use Cross\TestUtils\TestCase\ContainerDoubleTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use phpmock\prophecy\PHPProphet;

/**
 * Tests for \Cross\TestUtils\TestCase\ContainerDoubleTrait
 *
 * @covers \Cross\TestUtils\TestCase\ContainerDoubleTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.ContainerDoubleTrait
 */
class ContainerDoubleTraitTest extends TestCase
{

    public function servicesProvider()
    {
        return [
            [
                [],
                ['reveal' => [[]]]
            ],
            [
                [
                    'name' => 'service'
                ],
                [
                    'get' => [['name']],
                    'willReturn' => [['service'], [true]],
                    'has' => [['name']],
                    'reveal' => [[]],
                ],
            ],
            [
                [
                    'name' => []
                ],
                [
                    'get' => [['name']],
                    'willReturn' => [[null], [true]],
                    'has' => [['name']],
                    'reveal' => [[]],
                ],
            ],
            [
                [
                    'name' => ['service' => 'service', 'count_get' => 2, 'count_has' => 3],
                ],
                [
                    'get' => [['name']],
                    'willReturn' => [['service'], [true]],
                    'shouldBeCalledTimes' => [[2], [3]],
                    'has' => [['name']],
                    'reveal' => [[]],
                ]
            ],
            [
                [
                    'name' => ['service', 2],
                ],
                [
                    'get' => [['name']],
                    'willReturn' => [['service'], [true]],
                    'shouldBeCalledTimes' => [[2]],
                    'has' => [['name']],
                    'reveal' => [[]]
                ]
            ],
            [
                [
                    'name' => ['service', 2, 4],
                ],
                [
                    'get' => [['name']],
                    'willReturn' => [['service'], [true]],
                    'shouldBeCalledTimes' => [[2], [4]],
                    'has' => [['name']],
                    'reveal' => [[]]
                ]
            ],
            [
                [
                    'name' => false,
                ],
                [
                    'get' => [['name']],
                    'willThrow' => [['ExceptionMock']],
                    'reveal' => [[]],
                    'has' => [['name']],
                    'willReturn' => [[false]],
                ],
            ],
        ];
    }

    private function getConcreteTraitClass()
    {
        return new class
        {
            use ContainerDoubleTrait;

            public function prophesize($class)
            {

                return new class($class) extends \Prophecy\Prophecy\ObjectProphecy
                {
                    public $name;
                    public $calls = [];

                    public function __construct($class)
                    {
                        $this->name = $class;
                    }

                    public function __call($method, $args)
                    {
                        $this->calls[$method][] = $args;

                        return $this;
                    }

                    public function reveal()
                    {
                        if (\Psr\Container\NotFoundExceptionInterface::class == $this->name) {
                            return 'ExceptionMock';
                        }

                        $this->calls['reveal'][] = [];

                        return $this;
                    }

                    public function willBeConstructedWith(array $args = null)
                    {
                        $this->__call(__FUNCTION__, [$args]);
                    }

                    public function willImplement($interface)
                    {
                        $this->__call(__FUNCTION__, [$interface]);
                    }
                };
            }
        };

    }

    /**
     * @dataProvider servicesProvider
     *
     * @param array $services
     * @param array $expect
     */
    public function testCreatesContainerDouble(array $services, array $expect)
    {
        $target = $this->getConcreteTraitClass();
        $actual = $target->createContainerDouble($services);

        static::assertEquals(\Psr\Container\ContainerInterface::class, $actual->name);
        static::assertEquals($expect, $actual->calls);
    }

    public function servicesOptionsProvider()
    {
        $target     = new class {};
        $targetFqcn = get_class($target);
        return [
            [
                [],
                ['target' => $targetFqcn],
                [],
            ],
            [
                [],
                ['implements' => 'SomeInterface'],
                [['willImplement', ['SomeInterface']]]
            ],
            [
                [],
                ['implements' => ['SomeInterface', 'Other']],
                [['willImplement', ['SomeInterface'], ['willIpmlement', ['Other']]]],
            ],
            [
                [],
                ['arguments' => ['arg1', 'arg2']],
                [['willBeConstructedWith', [['arg1', 'arg2']]]],
            ],
            [
                [
                    'name' => [
                        'service',
                        'args_get' => ['arg1', 'arg2'],
                        'args_has' => ['has1', 'has2'],
                    ]
                ],
                [],
                [
                    ['get', ['name', 'arg1', 'arg2']],
                    ['has', ['name', 'has1', 'has2']],
                ],
            ],
            // global options
            [
                [
                    'name' => [
                        'service',
                    ]
                ],
                [
                    'args_get' => ['global1', 'global2'],
                    'args_has' => ['hasglobal1', 'hasglobal2'],
                ],
                [
                    ['get', ['name', 'global1', 'global2']],
                    ['has', ['name', 'hasglobal1', 'hasglobal2']],
                ],
            ],
            // global options override
            [
                [
                    'name' => [
                        'service',
                        'args_get' => ['arg1', 'arg2'],
                        'args_has' => ['has1', 'has2'],
                    ]
                ],
                [
                    'args_get' => ['global1', 'global2']
                ],
                [
                    ['get', ['name', 'arg1', 'arg2']],
                    ['has', ['name', 'has1', 'has2']],
                ],
            ],
            // promises
            [
                [
                    'name' => [
                        'service',
                        'promise' => 'will'
                    ]
                ],
                [],
                [
                    ['will', ['service']]
                ]
            ],
            // global promise
            [
                [
                    'name' => [
                        'service',
                    ]
                ],
                ['promise' => 'will'],
                [
                    ['will', ['service']]
                ]
            ],
            [
                [
                    'name' => [
                        'service',
                        'promise' => 'will'
                    ]
                ],
                ['promise' => 'willReturn'],
                [
                    ['will', ['service']]
                ]
            ],

        ];
    }

    /**
     * @dataProvider servicesOptionsProvider
     * @param  array  $services
     * @param  array  $options
     * @param  array  $expect
     * @return void
     */
    public function testCreatesContainerProphecyWithOptions(array $services, array $options, array $expect)
    {
        $target = $this->getConcreteTraitClass();
        $actual = $target->createContainerProphecy($services, $options);

        static::assertEquals($options['target'] ?? \Psr\Container\ContainerInterface::class, $actual->name);
        foreach ($expect as $spec) {
            $method = $spec[0];
            static::assertArrayHasKey($method, $actual->calls);
            $actualArgs = $actual->calls[$method];
            static::assertContains($spec[1], $actualArgs);
        }
    }

    public function testThrowsExceptionIfInterfaceOrClassIsNotDefined()
    {

        /** @var \phpmock\prophecy\FunctionProphecy $func */
        $func = (new PHPProphet)->prophesize('Cross\TestUtils\TestCase');
        $func->interface_exists(Argument::any())->willReturn(false);
        $func->class_exists(Argument::any())->willReturn(false);
        $func->reveal();

        $this->expectException(InvalidUsageException::class);

        $target = new class {
            use ContainerDoubleTrait;
        };

        $target->createContainerProphecy();
    }
}
