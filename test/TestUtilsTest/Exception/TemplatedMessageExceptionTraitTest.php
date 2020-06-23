<?php

/**
 * CROSS PHPUnit Utils
 *
 * @see       https://github.com/cross-solution/phpunit-utils for the canonical source repository
 * @copyright https://github.com/cross-solution/phpunit-utils/blob/master/COPYRIGHT
 * @license   https://github.com/cross-solution/phpunit-utils/blob/master/LICENSE MIT
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\Exception;

use Cross\TestUtils\Exception\InvalidUsageException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \Cross\TestUtils\Exception\TemplatedMessageExceptionTrait
 *
 * @covers \Cross\TestUtils\Exception\TemplatedMessageExceptionTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.Exception
 * @group Cross.TestUtils.Exception.TemplatedMessageExceptionTrait
 */
class TemplatedMessageExceptionTraitTest extends TestCase
{
    public function testCreateWithoutPrevious()
    {
        $target = InvalidUsageException::create('%s %s', 'value1', 'value2');

        static::assertEquals('value1 value2', $target->getMessage());
    }

    public function testCreateWithPrevious()
    {
        $ex = new \Exception;
        $target = InvalidUsageException::create('%s', 'value1', $ex);

        static::assertEquals('value1', $target->getMessage());
        static::assertSame($ex, $target->getPrevious());
    }

    public function testFromClass()
    {
        $target = InvalidUsageException::fromClass('CLASS', '%s', 'value1');

        static::assertEquals('CLASS: value1', $target->getMessage());
    }

    public function testFromTrait()
    {
        $target = InvalidUsageException::fromTrait('TRAIT', 'CLASS', '%s', 'value1');

        static::assertEquals('TRAIT: CLASS: value1', $target->getMessage());
    }
}
