<?php

/**
 * CROSS PHPUnit Utils
 *
 * @see       https://github.com/cross-solution/phpunit-utils for the canonical source repository
 * @copyright https://github.com/cross-solution/phpunit-utils/blob/master/COPYRIGHT
 * @license   https://github.com/cross-solution/phpunit-utils/blob/master/LICENSE MIT
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\Utils;

use Cross\TestUtils\Exception\InvalidUsageException;
use Cross\TestUtils\Utils\Instance;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \Cross\TestUtils\Utils\Instance
 *
 * @covers \Cross\TestUtils\Utils\Instance
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.Utils
 * @group Cross.TestUtils.Utils.Instance
 */
class InstanceTest extends TestCase
{

    public function createsReflectionData()
    {
        return [
            'fromString'       => [\stdClass::class],
            'fromObject'       => [new \stdClass()],
            'fromArrayNumeric' => [[\stdClass::class, 'elem1', 'elem2']],
            'fromArray'        => [['elem', 'class' => \stdClass::class]],
        ];
    }

    /**
     * @dataProvider createsReflectionData
     *
     * @param  mixed $spec
     * @return void
     */
    public function testCreatesReflection($spec)
    {
        $actual = Instance::reflection($spec);

        static::assertInstanceOf(\ReflectionClass::class, $actual);
    }

    public function createsObjectsData()
    {
        $object = new class
        {
            public $args;

            public function __construct(...$args)
            {
                $this->args = $args;
            }
        };
        $fqcn = get_class($object);

        return [
            [$fqcn, [], $fqcn],
            [$fqcn, ['arg1'], $fqcn, 'arg1'],
            [$fqcn, ['arg1'], [$fqcn, 'arg1'], 'arg2'],
            [\ReflectionClass::class, false, "!$fqcn"]
        ];
    }

    /**
     * @dataProvider createsObjectsData
     * @param  string $fqcn
     * @param  array|false $expect
     * @param  array ...$args
     * @return void
     */
    public function testCreatesObjects($fqcn, $expect, ...$args)
    {
        $actual = Instance::create(...$args);

        static::assertInstanceOf($fqcn, $actual);
        if (false !== $expect) {
            static::assertEquals($expect, $actual->args);
        }
    }

    public function testCreateThrowsException()
    {
        $this->expectException(InvalidUsageException::class);
        $this->expectExceptionMessage('string as FQCN');

        Instance::create(1);
    }

    public function mappedArgumentsData()
    {
        $object = new class
        {
            public $called;
            public $args;

            public function __construct(...$args)
            {
                $this->args = $args;
            }
        };
        $context = new class
        {
            public $called;

            public function __call($method, $args)
            {
                $this->called[] = $method;
                return '__mapped__';
            }
        };
        $context2 = new class
        {
            private function privateCallback()
            {
                return '__mapped__';
            }
        };

        $fqcn = get_class($object);

        return [
            // expectFqcn, expectArgs, expectCalled, fqcn, arguments, context
            [$fqcn, [], null, $fqcn, [], null],
            [$fqcn, ['arg1', 'arg2'], false, $fqcn, ['some' => 'arg1', 'arg2'], null],
            [$fqcn, ['@'], null, $fqcn, ['arg' => '@'], $context],
            [$fqcn, ['__mapped__', phpversion()], ['arg'], $fqcn, ['arg' => $context, 'rev' => 'phpversion'], $context],
            [$fqcn, [phpversion(), '__mapped__'], ['arg'], $fqcn, ['@phpversion', '@arg'], $context],
            [
                $fqcn,
                [phpversion(), '__mapped__', '__mapped__'],
                ['arg', 'arg2'],
                $fqcn,
                [['@' => 'phpversion'], ['@' => 'arg'], ['@' => [$context, 'arg2']]],
                $context
            ],
            [$fqcn, ['@nonExistentMethod'], false, $fqcn, ['@nonExistentMethod'], $context2],
            [$fqcn, ['__mapped__'], false, $fqcn, ['@privateCallback'], $context2],
            [$fqcn, ['__mapped__'], ['arg'], [$fqcn, '@arg'], ['ignored'], $context],
            [$fqcn, ['__mapped__'], ['arg'], [$fqcn, '@arg'], $context, null],
        ];
    }

    /**
     * @dataProvider mappedArgumentsData
     * @param  string $expectFqcn
     * @param  array|false|null $expectArgs
     * @param  array|false|null $expectCalled
     * @param  string|array $fqcn
     * @param  array|object $arguments
     * @param  object|null $context
     * @return void
     */
    public function testCreatesObjectsWithMappedArguments(
        $expectFqcn,
        $expectArgs,
        $expectCalled,
        $fqcn,
        $arguments,
        $context
    ) {
        if ($context) {
            $context->called = null;
        }

        $actual = Instance::withMappedArguments($fqcn, $arguments, $context);

        static::assertInstanceOf($expectFqcn, $actual);
        if (false !== $expectArgs) {
            static::assertEquals($expectArgs, $actual->args);
        }
        if (false !== $expectCalled && $context) {
            static::assertEquals($expectCalled, $context->called);
        }
    }
}
