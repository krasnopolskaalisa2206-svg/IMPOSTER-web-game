<?php
// test_xdebug.php - Simple test file to verify Xdebug setup
echo "<h1>Xdebug Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if Xdebug is loaded
if (extension_loaded('xdebug')) {
    echo "<p style='color: green;'>✓ Xdebug is loaded!</p>";
    echo "<p>Xdebug Version: " . phpversion('xdebug') . "</p>";
} else {
    echo "<p style='color: red;'>✗ Xdebug is NOT loaded</p>";
}

// Test breakpoint - set a breakpoint on the next line in VS Code
$x = "Hello Xdebug!";
echo "<p>Variable x = $x</p>";

// Force a debug session (you can remove this after testing)
if (isset($_GET['XDEBUG_SESSION_START'])) {
    echo "<p>Debug session triggered!</p>";
}
?>