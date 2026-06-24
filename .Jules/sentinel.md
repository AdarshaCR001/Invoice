## 2023-10-27 - Cross-Site Scripting (XSS) in Table Outputs
**Vulnerability:** User-controlled fields like buyer_company, buyer_address, item_name, bag, quantity, and vehicle_number were output directly to the HTML in bills.php without proper sanitization.
**Learning:** Even though input might be somewhat constrained, any data reflecting user input in HTML contexts must be escaped to prevent XSS. It's easy to miss some fields when outputting a large table.
**Prevention:** Always use htmlspecialchars() (or an equivalent contextual escaping function) for any database output or user input rendered in HTML, unless it is specifically known to be safe and appropriately encoded.
