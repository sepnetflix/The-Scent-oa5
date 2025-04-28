-- Ensure you are using the correct database
USE the_scent;

-- Create the 'user' role user
INSERT INTO `users` (`name`, `email`, `password`, `role`, `created_at`)
VALUES
('Abc Def', 'abc@def.com', 'REPLACE_WITH_USER_HASH_GENERATED_BY_PHP', 'user', NOW());

-- Create the 'admin' role user
INSERT INTO `users` (`name`, `email`, `password`, `role`, `created_at`)
VALUES
('Scent Admin', 'webadmin@scent.com', 'REPLACE_WITH_ADMIN_HASH_GENERATED_BY_PHP', 'admin', NOW());

