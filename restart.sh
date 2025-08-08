#!/bin/bash

echo "🔄 FOCJ PHP Application Restarting..."

# Stop existing containers
echo "Stopping existing containers..."
docker-compose down

# Build and start containers
echo "Building and starting containers..."
docker-compose up -d --build

# Wait for database
echo "Waiting for database..."
sleep 5

# Create tables
echo "Creating database tables..."
docker exec -it focj-db psql -U focj_user -d focj_db << EOF
-- 登録申込テーブル
CREATE TABLE IF NOT EXISTS registrations (
    id SERIAL PRIMARY KEY,
    family_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    family_name_kana VARCHAR(50) NOT NULL,
    first_name_kana VARCHAR(50) NOT NULL,
    name_alphabet VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    prefecture VARCHAR(20) NOT NULL,
    city_address VARCHAR(255) NOT NULL,
    building_name VARCHAR(255),
    phone_number VARCHAR(20) NOT NULL,
    mobile_number VARCHAR(20),
    email VARCHAR(255) NOT NULL,
    birth_date DATE,
    gender VARCHAR(10),
    occupation VARCHAR(100),
    company_name VARCHAR(255),
    car_model VARCHAR(100),
    model_year INTEGER,
    car_color VARCHAR(50),
    drivers_license_file VARCHAR(255),
    vehicle_inspection_file VARCHAR(255),
    business_card_file VARCHAR(255),
    how_found VARCHAR(50),
    how_found_other TEXT,
    comments TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP,
    approved_by INTEGER,
    rejection_reason TEXT
);

CREATE INDEX IF NOT EXISTS idx_registrations_status ON registrations(status);
CREATE INDEX IF NOT EXISTS idx_registrations_email ON registrations(email);
CREATE INDEX IF NOT EXISTS idx_registrations_created_at ON registrations(created_at);
EOF

# Show container status
echo ""
echo "📊 Container Status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

echo ""
echo "✅ Setup complete!"
echo ""
echo "📍 Access URLs:"
echo "   - Application: http://localhost:6500/"
echo "   - Registration Form: http://localhost:6500/registration/"
echo "   - Admin Panel: http://localhost:6500/admin/ (not implemented yet)"
echo "   - Database Admin: http://localhost:6502"
echo ""
echo "📝 Database Credentials (for Adminer):"
echo "   - Server: focj-db"
echo "   - Username: focj_user"
echo "   - Password: focj_password"
echo "   - Database: focj_db"