Arcadia

Arcadia is a full-stack digital game marketplace and browser-game portfolio built with PHP, MySQL, HTML, CSS, Bootstrap, and vanilla JavaScript.

The project combines a public-facing game website, an e-commerce store, customer accounts, an administrator panel, a simulated payment workflow, and a digital library for purchased games.

Note: The payment system is simulated for educational and portfolio purposes. It does not process real payments and should never be used to enter real card information.

Project Overview

Arcadia began as a browser-game portfolio and developed into a complete full-stack web application.

The project demonstrates:

Frontend interface design

Responsive layouts

Browser-game development

PHP backend development

MySQL relational database design

Authentication and session handling

Shopping-cart and checkout logic

Simulated payment processing

Customer order management

Digital game ownership and library access

Administrator CRUD functionality

Basic web security practices

Main Features

Public Website

Arcadia includes a complete public-facing website with:

Home page

About page

Games page

Contact page

Responsive Bootstrap navigation

Modern dark gaming-inspired design

Featured game sections

Project and developer information

Browser Games

The project contains 18 browser-based games:

Runner

Uncanny

TimeThief

BrainSplit

ScrollQuest

MirrorDark

MazeFade

GridLock

GravShift

DominoTrigger

ColorClash

CodeBreaker

CipherTrace

ChronoSplit

GlitchSnitch

TapFrenzy

ThoughtSnare

StackHack

The games explore mechanics such as timing, logic, pattern recognition, perception, movement, reactions, gravity, and state management.

Store Features

The Arcadia store provides:

Product catalog

Product detail pages

Search

Sorting

Game-type filtering

Category filtering

Featured games

Shopping cart

Checkout

Order summary

Customer information handling

Simulated payment processing

Payment success confirmation

Customer Features

Registered customers can:

Create an account

Log in and log out

Manage profile information

Add games to the shopping cart

Complete checkout

Use the simulated payment workflow

View order history

View individual order details

Access purchased games in a personal library

Launch supported browser games

Administrator Features

The administrator area provides:

Admin authentication

Protected admin pages

Product management

Product image uploads

Product categories

Browser/download game types

Featured-game management

Customer management

Order management

Order status updates

Order deletion

Administrator account setup utility for development

For security, development-only administrator setup scripts should be removed before public deployment.

Simulated Payment System

The simulated payment workflow follows this structure:

Shopping Cart
    ↓
Checkout
    ↓
Payment Page
    ↓
Simulated Payment Validation
    ↓
Order Creation
    ↓
Order Rows
    ↓
Purchased Game Ownership
    ↓
Payment Success
    ↓
Customer Library

The system validates demonstration payment fields and then creates the order using a database transaction.

No real card network, bank, PayPal account, Stripe account, or other real payment provider is contacted.

Security Features

The project includes several security practices appropriate for an educational portfolio application:

Password hashing with PHP password functions

Password verification during login

Prepared SQL statements

CSRF token generation and validation

Session-based authentication

Session ID regeneration after successful authentication

Admin authorization checks

Output escaping with htmlspecialchars

Server-side form validation

Database transactions for order processing

Image MIME-type validation

Image file-size limits

Duplicate purchase protection

This project is not claimed to be production-certified security software. A real commercial deployment would require additional security review, hardened configuration, secure secrets management, HTTPS, monitoring, backups, rate limiting, and a real payment provider.

Technology Stack

Frontend

HTML5

CSS3

Bootstrap 5

Vanilla JavaScript

Font Awesome

Backend

PHP

MySQL

MySQLi

Development Tools

Docker / Docker Compose

phpMyAdmin

DBeaver

Visual Studio Code

Git

Database

The application uses a relational MySQL database.

Core entities include:

Customers

Products

Orders

OrderRows

PurchasedGames

Admins

The order system links customers to orders and order rows, while PurchasedGames represents digital ownership of products.

The database also supports product metadata such as game file paths, game types, categories, and featured status.

Project Structure

A simplified version of the project structure looks like this:

Arcadia/
│
├── Games/
│   ├── Runner.html
│   ├── Uncanny.html
│   ├── TimeThief.html
│   └── ...
│
├── css/
├── images/
├── js/
│
├── index.html
├── about.html
├── games.html
├── contact.html
│
├── menu.php
├── product.php
├── cart.php
├── checkout.php
├── payment.php
├── process_payment.php
├── order_success.php
│
├── customer_login.php
├── customer_register.php
├── customer_dashboard.php
├── customer_profile.php
├── customer_orders.php
├── customer_order_details.php
├── library.php
├── play_game.php
│
├── admin_login.php
├── admin.php
├── admin_products.php
├── admin_customers.php
├── admin_orders.php
├── update_order.php
├── delete_order.php
│
├── db.php
├── csrf.php
├── auth_check.php
├── docker-compose.yml
└── Arcadia.sql

File names can differ slightly depending on the final cleanup and GameStation-to-Arcadia rename.

Local Installation

Requirements

Install:

Docker Desktop

Docker Compose

Git

A modern web browser

DBeaver and phpMyAdmin are optional but useful for database administration.

1. Clone the repository

git clone <your-repository-url>
cd Arcadia

2. Review Docker configuration

Open docker-compose.yml and confirm the PHP, MySQL, phpMyAdmin, database name, credentials, ports, and volumes match your local environment.

Do not commit real production credentials.

3. Start the containers

docker compose up -d --build

4. Import the database

Import the canonical Arcadia SQL schema using either:

phpMyAdmin

DBeaver

MySQL command line

Example:

mysql -u <username> -p Arcadia < Arcadia.sql

Adjust the database name and credentials to match your configuration.

5. Configure the application database connection

Ensure db.php points to the correct:

MySQL host

Database name

Username

Password

Port

For deployment, database credentials should be supplied using environment variables or another secure configuration method rather than committed directly to the repository.

6. Open the website

Open the local PHP server URL configured in Docker.

Administrator Setup

During development, an administrator account can be created using the project administrator setup utility.

After an administrator account has been successfully created and tested:

Remove the setup utility before deployment.

Never publish administrator passwords or credentials in:

README.md

Git history

Source code

Screenshots

Public documentation

Testing Checklist

Before deployment, test the following flows:

Public navigation works

All 18 browser-game links work

Customer registration works

Customer login/logout works

Profile updates work

Product search works

Sorting works

Filtering works

Featured games display correctly

Add-to-cart works

Remove-from-cart works

Clear-cart works

Checkout validation works

Payment simulation works

Successful payments create orders

Order rows are created

Purchased games appear in the customer library

Order history works

Order details work

Admin login works

Product CRUD works

Customer administration works

Order administration works

CSRF-protected forms still submit correctly

Responsive layouts work on mobile

No development-only credentials are exposed

Deployment Checklist

Before deploying Arcadia publicly:

Remove create_admin.php and other temporary setup scripts

Remove test credentials and generated password utilities

Replace placeholder contact information

Replace placeholder social links

Verify the GameStation → Arcadia rename

Use a production database configuration

Move secrets to environment variables

Enable HTTPS

Disable verbose PHP/database errors in production

Configure secure session-cookie settings

Back up the database

Test the site in multiple browsers

Test desktop and mobile layouts

Verify file-path case sensitivity on Linux

Review uploaded-image permissions

Confirm the simulated payment disclaimer is visible

Current Project Status

Arcadia is considered feature-complete as a portfolio and educational project.

Core systems completed:

Public website

18-game browser portfolio

Product catalog

Customer accounts

Shopping cart

Checkout

Simulated payments

Orders

Digital game library

Administrator panel

Database integration

Security improvements

Remaining work is primarily deployment configuration, final branding cleanup, documentation, and optional enhancements.

Possible Future Enhancements

These features are optional and are not required for the current version:

Real Stripe test-mode integration

Email order confirmations

PDF invoices

Wishlist

Reviews and ratings

Coupons and discount codes

Advanced admin analytics

Pagination

Real-time stock or availability features

Password reset system

Two-factor administrator authentication

Automated tests

CI/CD deployment pipeline

Project Purpose

Arcadia was developed as a portfolio project to demonstrate the ability to take a web application beyond an initial prototype and complete an interconnected system involving frontend development, backend development, databases, authentication, e-commerce logic, interactive games, and administration tools.

Author

Keyse Abdi Hassan

Frontend / Web Developer

License

This project was created for educational and portfolio purposes.

Add a formal open-source license before allowing third-party reuse or redistribution.
