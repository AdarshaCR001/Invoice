## 2024-05-24 - Persistent XSS in Data Tables
**Vulnerability:** Persistent Cross-Site Scripting (XSS) in `bills.php`. User-controlled fields like `invoice_number`, `buyer_company`, `buyer_address`, `item_name`, etc. were directly echoed in the HTML without sanitization.
**Learning:** Legacy PHP codebases frequently miss context-aware output encoding. While SQL injection was prevented using PDO prepared statements, outputting database contents directly back into the UI allowed for stored XSS.
**Prevention:** Always use `htmlspecialchars()` with appropriate flags or a dedicated templating engine that auto-escapes output when rendering user-generated content in HTML contexts.
