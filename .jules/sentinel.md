## 2024-05-24 - [HIGH] Fix Stored XSS in Bills view
**Vulnerability:** String fields like `invoice_number`, `buyer_company`, `buyer_address`, `item_name`, `vehicle_number`, `url`, `bag`, and `quantity` retrieved from the database were being directly echoed into the HTML view without any HTML escaping in `bills.php`. This leaves the application highly vulnerable to Stored Cross-Site Scripting (XSS) if any malicious HTML or JavaScript is saved to the database.
**Learning:** Even if data is fetched using secure prepared statements, it still needs to be properly escaped when presented in views to prevent XSS.
**Prevention:** Always wrap unescaped string data retrieved from the database with `htmlspecialchars()` before echoing it directly into HTML templates.
