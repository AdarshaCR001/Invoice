# 🧾 Premium Invoice Generator

A modern, responsive PHP-based Invoice Generator and Financial Dashboard designed to track sales, manage buyers, generate professional PDF invoices, and monitor outstanding balances with an elegant, responsive user interface.

---

## ✨ Key Features

- **📊 Dynamic Financial Dashboard**: Monitor total amounts, outstanding balances, top buyers, and highest balance holders. Filter metrics by month and year or view all-time statistics.
- **📁 Bills Management**: Detailed tracking of bills/invoices, including items, quantity, bags, vehicle numbers, freight, and outstanding balance. Fully searchable and filterable.
- **👥 Buyers Directory**: Maintain buyer details (name, company, address). Easily merge duplicate or legacy buyers with full transaction safety.
- **💰 Indian Currency Formatting**: Automated formatting using the Indian Numbering System (e.g., `₹ 1,50,000.00`) with two-digit decimal precision.
- **📄 PDF Generation & S3 Storage**: Instantly convert invoices to PDFs and upload them to AWS S3, with automatic local fallback support.
- **🎨 Modern Dark & Light Mode**: A gorgeous user experience styled with custom glassmorphic accents, harmonious palettes, and clean transitions.

---

## 🛠️ Prerequisites

Ensure you have the following installed on your local machine:

1. **PHP (>= 7.4)**
   - Required extensions: `pdo_mysql`, `openssl`, `mbstring`, `gd`
2. **Composer** (PHP Package Manager)
3. **MySQL Server**
4. **Web Browser**

---

## 🚀 Step-by-Step Installation & Local Setup

### Step 1: Clone the Repository
Clone the project repository to your local directory:
```bash
git clone <repository_url>
cd Invoice
```

### Step 2: Install Dependencies
Install the required Composer libraries (such as `phpdotenv`, `dompdf`, and `aws-sdk-php`):
```bash
composer install
```

### Step 3: Setup the Database
1. Make sure your local MySQL server is running.
2. Log into MySQL and create a database (default name is `invoice_db`):
   ```sql
   CREATE DATABASE invoice_db;
   ```
3. Import the clean database schema to initialize the tables:
   ```bash
   mysql -u root -p invoice_db < schema.sql
   ```

> [!NOTE]  
> If you are migrating a legacy database where buyer information was hardcoded directly inside the `bills` table, you can run the migration steps defined in [normalization.sql](file:///Users/adarsha/Projects/Invoice/normalization.sql) to safely extract buyers and link them using foreign keys.

### Step 4: Configure the Environment
1. Create a `.env` file by copying the template:
   ```bash
   cp .env.example .env
   ```
2. Open the `.env` file and configure your credentials:
   - **Database Host & Credentials**: Fill in `HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`.
   - **File Storage**:
     - **Local Storage (Default)**: Set `S3_BASE_URL=/uploads/` and leave S3 keys blank. This stores all generated PDF invoices in a local `uploads` directory.
     - **AWS S3 Cloud Storage**: Fill in `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `S3_BUCKET`, and `S3_REGION` if you want to store files in the cloud.

### Step 5: Configure Permissions for Local Storage
If you are using local storage (`S3_BASE_URL=/uploads/`), create the `uploads` directory and ensure the web server has write access to it:
```bash
mkdir -p uploads
chmod -R 775 uploads
```

### Step 6: Start the Local PHP Development Server
Run PHP's built-in development server from the root of the project:
```bash
php -S 127.0.0.1:8000
```

Open your browser and navigate to:
👉 **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

---

## 📂 Project Structure

- [index.php](file:///Users/adarsha/Projects/Invoice/index.php) - **Financial Dashboard** containing monthly/annual stats and recent activities.
- [bills.php](file:///Users/adarsha/Projects/Invoice/bills.php) - **Bills Management** interface with filters, searching, and invoice creation.
- [buyers.php](file:///Users/adarsha/Projects/Invoice/buyers.php) - **Buyers Directory** including features to add and merge buyers.
- [helpers.php](file:///Users/adarsha/Projects/Invoice/helpers.php) - Contains helper functions (e.g. Indian Currency formatter).
- [environment.php](file:///Users/adarsha/Projects/Invoice/environment.php) - Bootstraps the application environment and loads dependencies.
- [aws_s3.php](file:///Users/adarsha/Projects/Invoice/aws_s3.php) - Handles file uploads (supports both AWS S3 uploads and local server storage fallback).
- [htmlPdfConverter.php](file:///Users/adarsha/Projects/Invoice/htmlPdfConverter.php) - Generates PDF files using `dompdf`.
- [schema.sql](file:///Users/adarsha/Projects/Invoice/schema.sql) - Database initialization schema for fresh setups.
- [normalization.sql](file:///Users/adarsha/Projects/Invoice/normalization.sql) - Schema normalization script for legacy databases.

---

## 🌐 Production Deployment

The application is configured to easily deploy to hosting platforms like **[InfinityFree](https://dash.infinityfree.com/)**.

### Deployment Steps:
1. **Upload Files**: Transfer all application files and folders (including the `vendor` directory after running `composer install`) directly into the server's **`htdocs`** directory.
2. **Database Configuration**:
   - Create a MySQL database using the hosting control panel (e.g. MySQL Databases in cPanel).
   - Import the schema using [schema.sql](file:///Users/adarsha/Projects/Invoice/schema.sql) (or [normalization.sql](file:///Users/adarsha/Projects/Invoice/normalization.sql) if you are migrating existing data).
3. **Environment Configuration**:
   - Copy `.env.example` to `.env` inside the server's `htdocs` folder.
   - Update the `.env` configuration variables with the database credentials provided by your host (e.g. InfinityFree database host, user, password, and db name).