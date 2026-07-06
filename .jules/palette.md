
## 2024-07-06 - Login Form Accessibility and UX Enhancements
**Learning:** For standalone pages like `login.php` that do not load large libraries like jQuery, inline vanilla JS `onsubmit` handlers provide an efficient, lightweight way to handle micro-UX (such as loading states and preventing double submission). Furthermore, adding `id` attributes linked with `for` in `<label class="sr-only">` and using `autofocus` immediately on page load significantly improves keyboard navigation and screen-reader support without altering visual layout.
**Action:** Always link form inputs to labels via `id` and `for`, use `.sr-only` to preserve visual design while ensuring a11y, and default to lightweight vanilla JS for loading states on pages where jQuery is unnecessary.
