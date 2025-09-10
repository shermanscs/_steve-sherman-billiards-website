<?php
/**
 * Simple Direct API Test
 * This script directly includes the API class and tests it without HTTP requests
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Direct API Test</h1>";

// Test 1: Check if files exist
echo "<h2>File Check</h2>";
if (file_exists('drill-api.php')) {
    echo "✅ drill-api.php exists<br>";
} else {
    echo "❌ drill-api.php missing<br>";
    exit;
}

if (file_exists('wp-config.php')) {
    echo "✅ wp-config.php exists<br>";
} else {
    echo "❌ wp-config.php missing<br>";
    exit;
}

// Test 2: Try to load the API class directly
echo "<h2>API Class Test</h2>";

// Capture any output from the API file
ob_start();

try {
    // Mock the request environment for testing
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/drill-api.php/version';
    $_SERVER['SCRIPT_NAME'] = '/drill-api.php';
    
    // Include the API file
    include 'drill-api.php';
    
} catch (Exception $e) {
    echo "❌ Exception loading API: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "❌ Error loading API: " . $e->getMessage() . "<br>";
}

$output = ob_get_clean();

if (!empty($output)) {
    echo "<h3>API Output:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Try to decode as JSON
    $decoded = json_decode($output, true);
    if ($decoded) {
        echo "<h3>Parsed JSON:</h3>";
        echo "<pre>" . print_r($decoded, true) . "</pre>";
        
        if (isset($decoded['success']) && $decoded['success']) {
            echo "✅ API test successful!<br>";
        } else {
            echo "❌ API returned error: " . ($decoded['message'] ?? 'Unknown error') . "<br>";
        }
    } else {
        echo "❌ API output is not valid JSON<br>";
    }
} else {
    echo "❌ No output from API<br>";
}

// Test 3: Manual HTTP request test
echo "<h2>HTTP Request Test</h2>";

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$currentDir = dirname($_SERVER['REQUEST_URI']);
$baseUrl = "$protocol://$host$currentDir";

echo "Base URL: $baseUrl<br>";

$testEndpoints = [
    'version',
    'users', 
    'categories',
    'skills',
    'drills',
    'challenge-scoring-methods',
    'challenge-events'
];

foreach ($testEndpoints as $endpoint) {
    $url = "$baseUrl/drill-api.php/$endpoint";
    echo "<h3>Testing: $endpoint</h3>";
    echo "URL: $url<br>";
    
    if (function_exists('curl_init')) {
        // Test with cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "HTTP Code: $httpCode<br>";
        
        if ($error) {
            echo "❌ cURL Error: $error<br>";
        } elseif ($httpCode >= 400) {
            echo "❌ HTTP Error $httpCode<br>";
            echo "Response: " . htmlspecialchars(substr($result, 0, 500)) . "<br>";
        } else {
            $decoded = json_decode($result, true);
            if ($decoded && isset($decoded['success'])) {
                if ($decoded['success']) {
                    echo "✅ Success<br>";
                    if (isset($decoded['data']) && is_array($decoded['data'])) {
                        echo "Data items: " . count($decoded['data']) . "<br>";
                    }
                } else {
                    echo "❌ API Error: " . $decoded['message'] . "<br>";
                }
            } else {
                echo "❌ Invalid JSON response<br>";
                echo "Raw: " . htmlspecialchars(substr($result, 0, 200)) . "...<br>";
            }
        }
    } else {
        echo "❌ cURL not available<br>";
        
        // Try with file_get_contents
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Content-Type: application/json\r\n",
                'timeout' => 30
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        if ($result) {
            echo "✅ file_get_contents success<br>";
            $decoded = json_decode($result, true);
            if ($decoded && isset($decoded['success']) && $decoded['success']) {
                echo "✅ Valid API response<br>";
            } else {
                echo "❌ Invalid API response<br>";
                echo "Raw: " . htmlspecialchars(substr($result, 0, 200)) . "...<br>";
            }
        } else {
            echo "❌ file_get_contents failed<br>";
        }
    }
    
    echo "<hr>";
}

// Test 4: Challenge Event Creation Test
echo "<h2>Challenge Event Creation Test</h2>";

$testData = [
    'series_name' => 'Test Series',
    'title' => 'Test Event ' . date('Y-m-d H:i:s'),
    'drill_id' => 1, // Assuming drill ID 1 exists
    'scoring_method_id' => 1, // Assuming scoring method ID 1 exists
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+7 days')),
    'status' => 'scheduled',
    'description' => 'Test event created by API test',
    'max_attempts' => 3,
    'created_by' => 1, // Assuming user ID 1 exists
    'participants' => []
];

echo "Test data:<br>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

if (function_exists('curl_init')) {
    $url = "$baseUrl/drill-api.php/challenge-events";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "Create Event HTTP Code: $httpCode<br>";
    
    if ($error) {
        echo "❌ cURL Error: $error<br>";
    } elseif ($httpCode >= 400) {
        echo "❌ HTTP Error $httpCode<br>";
        echo "Response: " . htmlspecialchars($result) . "<br>";
    } else {
        echo "✅ Request completed<br>";
        $decoded = json_decode($result, true);
        if ($decoded) {
            echo "Response: <pre>" . json_encode($decoded, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "Raw response: " . htmlspecialchars($result) . "<br>";
        }
    }
} else {
    echo "❌ cURL not available for POST test<br>";
}

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li>If API file loading fails, check PHP syntax errors</li>";
echo "<li>If database connection fails, check wp-config.php</li>";
echo "<li>If HTTP requests fail, check server configuration</li>";
echo "<li>Check server error logs for detailed error messages</li>";
echo "</ul>";

?>
