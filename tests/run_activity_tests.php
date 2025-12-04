<?php
/**
 * Run All Activity Property Tests
 * 
 * This script runs all property-based tests for activity management
 */

echo "=============================================================\n";
echo "Running Activity Management Property Tests\n";
echo "=============================================================\n\n";

$tests = [
    'Property_1_ActivityCreationPersistence_Test.php',
    'Property_2_CategoryTagAssociationIntegrity_Test.php',
    'Property_3_OptionalFieldDefaults_Test.php',
    'Property_4_ActivityUpdatePreservation_Test.php',
    'Property_5_ActivityDeletionCascade_Test.php'
];

$results = [];
$totalPassed = 0;
$totalFailed = 0;

foreach ($tests as $test) {
    echo "\n";
    echo "Running: $test\n";
    echo "-------------------------------------------------------------\n";
    
    $output = [];
    $returnCode = 0;
    exec("php " . __DIR__ . "/$test 2>&1", $output, $returnCode);
    
    foreach ($output as $line) {
        echo $line . "\n";
    }
    
    $results[$test] = ($returnCode === 0);
    
    if ($returnCode === 0) {
        $totalPassed++;
    } else {
        $totalFailed++;
    }
}

echo "\n";
echo "=============================================================\n";
echo "Test Summary\n";
echo "=============================================================\n";

foreach ($results as $test => $passed) {
    $status = $passed ? "✓ PASSED" : "✗ FAILED";
    echo "$status - $test\n";
}

echo "\n";
echo "Total: " . count($tests) . " tests\n";
echo "Passed: $totalPassed\n";
echo "Failed: $totalFailed\n";
echo "\n";

if ($totalFailed > 0) {
    echo "Some tests failed. Please review the output above.\n";
    exit(1);
} else {
    echo "All tests passed!\n";
    exit(0);
}
