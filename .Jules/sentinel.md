
## 2024-05-27 - Fix Stored XSS in Bills View
**Vulnerability:** Found a Stored Cross-Site Scripting (XSS) vulnerability in `bills.php`. Database fields (like `buyer_company`, `buyer_address`, `item_name`, and `vehicle_number`) were echoed directly without escaping, meaning that any malicious HTML/JS payloads stored in the database would be executed when rendering the view.
**Learning:** In applications that rely heavily on database views without a templating engine (like raw PHP with Bootstrap), every user-supplied field fetched from the database must be escaped before being rendered. Failure to do so allows persistent execution of untrusted scripts.
**Prevention:** Always use `htmlspecialchars()` (or a secure templating library like Twig) to escape any data outputted to the HTML, especially strings that are fetched from a database layer or directly from user input.
