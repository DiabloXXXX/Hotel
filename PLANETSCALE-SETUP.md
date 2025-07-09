# Step-by-Step PlanetScale Setup for Hotel Senang Hati

## 1. Create PlanetScale Account
1. Go to https://planetscale.com
2. Click "Sign up" and choose "Continue with GitHub" (recommended)
3. Complete the account setup

## 2. Create Database
1. Click "Create database"
2. Database name: `hotel-senang-hati`
3. Region: Choose closest to your users (e.g., `us-east` or `ap-southeast`)
4. Click "Create database"

## 3. Import Schema using Web Console
1. Go to your database dashboard
2. Click "Console" tab
3. Copy and paste the content from `database/schema.sql`
4. Click "Execute" to run the schema

## 4. Get Connection Details
1. Go to "Connect" tab
2. Click "Create password"
3. Name: `production`
4. Role: `Administrator`
5. Copy the connection details:
   - Host: `aws.connect.psdb.cloud`
   - Username: (generated)
   - Password: (generated)
   - Database: `hotel-senang-hati`

## 5. Test Connection (Optional)
You can test the connection using any MySQL client or the web console.

**Save these credentials - you'll need them for Vercel deployment!**

```
DB_HOST=aws.connect.psdb.cloud
DB_NAME=hotel-senang-hati
DB_USER=your-generated-username
DB_PASS=your-generated-password
DB_PORT=3306
```
