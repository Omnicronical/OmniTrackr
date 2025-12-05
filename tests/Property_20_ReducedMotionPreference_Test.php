<?php
/**
 * Feature: omnitrackr, Property 20: Reduced motion preference respect
 * Validates: Requirements 9.5
 * 
 * Property: For any user with reduced motion preferences enabled, 
 * the system should disable or minimize animations throughout the interface.
 * 
 * This test verifies that the CSS properly respects the prefers-reduced-motion
 * media query and that animation durations are minimized when this preference is set.
 */

require_once __DIR__ . '/PropertyTestRunner.php';

// Create test runner
$runner = new PropertyTestRunner(100, false);

// Generator: Returns iteration number (we're testing static files)
$generator = function() {
    return ['iteration' => rand(1, 100)];
};

// Property: CSS and JavaScript must properly implement reduced motion support
$property = function($data) {
    $cssFile = __DIR__ . '/../public/css/main.css';
    $jsFile = __DIR__ . '/../public/js/app.js';
    
    // Verify CSS file exists
    PropertyTestRunner::assertTrue(
        file_exists($cssFile),
        "CSS file must exist at: $cssFile"
    );
    
    // Verify JavaScript file exists
    PropertyTestRunner::assertTrue(
        file_exists($jsFile),
        "JavaScript file must exist at: $jsFile"
    );
    
    $cssContent = file_get_contents($cssFile);
    $jsContent = file_get_contents($jsFile);
    
    // Test 1: CSS must contain prefers-reduced-motion media query
    PropertyTestRunner::assertTrue(
        strpos($cssContent, '@media (prefers-reduced-motion: reduce)') !== false,
        "CSS must contain @media (prefers-reduced-motion: reduce) query"
    );
    
    // Test 2: Extract and verify reduced motion block content
    preg_match(
        '/@media \(prefers-reduced-motion: reduce\)\s*\{([^}]+(?:\{[^}]*\}[^}]*)*)\}/s',
        $cssContent,
        $matches
    );
    
    PropertyTestRunner::assertTrue(
        !empty($matches[1]),
        "Could not extract reduced motion media query content"
    );
    
    $reducedMotionBlock = $matches[1];
    
    // Test 3: Verify animation-duration is minimized
    PropertyTestRunner::assertTrue(
        strpos($reducedMotionBlock, 'animation-duration: 0.01ms !important') !== false,
        "Reduced motion block must set animation-duration to minimal value with !important"
    );
    
    // Test 4: Verify transition-duration is minimized
    PropertyTestRunner::assertTrue(
        strpos($reducedMotionBlock, 'transition-duration: 0.01ms !important') !== false,
        "Reduced motion block must set transition-duration to minimal value with !important"
    );
    
    // Test 5: Verify scroll-behavior is set to auto
    PropertyTestRunner::assertTrue(
        strpos($reducedMotionBlock, 'scroll-behavior: auto') !== false,
        "Reduced motion block must set scroll-behavior to auto"
    );
    
    // Test 6: Verify transforms are disabled
    PropertyTestRunner::assertTrue(
        strpos($reducedMotionBlock, 'transform: none') !== false ||
        strpos($reducedMotionBlock, 'transform:none') !== false,
        "Reduced motion block should disable transforms"
    );
    
    // Test 7: JavaScript must detect prefers-reduced-motion
    PropertyTestRunner::assertTrue(
        strpos($jsContent, 'prefers-reduced-motion') !== false,
        "JavaScript must check for prefers-reduced-motion preference"
    );
    
    // Test 8: JavaScript must track reduced motion preference in state
    PropertyTestRunner::assertTrue(
        strpos($jsContent, 'prefersReducedMotion') !== false,
        "JavaScript must track reduced motion preference in state"
    );
    
    // Test 9: JavaScript must conditionally apply animations
    $hasConditionalLogic = (
        preg_match('/if\s*\(\s*[^)]*prefersReducedMotion[^)]*\)/', $jsContent) ||
        preg_match('/prefersReducedMotion\s*\?/', $jsContent)
    );
    
    PropertyTestRunner::assertTrue(
        $hasConditionalLogic,
        "JavaScript should conditionally apply animations based on prefersReducedMotion"
    );
    
    // Test 10: Scroll behavior must respect reduced motion
    $hasScrollBehaviorLogic = (
        preg_match('/behavior:\s*[^}]*prefersReducedMotion[^}]*[\'"]auto[\'"]/', $jsContent) ||
        preg_match('/scroll-behavior:\s*auto/', $jsContent)
    );
    
    PropertyTestRunner::assertTrue(
        $hasScrollBehaviorLogic,
        "Scroll behavior should respect reduced motion preference"
    );
    
    return true;
};

// Run the property test
$result = $runner->runProperty(
    "Property 20: Reduced motion preference respect",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
