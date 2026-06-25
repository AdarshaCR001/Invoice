## 2026-06-25 - Fix XSS Vulnerabilities in Data Tables
**Vulnerability:** Multiple Cross-Site Scripting (XSS) vulnerabilities found in `bills.php` due to directly echoing database fields (like `buyer_company`, `item_name`, `vehicle_number`, etc.) without HTML entity encoding.
**Learning:** PDO prepared statements protect against SQL Injection, but they do not automatically sanitize output. Any untrusted data or user-supplied content fetched from a database must be explicitly sanitized before rendering into the HTML context.
**Prevention:** Always wrap variables containing string data in `htmlspecialchars()` when outputting them into HTML documents. Enforce consistent output encoding throughout the application.
