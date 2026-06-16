## 2026-06-13 - [Custom Validation, Loading States, and Modal Accessibility]
**Learning:** Found that custom validation using jQuery is needed to prevent default browser validation UI from showing up. Also learned that when using SweetAlert for confirmations, moving the button state change into the `.then(result => { if (result.isConfirmed) { ... } })` block is safer than changing it beforehand, otherwise you need to handle the state reset in the `else` block if the user cancels.
**Action:** When adding validation or loading states to jQuery-based forms, always set `novalidate` on the form tag to suppress the browser UI, and carefully scope the loading state updates to the confirmed path of any prompt dialogs.

## 2024-06-16 - Programmatic Error Association
**Learning:** Adding a visible error message isn't enough for screen readers. Using `aria-describedby` dynamically links the error message to the input, and `aria-invalid="true"` explicitly signals the error state. `role="alert"` ensures the error is announced immediately.
**Action:** When adding form validation or error messages, ensure `aria-describedby` connects the input to the error container ID, and toggle `aria-invalid`. Always add explicit `<label>` tags with `for` attributes matching the input's `id`.
