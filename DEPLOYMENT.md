# FinTask Deployment Guide for Render

This guide explains how to deploy FinTask to Render with MySQL database support.

## Prerequisites

- GitHub account with this repository pushed
- Render.com account (free tier available)
- Git command-line tools

## Deployment Steps

### Step 1: Push Code to GitHub

```bash
git add -A
git commit -m "Prepare for Render deployment"
git push
```

### Step 2: Create Render Account

Go to [render.com](https://render.com) and sign up or log in.

### Step 3: Connect Repository

1. Go to [Render Dashboard](https://dashboard.render.com)
2. Click "New +"
3. Select "Web Service"
4. Click "Connect a repository"
5. Select "GitHub"
6. Authorize Render to access your GitHub account
7. Search for your `fintask` repository
8. Click "Connect"

### Step 4: Configure Web Service

When prompted to create a new web service, fill in:

**Basic Settings:**
- **Name:** `fintask` (or your preferred name)
- **Environment:** `Docker`
- **Plan:** `Standard` (minimum recommended for production)
- **Branch:** `main` (or your deployment branch)

**Environment Variables:**

Before deployment starts, add these environment variables:

1. Click "Advanced" or scroll to "Environment Variables"
2. Add the following variables:

| Key | Value |
|-----|-------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `LOG_CHANNEL` | `stack` |
| `LOG_STACK` | `stderr` |
| `SESSION_DRIVER` | `cookie` |
| `CACHE_STORE` | `array` |
| `QUEUE_CONNECTION` | `sync` |
| `DB_CONNECTION` | `mysql` |

3. Click "Create Web Service"

### Step 5: Wait for Initial Deployment

The web service will start building. This may take 5-10 minutes. Check the deployment logs.

**Expected logs:**
- Docker build starting
- Dependencies installing
- Migration messages (may skip if database not connected yet)
- Apache starting

### Step 6: Create MySQL Database

After the web service is created:

1. Go back to [Render Dashboard](https://dashboard.render.com)
2. Click "New +"
3. Click "MySQL"
4. **Name:** `fintask-mysql`
5. **Database Name:** `laravel`
6. **Database User:** `laravel`
7. **Region:** Same as your web service
8. **Plan:** `Free`
9. Click "Create Database"

Wait 3-5 minutes for the database to initialize and show connection details.

### Step 7: Get MySQL Connection Details

1. Click on your MySQL service (`fintask-mysql`)
2. In the "Connections" section, find:
   - **Host** (e.g., `mysql-xxxxx.render.com`)
   - **Port** (usually `3306`)
   - **Database:** `laravel`
   - **User:** `laravel`
   - **Password** (auto-generated)

### Step 8: Add Database Environment Variables to Web Service

1. Go back to your Web Service (`fintask`)
2. Click "Environment" tab
3. Add these variables with values from Step 7:

| Key | Value | Example |
|-----|-------|---------|
| `DB_HOST` | MySQL Host from connections | `mysql-xxxxx.render.com` |
| `DB_PORT` | `3306` | `3306` |
| `DB_DATABASE` | `laravel` | `laravel` |
| `DB_USERNAME` | `laravel` | `laravel` |
| `DB_PASSWORD` | Password from connections | (auto-generated string) |

4. Click "Save Changes"

This will trigger a **re-deployment** with the database credentials.

### Step 9: Verify Deployment

After re-deployment:

1. Go to your Web Service
2. Click on the URL shown at the top of the page
3. You should see the **FinTask login page with full styling** (CSS colors, fonts, layout)

### Step 10: Reset Database (Optional)

To reset the database with test data:

1. Go to your Web Service
2. Click "Shell" tab
3. Run these commands:

```bash
php artisan migrate:fresh --seed
```

This will:
- Drop all tables
- Run all migrations
- Seed test data

Test login with:
- **Email:** `user@example.com`
- **Password:** `password`

## Troubleshooting

### Issue: "CSS/JS Missing" or Unstyled Login Page

**Solution:** Clear the cache and hard-refresh the browser:
- Press `Ctrl+Shift+R` (Windows/Linux) or `Cmd+Shift+R` (Mac) to hard-refresh
- Or add a new query parameter: `https://your-app.render.com/?v=2`

### Issue: Database Connection Error

**Symptoms:** See "Connection refused" or "Host: 127.0.0.1" errors

**Steps to debug:**

1. Go to Web Service → "Logs"
2. Look for "Database Configuration:" section in logs
3. Check if `DB_HOST` shows:
   - Your MySQL host (e.g., `mysql-xxxxx.render.com`) → Database is properly connected
   - `127.0.0.1` → Environment variables not being read
4. Go to "Environment" tab and verify all database variables are set correctly
5. Redeploy by clicking "Manual Deploy" → "Deploy latest commit"

### Issue: MySQL Service Shows as "Suspended"

**Solution:**
- Free MySQL tier suspends after 90 days of inactivity
- Upgrade to a paid plan or create a new free database
- Update your Web Service environment variables with new credentials

### Issue: Login Fails Even with Database Connected

**Solution:**
1. Run database reset from shell:
   ```bash
   php artisan migrate:fresh --seed
   ```
2. Verify at least one user exists:
   ```bash
   php artisan tinker
   >>> User::count()
   ```
3. Try login with seeded user credentials

## Architecture Overview

```
Your GitHub Repository
         ↓
    Render.com
         ├─→ Web Service (Docker container with PHP 8.4-Apache)
         │    ├─ Runs your Laravel application
         │    ├─ Serves CSS and JavaScript assets
         │    └─ Handles HTTP requests
         └─→ MySQL Service (Database)
              ├─ Stores users, tasks, transactions
              └─ Backup automatically included
```

## Technology Stack

- **PHP:** 8.4 on Apache
- **Framework:** Laravel 13
- **Database:** MySQL
- **Frontend:** Vue 3 (loaded from CDN)
- **Styling:** Tailwind CSS + Custom CSS
- **Deployment:** Docker containers on Render

## Notes

- Assets (CSS, JS) are built into the Docker image, not downloaded at runtime
- Database credentials are injected at runtime via environment variables
- Logs are written to stderr (console output) for Render log collection
- Sessions use cookies (stateless) for compatibility with ephemeral containers

## Support

For Render-specific issues, see:
- [Render Docs](https://render.com/docs)
- [Render Dashboard](https://dashboard.render.com)

For Laravel-specific issues, see:
- [Laravel Documentation](https://laravel.com/docs)
