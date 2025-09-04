<?php
/**
 * Simple PHP Test Runner for Mithi Calendar Backend
 * 
 * This script runs basic functionality tests without requiring PHPUnit
 * Run with: php tests/backend/simple_test_runner.php
 */

echo "=== Mithi Calendar Backend Test Runner ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function runTest($testName, $testFunction) {
    global $totalTests, $passedTests, $failedTests;
    
    $totalTests++;
    echo "Running test: $testName... ";
    
    try {
        $result = $testFunction();
        if ($result === true) {
            echo "✅ PASSED\n";
            $passedTests++;
        } else {
            echo "❌ FAILED\n";
            $failedTests++;
        }
    } catch (Exception $e) {
        echo "❌ FAILED (Exception: " . $e->getMessage() . ")\n";
        $failedTests++;
    }
}

function assertTrue($condition, $message = "") {
    if (!$condition) {
        throw new Exception($message ?: "Assertion failed: expected true, got false");
    }
    return true;
}

function assertFalse($condition, $message = "") {
    if ($condition) {
        throw new Exception($message ?: "Assertion failed: expected false, got true");
    }
    return true;
}

function assertEquals($expected, $actual, $message = "") {
    if ($expected !== $actual) {
        throw new Exception($message ?: "Assertion failed: expected " . var_export($expected, true) . ", got " . var_export($actual, true));
    }
    return true;
}

function assertNotEmpty($value, $message = "") {
    if (empty($value)) {
        throw new Exception($message ?: "Assertion failed: expected non-empty value");
    }
    return true;
}

// Test 1: PHP Environment
runTest("PHP Environment Check", function() {
    assertTrue(version_compare(PHP_VERSION, '7.4.0', '>='), "PHP version should be 7.4 or higher");
    assertTrue(function_exists('curl_init'), "cURL extension should be available");
    assertTrue(function_exists('json_encode'), "JSON extension should be available");
    return true;
});

// Test 2: File System Access
runTest("File System Access", function() {
    $testFile = __DIR__ . '/../../backend/index.php';
    assertTrue(file_exists($testFile), "Backend index.php should exist");
    assertTrue(is_readable($testFile), "Backend index.php should be readable");
    return true;
});

// Test 3: Directory Structure
runTest("Directory Structure", function() {
    $requiredDirs = [
        __DIR__ . '/../../backend/classes',
        __DIR__ . '/../../backend/config',
        __DIR__ . '/../../backend/data'
    ];
    
    foreach ($requiredDirs as $dir) {
        assertTrue(is_dir($dir), "Directory should exist: " . basename($dir));
    }
    return true;
});

// Test 4: Configuration Files
runTest("Configuration Files", function() {
    $configFiles = [
        __DIR__ . '/../../backend/config/caldav.php',
        __DIR__ . '/../../backend/config/email.php'
    ];
    
    foreach ($configFiles as $file) {
        if (file_exists($file)) {
            assertTrue(is_readable($file), "Config file should be readable: " . basename($file));
        }
    }
    return true;
});

// Test 5: Basic PHP Functions
runTest("Basic PHP Functions", function() {
    assertEquals('test', strtolower('TEST'), "strtolower should work");
    assertEquals(3, count([1, 2, 3]), "count should work");
    assertNotEmpty('hello', "String should not be empty");
    return true;
});

// Test 6: JSON Functions
runTest("JSON Functions", function() {
    $data = ['test' => 'value'];
    $json = json_encode($data);
    $decoded = json_decode($json, true);
    assertEquals($data, $decoded, "JSON encode/decode should work");
    return true;
});

// Test 7: cURL Functions
runTest("cURL Functions", function() {
    assertTrue(function_exists('curl_init'), "cURL should be available");
    $ch = curl_init();
    assertTrue($ch !== false, "curl_init should return resource");
    curl_close($ch);
    return true;
});

// Test 8: Date Functions
runTest("Date Functions", function() {
    $date = date('Y-m-d');
    assertNotEmpty($date, "date() should return non-empty string");
    assertEquals(4, strlen(date('Y')), "Year should be 4 digits");
    return true;
});

// Test 9: Array Functions
runTest("Array Functions", function() {
    $array = [1, 2, 3];
    assertEquals(3, count($array), "Array count should work");
    assertEquals(1, array_shift($array), "array_shift should work");
    assertEquals(2, count($array), "Array count after shift should be correct");
    return true;
});

// Test 10: String Functions
runTest("String Functions", function() {
    $str = "Hello World";
    assertEquals("hello world", strtolower($str), "strtolower should work");
    assertEquals("HELLO WORLD", strtoupper($str), "strtoupper should work");
    assertEquals(11, strlen($str), "strlen should work");
    return true;
});

echo "\n=== Test Summary ===\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests ✅\n";
echo "Failed: $failedTests ❌\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n";

if ($failedTests === 0) {
    echo "\n🎉 All tests passed! Backend environment is ready.\n";
    exit(0);
} else {
    echo "\n⚠️ Some tests failed. Please check the environment setup.\n";
    exit(1);
}
