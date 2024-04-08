# Stock Management System
This project demonstrates the basic operation of stock management system.

# System Setup
1. Install Composer and Laravel in your machine to run this project. Link: https://getcomposer.org/, https://laravel.com
2. Run composer install to install dependencies
3. Update DB_* and DB_*_TEST variables, TIMEZONE in .env
4. Run php artisan key:generate to generate new application key
5. Run php artisan migrate to migrate database table
6. Run php artisan db:seed --class=ItemSeeder to create mock item (needed if only test for Transaction functionality)
7. Run artisan jwt:secret to generate JWT secret in .env
8. Run php artisan serve to serve the system

# Features
## JWT Authentication
Signup and login to obtain jwt token. Then put it as bearer header as authorization.

## CRUD for Item
Manage items relevant to your store. 
Obtain the lastest available item cost via items/{id}/cost.

## CRUD for Transaction
Manage purchase and sales of items in stock.