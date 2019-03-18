<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @copyright 2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

use Prophecy\Prophecy\ObjectProphecy;

/**
 * Provides methods to create SUT instances, refelctions, prophecies or doubles.
 *
 * @method ObjectProphecy prophesize(string $classOrInterface)
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait CreateTargetTrait
{

    /**
     * Creates an instance from a class name,
     *
     * @param string $class
     * @param array  ...$args
     *
     * @return object
     */
    public function createTarget(string $class, ...$args): object
    {
        return empty($args) ? new $class() : new $class(...$args);
    }

    /**
     * Creates a \ReflectionClass instance from a class name or object.
     *
     * @param object|string $class
     *
     * @return \ReflectionClass
     */
    public function createTargetReflection($class): \ReflectionClass
    {
        return new \ReflectionClass($class);
    }

    /**
     * Creates an object prohpecy object.
     *
     * $class can be a string, or an array where the first item is the class name and all
     * successive items are constructor arguments.
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
     * createTargetProphecy(
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
    public function createTargetProphecy($class, array $prophecies = []): ObjectProphecy
    {
        if (is_array($class)) {
            $args  = $class;
            $class = array_shift($args); //First item is the class name
        } else {
            $args = false;
        }


        $target = $this->prophesize($class);

        if ($args) {
            $target->willBeConstructedWith($args);
        }

        foreach ($prophecies as $methodProphecy) {
            $methodMock = $target;
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

        return $target;
    }

    /**
     * Creates a double.
     *
     * Works the same as {@link createTargetProphecy}, but instead of returning the
     * object prophecy, it returns the revealed test double.
     *
     * @see createTargetProphecy
     *
     * @param string|array $class
     * @param array        $prophecies
     *
     * @return object
     */
    public function createTargetDouble($class, array $prophecies = []): object
    {
        $prophecy = $this->createTargetProphecy($class, $prophecies);

        return $prophecy->reveal();
    }
}
