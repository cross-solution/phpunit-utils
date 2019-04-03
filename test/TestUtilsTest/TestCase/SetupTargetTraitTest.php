<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license MIT
 * @copyright 2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\TestCase;

use Cross\TestUtils\Exception\InvalidUsageException;
use Cross\TestUtils\TestCase\SetupTargetTrait;

/**
 * Tests for \Cross\TestUtils\TestCase\SetupTargetTrait
 *
 * @covers \Cross\TestUtils\TestCase\SetupTargetTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.SetupTargetTrait
 */
class SetupTargetTraitTest extends \PHPUnit_Framework_TestCase
{

    public function testSetupCallsSetupTarget()
    {
        $target = new class
        {
            use SetupTargetTrait;

            public $called=false;

            public function setupTarget()
            {
                $this->called = true;
            }
        };

        $target->setup();


        static::assertTrue($target->called);
    }

    public function testSetupTargetDoesNothingIfNoPropertyIsset()
    {
        $target = new class
        {
            use SetupTargetTrait;
            public $called = false;

            private function setupTargetInstance($spec): ?object
            {
                $this->called = true;
                return null;
            }
        };

        $target->setupTarget();

        static::assertFalse($target->called);
    }

    public function targetSpecData()
    {
        return [
            [false, null],
            [true, ['callback' => 'initTarget']],
            ['FQCN', ['target' => 'FQCN']],
            // Fallback no spec and no defaults
            [
                ['create' => [['for' => 'notTheRightTest']]],
                ['callback' => 'initTarget'],
                ['name' => 'theTest']
            ],
            // Fallback no spec, but defaults
            [
                [
                    'create' => [
                        [
                            'for' => 'notTheRightTest'
                        ]
                    ],
                    'default' => ['target' => 'FQCN'],
                ],
                ['target' => 'FQCN'],
                ['name' => 'theTest'],
            ],
            // Spec
            [
                [
                    'create' => [
                        [
                            'for' => 'theTest',
                            'target' => 'FQCN'
                        ]
                    ]
                ],
                ['for' => 'theTest', 'target' => 'FQCN'],
                ['name' => 'theTest']
            ],
            // Merged spec
            [
                [
                    'default' => [
                        'def' => 'def'
                    ],
                    'use' => [
                        'val' => 'use'
                    ],
                    'create' => [
                        [
                            'for' => 'test',
                            'target' => 'FQCN',
                            'use' => 'use',
                        ]
                    ],
                ],
                [
                    'def' => 'def',
                    'val' => 'use',
                    'use' => 'use',
                    'for' => 'test',
                    'target' => 'FQCN',
                ],
            ],
            // Spec set name and wildcard
            [
                [
                    'create' => [
                        [
                            'for' => 'theTest*|#1',
                        ]
                    ]
                ],
                ['for' => 'theTest*|#1'],
                ['name' => 'theTestIrrelevant with data set #1']
            ],
        ];
    }

    /**
     * @dataProvider targetSpecData
     * @param  array $targetSpec
     * @param  array $expectSpec
     * @param array|null $options
     * @return void
     */
    public function testSetupTargetSetsCorrectSpec($targetSpec, $expectSpec, $options = null)
    {
        $target = new class($targetSpec, $options)
        {
            use SetupTargetTrait;

            private $target;

            public function __construct($spec, ?array $options = null)
            {
                $this->target = $spec;

                if ($options) {
                    foreach ($options as $key => $value) {
                        $this->$key = $value;
                    }
                }
            }

            public $called;
            public $name = 'test';

            public function getName()
            {
                return $this->name;
            }

            private function setupTargetInstance($spec): ?object
            {
                $this->called = $spec;
                return null;
            }
        };

        $target->setupTarget();

        static::assertEquals($expectSpec, $target->called);
    }

    private function getConcreteTrait($spec)
    {
        return new class($spec)
        {
            use SetupTargetTrait;

            public $target;

            public function __construct($spec)
            {
                $spec['for'] = 'test';
                $this->target = [
                    'create' => [$spec]
                ];
            }

            public function getName()
            {
                return 'test';
            }

            public function callbackFqcn()
            {
                return \stdClass::class;
            }

        };
    }

    public function targetInstanceData()
    {
        return [
            [
                ['reflection' => \stdClass::class],
                \ReflectionClass::class
            ],
            // non-SUT Callback with object
            [
                ['callback' => function() { return new \stdClass; }],
                \stdClass::class
            ],
            // SUt-Callback with reflection
            [
                ['callback' => 'callbackFqcn',
                 'reflection' => true],
                \ReflectionClass::class
            ],
            // reflection with target
            [
                ['reflection' => true, 'target' => \stdClass::class],
                \ReflectionClass::class,
            ],
            [
                ['target' => \stdClass::class],
                \stdClass::class
            ],

        ];
    }

    /**
     * @dataProvider targetInstanceData
     * @param  array $targetSpec
     * @param  string $expects
     * @return void
     */
    public function testSetupsCorrectTargetInstance($targetSpec, $expects)
    {
        $target = $this->getConcreteTrait($targetSpec);

        $target->setupTarget();

        static::assertInstanceOf($expects, $target->target);
    }

    public function testInvalidCallbackThrowsException()
    {
        $target = $this->getConcreteTrait([
            'callback' => 'invalid',
        ]);

        $this->expectException(InvalidUsageException::class);

        $target->setupTarget();
    }

    public function testNoTargetSetsNull()
    {
        $target = $this->getConcreteTrait([]);

        $target->setupTarget();

        static::assertNull($target->target);
    }
}
