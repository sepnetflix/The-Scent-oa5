<?php
// models/User.php (Updated to implement getAddress)

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get user details by ID.
     * @param int $id User ID.
     * @return array|false User data array or false if not found.
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get user details by email address.
     * @param string $email Email address.
     * @return array|false User data array or false if not found.
     */
    public function getByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Creates a new user.
     * Assumes $data['password'] is already hashed.
     * Assumes 'newsletter_subscribed' and 'status' columns exist.
     *
     * @param array $data User data including name, email, password (hashed), role, newsletter preference.
     * @return int|false The ID of the newly created user or false on failure.
     */
    public function create($data) {
        // Assumes DB schema has: name, email, password, role, status, newsletter_subscribed, created_at, updated_at
        $sql = "
            INSERT INTO users (
                name, email, password, role, status, newsletter_subscribed, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";
        $stmt = $this->pdo->prepare($sql);

        $success = $stmt->execute([
            $data['name'],
            $data['email'],
            $data['password'], // Expecting already hashed password from controller
            $data['role'] ?? 'user',
            $data['status'] ?? 'active', // Default status to 'active'
            isset($data['newsletter']) ? (int)$data['newsletter'] : 0 // Convert boolean to int (0/1)
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : false;
    }

    /*
     * Removed generic update method - Replaced by specific update methods below.
     * public function update($id, $data) { ... }
     */

    /**
     * Deletes a user by ID.
     * @param int $id User ID.
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Verifies a user's password.
     * Renamed from validatePassword for clarity.
     *
     * @param int $userId User ID.
     * @param string $password The plain text password to verify.
     * @return bool True if the password is valid, false otherwise.
     */
    public function verifyPassword($userId, $password) {
        $user = $this->getById($userId);
        // Ensure user exists and password field is not empty before verifying
        return $user && !empty($user['password']) && password_verify($password, $user['password']);
    }

    /**
     * Gets the user's address details from the database.
     * Uses the address columns added to the 'users' table.
     *
     * @param int $userId User ID.
     * @return array|null Address data array or null if user not found.
     */
    public function getAddress(int $userId): ?array {
        // --- START FIX: Implement getAddress ---
        try {
            // Select the specific address columns from the users table
            $sql = "SELECT address_line1, address_line2, city, state, postal_code, country
                    FROM users
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);

            // fetch() returns false if no row is found. Return null in that case.
            return $address ?: null;

        } catch (PDOException $e) {
            // Log the error and return null if the query fails
            error_log("Error fetching address for user ID {$userId}: " . $e->getMessage());
            return null;
        }
        // --- END FIX ---
    }

    /**
     * Updates a user's basic information (name and email).
     * Assumes 'updated_at' column exists with ON UPDATE CURRENT_TIMESTAMP or is updated manually.
     *
     * @param int $userId User ID.
     * @param string $name New full name.
     * @param string $email New email address.
     * @return bool True on success, false on failure.
     */
    public function updateBasicInfo(int $userId, string $name, string $email): bool {
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $sql = "UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $email, $userId]);
    }

    /**
     * Checks if an email address is already registered by another user.
     *
     * @param string $email Email address to check.
     * @param int $currentUserId The ID of the user *currently* being updated (to exclude them from the check).
     * @return bool True if the email is taken by someone else, false otherwise.
     */
    public function isEmailTakenByOthers(string $email, int $currentUserId): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $currentUserId]);
        // If fetchColumn returns a value (an ID), it means the email is taken by another user.
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Sets or updates the password reset token and its expiry time for a user.
     * Assumes 'reset_token' and 'reset_token_expires_at' columns exist in the 'users' table.
     *
     * @param int $userId User ID.
     * @param string $token The secure reset token.
     * @param string $expiry SQL formatted DATETIME string for expiry.
     * @return bool True on success, false on failure.
     */
    public function setResetToken(int $userId, string $token, string $expiry): bool {
        // Assumes DB schema has: reset_token VARCHAR(255) NULL, reset_token_expires_at DATETIME NULL
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $sql = "UPDATE users SET reset_token = ?, reset_token_expires_at = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$token, $expiry, $userId]);
    }

    /**
     * Retrieves user data based on a valid (non-null and non-expired) password reset token.
     * Assumes 'reset_token' and 'reset_token_expires_at' columns exist.
     *
     * @param string $token The password reset token to search for.
     * @return array|false User data array or false if token is invalid/expired.
     */
    public function getUserByValidResetToken(string $token): ?array {
        // Assumes DB schema has: reset_token VARCHAR(255) NULL, reset_token_expires_at DATETIME NULL
        $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        return $user ?: null; // Return null instead of false for consistency
    }

    /**
     * Updates a user's password and clears the reset token information.
     * Assumes 'reset_token' and 'reset_token_expires_at' columns exist.
     *
     * @param int $userId User ID.
     * @param string $newPassword The new plain text password (will be hashed).
     * @return bool True on success, false on failure.
     */
    public function resetPassword(int $userId, string $newPassword): bool {
        // Assumes DB schema has: reset_token VARCHAR(255) NULL, reset_token_expires_at DATETIME NULL
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            error_log("Password hashing failed during password reset for user ID: {$userId}");
            return false; // Indicate failure
        }

        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Updates the user's newsletter subscription preference.
     * Assumes 'newsletter_subscribed' column (BOOLEAN/TINYINT) exists.
     *
     * @param int $userId User ID.
     * @param bool $isSubscribed True to subscribe, false to unsubscribe.
     * @return bool True on success, false on failure.
     */
    public function updateNewsletterPreference(int $userId, bool $isSubscribed): bool {
        // Assumes DB schema has: newsletter_subscribed BOOLEAN or TINYINT(1)
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $sql = "UPDATE users SET newsletter_subscribed = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([(int)$isSubscribed, $userId]); // Cast boolean to 0 or 1
    }

    /**
     * Updates only the user's password. Called after verifying the current password.
     *
     * @param int $userId User ID.
     * @param string $newPassword New plain text password (will be hashed).
     * @return bool True on success, false on failure.
     */
    public function updatePassword(int $userId, string $newPassword): bool {
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            error_log("Password hashing failed during password update for user ID: {$userId}");
            return false;
        }

        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }

} // End of User class
