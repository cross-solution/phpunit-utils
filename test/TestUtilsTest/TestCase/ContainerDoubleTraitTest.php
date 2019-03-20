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

use Cross\TestUtils\Exception\InvalidUsageException;

use Cross\TestUtils\TestCase\ContainerDoubleTrait;

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

    /**
     * @dataProvider servicesProvider
     *
     * @param array $services
     * @param array $expect
     */
    public function testCreatesContainerDouble(array $services, array $expect)
    {
        $scope = $this;
        $target = new class
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
                };
            }
        };

        $actual = $target->createContainerDouble($services);

        static::assertEquals(\Psr\Container\ContainerInterface::class, $actual->name);
        static::assertEquals($expect, $actual->calls);
    }

    public function testThrowsExceptionIfInterfaceIsNotDefined()
    {

        $func = (new PHPProphet)->prophesize('Cross\TestUtils\TestCase');
        $func->interface_exists(Argument::any())->willReturn(false);
        $func->reveal();

        $this->expectException(InvalidUsageException::class);

        $target = new class {
            use ContainerDoubleTrait;
        };

        $target->createContainerProphecy();

    }
}
