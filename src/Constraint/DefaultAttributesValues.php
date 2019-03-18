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
 * Constraint to assert the existence and default values of attributes.
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
class DefaultAttributesValues extends \PHPUnit_Framework_Constraint
{
    /**
     * The default attributes.
     *
     * Must be an array with propertyName => value pairs.
     *
     * @var array<string => string>
     */
    private $defaultAttributes = [];

    /**
     * Stores the result of each test for internal use.
     *
     * @var array<bool|string>
     */
    private $result = [];

    /**
     * Creates a new instance.
     *
     * @param iterable $defaultAttributes
     */
    public function __construct(iterable $defaultAttributes = [])
    {
        $this->defaultAttributes = $defaultAttributes;
        parent::__construct();
    }

    public function count(): int
    {
        return count($this->defaultAttributes);
    }

    /**
     * Tests if an object has the required attributes and they have the correct value.
     *
     * Returns true, if and only if the object defines ALL attributes and they have the expected value
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

        $reflection = $other instanceof \ReflectionClass ? $other : new \ReflectionClass($other);
        $properties = $reflection->getDefaultProperties();

        foreach ($this->defaultAttributes as $prop => $value) {
            if (is_int($prop)) {
                $prop = $value;
                $value = null;
            }

            if (array_key_exists($prop, $properties)) {
                try {
                    (new \PHPUnit_Framework_Constraint_IsIdentical($value))->evaluate($properties[$prop]);
                    $this->result[$prop] = true;
                } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
                    $message = $e->toString();

                    if ($comparisonFailure = $e->getComparisonFailure()) {
                        $message .= sprintf(
                            "\n%30sExpected: %s\n%30sActual  : %s\n",
                            '',
                            $comparisonFailure->getExpectedAsString(),
                            '',
                            $comparisonFailure->getActualAsString()
                        );
                    }

                    $this->result[$prop] = $message;
                    $success = false;
                }
            } else {
                $this->result[$prop] = 'Attribute is not defined.';
                $success = false;
            }
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

        foreach ($this->result as $prop => $msg) {
            if (true === $msg) {
                $info .= "\n + $prop";
            } else {
                $info .= sprintf("\n - %-25s: %s", $prop, $msg);
            }
        }

        return $info;
    }

    public function toString(): string
    {
        return 'has expected default attributes and its values.';
    }
}
