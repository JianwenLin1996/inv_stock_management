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
8. Run php artisan l5-swagger:generate to generate swagger
9. Run php artisan serve to serve the system
10. Visit localhost:8000/api/documentation to check specification for every APIs

# Features
## JWT Authentication
Signup and login to obtain jwt token. Then put it as bearer header as authorization.

Obtain user information via api/me.

If token is expired, call api/refresh to obtained refreshed token.

## CRUD for Item
Manage items relevant to your store. (Create, Read all or specific item, Update and Delete).

Obtain the lastest cost_per_item via api/items/{id}/cost.

## CRUD for Transaction
Manage purchase and sales of items in stock (Create, Read all or specific transaction, Update and Delete).

Transaction list by default shows sales, add is_purchase=1 to show purchase. Cost_per_item during the transaction will also be shown along.

** Transaction item and date CANNOT be updated atm. Suggest to delete transaction and create new to achieve the same effect.

# API Feature Test
Run php artisan test to test Auth, Item and Transaction controller after any updates on the flow.