# Hotel Senang Hati - Deployment Guide

## 🌐 Deployment Options

### Option 1: Vercel (Frontend) + PlanetScale (Database) ⭐ RECOMMENDED
- **Frontend**: Deploy to Vercel (gratis)
- **Database**: PlanetScale MySQL (gratis tier 5GB)
- **Setup**: Mudah, scalable, production-ready

### Option 2: Netlify + Supabase
- **Frontend**: Netlify (gratis)
- **Database**: Supabase PostgreSQL (gratis tier 500MB)
- **Setup**: Perlu convert MySQL ke PostgreSQL

### Option 3: Railway (Full Stack)
- **All-in-one**: Frontend + Backend + Database
- **Cost**: $5/month setelah trial
- **Setup**: Paling mudah untuk PHP + MySQL

## 🚀 Quick Deploy dengan PlanetScale + Vercel

### Step 1: Setup Database di PlanetScale
1. Buat akun di [planetscale.com](https://planetscale.com)
2. Create database: `hotel-senang-hati`
3. Copy connection string
4. Import schema.sql via PlanetScale CLI atau web console

### Step 2: Update Database Config
```php
// api/config/database.php
$host = $_ENV['DB_HOST'] ?? 'aws.connect.psdb.cloud';
$dbname = $_ENV['DB_NAME'] ?? 'hotel-senang-hati';
$username = $_ENV['DB_USER'] ?? 'your-username';
$password = $_ENV['DB_PASS'] ?? 'your-password';
$port = $_ENV['DB_PORT'] ?? 3306;

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/cert.pem'
];
```

### Step 3: Deploy ke Vercel
```bash
# Install Vercel CLI
npm i -g vercel

# Deploy dari folder hotel-app
cd hotel-app
vercel

# Set environment variables di Vercel dashboard:
# DB_HOST=your-planetscale-host
# DB_NAME=hotel-senang-hati  
# DB_USER=your-username
# DB_PASS=your-password
```

## 🛠️ Alternative: Deploy ke Railway (Easiest)

1. Push ke GitHub repository
2. Connect Railway ke GitHub repo
3. Railway auto-detect PHP + MySQL
4. Environment otomatis ter-setup

## 📦 File yang Diperlukan untuk Deployment

- ✅ vercel.json (sudah dibuat)
- ✅ package.json (untuk dependencies)
- ✅ .env.example (template environment)
- ✅ database/schema.sql (database structure)

## 🔧 Environment Variables Required

```
DB_HOST=your-database-host
DB_NAME=hotel_senang_hati
DB_USER=your-username  
DB_PASS=your-password
DB_PORT=3306
```

## 📱 Features After Deployment

- ✅ Responsive hotel website
- ✅ Room booking system
- ✅ Reservation management
- ✅ WhatsApp integration
- ✅ Admin dashboard
- ✅ Payment tracking
- ✅ Real-time availability

## 🎯 Recommended: PlanetScale + Vercel

**Why this combo?**
- 🆓 Free tier yang generous
- 🚀 Deploy dalam 5 menit
- 📈 Auto-scaling
- 🔒 Production-ready security
- 🌍 Global CDN
- 📊 Built-in analytics

Would you like me to help you set this up step by step?
