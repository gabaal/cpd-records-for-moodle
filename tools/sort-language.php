<?php
/**
 * Generate a key-sorted Moodle language file for review.
 */

declare(strict_types=1);

$source = __DIR__ . '/../cpdrecords/lang/en/local_cpdrecords.php';
$target = __DIR__ . '/../test-output/lang-sorted.php';

$content = file_get_contents($source);
$parts = preg_split('/(?=^\$string\[\'[^\']+\'\])/m', $content);
$header = array_shift($parts);
$entries = [];
foreach ($parts as $part) {
    if (preg_match('/^\$string\[\'([^\']+)\'\]/', $part, $matches)) {
        $entries[$matches[1]] = trim($part);
    }
}
ksort($entries, SORT_STRING);

if (!is_dir(dirname($target))) {
    mkdir(dirname($target), 0777, true);
}
file_put_contents($target, rtrim($header) . "\n\n" . implode("\n\n", $entries) . "\n");

echo 'Sorted ' . count($entries) . " language strings to {$target}\n";
