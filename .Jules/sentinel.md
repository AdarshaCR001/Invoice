## 2024-05-27 - [XSS Vulnerability in Direct Output]
**Vulnerability:** Found direct echoes of database values (e.g. `$row['buyer_company']`, `$row['buyer_address']`, `$row['item_name']`, `$row['vehicle_number']`, `$row['url']`) inside HTML tags without proper sanitization. This is a severe Cross-Site Scripting (XSS) vulnerability.
**Learning:** Even though the database interaction uses prepared statements (preventing SQL injection), developers often forget that values retrieved from the database and output back to the page must still be sanitized.
**Prevention:** Always wrap variables output directly to the HTML document within `htmlspecialchars()` to encode special characters properly and prevent malicious scripts from executing in the browser.
