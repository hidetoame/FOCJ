-- MySQLに不足しているカラムを追加
ALTER TABLE registrations 
ADD COLUMN IF NOT EXISTS how_found VARCHAR(50) AFTER referrer2,
ADD COLUMN IF NOT EXISTS how_found_other TEXT AFTER how_found,
ADD COLUMN IF NOT EXISTS comments TEXT AFTER how_found_other;

-- 他の不足している可能性のあるカラムも追加
ALTER TABLE registrations 
ADD COLUMN IF NOT EXISTS other_cars TEXT AFTER car_number,
ADD COLUMN IF NOT EXISTS schedule_url VARCHAR(255) AFTER self_introduction;