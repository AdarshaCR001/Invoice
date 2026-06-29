## 2024-10-27 - [Stored XSS in Bills List view]
**Vulnerability:** Several user-controlled database inputs (`buyer_company`, `item_name`, `vehicle_number`, etc.) were directly echoed into the HTML context in `bills.php` without escaping. Even though data comes from the database (via PDO prepared statements), it acts as a Stored XSS vulnerability if an attacker had managed to insert malicious scripts previously.
**Learning:** Stored data is NOT inherently safe data. Just because data was safely stored using PDO prepared statements (preventing SQL injection) does not mean it is safe to render directly in HTML contexts.
**Prevention:** Always use `htmlspecialchars()` when echoing any user-controlled data into HTML, even if that data was retrieved from the application's own database.
