@echo off
REM deploy.bat - Windows deployment script for Hotel Senang Hati

echo 🏨 Hotel Senang Hati - Automated Deployment
echo =============================================

REM Check if Vercel CLI is installed
vercel --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Vercel CLI not found. Installing...
    npm install -g vercel
)

REM Check if we're in the right directory
if not exist "vercel.json" (
    echo ❌ Not in the correct directory. Please run from hotel-app folder.
    pause
    exit /b 1
)

echo ✅ Environment check passed

REM Deploy to Vercel
echo 🚀 Deploying to Vercel...
vercel --prod

echo.
echo 🎉 Deployment completed!
echo.
echo Next steps:
echo 1. Set environment variables in Vercel dashboard
echo 2. Configure PlanetScale database  
echo 3. Test your live website
echo.
echo 📖 See VERCEL-DEPLOYMENT.md for detailed instructions
pause
