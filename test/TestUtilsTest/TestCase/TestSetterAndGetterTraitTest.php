<?php

/**
 * CROSS PHPUnit Utils
 *
 * @see       https://github.com/cross-solution/phpunit-utils for the canonical source repository
 * @copyright https://github.com/cross-solution/phpunit-utils/blob/master/COPYRIGHT
 * @license   https://github.com/cross-solution/phpunit-utils/blob/master/LICENSE MIT
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\TestCase;

use PHPUnit\Framework\TestCase;
use Cross\TestUtils\Exception\InvalidUsageException;

use Cross\TestUtils\TestCase\TestSetterAndGetterTrait;

/**
 * Tests for \Cross\TestUtils\TestCase\TestSetterAndGetterTrait
 *
 * @covers \Cross\TestUtils\TestCase\TestSetterAndGetterTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.TestSetterAndGetterTrait
 */
class TestSetterAndGetterTraitTest extends TestCase
{
    public function testSetterAndGetterDataReturnsPropertyValue()
    {
        $target = new class { use TestSetterAndGetterTrait; private $setterAndGetter = [['prop', 'value']];};

        static::assertEquals([['prop', 'value']], $target->setterAndGetterData());
    }

    public function testSetterAndGetterDataThrowsExceptionIfNotOverridden()
    {
        $target = new class { use TestSetterAndGetterTrait;};

        $this->expectException(InvalidUsageException::class);
        $this->expectExceptionMessage('$setterAndGetter is not defined');

        $target->setterAndGetterData();
    }

    public function testReturnsNullIfSpecIsNotGiven()
    {
        $target = new class
        {
            use TestSetterAndGetterTrait;
            public $normalizeCalled = false;
            public function setterAndGetterNormalizeSpec($spec, $name, $target)
            {
                $this->normalizeCalled = true;
            }
        };

        static::assertNull($target->testSetterAndGetter('something', null));
        static::assertFalse($target->normalizeCalled);
    }

    /**
     * @testWith ["Exception", null]
     *           ["Exception", "message"]
     *
     * @param $ex
     * @param $msg
     */
    public function testExpectException($ex, $msg)
    {
        $target = new class
        {
            use TestSetterAndGetterTrait;
            public $target = \stdClass::class;
            public $called = [];
            public function __call($method, $args)
            {
                $this->called[$method][] = $args;
            }
        };

        $spec = [
            'exception' => null == $msg ? $ex : [$ex, $msg],
            'getter' => false,
            'setter' => false,
        ];

        $target->testSetterAndGetter('property', $spec);

        static::assertArrayHasKey('expectException', $target->called);
        static::assertEquals($target->called['expectException'][0], [$ex]);

        if (null === $msg) {
            static::assertArrayNotHasKey('expectExceptionMessage', $target->called);
        } else {
            static::assertArrayHasKey('expectExceptionMessage', $target->called);
            static::assertEquals([$msg], $target->called['expectExceptionMessage'][0]);
        }

    }

    public function provideIndividualTargetData()
    {
        $obj = new \stdClass;
        $obj2 = new class ('', '') {
            public $arg1;
            public $arg2;
            public function __construct($arg1, $arg2)
            {
                $this->arg1 = $arg1;
                $this->arg2 = $arg2;
            }
        };

        return [
            [
                ['target' => \stdClass::class],
                \stdClass::class
            ],
            [
                ['target' => $obj],
                $obj
            ],
            [
                ['target' => [get_class($obj2), 'arg1', 'arg2']],
                [get_class($obj2), ['arg1' => 'arg1', 'arg2' => 'arg2']]
            ],

            [
                ['target_callback' => function () use ($obj) { return $obj; }],
                $obj,
            ],
            [
                ['target_callback' => 'getSut'],
                \stdClass::class,
            ],
        ];
    }

    /**
     * @dataProvider provideIndividualTargetData
     */
    public function testAllowsProvidingIndividualTarget(array $spec, $expect): void
    {
        $target = new class {
            use TestSetterAndGetterTrait {
                TestSetterAndGetterTrait::setterAndGetterGetTarget as originalGetTarget;
            }

            public $sut;

            public function testSetterAndGetter($name, $spec=null) {
                $this->testSetterAndGetterGetTarget($spec);
            }

            private function testSetterAndGetterGetTarget($spec) {
                $this->sut = $this->originalGetTarget($spec);
            }

            public function getSut() {
                return new \stdClass;
            }
        };

        $target->testSetterAndGetter('test', $spec);

        if (is_object($expect)) {
            static::assertSame($target->sut, $expect);
        } elseif (is_array($expect)) {
            static::assertInstanceOf($expect[0], $target->sut);
            foreach ($expect[1] as $name => $value) {
                static::assertEquals($value, $target->sut->$name);
            }
        } else {
            static::assertInstanceOf($expect, $target->sut);
        }
    }

    public function testSpecifyInvalidTargetCallbackThrowsException()
    {
        $target = new class {
            use TestSetterAndGetterTrait;
        };

        $this->expectException(InvalidUsageException::class);
        $this->expectExceptionMessage('Invalid target callback');

        $target->testSetterAndGetter('test', ['target_callback' => 'invalidCallback']);
    }

    public function testAssureTargetCallbackReturnsObject()
    {
        $target = new class {
            use TestSetterAndGetterTrait;

            public function sut() {
                return 'not an object';
            }
        };

        $this->expectException(InvalidUsageException::class);
        $this->expectExceptionMessage('must return an object');

        $target->testSetterAndGetter('test', ['target_callback' => 'sut']);
    }

    public function normalizationData() : array
    {
        return [
            ['value', ['value' => 'value']],
            [10, ['value' => 10]],
            [true, ['value' => true]],
            [new \stdClass, 'Must be array'],
            [
                ['setter_value' => '__SELF__'],
                ['setter_value' => '__TARGET__']
            ],
            [
                ['value' => 'value', 'expect' => 'expect'],
                ['value' => 'value', 'expect' => 'expect'],
            ],

            [
                [
                    'setter_value' => '__SELF__',
                    'setter_assert' => [$this, 'assertEquals'],
                ],
                [
                    'setter_value' => '__TARGET__',
                    'setter_assert' => [$this, 'assertEquals']
                ],
            ],

            [
                ['setter' => 'setSome', 'getter' => ['getSome', ['arg1']]],
                ['setter' => ['setSome', []], 'getter' => ['getSome', ['arg1']]]
            ],

            [
                [
                    'value_object' => \stdClass::class,
                    'setter_value_object' => [\stdClass::class, ['arg']],
                    'expect_object' => \stdClass::class,
                ],
                [
                    'value' => new \stdClass,
                    'setter_value' => new \stdClass,
                    'expect' => new \stdClass,
                ]
            ],

            [
                [
                    'value_object' => \stdClass::class,
                    'assert' => [$this, 'assertEquals'],
                ],
                [
                    'assert' => [$this, 'assertEquals'],
                ],
            ],

            [
                ['value_callback' => 'unallable'],
                'Invalid callback',
            ],

            [
                ['value_callback' => function() { return 'calledValue'; }],
                ['value' => 'calledValue'],
            ],

            [
                ['expect_callback' => function() { return 'calledback'; }],
                ['expect' => 'calledback']
            ],

            [
                ['assert' => [$this, 'uncallable']],
                'Invalid callback'
            ],
            [
                ['nonexistent' => 'papp'],
                ['value' => null],
            ]

        ];
    }


    /**
     * @dataProvider normalizationData
     *
     * @param $spec
     * @param $expect
     */
    public function testNormalization($spec, $expect)
    {
        $target = new class
        {
            use TestSetterAndGetterTrait;

            public $target;

            public function __construct()
            {
                $this->target = new \stdClass;
            }

            public function testSetterAndGetter($name, $spec)
            {
                return $this->setterAndGetterNormalizeSpec($spec, $name, $this->target);
            }

            public function callback() {}
        };

        if (is_string($expect)) {
            $this->expectException(InvalidUsageException::class);
            $this->expectExceptionMessage($expect);
        }

        $normalized = $target->testSetterAndGetter('prop', $spec);

        if (is_array($expect)) {
            foreach ($expect as $key => $val) {
                if ('__TARGET__' === $val) {
                    static::assertSame($target->target, $normalized[$key]);
                } else {
                    static::assertEquals($val, $normalized[$key]);
                }
            }
        }
    }

    public function testNormalizationAssertCallbacks()
    {
        $target = new class
        {
            use TestSetterAndGetterTrait;

            public function testSetterAndGetter($name, $spec)
            {
                return $this->setterAndGetterNormalizeSpec($spec, $name, new \stdClass);
            }

            public function callback() {}
        };

        $spec = [
            'assert' => 'callback'
        ];

        $normalized = $target->testSetterAndGetter('prop', $spec);

        static::assertEquals([$target, 'callback'], $normalized['assert']);
    }

    public function testNormalizationAssertStringMapToAssertMethods()
    {
        $target = new class
        {
            use TestSetterAndGetterTrait;

            public function testSetterAndGetter($name, $spec)
            {
                return $this->setterAndGetterNormalizeSpec($spec, $name, new \stdClass);
            }

            public function callback() {}
        };

        $spec = [
            'assert' => 'someFunction'
        ];

        $normalized = $target->testSetterAndGetter('prop', $spec);

        static::assertEquals([get_class($target), 'assert' . $spec['assert']], $normalized['assert']);
    }

    private function getConcreteTrait() : object
    {
        return new class
        {
            use TestSetterAndGetterTrait;

            public $target;

            public function __construct()
            {
                $this->target = new class
                {
                    public $called = [];
                    public $return = [];
                    public function __call($method, $args)
                    {
                        $this->called[$method][] = $args;
                        if (isset($this->return[$method]) && count($this->return[$method])) {
                            return array_pop($this->return[$method]);
                        }
                    }
                };
            }
        };
    }

    public function testSetterGetsCalledButNotAsserted()
    {
        $trait = $this->getConcreteTrait();

        $spec = [
            'value' => 'value',
            'getter' => false,
            'setter_assert' => function($expect = null, $value = null)
            {
                static $called = false;
                if (null === $expect) return $called;
                $called = true;
            },
        ];

        $trait->testSetterAndGetter('prop', $spec);

        static::assertEquals(['value'], $trait->target->called['setprop'][0]);
        static::assertFalse($spec['setter_assert']());
    }

    public function testSetterGetsCalledAndAsserted()
    {
        $trait = $this->getConcreteTrait();

        $spec = [
            'value' => 'value',
            'setter_value' => 'popel',
            'getter' => false,
            'setter_assert' => function($expect = null, $value = null)
            {
                static $called = null;
                if (null === $expect) return $called;
                $called = $value;
            },
        ];

        $trait->target->return['setprop'][] = 'setterValue';
        $trait->testSetterAndGetter('prop', $spec);

        static::assertEquals(['value'], $trait->target->called['setprop'][0]);
        static::assertEquals('setterValue', $spec['setter_assert']());
    }

    public function testGetterAssertion()
    {
        $trait = $this->getConcreteTrait();

        $trait->target->return['getprop'][] = 'value';

        $spec = [
            'value' => 'value',
            'assert' => function($expect, $value) use ($trait) { $trait->target->assert($expect, $value); }
        ];

        $trait->testSetterAndGetter('prop', $spec);

        static::assertEquals(['value'], $trait->target->called['setprop'][0]);
        static::assertEquals(['value', 'value'], $trait->target->called['assert'][0]);
    }

    public function testExpectedValueWillBePassedToGetterAssertion()
    {
        $trait = $this->getConcreteTrait();

        $trait->target->return['getprop'][] = 'modifiedValueFromGetter';

        $assertVars = [];
        $spec = [
            'value' => 'value',
            'expect' => 'modifiedValue',
            'assert' => function($expect, $actual) use (&$assertVars) {
                $assertVars = [$expect, $actual];
            }
        ];

        $trait->testSetterAndGetter('prop', $spec);

        static::assertEquals(['modifiedValue', 'modifiedValueFromGetter'], $assertVars);
    }
}
