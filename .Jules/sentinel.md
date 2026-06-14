## 2024-06-14 - Stored XSS in Dashboard Data Grid
**Vulnerability:** Found unescaped user inputs rendered directly into an HTML table in `bills.php`. Specifically, columns like `buyer_company`, `buyer_address`, `item_name`, and `url` were vulnerable to Stored XSS.
**Learning:** Legacy PHP files often concatenate database output directly into HTML without escaping. In older applications, finding direct variable interpolation in views (e.g. `<?php echo $row['column']; ?>`) is a prime indicator of potential XSS risk.
**Prevention:** Always use `htmlspecialchars()` (or a modern template engine with automatic escaping) when rendering untrusted or user-generated data from the database into HTML contexts.
