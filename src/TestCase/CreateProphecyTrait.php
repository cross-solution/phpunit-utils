<?php
/**
 * CROSS PHPunit Utils
 *
 * @filesource
 * @copyright 2019 Cross Solution <https://www.cross-solution.de>
 * @license MIT
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

use Cross\TestUtils\Exception\InvalidUsageException;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Creates object prophecies or doubles.
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @todo write tests
 */
trait CreateProphecyTrait
{

    /**
     * Creates an object prohpecy object.
     *
     * $class can be a string, or an array where the first item is the class name and all
     * successive items are constructor arguments.
     *
     * $class can also be an array using a more verbose style:
     * <code>
     * [
     *  'class' => FQCN, // override FQCN given at key 0
     *  'implements' => [InterfaceFQCN, ...],
     *  'extends'   => FQCN,
     *  'arguments' => [argument, ...] // override arguments given without keys
     * ]
     * </code>
     *
     * you can mix:
     * <code>
     * [
     *  FQCN::class, argument, 'implements' => [Interface::class]
     * ]
     * </code>
     *
     * NOTE: If arguments are given without keys, it is assumed that the FQCN is given at the key 0 -
     *      which is ignored when gathering the arguments. So
     *      ['class' => FQCN, argument1, argument2] will only take argument2 as argument.
     *
     * $prophecies is an array of method prophecy specifications:
     * [
     *     [
     *          methodName,                 // Call method without arguments.
     *          methodName => [ ...args ]   // Call method with arguments args.
     *          methodName => arg           // Single argument must not be wrapped in array.
     *          methodName => [[subArg,..]] // Passing an array: Must be wrapped in an array.
     *     ]
     * ]
     *
     * Please note: method calls in the prophecy specification are chained - the succcessive method
     * will be called on the returned value from the method before. The first method is called on the object prophecy.
     *
     * <example>
     * createProphecy(
     *      [subjectUnderTest::class, argument1, argument2],
     *      [
     *          [ 'methodToTest' => ['arg1'], 'willReturn' => true, 'shouldBeCalled' ]
     *      ]
     * );
     * </example>
     *
     * @param string|array $class
     * @param array        $prophecies
     *
     * @return ObjectProphecy
     */
    public function createProphecy($class, array $prophecies = []): ObjectProphecy
    {
        [$class, $arguments, $extends, $implements] = $this->createProphecyParseOptions($class);

        /** @var ObjectProphecy $prophecy */
        $prophecy = $this->prophesize($class);

        if ($arguments) {
            $prophecy->willBeConstructedWith($arguments);
        }

        if ($extends) {
            $prophecy->willExtend($extends);
        }

        if ($implements) {
            foreach ($implements as $interface) {
                $prophecy->willImplement($interface);
            }
        }

        foreach ($prophecies as $methodProphecy) {
            $methodMock = $prophecy;
            foreach ($methodProphecy as $methodName => $methodArgs) {
                if (is_numeric($methodName)) {
                    $methodMock = $methodMock->$methodArgs();
                    continue;
                }

                if (!is_array($methodArgs)) {
                    $methodArgs = [$methodArgs];
                }

                $methodMock = $methodMock->$methodName(...$methodArgs);
            }
        }

        return $prophecy;
    }

    /**
     * Creates a double.
     *
     * @see createProphecy()
     *
     * @param  string|array $class
     * @param  array $prophecies
     *
     * @return object
     */
    public function createDouble($class, array $prophecies = []): object
    {
        return $this->createProphecy($class, $prophecies)->reveal();
    }

    /**
     * Normalizes specification.
     *
     * @param  string|array $spec
     *
     * @return array
     */
    private function createProphecyParseOptions($spec): array
    {
        if (is_string($spec)) {
            return [$spec, false, false, false];
        }

        if (!is_array($spec)) {
            throw InvalidUsageException::fromTrait(
                __TRAIT__,
                __CLASS__,
                'Expected string or array, but received %s',
                gettype($spec)
            );
        }

        $class      = $spec['class'] ?? $spec[0] ?? null;
        $arguments  = $spec['arguments'] ?? false;
        $extends    = $spec['extends'] ?? false;
        $implements = $spec['implements'] ?? false;

        if (!$class) {
            throw InvalidUsageException::fromTrait(
                __TRAIT__,
                __CLASS__,
                'No FQCN found.'
            );
        }

        if (!$arguments) {
            $spec = array_filter(
                $spec,
                function ($key) {
                    return is_numeric($key) && 0 != $key;
                },
                ARRAY_FILTER_USE_KEY
            );

            if (!empty($spec)) {
                $arguments = $spec;
            }
        }

        return [$class, $arguments, $extends, (array) $implements];
    }
}
