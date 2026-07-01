## 2024-05-24 - [HIGH] Inconsistent output encoding across files
**Vulnerability:** Output encoding was inconsistently applied in the codebase. Some files properly escaped output, while others (like bills.php) echoed user input directly, leading to XSS vulnerabilities.
**Learning:** We should enforce a standard encoding mechanism (like htmlspecialchars) for all user-generated data displayed in the application, and we should audit other files for similar issues.
**Prevention:** Always escape user input before rendering it in the UI. Consider using a template engine or a centralized view rendering function to apply escaping by default.
