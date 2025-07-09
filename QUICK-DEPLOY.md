# 🚀 Quick Deploy to Vercel + PlanetScale

## Step 1: Setup PlanetScale Database (5 minutes)

1. **Create PlanetScale Account**
   - Go to [planetscale.com](https://planetscale.com)
   - Sign up with GitHub (recommended)
   - Create new database: `hotel-senang-hati`

2. **Import Database Schema**
   ```bash
   # Install PlanetScale CLI
   npm install -g @planetscale/cli
   
   # Login to PlanetScale
   pscale auth login
   
   # Create database
   pscale database create hotel-senang-hati
   
   # Import schema
   pscale shell hotel-senang-hati main < database/schema.sql
   ```

3. **Get Connection Details**
   ```bash
   # Get connection string
   pscale connect hotel-senang-hati main --port 3309
   
   # Or create password for production
   pscale password create hotel-senang-hati main production
   ```

## Step 2: Deploy to Vercel (3 minutes)

1. **Install Vercel CLI**
   ```bash
   npm install -g vercel
   ```

2. **Deploy from hotel-app folder**
   ```bash
   cd hotel-app
   vercel
   ```

3. **Configure Environment Variables**
   - Go to Vercel Dashboard → Your Project → Settings → Environment Variables
   - Add these variables:
   ```
   DB_HOST=aws.connect.psdb.cloud
   DB_NAME=hotel-senang-hati
   DB_USER=your-planetscale-username
   DB_PASS=your-planetscale-password
   DB_PORT=3306
   ```

4. **Redeploy**
   ```bash
   vercel --prod
   ```

## Step 3: Test Your Live Website

Your website will be available at: `https://your-project.vercel.app`

**Test these features:**
- ✅ Home page loads
- ✅ Room booking form works
- ✅ Reservation system functional
- ✅ Database connection successful
- ✅ WhatsApp integration working

## 🎯 Expected Result

**Live Website Features:**
- 🏨 Professional hotel website
- 📱 Mobile-responsive design
- 🛏️ Room booking system
- 📋 Reservation management
- 💳 Payment tracking
- 📞 WhatsApp integration
- 🔐 Admin dashboard
- 📊 Analytics ready

**Performance:**
- ⚡ Fast loading (CDN)
- 🌍 Global availability
- 📈 Auto-scaling
- 🔒 SSL certificate
- 📱 Mobile optimized

## 🔧 Alternative: One-Click Deploy Options

### Option A: Railway (Easiest)
1. Push code to GitHub
2. Connect Railway to repo
3. Auto-deploy with MySQL included
4. Cost: $5/month after trial

### Option B: Netlify + Supabase
1. Deploy frontend to Netlify
2. Convert MySQL to PostgreSQL for Supabase
3. Free tier available

## 📞 Need Help?

If you encounter any issues:
1. Check Vercel deployment logs
2. Test PlanetScale connection
3. Verify environment variables
4. Check PHP compatibility

**Common Issues:**
- Database connection timeout → Check credentials
- 500 error → Check PHP syntax
- CSS/JS not loading → Check file paths
- Form submission fails → Check API endpoints

Ready to deploy? Let's start with PlanetScale setup!
