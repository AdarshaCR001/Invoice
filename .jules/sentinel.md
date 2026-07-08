## 2024-07-08 - XSS Vulnerability in Output Echoes
**Vulnerability:** Found multiple instances of direct database output echoes in views (like `bills.php`) without proper escaping, e.g., `echo $row['buyer_company'];`. This creates Cross-Site Scripting (XSS) vulnerabilities if malicious data is saved to the database and later displayed.
**Learning:** The application extensively uses PDO prepared statements which protect against SQL injection on the input side, but it frequently lacks manual output escaping on the output side.
**Prevention:** Always wrap unescaped variable output from the database with `htmlspecialchars()` when echoing it into the HTML view layer. E.g., `echo htmlspecialchars($row['variable']);`.
