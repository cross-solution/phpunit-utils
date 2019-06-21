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

        $target = Target::get(
            $this,
            ['getSetterAndGetterTarget', 'getTarget'],
            ['setterAndGetterTarget', 'target'],
            'setterAndGetter',
            true
        );

        $spec = $this->setterAndGetterNormalizeSpec($spec, $name, $target);
        $value = $spec['value'];

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

            if ('__SETTER_AND_GETTER__' != $spec['setter_value']) {
                $spec['setter_assert']($spec['setter_value'], $setterValue);
            }
        }

        if (false !== $spec['getter'][0]) {
            [$getter, $getterArgs] = $spec['getter'];
            $getterValue = $target->$getter(...$getterArgs);

            if ($spec['expect'] != '__SETTER_AND_GETTER__') {
                $value = $spec['expect'];
            }

            $spec['assert']($value, $getterValue);
        }
    }

    /**
     * Normalize the test specification.
     *
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

        $err = __TRAIT__ . ': ' . get_class($this) . ': ';

        if (!is_array($spec)) {
            throw new \PHPUnit\Framework\Exception($err . 'Invalid specification. Must be array.');
        }

        foreach ($spec as $key => $value) {
            switch ($key) {
                default:
                    break;

                case 'setter_value':
                    if ('__SELF__' == $value) {
                        $value = $target;
                        if (!isset($spec['setter_assert'])) {
                            $normalized['setter_assert'] = [static::class, 'assertSame'];
                        }
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

                    $assertKey = str_replace('value', 'assert', $key);

                    if (!isset($spec[$assertKey]) && array_key_exists($assertKey, $normalized)) {
                        $normalized[$assertKey] = [static::class, 'assertSame'];
                    }

                    break;

                case 'value_callback':
                case 'setter_value_callback':
                case 'expect_callback':
                    if (is_string($value)) {
                        $value = [$this, $value];
                    }

                    if (!is_callable($value)) {
                        throw new \PHPUnit\Framework\Exception($err . 'Invalid callback for "' . $key . '".');
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
                        throw new \PHPUnit\Framework\Exception($err . 'Invalid callback for "' . $key . '".');
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
