#!/bin/bash
# deploy.sh - Automated deployment script for Hotel Senang Hati

echo "🏨 Hotel Senang Hati - Automated Deployment"
echo "============================================="

# Check if Vercel CLI is installed
if ! command -v vercel &> /dev/null; then
    echo "❌ Vercel CLI not found. Installing..."
    npm install -g vercel
fi

# Check if we're in the right directory
if [ ! -f "vercel.json" ]; then
    echo "❌ Not in the correct directory. Please run from hotel-app folder."
    exit 1
fi

echo "✅ Environment check passed"

# Deploy to Vercel
echo "🚀 Deploying to Vercel..."
vercel --prod

echo ""
echo "🎉 Deployment completed!"
echo ""
echo "Next steps:"
echo "1. Set environment variables in Vercel dashboard"
echo "2. Configure PlanetScale database"
echo "3. Test your live website"
echo ""
echo "📖 See VERCEL-DEPLOYMENT.md for detailed instructions"
