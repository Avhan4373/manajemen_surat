<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Manajemen Surat

Manajemen Surat is a Laravel-based application designed to manage and streamline the process of handling official correspondence. This project leverages Laravel's robust features to provide a user-friendly and efficient system for managing letters and notifications.

## Features

- **User Management**: Manage user roles and permissions.
- **Letter Management**: Create, update, and track letters.
- **Notifications**: Notify users about new letters and status updates.
- **PDF Generation**: Generate PDF versions of letters.
- **Database Seeding**: Preload the application with sample data for testing and development.

## Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   ```
2. Navigate to the project directory:
   ```bash
   cd manajemen-surat
   ```
3. Install dependencies:
   ```bash
   composer install
   npm install
   ```
4. Set up the environment file:
   ```bash
   cp .env.example .env
   ```
   Configure the `.env` file with your database and mail settings.

5. Run migrations and seed the database:
   ```bash
   php artisan migrate --seed
   ```
6. Start the development server:
   ```bash
   php artisan serve
   ```

## Usage

- Access the application at `http://localhost:8000`.
- Log in with seeded user credentials or create a new account.
- Manage letters, users, and notifications through the intuitive interface.

## Testing

Run the test suite to ensure the application is working as expected:
```bash
php artisan test
```

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
