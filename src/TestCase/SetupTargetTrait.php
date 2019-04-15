<?php
/**
 * CROSS PHPUnit Utils
 *
 * @filesource
 * @copyright 2019 Cross Solution <http://cross-solution.de>
 */

declare(strict_types=1);

namespace Cross\TestUtils\TestCase;

use Cross\TestUtils\Exception\InvalidUsageException;
use Cross\TestUtils\Utils\Instance;

/**
 * Setup the SUT from specifications.
 *
 * If the testcase using this trait provide its own setup() method,
 * it needs to call the setupTarget() method.
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 */
trait SetupTargetTrait
{

    protected function setUp(): void
    {
        $this->setupTarget();
    }

    /**
     * Setup the SUT from specification
     *
     * You need to define the property _$target_.
     * This property holds the specification and will be set to the SUT.
     *
     * ### Examples
     *
     * 1. Create one SUT for all tests via the callback "initTarget":
     *    _Read more about callbacks futher down._
     *
     *    `$target = true;`
     *
     *
     * 2. Create one SUT for all tests from a specification for use with
     *    {@link \Cross\TestUtils\Utils\Instance::create}:
     *
     *    ```
     *    $target = FQCN;
     *    $target = [FQCN, argument, ...];
     *    ```
     *
     * 3. If you want to create different SUTs for some tests, you need to
     *    use the more verbose specification format:
     *
     *    ```
     *    $target = [
     *        'default' => [],
     *        'create' => [
     *            'for' => testnamePatterns,
     *            'target' => targetSpec,
     *            'reflection' => FQCN|bool,
     *            'callback' => callable,
     *            'arguments' => [argument, ...],
     *            'use' => presetName
     *        ],
     *    ];
     *    ```
     *
     * * 'for' : Specify one or more (as array) patterns matching test names for which this
     *   SUT specification should apply.
     *   You may use '*' to match all test names starting with the string.
     *   __Examples__:
     *   * 'testCorrectBehaviour' : will match exactly the test name
     *   * 'testCorrect*' : match all tests starting with "testCorrect"
     *   * 'testWithProvider|#4': matches the test with dataset #4.
     *   * 'testWith*|#2': matches all tests starting with testWith at dataset #2.
     *   * '*': matches all tests. This is the default value assumed, if 'for' is not provided.
     *     That means, it should always be the last entry, as all following entries are
     *     ignored.
     *
     * * 'target' : specification for the target as understood by
     *   {@link \Cross\TestUtils\Utils\Instance::create}
     *
     * * 'reflection' :
     *   * _string_: Creates a \ReflectionClass from the FQCN
     *   * _bool_: Enable or disable the creation of a reflection from
     *     target specification (override default value)
     *     For example you might want to create a reflection class
     *     from specifications returned by a callback.
     *
     * * 'callback' : Specify a callback to be called, which should either return thr SUT instance
     *   or a specification understood by {@link \Cross\TestUtils\Utils\Instance::withMappedArguments}
     *
     * * 'arguments' : Array of constructor arguments used to create the SUT.
     *   Note: If target specification is an array, this arguments are ignored.
     *
     * * 'use': You can define presets to be used with a specification.
     *   Presets are like the default values with a unique key name.
     *   If use is present, the options from this preset key are merged in the
     *   specification. (order: default -> preset -> spec (later merges override previous ones))
     *
     *
     *  Default values:
     *  Each test specification will be merged into the default values, if provided.
     *
     * @return void
     */
    private function setupTarget(): void
    {
        if (!property_exists($this, 'target')) {
            return;
        }

        if (!isset($this->target['create']) && !isset($this->target['default'])) {
            if (false === $this->target) {
                return;
            }
            $spec = true === $this->target ? ['callback' => 'initTarget'] : ['target' => $this->target];
            $this->target = $this->setupTargetInstance($spec);

            return;
        }

        $specs     = $this->target['create'] ?? [];
        $nameParts = explode(' ', $this->getName());
        $name      = reset($nameParts);
        $set       = end($nameParts);
        $set       = '#' == $set{0} || '"' == $set{0} ? trim($set, '"') : '';

        foreach ($specs as $spec) {
            $for = isset($spec['for']) ? (array) $spec['for'] : ['*'];

            foreach ($for as $pattern) {
                $search  = false !== strpos($pattern, '|') ? "$name|$set" : $name;
                $pattern = str_replace(['*', '|'], ['.*', '\|'], $pattern);

                if (preg_match('~^' . $pattern . '$~i', "$search")) {
                    $defaultSpec  = $this->target['default'] ?? [];
                    $useSpec      = isset($spec['use']) && isset($this->target[$spec['use']])
                        ? $this->target[$spec['use']]
                        : []
                    ;
                    $spec         = array_merge($defaultSpec, $useSpec, $spec);
                    $this->target = $this->setupTargetInstance($spec);
                    return;
                }
            }
        }

        $spec = $this->target['default'] ?? ['callback' => 'initTarget'];
        $this->target = $this->setupTargetInstance($spec);
    }

    /**
     * Setup a SUT from specification.
     *
     * @param  array $spec
     * @return object|null
     */
    private function setupTargetInstance($spec): ?object
    {
        $reflection = false;
        $arguments  = $spec['arguments'] ?? [];

        if (isset($spec['reflection'])) {
            if (is_string($spec['reflection'])) {
                return Instance::reflection($spec['reflection']);
            }
            $reflection = (bool) $spec['reflection'];
        }

        if (isset($spec['callback']) && false !== $spec['callback']) {
            if (!is_callable($spec['callback'])) {
                $spec['callback'] = [$this, $spec['callback']];

                if (!is_callable($spec['callback'])) {
                    throw InvalidUsageException::fromTrait(
                        __TRAIT__,
                        __CLASS__,
                        'Invalid callback.'
                    );
                }
            }

            $target = $spec['callback']();

            if (!is_object($target)) {
                return $reflection
                    ? Instance::reflection($target)
                    : Instance::withMappedArguments($target, $arguments, $this);
            }

            return $target;
        }

        $target = $spec['target'] ?? false;

        if (!$target) {
            return null;
        }

        if ($reflection) {
            return Instance::reflection($target);
        }

        return Instance::withMappedArguments($target, $arguments, $this);
    }
}
