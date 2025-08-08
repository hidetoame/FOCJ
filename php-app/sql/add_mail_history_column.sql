-- Add sent_by column to mail_history table
ALTER TABLE mail_history 
ADD COLUMN IF NOT EXISTS sent_by VARCHAR(100);