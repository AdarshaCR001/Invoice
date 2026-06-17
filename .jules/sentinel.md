## 2024-05-18 - Fix Stored XSS in Bills rendering
**Vulnerability:** Found missing `htmlspecialchars()` when rendering buyer details and bill fields (`buyer_company`, `buyer_address`, `item_name`, `vehicle_number`, etc.) in the data table in `bills.php`. This allows a Stored Cross-Site Scripting (XSS) if user input contains HTML/JavaScript.
**Learning:** Even if data is inserted safely using prepared statements, it still needs to be output-escaped when rendering HTML to prevent XSS.
**Prevention:** Always wrap all dynamic output variables representing user-inputted strings with `htmlspecialchars()` before displaying them in HTML context.
