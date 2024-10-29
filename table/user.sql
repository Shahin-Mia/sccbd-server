CREATE TABLE USERS (
    id INT AUTO_INCREMENT PRIMARY KEY,         -- Unique user ID (auto-incremented)
    username VARCHAR(50) NOT NULL,             -- Username (must be unique)
    email VARCHAR(100) NOT NULL UNIQUE,        -- Email (must be unique)
    password VARCHAR(255) NOT NULL,            -- Password (hashed, so length is longer)
    role ENUM('admin', 'maintainer', 'viewer', 'student') DEFAULT 'viewer', -- User role
    profile_image VARCHAR(255),                -- Path to profile image (optional)
    phone VARCHAR(255),                        -- phone (optional)
    api_key VARCHAR(255),                      -- Store api key of user
    api_key_hash VARCHAR(255),                 -- Store api key of user
    email_activation_token VARCHAR(255),       -- Store email activation token key of user
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Automatically set on record creation
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Automatically updated on changes
);