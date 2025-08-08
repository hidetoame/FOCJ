-- 画像ファイル保存用のカラムを追加
ALTER TABLE registrations 
ADD COLUMN IF NOT EXISTS license_image VARCHAR(255),
ADD COLUMN IF NOT EXISTS vehicle_inspection_image VARCHAR(255),
ADD COLUMN IF NOT EXISTS business_card_image VARCHAR(255);

-- 既存のファイルカラムからデータを移行（必要に応じて）
UPDATE registrations 
SET license_image = drivers_license_file 
WHERE license_image IS NULL AND drivers_license_file IS NOT NULL;

UPDATE registrations 
SET vehicle_inspection_image = vehicle_inspection_file 
WHERE vehicle_inspection_image IS NULL AND vehicle_inspection_file IS NOT NULL;

UPDATE registrations 
SET business_card_image = business_card_file 
WHERE business_card_image IS NULL AND business_card_file IS NOT NULL;