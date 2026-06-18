## 2024-06-18 - [Fix Cross-Site Scripting (XSS) in bills and index views]
**Vulnerability:** Unsanitized database outputs directly echoed into the DOM, specifically arrays variables such as `echo $row['buyer_company']`, `echo $row['buyer_address']`, and `echo $row['item_name']` in files `bills.php` and `index.php`.
**Learning:** Even if data comes from our own database, we cannot blindly trust it because it could have been inserted via vectors lacking strict sanitization, or modified improperly. Direct interpolation of output into HTML context leaves applications vulnerable to Cross-Site Scripting (XSS).
**Prevention:** All database outputs intended for the HTML context must be sanitized using `htmlspecialchars()` before rendering.
