# Stock Management System
This project demonstrates the basic operation of stock management system.

# Installation
1. Install Composer and Laravel in your machine to run this project. Link: https://getcomposer.org/, https://laravel.com
2. Run composer install to install dependencies
3. Update DB variables, TIMEZONE in .env
4. Run php artisan key:generate to generate new application key
5. Run php artisan migrate
6. Run artisan jwt:secret

# Features
## JWT Authentication
Signup and login to obtain jwt token. Then put it as bearer header as authorization.

## CRUD for Item
Manage items relevant to your store. 
Obtain the lastest available item cost via items/{id}/cost.

## CRUD for Transaction
Manage purchase and sales of items in store.