-- Migration for whatsapp_messages table
ALTER TABLE whatsapp_messages ADD COLUMN organization_id INT NULL AFTER id;
UPDATE whatsapp_messages wm 
JOIN users u ON wm.executive_id = u.id 
SET wm.organization_id = u.organization_id 
WHERE wm.organization_id IS NULL;
-- For those without users (if any), set to default org
UPDATE whatsapp_messages SET organization_id = 1 WHERE organization_id IS NULL;
ALTER TABLE whatsapp_messages MODIFY organization_id INT NOT NULL;
ALTER TABLE whatsapp_messages ADD CONSTRAINT fk_whatsapp_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;
