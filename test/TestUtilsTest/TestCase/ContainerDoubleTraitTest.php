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

use Cross\TestUtils\TestCase\ContainerDoubleTrait;

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
class ContainerDoubleTraitTest extends \PHPUnit_Framework_TestCase
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
        ];
    }

    /**
     * @dataProvider servicesProvider
     *
     * @param array $services
     * @param array $expect
     */
    public function testCreatesContainerDouble(array $services, array $expect)
    {
        $target = new class
        {
            use ContainerDoubleTrait;

            public function prophesize($class)
            {
                return new class($class)
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
                };
            }
        };

        $actual = $target->createContainerDouble($services);

        /** @noinspection PhpUndefinedNamespaceInspection */
        /** @noinspection PhpUndefinedClassInspection */
        static::assertEquals(\Psr\Container\ContainerInterface::class, $actual->name);
        static::assertEquals($expect, $actual->calls);
    }
}
