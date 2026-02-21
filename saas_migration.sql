-- SaaS Migration SQL for Calldesk CRM

-- 1. Create Organizations Table
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    plan ENUM('free', 'pro', 'enterprise') DEFAULT 'free',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Insert Default Organization for existing data
INSERT IGNORE INTO organizations (id, name) VALUES (1, 'Default Organization');

-- 3. Update Users Table
ALTER TABLE users ADD COLUMN organization_id INT NULL AFTER id;
UPDATE users SET organization_id = 1 WHERE organization_id IS NULL;
ALTER TABLE users MODIFY organization_id INT NOT NULL;
ALTER TABLE users ADD CONSTRAINT fk_user_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- 4. Update Lead Sources Table
ALTER TABLE lead_sources ADD COLUMN organization_id INT NULL AFTER id;
UPDATE lead_sources SET organization_id = 1 WHERE organization_id IS NULL;
-- Need to remove UNIQUE constraint on source_name if it exists, and make it unique per organization
ALTER TABLE lead_sources DROP INDEX source_name;
ALTER TABLE lead_sources ADD UNIQUE KEY unique_source_per_org (organization_id, source_name);
ALTER TABLE lead_sources MODIFY organization_id INT NOT NULL;
ALTER TABLE lead_sources ADD CONSTRAINT fk_source_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- 5. Update Leads Table
ALTER TABLE leads ADD COLUMN organization_id INT NULL AFTER id;
UPDATE leads SET organization_id = 1 WHERE organization_id IS NULL;
ALTER TABLE leads MODIFY organization_id INT NOT NULL;
ALTER TABLE leads ADD CONSTRAINT fk_lead_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- 6. Update Call Logs Table
ALTER TABLE call_logs ADD COLUMN organization_id INT NULL AFTER id;
UPDATE call_logs SET organization_id = 1 WHERE organization_id IS NULL;
ALTER TABLE call_logs MODIFY organization_id INT NOT NULL;
ALTER TABLE call_logs ADD CONSTRAINT fk_call_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- 7. Update Follow-up Table
ALTER TABLE follow_ups ADD COLUMN organization_id INT NULL AFTER id;
UPDATE follow_ups SET organization_id = 1 WHERE organization_id IS NULL;
ALTER TABLE follow_ups MODIFY organization_id INT NOT NULL;
ALTER TABLE follow_ups ADD CONSTRAINT fk_followup_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;
