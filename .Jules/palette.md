## 2026-06-13 - [Custom Validation, Loading States, and Modal Accessibility]
**Learning:** Found that custom validation using jQuery is needed to prevent default browser validation UI from showing up. Also learned that when using SweetAlert for confirmations, moving the button state change into the `.then(result => { if (result.isConfirmed) { ... } })` block is safer than changing it beforehand, otherwise you need to handle the state reset in the `else` block if the user cancels.
**Action:** When adding validation or loading states to jQuery-based forms, always set `novalidate` on the form tag to suppress the browser UI, and carefully scope the loading state updates to the confirmed path of any prompt dialogs.

## 2026-06-15 - [Vanilla JS Loading States for Simple Forms]
**Learning:** For simple pages (like login) that do not load large libraries like jQuery, providing micro-UX like form submission loading states requires using vanilla JS. Inline `onsubmit` handlers are an effective, minimal-code way to handle this without requiring additional script blocks.
**Action:** When working on standalone/lightweight pages, use native HTML attributes (like `autofocus`, `aria-label`) and inline vanilla JavaScript to accomplish UX wins while respecting the existing architectural boundaries (i.e. not adding jQuery where it isn't already used).
