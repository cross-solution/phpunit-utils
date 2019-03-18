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

/**
 * Creates a service manager double with configured services.
 *
 * @method \Prophecy\Prophecy\ObjectProphecy prophesize(string $classOrInterface)
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait ContainerDoubleTrait
{
    /**
     * Prohesizes a container interface double.
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
     * @param iterable $services
     *
     * @return object
     */
    public function createContainerDouble(iterable $services = []): object
    {
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        $container = $this->prophesize(\Psr\Container\ContainerInterface::class);

        foreach ($services as $name => $spec) {
            if (!is_array($spec)) {
                $container->get($name)->willReturn($spec);
                $container->has($name)->willReturn(true);
                continue;
            }

            $countGet = $spec['count_get'] ?? $spec[1] ?? 0;
            $countHas = $spec['count_has'] ?? $spec[2] ?? 0;
            $service  = $spec['service'] ?? $spec[0] ?? null;

            /** @var \Prophecy\Prophecy\MethodProphecy $method */
            $method = $container->get($name);
            $method->willReturn($service);

            if ($countGet) {
                $method->shouldBeCalledTimes($countGet);
            }

            $method = $container->has($name);
            $method->willReturn(true);

            if ($countHas) {
                $method->shouldBeCalledTimes($countHas);
            }
        }

        return $container->reveal();
    }
}
