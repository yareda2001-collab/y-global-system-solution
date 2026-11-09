-- Table: users
-- Purpose: Stores user information, including authentication and profile details
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) DEFAULT NULL, -- NULL for Google OAuth users
    `role` ENUM('user', 'admin', 'super_admin') NOT NULL DEFAULT 'user',
    `status` TINYINT(1) DEFAULT 1,
    `force_password_change` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `profile_picture` VARCHAR(255) DEFAULT NULL,
    `provider` VARCHAR(50) DEFAULT NULL, -- e.g., 'google'
    `provider_id` VARCHAR(255) DEFAULT NULL UNIQUE -- Google user ID
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Table: admin_rights
-- Purpose: Defines specific permissions for admin users
-- Status: Unchanged
CREATE TABLE IF NOT EXISTS `admin_rights` (
    `admin_id` INT PRIMARY KEY,
    `can_create_admins` TINYINT(1) DEFAULT 0,
    `can_reset_passwords` TINYINT(1) DEFAULT 0,
    `can_manage_users` TINYINT(1) DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: password_reset_requests
-- Purpose: Manages password reset requests with OTP and token
-- Status: Unchanged
CREATE TABLE IF NOT EXISTS `password_reset_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `otp` VARCHAR(6) DEFAULT NULL,
    `otp_expires_at` DATETIME DEFAULT NULL,
    `requested_at` DATETIME NOT NULL,
    `status` ENUM('pending', 'approved', 'expired', 'completed') DEFAULT 'pending',
    `token` VARCHAR(64) UNIQUE DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: contact
-- Purpose: Stores contact form submissions
-- Status: Unchanged
CREATE TABLE IF NOT EXISTS `contact` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: news
-- Purpose: Stores news articles with title, content, and optional image
-- Status: Unchanged
CREATE TABLE IF NOT EXISTS `news` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: posts
-- Purpose: Stores blog posts with title and content
-- Status: Modified (removed image_path column)
CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: images
-- Purpose: Stores image URLs for various entities (e.g., posts)
-- Status: Unchanged
CREATE TABLE IF NOT EXISTS `images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `image_url` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: post_images
-- Purpose: Links posts to multiple images (many-to-many relationship)
-- Status: New (created to support multiple images per post)
CREATE TABLE IF NOT EXISTS `post_images` (
    `post_id` INT(11) NOT NULL,
    `image_id` INT(11) NOT NULL,
    PRIMARY KEY (`post_id`, `image_id`),
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`image_id`) REFERENCES `images`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: portfolio
-- Purpose: Stores portfolio items with title, link, and description
-- Status: Modified (removed image column)
CREATE TABLE IF NOT EXISTS `portfolio` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `link` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: portfolio_images
-- Purpose: Stores image URLs specifically for portfolio items
-- Status: New (created to differentiate from images table used for posts)
CREATE TABLE IF NOT EXISTS `portfolio_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `image_url` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: portfolio_image_mappings
-- Purpose: Links portfolio items to multiple images (many-to-many relationship)
-- Status: New (created to support multiple images per portfolio)
CREATE TABLE IF NOT EXISTS `portfolio_image_mappings` (
    `portfolio_id` INT(11) NOT NULL,
    `image_id` INT(11) NOT NULL,
    PRIMARY KEY (`portfolio_id`, `image_id`),
    FOREIGN KEY (`portfolio_id`) REFERENCES `portfolio`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`image_id`) REFERENCES `portfolio_images`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Table: messages
-- Purpose: Stores chat messages between users
-- Status: Unchanged
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `sender_id` INT(11) DEFAULT NULL,
    `receiver_id` INT(11) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `is_admin` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_seen` TINYINT(1) DEFAULT 0,
    `seen_by_user` TINYINT(1) DEFAULT 0,
    `is_edited` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
