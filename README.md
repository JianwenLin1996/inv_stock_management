# Stock Management System
This project demonstrates the basic operation of stock management system.

# System Setup
1. Install Composer and Laravel in your machine to run this project. Link: https://getcomposer.org/, https://laravel.com
2. Run composer install to install dependencies
3. Copy .env.example and rename it to .env
4. Update DB_* and DB_*_TEST variables, TIMEZONE in .env
5. Run _php artisan key:generate_ to generate new application key
6. Run _php artisan migrate_ to migrate database table
7. Run _php artisan db:seed --class=ItemSeeder_ to create mock item (needed if only test for Transaction functionality)
8. Run _php artisan jwt:secret_ to generate JWT secret in .env
9. Run _php artisan l5-swagger:generate_ to generate swagger
10. Run _php artisan serve_ to serve the system
11. Visit http://localhost:8000/api/documentation to check specification for every APIs

# Features
## JWT Authentication
Signup and login to obtain jwt token. Then put it as bearer header as authorization.

Obtain user information via _api/me_.

If token is expired, call _api/refresh_ to obtained refreshed token.

## CRUD for Item
Manage items relevant to your store. (Create, Read all or specific item, Update and Delete).

Obtain the lastest cost_per_item via _api/items/{id}/cost_.

## CRUD for Transaction
Manage purchase and sales of items in stock (Create, Read all or specific transaction, Update and Delete).

Transaction list by default shows sales, add is_purchase=1 to show purchase. Cost_per_item during the transaction will also be shown along.

** Transaction item and date CANNOT be updated atm. Suggest to delete transaction and create new to achieve the same effect.

# API Feature Test
Run _php artisan test_ to test Auth, Item and Transaction controller after any updates on the flow.