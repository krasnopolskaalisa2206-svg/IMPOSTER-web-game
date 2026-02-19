<?php
session_start();
require_once "system.dbh.inc.php";

header('Content-Type: application/json');

$result = wordAllocation(); // returns the word string or false

if (!$result) {
    echo json_encode(['success' => false]);
    exit();
}

// wordAllocation() only returns the word — you'll need to also return the category.
// If you modify wordAllocation() to return ['word' => ..., 'category' => ...], great.
if (is_array($result)) {
    echo json_encode([
        'word'     => $result['word'],
        'category' => $result['category']
    ]);
} else {
    echo json_encode([
        'word'     => $result,
        'category' => 'Unknown'
    ]);
}
?>