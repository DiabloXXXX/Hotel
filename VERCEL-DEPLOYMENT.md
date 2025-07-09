# 🚀 VERCEL + PLANETSCALE DEPLOYMENT GUIDE
# Hotel Senang Hati - Complete Setup

## 📋 Prerequisites
- Node.js installed
- Git installed
- GitHub account
- PlanetScale account
- Vercel account

## 🗄️ STEP 1: Setup PlanetScale Database

### 1.1 Create PlanetScale Account
```
1. Go to https://planetscale.com
2. Sign up with GitHub
3. Verify your account
```

### 1.2 Create Database
```
1. Click "New database"
2. Name: hotel-senang-hati
3. Region: us-east-1 (or closest to you)
4. Click "Create database"
```

### 1.3 Import Schema
```
1. Go to database dashboard
2. Click "Console" tab
3. Copy content from database/schema.sql
4. Paste and execute in console
```

### 1.4 Create Production Password
```
1. Go to "Settings" → "Passwords"
2. Click "New password"
3. Name: production
4. Role: Administrator
5. Save the credentials:
   - Host: aws.connect.psdb.cloud
   - Username: [generated]
   - Password: [generated]
   - Database: hotel-senang-hati
```

## 🌐 STEP 2: Deploy to Vercel

### 2.1 Install Vercel CLI
```bash
npm install -g vercel
```

### 2.2 Login to Vercel
```bash
vercel login
```

### 2.3 Deploy from hotel-app folder
```bash
cd hotel-app
vercel
```

Follow the prompts:
- Link to existing project? N
- What's your project's name? hotel-senang-hati
- In which directory is your code located? ./
- Want to override the settings? N

### 2.4 Set Environment Variables
```bash
vercel env add DB_HOST
# Enter: aws.connect.psdb.cloud

vercel env add DB_NAME  
# Enter: hotel-senang-hati

vercel env add DB_USER
# Enter: your-planetscale-username

vercel env add DB_PASS
# Enter: your-planetscale-password

vercel env add DB_PORT
# Enter: 3306

vercel env add APP_ENV
# Enter: production

vercel env add APP_DEBUG
# Enter: false
```

### 2.5 Deploy to Production
```bash
vercel --prod
```

## 🎯 STEP 3: Test Your Deployment

Your website will be available at:
`https://hotel-senang-hati.vercel.app`

Test these features:
- ✅ Homepage loads
- ✅ Room booking works
- ✅ Database connection successful
- ✅ Reservation system functional
- ✅ WhatsApp integration working

## 🔧 STEP 4: Configure Custom Domain (Optional)

1. Go to Vercel dashboard
2. Select your project
3. Go to "Settings" → "Domains"
4. Add your custom domain
5. Update DNS records as instructed

## 🚨 Troubleshooting

### Database Connection Issues:
```bash
# Check environment variables
vercel env ls

# View deployment logs
vercel logs

# Test database connection
```

### Common Issues:
- **500 Error**: Check PHP syntax and database credentials
- **Database timeout**: Verify PlanetScale credentials
- **CSS/JS not loading**: Check file paths in vercel.json

## 📱 Expected Result

**Your live website will have:**
- 🏨 Professional hotel website
- 📱 Mobile-responsive design
- 🛏️ Working room booking system
- 📋 Reservation management
- 🔐 Admin dashboard access
- 📞 WhatsApp integration
- ⚡ Fast global loading via CDN

## 🔄 Future Updates

To update your live website:
```bash
# Make changes to your code
git add .
git commit -m "Update website"
git push

# Or redeploy directly
vercel --prod
```

## 📞 Support

If you need help:
1. Check Vercel deployment logs
2. Test PlanetScale connection in console
3. Verify all environment variables are set
4. Check PHP error logs

**Your Hotel Senang Hati website will be live and ready for bookings!** 🎉
