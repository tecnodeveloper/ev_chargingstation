# EV Charging Management System (Web Application)

A comprehensive Electric Vehicle (EV) charging management system built with Laravel 12, featuring a complete web interface and RESTful API architecture.

## Features

### Web Application

-   **Responsive Dashboard** - Modern EV charging interface with real-time station data
-   **User Authentication** - Complete registration, login, and OTP verification system
-   **Admin Panel** - Comprehensive management interface for stations, users, and bookings
-   **User Pages** - Bookings, reservations, and profile management interfaces
-   **Real-time Updates** - Live charging session monitoring and notifications

### API Features

-   **RESTful API** - Complete API coverage for all system functionality
-   **Authentication** - Sanctum-based API authentication with token management
-   **Station Management** - CRUD operations with location-based search
-   **Booking System** - Complete booking lifecycle management
-   **User Management** - Profile, preferences, and vehicle management
-   **Admin Dashboard** - Analytics, user management, and system monitoring
-   **Notifications** - Real-time notification system with multiple delivery methods

## Tech Stack

-   **Backend**: Laravel 12, PHP 8.2+
-   **Frontend**: TailwindCSS 4.0, Alpine.js
-   **Database**: MySQL with Eloquent ORM
-   **Authentication**: Laravel Sanctum (API) + Session-based (Web)
-   **APIs**: RESTful architecture with comprehensive endpoints

##  Requirements

-   PHP 8.2 or higher
-   Composer
-   Node.js & NPM
-   MySQL 8.0+
-   Laravel 12

2. **Install PHP dependencies**

    ```bash
    composer install
    ```

3. **Install Node.js dependencies**

    ```bash
    npm install
    ```

4. **Environment setup**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5. **Configure database**

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=evc
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

6. **Run migrations**

    ```bash
    php artisan migrate
    ```

7. **Build assets**

    ```bash
    npm run build
    ```

8. **Start the development server**
    ```bash
    php artisan serve
    ```

##  Project Structure

```
├── app/
│   ├── Http/Controllers/
│   │   ├── API/               # API Controllers
│   │   │   ├── AuthApiController.php
│   │   │   ├── UserApiController.php
│   │   │   ├── StationApiController.php
│   │   │   ├── BookingApiController.php
│   │   │   ├── AdminApiController.php
│   │   │   └── NotificationApiController.php
│   │   └── [Other Controllers] # Web Controllers
│   ├── Models/
│   │   ├── User.php
│   │   ├── Station.php
│   │   ├── Booking.php
│   │   ├── Notification.php
│   │   ├── Payment.php
│   │   ├── PaymentMethod.php
│   │   └── VehiclePreference.php
├── database/migrations/       # Database schemas
├── resources/views/          # Blade templates
├── routes/
│   ├── web.php              # Web routes
│   └── api.php              # API routes
```

##  Database Schema

### Key Tables

-   **users** - User accounts and profiles
-   **stations** - Charging station information
-   **bookings** - Charging session bookings
-   **notifications** - User notifications
-   **payments** - Payment records
-   **payment_methods** - User payment methods
-   **vehicle_preferences** - User vehicle information

### Relationships

-   User → Bookings (One to Many)
-   User → VehiclePreferences (One to Many)
-   User → Notifications (One to Many)
-   Station → Bookings (One to Many)
-   Booking → Payment (One to One)

##  Authentication

The system supports dual authentication:

-   **Web Application**: Session-based authentication
-   **API**: Laravel Sanctum token-based authentication

##  Usage Examples

### Complete Booking Flow

1. **Find nearby stations**
2. **Check availability**
3. **Create booking**
4. **Start charging session**
5. **Stop charging session**
6. **View booking history**

### Admin Workflow

1. **Login as admin**
2. **View dashboard analytics**
3. **Manage users and stations**
4. **Monitor bookings**
5. **Generate reports**

##  Response Format

All API responses follow a consistent format:

##  Performance Features

-   **Pagination** - All list endpoints support pagination
-   **Filtering** - Advanced filtering options for data retrieval
-   **Caching** - Model relationships and query optimization
-   **Location-based Search** - Efficient distance calculations for stations
-   **Background Processing** - Async notification delivery

##  Security Features

-   **Input Validation** - Comprehensive request validation
-   **Rate Limiting** - API rate limiting protection
-   **CORS Support** - Configurable cross-origin requests
-   **Token Management** - Secure API token handling
-   **Admin Authorization** - Role-based access control

##  Error Handling

The API includes comprehensive error handling with appropriate HTTP status codes:

-   `200` - Success
-   `201` - Created
-   `400` - Bad Request
-   `401` - Unauthorized
-   `403` - Forbidden
-   `404` - Not Found
-   `422` - Validation Error
-   `500` - Server Error

##  Development

### Testing

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

### Database Seeding

```bash
php artisan db:seed
```
---

**Built with ❤️ for the future of electric mobility**
