## 2024-05-30 - [Stored XSS in Database Output]
**Vulnerability:** Unescaped database values (`invoice_number`, `buyer_company`, `buyer_address`, etc.) were directly echoed in the HTML table in `bills.php`, creating a Stored XSS vulnerability.
**Learning:** In this PHP application, string data retrieved from the database (e.g., in `$row` arrays) is frequently unescaped.
**Prevention:** Always manually wrap unescaped database values in `htmlspecialchars()` when echoing them to views to prevent Stored XSS.
