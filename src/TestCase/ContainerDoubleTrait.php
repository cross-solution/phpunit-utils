<?php

/**
 * CROSS PHPUnit Utils
 *
 * @see       https://github.com/cross-solution/phpunit-utils for the canonical source repository
 * @copyright https://github.com/cross-solution/phpunit-utils/blob/master/COPYRIGHT
 * @license   https://github.com/cross-solution/phpunit-utils/blob/master/LICENSE MIT
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

use Cross\TestUtils\Exception\InvalidUsageException;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Creates a service container prophecy or double with configured services.
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
     * ```
     * [
     *      // short
     *      'name' => service,
     *
     *      // verbose style
     *      'name' => [
     *           'service' => service,
     *           'args_get' => array: Additional arguments the get
     *                                method is called with
     *           'args_has' => array: Additional arguments the has
     *                                method is called with
     *           'count_get' => int,
     *           'count_has' => int,
     *           'promise' => string: The promise method for the
     *                                prophecy
     *                                * willReturn (default)
     *                                * willThrow
     *                                * will
     *                                * _etc._
     *      ],
     *
     *      // compact style
     *      'name' => [service{, count_get{, count_has}}],
     *
     *      // mixed (example)
     *      'name' => [service, count_get, 'args_get' => ['arg'], count_has]
     * ]
     * ```
     *
     * Passing the boolean value _false_ as service will cause the following:
     * * Calling has(service) will return false.
     * * Calling get(service) will throw an exception.
     *
     * ### Options
     * ```
     * [
     *      'target' => FQCN of the Container class
     *                  default: \Psr\Container\ContainerInterface
     *
     *      'arguments' => array of constructor arguments for the container
     *      'implements' => array of interfaces the container
     *                      should implement
     *      'args_get' => default arguments for the get method
     *                    for each service
     *      'args_has' => default arguments for the has method
     *                    for each service
     *      'promise' => default promise method for each service
     * ]
     * ```
     * @param iterable $services
     * @param array    $options
     *
     * @return ObjectProphecy
     */
    public function createContainerProphecy(iterable $services = [], array $options = []): ObjectProphecy
    {
        $target = $options['target'] ?? \Psr\Container\ContainerInterface::class;

        if (!interface_exists($target) && !class_exists($target)) {
            throw InvalidUsageException::fromTrait(
                __TRAIT__,
                __CLASS__,
                'Cannot create container double. Interface or class %s does not exist.',
                $target
            );
        }

        $container = $this->prophesize($target);

        if (isset($options['arguments'])) {
            $container->willBeConstructedWith($options['arguments']);
        }

        if (isset($options['implements'])) {
            foreach ((array) $options['implements'] as $interface) {
                $container->willImplement($interface);
            }
        }

        foreach ($services as $name => $spec) {
            if (!is_array($spec)) {
                $spec = ['service' => $spec];
            }

            $service = $spec['service'] ?? $spec[0] ?? null;
            $count   = $spec['count_get'] ?? $spec[1] ?? $options['count_get'] ?? 0;
            $args    = $spec['args_get'] ?? $options['args_get'] ?? [];

            /** @var \Prophecy\Prophecy\MethodProphecy $method */
            $method = $container->get($name, ...$args);

            if (false === $service) {
                $ex = $this->prophesize(\Psr\Container\NotFoundExceptionInterface::class)->reveal();
                $method->willThrow($ex);
            } else {
                $promise = $spec['promise'] ?? $options['promise'] ?? 'willReturn';
                $method->$promise($service);
            }

            if ($count) {
                $method->shouldBeCalledTimes($count);
            }

            $args   = $spec['args_has'] ?? $options['args_has'] ?? [];
            $count  = $spec['count_has'] ?? $spec[2] ?? $options['count_has'] ?? 0;
            $method = $container->has($name, ...$args);

            $method->willReturn(false !== $service);

            if ($count) {
                $method->shouldBeCalledTimes($count);
            }
        }

        return $container;
    }

    /**
     * Creates a container interface double.
     * see {@link createContainerProphecy()}
     *
     * @param iterable $services
     * @param array $options
     *
     * @see createContainerProphecy()
     * @return object The revealed container double
     */
    public function createContainerDouble(iterable $services = [], array $options = []): object
    {
        return $this->createContainerProphecy($services, $options)->reveal();
    }
}
