## 2024-05-15 - Missing output escaping in dynamic table rendering
**Vulnerability:** Cross-Site Scripting (XSS) vulnerability found in `bills.php` where user inputs (e.g., buyer details, item names, vehicle numbers) were rendered inside table cells without escaping.
**Learning:** Even if data is fetched from the database, it must be escaped when outputting it as HTML context to prevent stored XSS attacks.
**Prevention:** Always use `htmlspecialchars()` or equivalent context-aware escaping functions for all variables printed into HTML templates, regardless of whether the source is believed to be trusted.
