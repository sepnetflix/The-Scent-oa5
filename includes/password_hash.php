<?php
// --- GENERATE HASHES ---
// Choose strong passwords for your users!
$passwordUser = 'UserPassword123!'; // Replace with a strong password
$passwordAdmin = 'AdminPassword123!'; // Replace with a strong password

// Use PASSWORD_DEFAULT for the best available algorithm (currently BCRYPT)
$hashUser = password_hash($passwordUser, PASSWORD_DEFAULT);
$hashAdmin = password_hash($passwordAdmin, PASSWORD_DEFAULT);

echo "User Hash: " . $hashUser . "\n";
echo "Admin Hash: " . $hashAdmin . "\n";

// --- Example Output (DO NOT USE THESE HASHES DIRECTLY) ---
// User Hash: $2y$10$XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
// Admin Hash: $2y$10$YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY
?>
