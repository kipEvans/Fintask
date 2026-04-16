# PostgreSQL Setup Guide for FinTask

## Overview

FinTask is now configured to use **PostgreSQL** (not MySQL). PostgreSQL is available as a free managed service on Render.com.

## Local Development Setup

### Install PostgreSQL

**macOS (using Homebrew):**
```bash
brew install postgresql@15
brew services start postgresql@15
```

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install postgresql postgresql-contrib
```

**Windows:**
Download from [postgresql.org](https://www.postgresql.org/download/windows/) or use [PostgreSQL Installer](https://www.pgadmin.org/download/pgadmin-4-windows/)

### Create Local Database

1. **Access PostgreSQL terminal:**
   ```bash
   psql -U postgres
   ```

2. **Create database and user:**
   ```sql
   CREATE DATABASE fintask;
   CREATE USER fintask WITH PASSWORD 'your_password_here';
   GRANT ALL PRIVILEGES ON DATABASE fintask TO fintask;
   \q
   ```

3. **Update `.env` file:**
   ```bash
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=fintask
   DB_USERNAME=fintask
   DB_PASSWORD=your_password_here
   ```

4. **Run migrations:**
   ```bash
   php artisan migrate
   php artisan db:seed  # Optional: load test data
   ```

5. **Login with test credentials:**
   - Email: `user@example.com`
   - Password: `password`

## Render.com Deployment

### Prerequisites
- GitHub account with code pushed
- Render.com account (free tier available)

### Step-by-Step Deployment

#### 1. Create Web Service

1. Go to [render.com/dashboard](https://dashboard.render.com)
2. Click **"New +"**
3. Select **"Web Service"**
4. Connect your GitHub repository
5. Fill in:
   - **Name**: `fintask`
   - **Environment**: `Docker`
   - **Plan**: `Standard` (minimum for production)
   - **Branch**: `main`
6. Add base environment variables:

   | Key | Value |
   |-----|-------|
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `LOG_CHANNEL` | `stack` |
   | `LOG_STACK` | `stderr` |
   | `SESSION_DRIVER` | `cookie` |
   | `CACHE_STORE` | `array` |
   | `QUEUE_CONNECTION` | `sync` |
   | `DB_CONNECTION` | `pgsql` |

7. Click **"Create Web Service"**
8. Wait for deployment to complete (5-10 minutes)

#### 2. Create PostgreSQL Database

1. Back in [Render Dashboard](https://dashboard.render.com)
2. Click **"New +"**
3. Click **"PostgreSQL"**
4. Fill in:
   - **Name**: `fintask-postgres`
   - **Database**: `fintask`
   - **User**: `fintask`
   - **Region**: Same as your web service
   - **Plan**: `Free`
5. Click **"Create Database"**
6. Wait 3-5 minutes for database to initialize

#### 3. Get PostgreSQL Connection Details

1. Click on **`fintask-postgres`** service
2. Click **"Connections"** at the top right
3. Note these values:
   - **Host**: (e.g., `dpg-cxxxxxxx.render.com`)
   - **Database**: `fintask`
   - **User**: `fintask`
   - **Password**: (auto-generated, shown once)
   - **Port**: `5432` (default)

**⚠️ Important**: Save the password - you can't retrieve it again!

#### 4. Add Database Credentials to Web Service

1. Go back to your **`fintask`** Web Service
2. Click **"Environment"** tab
3. Add these variables:

   | Key | Value |
   |-----|-------|
   | `DB_HOST` | Your PostgreSQL Host |
   | `DB_PORT` | `5432` |
   | `DB_DATABASE` | `fintask` |
   | `DB_USERNAME` | `fintask` |
   | `DB_PASSWORD` | Your PostgreSQL Password |

4. Click **"Save Changes"**
5. Render will **automatically redeploy** with new credentials

#### 5. Verify Deployment

1. Wait for redeployment to complete
2. Go to Web Service and click the URL at the top
3. You should see the **FinTask login page with full styling**
4. Try logging in with:
   - **Email**: `user@example.com`
   - **Password**: `password`

If login fails, check the next section for troubleshooting.

### 6. Initialize Database (Optional)

If login fails or you want fresh test data:

1. Go to your Web Service
2. Click **"Shell"** tab
3. Run these commands:

```bash
# Reset database with fresh schema and test data
php artisan migrate:fresh --seed
```

## Troubleshooting

### Issue: "Connection refused" or Database Errors

**Check logs:**
1. Go to Web Service → **"Logs"**
2. Look for the "Configuration" section that shows:
   ```
   Database (PostgreSQL):
     Host: dpg-xxxxx.render.com
     Port: 5432
     Database: fintask
     User: fintask
   ```
3. Check if `Host` shows your PostgreSQL domain or `127.0.0.1`:
   - **If `127.0.0.1`**: Environment variables not set correctly
   - **If your domain**: Database connection attempted, but may have credentials issue

**Solution:**
1. Go to Web Service → **"Environment"**
2. Verify all database variables are correct
3. Click **"Manual Deploy"** → **"Deploy Latest Commit"**
4. Check logs again after redeployment

### Issue: Unstyled/Plain HTML Login Page

**Solution:**
1. Hard refresh browser: `Ctrl+Shift+R` (Windows/Linux) or `Cmd+Shift+R` (Mac)
2. Or add cache buster: `https://your-app.render.com/?v=2`
3. Check browser DevTools (F12) → Console for any JavaScript errors

### Issue: Login Fails Even with Database Connected

**Verify database has users:**
1. Go to Web Service → **"Shell"**
2. Run:
   ```bash
   php artisan tinker
   >>> User::count()
   ```
   Should return `1` or more

3. If returns `0`, seed test data:
   ```bash
   php artisan db:seed
   ```

### Issue: PostgreSQL Service Shows "Suspended"

**Reason:** Free tier suspends after 90 days of inactivity

**Solution:**
- Upgrade to paid plan, OR
- Delete and recreate a new free database
- Update Web Service environment variables with new credentials

### Issue: Migrations Failed

**If migrations didn't run during deployment:**
1. Go to Web Service → **"Shell"**
2. Run manually:
   ```bash
   php artisan migrate --force
   php artisan db:seed  # If you want test data
   ```

## Understanding PostgreSQL in Docker

### Configuration Flow

```
Render Dashboard (Web Service)
    ↓
Environment Variables
    ↓
Docker Container
    ↓
docker-entrypoint.sh (generates .env)
    ↓
Laravel Configuration
    ↓
Database Connection
```

### Key Files

- **`render.yaml`**: Defines PostgreSQL and Web services on Render
- **`docker-entrypoint.sh`**: Generates `.env` file at runtime from environment variables
- **`config/database.php`**: Laravel database configuration (connects via PostgreSQL driver)
- **`.env.example`**: Template showing all required environment variables

## PostgreSQL Features Used in FinTask

- **User Authentication**: Stores hashed passwords, session management
- **Tasks**: Stores personal to-do items with completion status
- **Transactions**: Financial records with categories and amounts
- **Relationships**: Connects users to their tasks and transactions

## Performance Tips

### For Local Development
- PostgreSQL runs on `localhost:5432` by default
- Use `pg_dump` to backup your database:
  ```bash
  pg_dump fintask > backup.sql
  ```
- Restore with `psql fintask < backup.sql`

### For Render Production
- Free tier has connection limits but sufficient for personal use
- Database is automatically backed up by Render
- Logs visible in Render dashboard for debugging

## Upgrading Your Database Plan

If you outgrow the free tier:

1. Go to PostgreSQL service in Render dashboard
2. Click **"Plan"** tab
3. Select a **Standard** or **Professional** plan
4. Database continues running with no downtime (for Standard+)

Pricing scales with storage and compute needs.

## Next Steps

1. **Push code**: `git push`
2. **Deploy**: Follow deployment steps above
3. **Test**: Verify login and features work
4. **Customize**: Update UI, add features as needed

## Support & Resources

- **Render PostgreSQL Docs**: https://render.com/docs/postgresql
- **Laravel Database Docs**: https://laravel.com/docs/database
- **PostgreSQL Official**: https://www.postgresql.org/
- **Troubleshooting**: Check Render logs and Laravel error messages

---

**Questions?** Check [DEPLOYMENT.md](DEPLOYMENT.md) for more detailed deployment guidance.
