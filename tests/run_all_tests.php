<?php
/**
 * Run All Property-Based Tests
 * 
 * This script runs all authentication property tests
 */

echo "=============================================================\n";
echo "OmniTrackr Property-Based Tests\n";
echo "=============================================================\n\n";

$tests = [
    // Activity Management Tests
    'Property_1_ActivityCreationPersistence_Test.php' => 'Property 1: Activity Creation Persistence',
    'Property_2_CategoryTagAssociationIntegrity_Test.php' => 'Property 2: Category and Tag Association Integrity',
    'Property_3_OptionalFieldDefaults_Test.php' => 'Property 3: Optional Field Defaults',
    'Property_4_ActivityUpdatePreservation_Test.php' => 'Property 4: Activity Update Preservation',
    'Property_5_ActivityDeletionCascade_Test.php' => 'Property 5: Activity Deletion Cascade',
    
    // Category and Tag Management Tests
    'Property_6_EntityCreationUniqueness_Test.php' => 'Property 6: Entity Creation Uniqueness',
    'Property_7_EntityRenameAssociationPreservation_Test.php' => 'Property 7: Entity Rename Association Preservation',
    'Property_8_EntityDeletionCascade_Test.php' => 'Property 8: Entity Deletion Cascade',
    'Property_9_DuplicateNameRejection_Test.php' => 'Property 9: Duplicate Name Rejection',
    
    // User Activity Isolation
    'Property_10_UserActivityIsolation_Test.php' => 'Property 10: User Activity Isolation',
    
    // Filtering Tests
    'Property_12_MultiFilterConjunction_Test.php' => 'Property 12: Multi-Filter Conjunction',
    'Property_13_FilterClearRestoration_Test.php' => 'Property 13: Filter Clear Restoration',
    
    // Statistics Tests
    'Property_14_StatisticsAggregationAccuracy_Test.php' => 'Property 14: Statistics Aggregation Accuracy',
    'Property_15_VisualizationDataGeneration_Test.php' => 'Property 15: Visualization Data Generation',
    
    // Authentication Tests
    'Property_16_UserRegistrationEncryption_Test.php' => 'Property 16: User Registration with Encryption',
    'Property_17_AuthenticationValidCredentials_Test.php' => 'Property 17: Authentication with Valid Credentials',
    'Property_18_AuthenticationInvalidCredentials_Test.php' => 'Property 18: Authentication with Invalid Credentials',
    'Property_19_SessionTermination_Test.php' => 'Property 19: Session Termination on Logout',
    
    // Error Handling Tests
    'Property_22_DatabaseErrorHandling_Test.php' => 'Property 22: Database Error Handling'
];

$results = [];
$totalPassed = 0;
$totalFailed = 0;

foreach ($tests as $file => $name) {
    echo "\n";
    echo "=============================================================\n";
    echo "Running: $name\n";
    echo "=============================================================\n";
    
    $output = [];
    $returnCode = 0;
    
    exec("php \"" . __DIR__ . "/$file\" 2>&1", $output, $returnCode);
    
    $results[$name] = [
        'passed' => $returnCode === 0,
        'output' => implode("\n", $output)
    ];
    
    echo implode("\n", $output) . "\n";
    
    if ($returnCode === 0) {
        $totalPassed++;
    } else {
        $totalFailed++;
    }
}

echo "\n\n";
echo "=============================================================\n";
echo "Test Summary\n";
echo "=============================================================\n";
echo "Total Tests: " . count($tests) . "\n";
echo "Passed: $totalPassed\n";
echo "Failed: $totalFailed\n";
echo "=============================================================\n\n";

if ($totalFailed > 0) {
    echo "Failed Tests:\n";
    foreach ($results as $name => $result) {
        if (!$result['passed']) {
            echo "  - $name\n";
        }
    }
    echo "\n";
    exit(1);
} else {
    echo "✓ All tests passed!\n\n";
    exit(0);
}
