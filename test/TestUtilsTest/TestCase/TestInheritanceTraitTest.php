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

use Cross\TestUtils\Exception\InvalidUsageException;

use Cross\TestUtils\TestCase\AssertInheritanceTrait;
use Cross\TestUtils\TestCase\TestInheritanceTrait;
use Cross\TestUtils\TestCase\TestUsesTraitsTrait;

/**
 * Tests for \Cross\TestUtils\TestCase\TestInheritanceTrait
 *
 * @covers \Cross\TestUtils\TestCase\TestInheritanceTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.TestCase
 * @group Cross.TestUtils.TestCase.TestInheritanceTrait
 */
class TestInheritanceTraitTest extends \PHPUnit_Framework_TestCase
{
    use TestUsesTraitsTrait;

    private $usesTraits = [
        'target' => TestInheritanceTrait::class,
        AssertInheritanceTrait::class,
    ];

    public function testThrowsExceptionIfPropertyDoesNotExist()
    {
        $this->expectException(InvalidUsageException::class);
        $this->expectExceptionMessage('is not defined');

        $target = new class
        {
            use TestInheritanceTrait;
        };

        $target->testInheritance();
    }

    public function testThrowsExceptionIfPropertyIsNotAnArray()
    {
        $this->expectException(InvalidUsageException::class);
        $this->expectExceptionMessage('not an array');

        $target = new class
        {
            use TestInheritanceTrait;

            public $inheritance = 'string';
        };

        $target->testInheritance();
    }

    public function testCallsExpectedMethods()
    {
        $obj = new \stdClass();

        $target = new class($obj)
        {
            use TestInheritanceTrait;

            public function __construct($obj)
            {
                $this->target = $obj;
            }

            public $target;

            public $inheritance = ['one', 'two'];

            public static $assertInheritanceArgs;

            public static function assertInheritance()
            {
                static::$assertInheritanceArgs = func_get_args();
            }
        };


        $target->testInheritance();

        static::assertEquals(
            [
                $target->inheritance,
                $obj,
            ],
            $target::$assertInheritanceArgs
        );
    }
}
