# STAINLESS LAUNDRY SERVICES - WEB APPLICATION

> **"Fresh Clothes, Happy You!"**  
> **Clean | Dry | Fold**  
> Serving students, residents, and businesses around **Ndagani, Chuka, Kenya**.  
> Direct Hotline / WhatsApp: **0101476174**

## Live Website

Visit the live application: [https://stainlesslaundry.wuaze.com/](https://stainlesslaundry.wuaze.com/)

This is the publicly deployed production/demo instance of **Stainless Laundry Services**. Visitors can use it to explore the customer-facing website and the available laundry services.

---

## 1. Project Overview

**STAINLESS LAUNDRY SERVICES** is a full-stack PHP & MySQL web application engineered for a modern Kenyan laundry business based near Chuka University in Ndagani. 

This platform implements the complete laundry service journey based on the official business poster:
- **Branding & Visual Identity:** Exact color palette (Navy Blue `#082B63`, Primary Blue `#0759B8`, Clean White `#FFFFFF`, Light Blue Background `#EFF6FD`, Bright Yellow Accent `#FFD32A`, Body `#24344D`).
- **Poster Services & Pricing:** 
  - **Laundry Basket Washing:** Small (KES 250), Medium (KES 300), Large Narrow (KES 350), Large Wide (KES 400)
  - **Duvet Cleaning:** 3 &times; 4 (KES 250), 4 &times; 6 (KES 300), 5 &times; 6 (KES 350), 6 &times; 6 (KES 400)
  - Washing, Drying, and Folding
  - Ironing and Pressing
  - Free Doorstep Pickup & Delivery around Ndagani & Chuka
- **Customer Portal:** Registration, authentication, multi-item order booking with live dynamic subtotal calculator, server-side price protection, order history, and in-app milestone notifications.
- **12-Stage Real-Time Laundry Tracking:** Tracks orders from placement through collection, washing, drying, folding, quality check, to delivery with staff signatures and timestamps.
- **Admin & Staff Management:** Order management, pickup logistics, delivery dispatching, verified payment recording (M-Pesa, Cash, Bank), service catalog pricing control, audit logs, and business reports.

---

## 2. Technology Stack

- **Frontend:** Semantic HTML5, CSS3 (Solid color branding, responsive mobile drawer, accessible forms), Vanilla JavaScript (no heavy frontend frameworks, no fade-on-scroll animations).
- **Backend:** PHP 8.2+ with strict typing and PDO prepared statements.
- **Database:** MySQL 8 / MariaDB (InnoDB, foreign keys, UTF8mb4, transaction-safe).
- **Security:** Bcrypt password hashing (`password_hash`), CSRF tokens for all state changes, role-based access control, output escaping (`htmlspecialchars`), session regeneration, and server-side price recalculation.

---

## 3. Local Development/Testing Installation on Windows with XAMPP

### Step 1: Install XAMPP
1. Download **XAMPP for Windows** (with PHP 8.2 or newer) from [Apache Friends](https://www.apachefriends.org/).
2. Run the installer and ensure **Apache** and **MySQL** components are checked.
3. Install to the default directory (typically `C:\xampp`).

### Step 2: Place Project Files
Place this folder inside the XAMPP web root directory:
```
C:\xampp\htdocs\laundry service
```
*(Alternatively, you can rename the folder to `stainless-laundry` at `C:\xampp\htdocs\stainless-laundry`).*

### Step 3: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

### Step 4 & 5: Create Database and Import Schema
1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click **New** in the left sidebar.
3. Name the database: `stainless_laundry` with collation `utf8mb4_unicode_ci` and click **Create**.
4. Select the newly created `stainless_laundry` database, click the **Import** tab.
5. Click **Choose File**, select `database.sql` from your project folder, and click **Import** (or **Go**).
   - This creates all 15 relational tables with sample poster service tiers and system settings.

### Step 6: Configure the Database Connection
The application database connection configuration is located in `config/database.php`:
```php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$name = getenv('DB_NAME') ?: 'stainless_laundry';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
```
On standard default XAMPP setups, no modifications are needed.

### Step 7: Create the First Administrator Securely
Run the initialization script from the terminal or open in browser:
```powershell
php setup-admin.php
```
Or open in your browser:
```
http://localhost/laundry service/setup-admin.php
```
- Default configured admin: `admin@stainlesslaundry.co.ke`
- You can specify your custom strong administrative password during this initial setup.

### Step 8: Open the Local Development Site in Browser
Access the local development/testing instance in your web browser:
```
http://localhost/laundry service/
```
*(Or `http://localhost/stainless-laundry/` if renamed)*.

---

## 4. Complete Application Pages & Workflows

### Public Pages
1. `index.php` &ndash; Official homepage with poster branding, 4 feature badges, pricing preview, how-it-works timeline, and contact form.
2. `services.php` &ndash; Detailed explanation of basket washing, duvet cleaning, wash & dry & fold, and pressing.
3. `pricing.php` &ndash; Exact poster rates with sizing breakdown, notes, and direct booking links.
4. `about.php` &ndash; Story, hygiene principles, and service commitment in Ndagani/Chuka.
5. `contact.php` &ndash; Contact details, working hours, and contact form saving directly to the database.
6. `login.php` &ndash; User and staff authentication portal.
7. `register.php` &ndash; Customer account creation with Kenyan phone validation and hostel estate location.
8. `forgot-password.php` &ndash; Secure password reset token generation.
9. `reset-password.php` &ndash; Password reset completion with token hashing verification.
10. `track-order.php` &ndash; Public tracking lookup using Order Reference and customer phone verification.
11. `privacy-policy.php` &ndash; Customer data confidentiality terms.
12. `terms.php` &ndash; Turnaround policies, pricing rules, and garment care terms.
13. `logout.php` &ndash; Destroys active session securely.

### Customer Portal (`/customer/`)
14. `customer/dashboard.php` &ndash; Personalized dashboard with total/pending/completed order stats, active order tracker, and notification alerts.
15. `customer/place-order.php` &ndash; Interactive booking form with multi-item basket/duvet selection, vanilla JS live calculator, and strict server price validation.
16. `customer/orders.php` &ndash; Order history directory with search and status filters (All, Active, Completed, Cancelled).
17. `customer/order-details.php` &ndash; Complete order invoice, locked price snapshots, logistics timestamps, and 10-milestone visual progress timeline.
18. `customer/track-order.php` &ndash; Fast order lookup and active workflow tracker.
19. `customer/profile.php` &ndash; Manage phone number, default hostel/residence address, and password.
20. `customer/notifications.php` &ndash; In-app order status milestone alerts with unread count and "Mark All as Read".

### Admin Portal (`/admin/`)
21. `admin/login.php` &ndash; Dedicated staff and administrator access point.
22. `admin/dashboard.php` &ndash; Operational control center: orders by state, verified revenue metrics (excluding unpaid orders), outstanding balance tracker, and inline status updates.
23. `admin/orders.php` &ndash; Multi-filter order management table with status dropdown, date range, search, and pagination.
24. `admin/order-details.php` &ndash; Full order breakdown, staff status changer with custom customer notes, and payment recorder.
25. `admin/customers.php` &ndash; Registered client profiles, telephone numbers, hostel addresses, and lifetime order volumes.
26. `admin/services.php` &ndash; Manage service categories, descriptions, and toggle active/disabled states.
27. `admin/pricing.php` &ndash; Configure basket and duvet rates, add new size tiers, and update the default pickup fee without altering historical orders.
28. `admin/tracking.php` &ndash; Dedicated order tracking pipeline to advance orders through all 12 operational stages.
29. `admin/pickups.php` &ndash; Pickup logistics queue, rider assignment, overdue alerts, and physical collection timestamping.
30. `admin/deliveries.php` &ndash; Return delivery dispatching, out-for-delivery marking, and final delivery confirmation.
31. `admin/payments.php` &ndash; Payment ledger for M-Pesa, Cash, and Bank payments with duplicate transaction code prevention.
32. `admin/reports.php` &ndash; Business analytics: status breakdown, popular items, daily sales activity, and realized revenue.
33. `admin/settings.php` &ndash; Business contact details, physical address, pickup fees, and security audit log.
34. `admin/users.php` &ndash; Staff account creation, role assignment (Staff vs Admin), and active state toggles.

---

## 5. The 12 Laundry Tracking Workflow Milestones

Every order progresses through these 12 distinct database-recorded milestones:
1. **Order Placed** &ndash; Customer books pickup online.
2. **Pickup Scheduled** &ndash; Pickup slot confirmed.
3. **Pickup Assigned** &ndash; Staff rider dispatched to collect.
4. **Laundry Collected** &ndash; Laundry collected from hostel/estate.
5. **Received at Laundry Facility** &ndash; Arrived at Ndagani laundry depot for sorting.
6. **Washing in Progress** &ndash; Machine wash with quality detergents and fabric softeners.
7. **Drying in Progress** &ndash; Temperature-regulated tumble drying.
8. **Folding and Quality Check** &ndash; Neatly folded, checked, and packaged.
9. **Ready for Delivery** &ndash; Laundry prepared for dispatch.
10. **Out for Delivery** &ndash; Rider in transit with clean clothes.
11. **Delivered** &ndash; Handed over to customer; order completed.
12. **Cancelled** &ndash; Order terminated or rejected with staff note.

---

## 6. How to Run Automated Tests

A comprehensive PHP test suite is included in `tests/test_suite.php`. Run it from PowerShell or Command Prompt:
```powershell
php tests/test_suite.php
```
The test suite verifies:
- Database connection & presence of all 15 tables
- Integrity of poster basket & duvet prices
- Customer registration & phone normalization
- Duplicate email prevention
- Password hashing & secure verification
- Server-side price recalculation & order item snapshot preservation
- Unique order reference format (`SLS-YYMMDD-XXXX`)
- Tracking history events & customer in-app alerts
- Exclusion of unpaid orders from verified revenue
- Public order tracking phone privacy verification
- Administrative account presence

---

## 7. Future M-Pesa Safaricom Daraja API Integration

The system currently uses **Manual Payment Confirmation** where staff verify physical cash or M-Pesa transaction SMS codes before recording them into the database.

To integrate automated STK Push via Safaricom Daraja in production:
1. Register on the [Safaricom Daraja Developer Portal](https://developer.safaricom.co.ke/).
2. Obtain your **Consumer Key**, **Consumer Secret**, **Passkey**, and **Shortcode (Till/Paybill)**.
3. Configure environment variables in `.env` or Apache `SetEnv`:
   - `MPESA_ENV=sandbox` (or `live`)
   - `MPESA_CONSUMER_KEY=...`
   - `MPESA_CONSUMER_SECRET=...`
   - `MPESA_SHORTCODE=...`
   - `MPESA_PASSKEY=...`
   - `MPESA_CALLBACK_URL=https://yourdomain.co.ke/api/mpesa-callback.php`
4. Create an endpoint that receives the Daraja JSON callback and automatically inserts a verified record into the `payments` table.

---

## 8. Production Deployment & Security Checklist

Before deploying to live web hosting (e.g., cPanel, VPS, or Cloud):
1. **Change Database Credentials:** Update `config/database.php` to use strong database user credentials rather than default `root`.
2. **Enforce HTTPS:** Ensure an SSL certificate is active. Sessions are configured to automatically enable the `Secure` flag on HTTPS.
3. **Turn Off PHP Error Display:** In `php.ini`, set `display_errors = Off` and `log_errors = On`.
4. **Change Default Admin Password:** Run `setup-admin.php` or update your password via `admin/users.php`.
5. **Protect Sensitive Directories:** Ensure `.git`, `tests/`, and `database.sql` are blocked from public HTTP access via `.htaccess`:
   ```apache
   <FilesMatch "\.(sql|env|json|md)$">
       Require all denied
   </FilesMatch>
   ```