## 2024-05-09 - Fix XSS Vulnerability in bills.php table
**Vulnerability:** Cross-Site Scripting (XSS) vulnerability found in `bills.php` where database fields (like `buyer_company`, `item_name`, etc.) were being directly echoed into HTML table cells without escaping.
**Learning:** Even internal or admin-facing applications are susceptible to XSS if user-provided input (or data later retrieved from the database) is rendered directly. Direct `echo` of array fields without `htmlspecialchars()` is a common pattern to watch out for.
**Prevention:** Always use `htmlspecialchars()` when rendering string variables derived from the database or user input directly into HTML output context.
