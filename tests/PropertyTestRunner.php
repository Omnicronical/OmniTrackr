<?php
/**
 * Property-Based Test Runner
 * 
 * Simple property-based testing framework for PHP
 */

class PropertyTestRunner {
    private $iterations;
    private $verbose;

    public function __construct($iterations = 100, $verbose = false) {
        $this->iterations = $iterations;
        $this->verbose = $verbose;
    }

    /**
     * Run a property test
     * 
     * @param string $name Test name
     * @param callable $generator Function that generates test data
     * @param callable $property Function that tests the property
     * @return array Test result with success status and details
     */
    public function runProperty($name, $generator, $property) {
        echo "\nRunning property test: $name\n";
        echo "Iterations: {$this->iterations}\n";
        echo str_repeat("-", 60) . "\n";

        $failures = [];
        $passed = 0;

        for ($i = 0; $i < $this->iterations; $i++) {
            try {
                // Generate test data
                $data = $generator();

                // Test the property
                $result = $property($data);

                if ($result === true) {
                    $passed++;
                    if ($this->verbose) {
                        echo ".";
                    }
                } else {
                    $failures[] = [
                        'iteration' => $i + 1,
                        'data' => $data,
                        'result' => $result
                    ];
                    if ($this->verbose) {
                        echo "F";
                    }
                }
            } catch (Exception $e) {
                $failures[] = [
                    'iteration' => $i + 1,
                    'data' => isset($data) ? $data : null,
                    'exception' => $e->getMessage()
                ];
                if ($this->verbose) {
                    echo "E";
                }
            }
        }

        if ($this->verbose) {
            echo "\n";
        }

        echo str_repeat("-", 60) . "\n";

        if (empty($failures)) {
            echo "✓ PASSED: All {$this->iterations} iterations passed\n";
            return [
                'success' => true,
                'passed' => $passed,
                'failed' => 0,
                'total' => $this->iterations
            ];
        } else {
            echo "✗ FAILED: " . count($failures) . " out of {$this->iterations} iterations failed\n";
            echo "\nFirst failure:\n";
            $firstFailure = $failures[0];
            echo "  Iteration: {$firstFailure['iteration']}\n";
            echo "  Data: " . json_encode($firstFailure['data'], JSON_PRETTY_PRINT) . "\n";
            if (isset($firstFailure['exception'])) {
                echo "  Exception: {$firstFailure['exception']}\n";
            } else {
                echo "  Result: " . json_encode($firstFailure['result']) . "\n";
            }

            return [
                'success' => false,
                'passed' => $passed,
                'failed' => count($failures),
                'total' => $this->iterations,
                'failures' => $failures
            ];
        }
    }

    /**
     * Assert that a condition is true
     * 
     * @param bool $condition Condition to check
     * @param string $message Error message if condition is false
     * @return bool True if condition is true
     * @throws Exception if condition is false
     */
    public static function assertTrue($condition, $message = "Assertion failed") {
        if (!$condition) {
            throw new Exception($message);
        }
        return true;
    }

    /**
     * Assert that two values are equal
     * 
     * @param mixed $expected Expected value
     * @param mixed $actual Actual value
     * @param string $message Error message if values are not equal
     * @return bool True if values are equal
     * @throws Exception if values are not equal
     */
    public static function assertEquals($expected, $actual, $message = "Values are not equal") {
        if ($expected !== $actual) {
            throw new Exception("$message. Expected: " . json_encode($expected) . ", Actual: " . json_encode($actual));
        }
        return true;
    }

    /**
     * Assert that a value is not null
     * 
     * @param mixed $value Value to check
     * @param string $message Error message if value is null
     * @return bool True if value is not null
     * @throws Exception if value is null
     */
    public static function assertNotNull($value, $message = "Value is null") {
        if ($value === null) {
            throw new Exception($message);
        }
        return true;
    }

    /**
     * Assert that a value is null
     * 
     * @param mixed $value Value to check
     * @param string $message Error message if value is not null
     * @return bool True if value is null
     * @throws Exception if value is not null
     */
    public static function assertNull($value, $message = "Value is not null") {
        if ($value !== null) {
            throw new Exception($message);
        }
        return true;
    }
}
