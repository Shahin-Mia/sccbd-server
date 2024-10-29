CREATE TABLE DESTINATIONS (
    id INT AUTO_INCREMENT PRIMARY KEY,                     -- Unique destination ID (auto-incremented)
    destination_name VARCHAR(50) NOT NULL,                 -- Destination name
    destination_thumbnail VARCHAR(100) NOT NULL UNIQUE,    -- Destination thumbnail
    destination_images VARCHAR(255),                       -- Path to destination images
    description TEXT,                                      -- Description
    published BOOLEAN,                                      -- published
    created_by INT,                                        -- ID of the user who created the destination
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,        -- Automatically set on record creation
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Automatically updated on changes
    FOREIGN KEY (created_by) REFERENCES USERS(id)          -- Add foreign key constraint
);
