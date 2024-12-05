<?php
// Test password verification
$password = 'password123';
$hash = '$2y$10$YYKcURLLTlYGMuKVTkqVT.C5.6H3y9IOqvQnXUh0RMZXJmkXT2E6i';

echo "Testing password verification:\n";
echo "Password: $password\n";
echo "Hash: $hash\n";
echo "Verification result: " . (password_verify($password, $hash) ? "SUCCESS" : "FAILED") . "\n";

// Generate a new hash for comparison
$newHash = password_hash($password, PASSWORD_DEFAULT);
echo "\nNew hash generated: $newHash\n";
echo "New hash verification: " . (password_verify($password, $newHash) ? "SUCCESS" : "FAILED") . "\n";
?> 