## 2024-05-18 - Cross-Site Scripting (XSS) Vulnerability in Bills Table
**Vulnerability:** Several fields fetched from the database in `bills.php` (`buyer_company`, `buyer_address`, `item_name`, `bag`, `quantity`, `vehicle_number`, `url`) are directly echoed into the HTML output without being escaped.
**Learning:** This occurs when data stored in the database is assumed to be safe. If an attacker injects a malicious payload into any of these fields (e.g., during bill creation/update or buyer creation), it will be executed when the `bills.php` page is viewed.
**Prevention:** Always use `htmlspecialchars()` when rendering user-controlled data or data retrieved from the database in HTML contexts, even if it is believed to be safe.
