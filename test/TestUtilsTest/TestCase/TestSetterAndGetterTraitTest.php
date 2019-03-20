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

use Cross\TestUtils\TestCase\TestSetterAndGetterTrait;
use Cross\TestUtils\TestCase\TestUsesTraitsTrait;

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
class TestSetterAndGetterTraitTest extends \PHPUnit_Framework_TestCase
{
    use TestUsesTraitsTrait;

    private $usesTraits = ['target' => TestSetterAndGetterTrait::class];

    public function testSetterAndGetterDataReturnsPropertyValue()
    {
        $target = new class { use TestSetterAndGetterTrait; private $setterAndGetter = [['prop', 'value']];};

        static::assertEquals([['prop', 'value']], $target->setterAndGetterData());
    }

    public function testSetterAndGetterDataReturnsEmptyArray()
    {
        $target = new class { use TestSetterAndGetterTrait;};

        static::assertEquals([], $target->setterAndGetterData());
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


    public function normalizationData() : array
    {
        return [
            ['value', ['value' => 'value']],
            [new \stdClass, 'Must be array'],
            [['property' => true], ['property' => ['prop', '__VALUE__']]],
            [['property' => ['test']], ['property' => ['prop', 'test']]],
            [['property' => ['name', 'test']], ['property' => ['name', 'test']]],
            [['property' => 'name'], ['property' => ['name', '__VALUE__']]],

            [
                ['setter_value' => '__SELF__'],
                ['setter_value' => '__TARGET__']
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
                ],
                [
                    'value' => new \stdClass,
                    'setter_value' => new \stdClass,
                ]
            ],

            [
                [
                    'value_object' => \stdClass::class,
                    'assert' => [$this, 'assertEquals'],
                    'property_assert' => [$this, 'assertEquals'],
                ],
                [
                    'assert' => [$this, 'assertEquals'],
                    'property_assert' => [$this, 'assertEquals'],
                ],
            ],

            [
                ['value_callback' => 'unallable'],
                'Invalid value callback',
            ],

            [
                ['value_callback' => function() { return 'calledValue'; }],
                ['value' => 'calledValue'],
            ],

            [
                ['assert' => [$this, 'uncallable']],
                'Invalid assert callback'
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
            $this->expectException(\PHPUnit_Framework_Exception::class);
            $this->expectExceptionMessage($expect);
        }

        $normalized = $target->testSetterAndGetter('prop', $spec);

        if (is_array($expect)) {
            foreach ($expect as $key => $val) {
                if ('__TARGET__' == $val) {
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

        $spec = [
            'property_assert' => 'Same',
        ];

        $normalized = $target->testSetterAndGetter('prop', $spec);

        static::assertEquals([get_class($target), 'assertAttributeSame'], $normalized['property_assert']);
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

    public function testPropertyAssertion()
    {
        $trait = $this->getConcreteTrait();
        $trait->target = new class
        {
            public $prop = null;

            public function setprop($prop) { $this->prop = $prop; }
        };

        $spec = [
            'value' => 'value',
            'property' => 'prop',
            'property_assert' => function($expect, $name, $target) { $target->expect = $expect; },
        ];

        $trait->testSetterAndGetter('prop', $spec);

        static::assertEquals('value', $trait->target->prop);
        static::assertEquals('value', $trait->target->expect);
    }

    public function testPropertyAssertionWithInjectedValue()
    {
        $trait = $this->getConcreteTrait();
        $trait->target = new class
        {
            public $prop = null;

            public function setprop($prop) { $this->prop = $prop; }
        };

        $spec = [
            'value' => 'value',
            'property' => ['prop', '__VALUE__'],
            'property_assert' => function($expect, $name, $target) { $target->expect = $expect; },
        ];

        $trait->testSetterAndGetter('prop', $spec);

        static::assertEquals('value', $trait->target->prop);
        static::assertEquals('value', $trait->target->expect);
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
}
