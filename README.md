# FinTask - Personal Finance & Task Management Application

A modern web application built with Laravel and Vue.js for managing personal finances and tasks.

## Features

- **Dashboard** - Overview of financial summary, recent tasks, and budget status
- **Task Management** - Create, update, and track personal tasks with priority levels
- **Finance Tracking** - Record and categorize financial transactions
- **Budget Planning** - Set and monitor budgetary goals
- **Reports** - Detailed financial reports and analytics
- **Authentication** - Secure login and registration system

## Technology Stack

- **Backend**: Laravel 13.2.0 (PHP 8.4)
- **Frontend**: Vue 3.4.21 with Tailwind CSS 4.0.0
- **Database**: PostgreSQL
- **Build**: Vite 8.0.0 for asset bundling
- **Deployment**: Docker on Render

## Quick Start

### Local Development

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd fintask
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Setup database**
   ```bash
   # Create PostgreSQL database (if on local PostgreSQL server)
   createdb fintask
   createuser fintask
   
   # Run migrations
   php artisan migrate
   
   # Seed with test data (optional)
   php artisan db:seed
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

   Visit `http://localhost:8000` in your browser.

### Deployment

For deploying to Render (or other platforms), see [DEPLOYMENT.md](DEPLOYMENT.md).

**Quick summary:**
1. Push code to GitHub
2. Create services on Render dashboard (Web + MySQL)
3. Set environment variables for database connection
4. Redeploy to activate database credentials

## Database

The application uses MySQL with three main tables:

- **users** - User accounts and authentication
- **tasks** - Personal to-do items
- **transactions** - Financial transaction records

Run migrations to create tables:
```bash
php artisan migrate
```

### Test Credentials

After seeding the database:
- **Email**: `user@example.com`
- **Password**: `password`

## Project Structure

```
fintask/
├── app/                  # Laravel application code
│   ├── Http/            # Controllers, middleware, requests
│   ├── Models/          # Eloquent models (User, Task, Transaction)
│   └── Services/        # Business logic (FinanceService, TaskService)
├── resources/           # Frontend assets
│   ├── css/            # Stylesheets (fintask.css)
│   ├── js/             # Vue components and scripts (fintask.js)
│   └── views/          # Blade templates
├── routes/             # API and web routes
├── database/           # Migrations, factories, seeders
├── public/             # Web-accessible files
├── tests/              # Unit and feature tests
├── Dockerfile          # Docker image definition
└── render.yaml         # Render infrastructure config
```

## API Endpoints

Authentication:
- `POST /api/register` - Register new user
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `GET /api/user` - Get authenticated user

Tasks:
- `GET /api/tasks` - List user's tasks
- `POST /api/tasks` - Create new task
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task

Finance:
- `GET /api/transactions` - List transactions
- `POST /api/transactions` - Create transaction
- `GET /api/reports/summary` - Financial summary

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test file:
```bash
php artisan test tests/Feature/TaskApiTest.php
```

## Troubleshooting

### CSS/JS Not Styling
- Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)
- Hard refresh with `?v=2` query parameter
- Check that `public/css/` and `public/js/` directories exist with files

### Database Connection Error
- Verify PostgreSQL is running locally
- Check `.env` file has correct PostgreSQL credentials
- Verify `DB_CONNECTION=pgsql` in .env
- Run migrations: `php artisan migrate`
- For Render deployment, see Troubleshooting in [DEPLOYMENT.md](DEPLOYMENT.md)

### Port Already in Use
```bash
# Use different port
php artisan serve --port=8001
```

## Environment Variables

Required for production:
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=<base64-encoded-key>

DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=fintask
DB_USERNAME=fintask
DB_PASSWORD=<password>

LOG_STACK=stderr
SESSION_DRIVER=cookie
CACHE_STORE=array
```

## License

MIT License - see LICENSE file for details

## Support

For issues or questions:
1. Check [DEPLOYMENT.md](DEPLOYMENT.md) for deployment-related help
2. Review Laravel documentation: https://laravel.com/docs
3. Check test files for usage examples
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
