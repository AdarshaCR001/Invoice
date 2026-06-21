
## 2024-05-18 - Missing Output Sanitization in bills.php
**Vulnerability:** Found multiple instances in `bills.php` where user-controlled inputs such as `invoice_number`, `buyer_company`, `buyer_address`, `item_name`, `bag`, `quantity`, `vehicle_number`, and `url` were echoed directly to the HTML page without proper escaping.
**Learning:** This exposes the application to Persistent Cross-Site Scripting (XSS). When rendering output from the database that originates from user input, we must strictly validate or sanitize it to prevent script injection.
**Prevention:** Always use `htmlspecialchars()` when rendering string variables in HTML templates.
