<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license    MIT
 * @copyright  2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

use Cross\TestUtils\Exception\InvalidUsageException;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Creates a service manager prophecy or double with configured services.
 *
 * @method \Prophecy\Prophecy\ObjectProphecy prophesize(string $classOrInterface)
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait ContainerDoubleTrait
{
    /**
     * Prohesizes a container interface.
     *
     * Pass in services as iterable:
     * <pre>
     * [
     *      'name' => service,
     *      'name' => [
     *           'service' => service,
     *           'count_get' => int,
     *           'count_has' => int,
     *      ],
     *      'name' => [service{, count_get{, count_has}}],
     * ]
     * </pre>
     *
     * Passing the boolean value 'false' as service will cause the following:
     * - Calling has(service) will return false.
     * - Calling get(service) will throw an exception.
     *
     * @param iterable $services
     *
     * @return ObjectProphecy
     */
    public function createContainerProphecy(iterable $services = []): ObjectProphecy
    {
        if (!interface_exists(\Psr\Container\ContainerInterface::class)) {
            throw InvalidUsageException::fromTrait(
                __TRAIT__,
                get_class($this),
                'Cannot create container double. Interface %s does not exist.',
                \Psr\Container\ContainerInterface::class
            );
        }

        $container = $this->prophesize(\Psr\Container\ContainerInterface::class);

        foreach ($services as $name => $spec) {
            if (!is_array($spec)) {
                $spec = ['service' => $spec];
            }

            $countGet = $spec['count_get'] ?? $spec[1] ?? 0;
            $countHas = $spec['count_has'] ?? $spec[2] ?? 0;
            $service  = $spec['service'] ?? $spec[0] ?? null;

            /** @var \Prophecy\Prophecy\MethodProphecy $method */
            $method = $container->get($name);

            if (false === $service) {
                $ex = $this->prophesize(\Psr\Container\NotFoundExceptionInterface::class)->reveal();
                $method->willThrow($ex);
            } else {
                $method->willReturn($service);
            }

            if ($countGet) {
                $method->shouldBeCalledTimes($countGet);
            }

            $method = $container->has($name);
            $method->willReturn(false !== $service);

            if ($countHas) {
                $method->shouldBeCalledTimes($countHas);
            }
        }

        return $container;
    }

    /**
     * Creates a container interface double.
     *
     * @param iterable $services The services the container should provide
     *                           see {@link createContainerProphecy()}
     *
     * @return object The revealed container double
     */
    public function createContainerDouble(iterable $services = []): object
    {
        return $this->createContainerProphecy($services)->reveal();
    }
}
