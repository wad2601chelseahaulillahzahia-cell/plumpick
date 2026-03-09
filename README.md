🍇 PLUMPICK — It's Giving Savings!
PLUMPICK is a smart web-based application built with PHP and MySQL designed to help students find the best food and beverage deals. By utilizing Smart Tender logic, the system automatically compares prices and distances from various vendors to provide recommendations that are both the "Cheapest" and the "Closest".

✨ Features
Smart Auction/Tender Logic: Automatically detects searched items and highlights the Cheapest and Closest options among 50+ localized data points.

Gen-Z Aesthetic UI: A modern interface featuring a "Violet/Plum" palette (#6e169a), Plus Jakarta Sans typography, and interactive glassmorphism effects.

Real-time Shopping Cart: Users can request items, confirm orders, and manage a virtual checkout process seamlessly.

Live Spending Tracker: Displays the user's total successful expenditures directly in the header for easy financial monitoring.

Payment Archive: A dedicated modal to view historical receipts and transaction details.

Dynamic Profile Management: Allows users to update their name and delivery address, which persists in the database.

🛠️ Tech Stack
Backend: PHP (Native)

Database: MySQL / MariaDB

Frontend: HTML5, CSS3 (Custom Variables & Flexbox), and JavaScript (Fetch API)

Design: Plus Jakarta Sans via Google Fonts

🚀 Getting Started
Prerequisites
A local server environment like XAMPP, WAMP, or MAMP.

A modern web browser (Chrome, Edge, or Firefox).

Installation
Clone the Repository

Bash
git clone https://github.com/yourusername/plumpick.git
Database Configuration

Open phpMyAdmin.

Create a new database named plumpick_db.

Import the provided SQL script to generate the users, sellers_products, and offers tables.

Connect the App

Open index.php.

Update the database credentials to match your local setup:

PHP
$host = "localhost"; 
$user = "root"; 
$pass = "your_password"; 
$db = "plumpick_db";
Run Locally

Move the project folder to your htdocs directory.

Navigate to http://localhost/plumpick in your browser.

📊 Database Schema
The system relies on three core tables to function:

users: Stores user identification and delivery data.

sellers_products: Contains a catalog of 50 items with price and distance metadata.

offers: Logs chat requests, pending selections, and finalized "Paid" transactions.

📸 Project Vibe
Primary Accent: Plum Purple (#6e169a)

Highlight Color: Amber Gold (#fbbf24)
