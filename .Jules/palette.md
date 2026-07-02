## 2026-06-13 - [Custom Validation, Loading States, and Modal Accessibility]
**Learning:** Found that custom validation using jQuery is needed to prevent default browser validation UI from showing up. Also learned that when using SweetAlert for confirmations, moving the button state change into the `.then(result => { if (result.isConfirmed) { ... } })` block is safer than changing it beforehand, otherwise you need to handle the state reset in the `else` block if the user cancels.
**Action:** When adding validation or loading states to jQuery-based forms, always set `novalidate` on the form tag to suppress the browser UI, and carefully scope the loading state updates to the confirmed path of any prompt dialogs.

## 2026-07-02 - [Login Micro-UX Accessibility and Feedback]
**Learning:** Found that when pages don't load jQuery (like `login.php`), we must rely on native HTML attributes (`autofocus`, `.sr-only` class) and vanilla JS for state management. The inline `onsubmit` handler is an effective lightweight approach for preventing double submissions while giving immediate feedback ("Logging in...").
**Action:** Use native HTML5 attributes like `autofocus` combined with `.sr-only` labels to enhance immediate accessibility without adding overhead on non-jQuery pages, and use simple vanilla JS onsubmit handlers to handle immediate state feedback.
