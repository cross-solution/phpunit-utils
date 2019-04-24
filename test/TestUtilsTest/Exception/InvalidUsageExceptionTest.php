<?php
/**
 * CROSS PHPunit Utils
 *
 * @filesource
 * @copyright 2019 Cross Solution <https://www.cross-solution.de>
 * @license MIT
 */

declare(strict_types=1);

namespace Cross\TestUtilsTest\Exception;

use Cross\TestUtils\Exception\InvalidUsageException;
use PHPUnit\Framework\TestCase;

use Cross\TestUtils\Exception\TemplatedMessageExceptionTrait;
use Cross\TestUtils\TestCase\TestUsesTraitsTrait;
use Cross\TestUtils\TestCase\TestInheritanceTrait;

use Cross\TestUtils\Exception\ExceptionInterface;

/**
 * Tests for \Cross\TestUtils\Exception\InvalidUsageException
 *
 * @covers \Cross\TestUtils\Exception\InvalidUsageException
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 *
 * @group Cross.TestUtils
 * @group Cross.TestUtils.Exception
 * @group Cross.TestUtils.Exception.InvalidUsageException
 */
class InvalidUsageExceptionTest extends TestCase
{
    use TestInheritanceTrait, TestUsesTraitsTrait;

    private $target = InvalidUsageException::class;
    private $inheritance = [ \PHPUnit\Framework\Exception::class, ExceptionInterface::class ];
    private $usesTraits  = [ TemplatedMessageExceptionTrait::class ];

}
