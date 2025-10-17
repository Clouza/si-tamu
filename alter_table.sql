-- add phone_number column to guest_entries table
ALTER TABLE guest_entries ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL;

-- update ktp_number to allow null values (make it optional)
-- note: sqlite doesn't support modify column directly, so we need to check if it's already nullable
-- if ktp_number was set as NOT NULL before, you may need to recreate the table

-- to verify the changes
-- SELECT * FROM guest_entries;
