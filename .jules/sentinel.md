## 2024-05-18 - [Fix High Priority XSS Vulnerabilities in PHP templates]
**Vulnerability:** Several fields in the PHP template data tables (e.g., `buyer_company`, `buyer_address`, `item_name`, `vehicle_number`, `url` in `bills.php`) were output directly to HTML without escaping (`<?php echo $row['field']; ?>`).
**Learning:** Legacy PHP code often omits automatic escaping of variables when outputting them into HTML templates, making them highly susceptible to Cross-Site Scripting (XSS).
**Prevention:** Always use `htmlspecialchars()` when outputting dynamic content from the database or user input directly into HTML tags or attributes in PHP templates.

## 2024-05-18 - [Fix Unsanitized Pagination Input]
**Vulnerability:** The `$_GET['page']` parameter in `buyers.php` was used directly in arithmetic operations without being sanitized or cast to an integer.
**Learning:** Unsanitized user inputs used directly in mathematical operations or SQL `LIMIT`/`OFFSET` calculations can lead to SQL Injection or unexpected type errors.
**Prevention:** Always sanitize or cast numeric user inputs (like pagination parameters) using `intval()` or similar type-casting functions before utilizing them in calculations or queries.
