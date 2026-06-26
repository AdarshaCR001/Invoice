## 2024-05-24 - Fix Stored XSS in `bills.php`
**Vulnerability:** Several properties pulled from the database, such as `buyer_company`, `buyer_address`, `item_name`, and `vehicle_number`, were output directly to HTML without being escaped in the main table of `bills.php`. This allows for Stored Cross-Site Scripting (XSS) if malicious payloads are inserted into these fields.
**Learning:** Even though the database input goes through PDO prepared statements, output must still be escaped to prevent XSS.
**Prevention:** Wrap all variable outputs in HTML with `htmlspecialchars()` to escape potential HTML/JS payloads.
