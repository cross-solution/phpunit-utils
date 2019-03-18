<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @license    MIT
 * @copyright  2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtils\Constraint;

/**
 * Constraint to assert the extending or implementing of specific classes and interfaces.
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @todo write tets.
 */
class ExtendsOrImplements extends \PHPUnit_Framework_Constraint
{
    /**
     * The FQCN of the classes and interfaces which the tested object
     * must extend or implement.
     *
     * @var string[]
     */
    private $parentsAndInterfaces = [];

    /**
     * Stores the result of each tested class|interface for internal use.
     *
     * @var array
     */
    private $result = [];

    /**
     * Creates a new instance.
     *
     * @param iterable $parentsAndInterfaces FQCNs of classes or interfaces.
     */
    public function __construct(iterable $parentsAndInterfaces = [])
    {
        $this->parentsAndInterfaces = (array) $parentsAndInterfaces;
        parent::__construct();
    }

    public function count(): int
    {
        return count($this->parentsAndInterfaces);
    }

    /**
     * Tests if an object extends or implements the required classes or interfaces.
     *
     * Returns true, if and only if the object extends or implements ALL the classes and interfaces
     * provided with {@link $parentsAndInterfaces}
     *
     * @param object|\ReflectionClass|string $other
     *
     * @return bool
     *
     */
    protected function matches($other): bool
    {
        $this->result = [];
        $success      = true;
        if (is_string($other)) {
            $other = new \ReflectionClass($other);
        }
        $isReflection = $other instanceof \ReflectionClass;

        foreach ($this->parentsAndInterfaces as $fqcn) {
            $check               = $isReflection ? $other->isSubclassOf($fqcn) : $other instanceof $fqcn;
            $this->result[$fqcn] = $check;
            $success             = $success && $check;
        }

        return $success;
    }

    protected function failureDescription($other): string
    {
        if ($other instanceof \ReflectionClass) {
            $name = $other->getName();
        } elseif (is_object($other)) {
            $name = get_class($other);
        } else {
            $name = $other;
        }

        return $name . ' ' . $this->toString();
    }

    protected function additionalFailureDescription($other): string
    {
        $info = '';

        foreach ($this->result as $fqcn => $valid) {
            $info .= sprintf("\n %s %s", $valid ? '+' : '-', $fqcn);
        }

        return $info;
    }

    public function toString(): string
    {
        return 'extends or implements required classes and interfaces';
    }
}
