Exam Portal Backend (Laravel)

==> Project Overview

This is a Laravel-based web application designed to handle form submissions and secure online payments via Stripe. The system utilizes JWT Token-based authentication for API security and generates PDF receipts (using DomPDF) upon successful payment.

==> Prerequisites

The following software must be installed on your system to run the project:

PHP (8.2+)

Composer

Laravel Framework (^12.0)

Node.js & npm (for frontend assets, if applicable)

MySQL/PostgreSQL (or any other database)

==> Installation Steps

Follow the steps below to set up the project locally.

Step 1: Cloning and Initial Setup

Clone the repository:

git clone [https://github.com/yourusername/Exam-portal-backend.git](https://github.com/Soni63897654/Exam-portal-backend.git)
cd Exam-portal-backend


Install Composer dependencies:

composer install



Create the environment file (.env) and generate the Application Key:

cp .env.example .env
php artisan key:generate



Step 2: Database Setup and Migration

Open the .env file and configure your database credentials (DB_DATABASE, DB_USERNAME, DB_PASSWORD).

Run migrations to create the necessary tables (including users, form_submissions, payments, and user_meta):

php artisan migrate



Seed the default user data using the UserSeeder:

php artisan db:seed



(Note: Ensure that database/seeders/UserSeeder.php exists and contains the logic to insert initial user data.)

Step 3: JWT Authentication Setup

The JWT Auth package is assumed to be installed via Composer. To finalize its configuration:

Generate the JWT secret key:

php artisan jwt:secret



This command sets the JWT_SECRET variable in your .env file.

(Verification) Confirm that the jwt driver is being used in the guards array within config/auth.php.

Step 4: Payment Gateway (Stripe) Setup

Payment processing is managed using the Stripe PHP SDK.

Obtain your API Keys from your Stripe Dashboard.

Set the following variables in your .env file:

# Stripe Configuration
STRIPE_KEY="pk_test_..."
STRIPE_SECRET="sk_test_..."



Step 5: PDF Generation (DomPDF) Setup

The DomPDF package is used to create printable receipts.

(If not installed) Install the DomPDF package via Composer:

composer require barryvdh/laravel-dompdf



(The Service Provider and Facade are typically auto-discovered in modern Laravel versions.)

Create the Storage Symlink:
PDFs are stored in the storage/app/public folder. Create a symbolic link to make them publicly accessible:

php artisan storage:link



==>>Configuration

.env File

Ensure that the following critical variables are correctly set in your .env file:

APP_URL=http://localhost:8000
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

JWT_SECRET=[Automatically Generated]
STRIPE_KEY=[Your Publishable Key]
STRIPE_SECRET=[Your Secret Key]



=>> Usage

To start the project:

Start the Laravel Development Server:

php artisan serve
