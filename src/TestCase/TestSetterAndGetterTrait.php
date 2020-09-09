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
use Cross\TestUtils\Utils\Instance;
use Cross\TestUtils\Utils\Target;

/**
 * Trait for testing setters and getters of the SUT.
 *
 * You specify the tests to be made in the property "setterAndGetter" or by
 * overriding the method "setterAndGetterData".
 *
 * The specifications must be in the format:
 * <pre>
 * [
 *      [ <propertyName>, <spec> ],
 *      ...
 * ]
 * </pre>
 *
 * The first entry in that specifications can be the SUT to be used, provided as
 * FQCN or [FQCN, constructorArg, ... ]
 * Please note that in that case, PHPUnit will report a test with no assertions.
 *
 * if <spec> is not an array, it is assumed as the 'value' key (= ['value' => <spec>])
 *
 * Available keys in the <spec> array:
 *
 * * 'target': Allows to specify a SUT for this particular test only.
 *             The value must be either an object a string representing a FQCN
 *             or an array [FQCN, arg, ...]
 *
 * * target_callback': Get the SUT via a callback.
 *                     If a string is given, it is assumed that a method
 *                     in the TestCase is meant.
 *                     The callbakc must return an object.
 *
 * * 'value': The value to test the setter and getter with.
 *            First the setter will be called with the value as argument.
 *            Then the assertion will gets called, passing in the value and
 *            the returned value from the getter method.
 *
 * * 'value_object': FQCN or [FQCN, arg, ...]
 *                   Create an instance of the FQCN as the value. This instance is created
 *                   prior to calling the setter with it.
 *
 * * 'value_callback': callable || method name (in the TestCase class)
 *                     The return value of that callback is taken as value for the test.
 *
 * * 'expect': If the setter modifies the value, you can specify the expected value
 *             for the getter here.
 * * 'expect_object': see 'value_object'
 * * 'expect_callback': see 'value_callback'
 *
 * * 'getter': - Name of getter method (a '*' gets replaced by the property name)
 *             - [GetterName, [arg, arg, ...]]
 *               Use this format to provide arguments to the getter method.
 *            DEFAULT: 'get*'
 *
 * * 'assert': Callback for getter value assertion.
 *           For assert* Methods, simply give the string omitting the prefix (eg: 'same' => static::assertSame)
 *           The callback will get two arguments: The expected value ('value') and the
 *           value returned by the getter method.
 *           DEFAULT: 'equals'
 *
 * * 'setter': - Name of Setter (a '*' gets replaced by the property name)
 *             - [SetterName, [arg, arg...]]
 *               Use this format to provide arguments to the getter method.
 *               Please note that the first argument to the setter is always the value.
 *             DEFAULT: 'set*'
 *
 * * 'setter_value': If the setter should return a value, you can specify it here.
 *                   There is a special value '__SELF__', if setter should return the target (fluent interface)
 *
 * * 'setter_value_object': see 'value_object'
 *
 * * 'setter_value_callback': see 'value_callback'
 *
 * * 'setter_assert': Callback for setter value assertion. Only gets called if a setter value is set.
 *                    see 'assert'
 *
 * * 'exception' => - FQCN of an expected exception
 *                  - [FQCN, 'expected exception message']
 *                 If the setter or the getter method is expected to throw an exception.
 *
 *
 * @property array $setterAndGetter
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @since 2.x Allow SUT per individual test.
 */
trait TestSetterAndGetterTrait
{
    /**
     * Data provider for the properties test.
     *
     * Override this in the TestCase or provide the attribute "setterAndGetter"
     *
     * @return array
     */
    public function setterAndGetterData(): array
    {
        if (!property_exists($this, 'setterAndGetter')) {
            throw InvalidUsageException::fromTrait(
                __TRAIT__,
                __CLASS__,
                'Property $setterAndGetter is not defined and method setterAndGetter is not overridden.'
            );
        }

        return $this->setterAndGetter;
    }

    /**
     * @dataProvider setterAndGetterData
     *
     * @param string|array $name
     * @param array|null $spec
     */
    public function testSetterAndGetter($name, $spec = null): void
    {
        if (null === $spec) {
            return;
        }

        $target = $this->setterAndGetterGetTarget($spec);
        $spec   = $this->setterAndGetterNormalizeSpec($spec, $name, $target);
        $value  = $spec['value'];

        if ($spec['exception']) {
            if (is_array($spec['exception'])) {
                $exception = $spec['exception'][0] ?? null;
                $message   = $spec['exception'][1] ?? null;
            } else {
                $exception = $spec['exception'];
                $message = null;
            }

            $this->expectException($exception);
            if ($message) {
                $this->expectExceptionMessage($message);
            }
        }

        // Test setter
        if (false !== $spec['setter'][0]) {
            [$setter, $setterArgs] = $spec['setter'];
            $setterValue = $target->$setter($value, ...$setterArgs);

            if ($spec['setter_value'] !== '__SETTER_AND_GETTER__') {
                $spec['setter_assert']($spec['setter_value'], $setterValue);
            }
        }

        if (false !== $spec['getter'][0]) {
            [$getter, $getterArgs] = $spec['getter'];
            $getterValue = $target->$getter(...$getterArgs);

            if ($spec['expect'] !== '__SETTER_AND_GETTER__') {
                $value = $spec['expect'];
            }

            $spec['assert']($value, $getterValue);
        }
    }

    /**
     * @param string|array $spec
     * @internal
     */
    private function setterAndGetterGetTarget($spec): object
    {
        if (isset($spec['target'])) {
            return
                is_object($spec['target'])
                ? $spec['target']
                : Instance::withMappedArguments($spec['target'], $this)
            ;
        }

        if (isset($spec['target_callback'])) {
            $cb = $spec['target_callback'];

            if (is_string($cb) && !is_callable($cb)) {
                $cb = [$this, $cb];
            }

            if (!is_callable($cb)) {
                throw InvalidUsageException::fromTrait(__TRAIT__, __CLASS__, 'Invalid target callback.');
            }

            $target = $cb();

            if (!is_object($target)) {
                throw InvalidUsageException::fromTrait(__TRAIT__, __CLASS__, 'Target callback must return an object.');
            }

            return $target;
        }

        return Target::get(
            $this,
            ['getSetterAndGetterTarget', 'getTarget'],
            ['setterAndGetterTarget', 'target'],
            'setterAndGetter',
            true
        );
    }

    /**
     * Normalize the test specification.
     *
     * @internal
     * @param array|string $spec
     * @param string $name
     * @param object $target
     *
     * @return array
     */
    private function setterAndGetterNormalizeSpec($spec, string $name, object $target): array
    {
        $normalized = [
            'getter' => ["get$name", []],
            'assert' => [static::class, 'assertEquals'],
            'setter' => ["set$name", []],
            'setter_assert' => [static::class, 'assertEquals'],
            'setter_value' => '__SETTER_AND_GETTER__',
            'value' => null,
            'expect' => '__SETTER_AND_GETTER__',
            'exception' => null,
        ];

        if (is_scalar($spec)) {
            $normalized['value'] = $spec;
            return $normalized;
        }

        if (!is_array($spec)) {
            throw InvalidUsageException::fromTrait(__TRAIT__, __CLASS__, 'Invalid specification. Must be array.');
        }

        foreach ($spec as $key => $value) {
            switch ($key) {
                default:
                    break;

                case 'setter_value':
                    if ('__SELF__' === $value) {
                        $value = $target;
                    }
                    break;

                case 'setter':
                case 'getter':
                    if (!is_array($value)) {
                        $value = [$value, []];
                    }

                    if (is_string($value[0])) {
                        $value[0] = str_replace('*', $name, $value[0]);
                    }

                    break;

                case 'value_object':
                case 'setter_value_object':
                case 'expect_object':
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $class = $value[0];
                    $args  = $value[1] ?? [];
                    $key = substr($key, 0, -7);
                    $value = new $class(...$args);
                    break;

                case 'value_callback':
                case 'setter_value_callback':
                case 'expect_callback':
                    if (is_string($value) && !is_callable($value)) {
                        $value = [$this, $value];
                    }

                    if (!is_callable($value)) {
                        throw InvalidUsageException::fromTrait(
                            __TRAIT__,
                            __CLASS__,
                            'Invalid callback for "' . $key . '".'
                        );
                    }

                    $key = substr($key, 0, -9);
                    $value = $value();
                    break;

                case 'setter_assert':
                case 'assert':
                    if (is_string($value)) {
                        if (method_exists($this, $value)) {
                            $value = [$this, $value];
                            break;
                        }
                        $value = [static::class, "assert$value"];
                        break;
                    }

                    if (!is_callable($value)) {
                        throw InvalidUsageException::fromTrait(
                            __TRAIT__,
                            __CLASS__,
                            'Invalid callback for "' . $key . '".'
                        );
                    }

                    break;
            }

            if (array_key_exists($key, $normalized)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
