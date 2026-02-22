-- Migration to create whatsapp_messages table if it doesn't exist
-- and ensure it has the organization_id column for SaaS support

CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `executive_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `executive_id` (`executive_id`),
  KEY `organization_id` (`organization_id`),
  CONSTRAINT `fk_whatsapp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If table already existed but organization_id was missing (unlikely given previous migrations but good for safety)
SET @dbname = DATABASE();
SET @tablename = "whatsapp_messages";
SET @columnname = "organization_id";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND COLUMN_NAME = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE whatsapp_messages ADD COLUMN organization_id INT NOT NULL AFTER id, ADD CONSTRAINT fk_whatsapp_org_alt FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
