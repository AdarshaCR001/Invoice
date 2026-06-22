## 2026-06-13 - [Custom Validation, Loading States, and Modal Accessibility]
**Learning:** Found that custom validation using jQuery is needed to prevent default browser validation UI from showing up. Also learned that when using SweetAlert for confirmations, moving the button state change into the `.then(result => { if (result.isConfirmed) { ... } })` block is safer than changing it beforehand, otherwise you need to handle the state reset in the `else` block if the user cancels.
**Action:** When adding validation or loading states to jQuery-based forms, always set `novalidate` on the form tag to suppress the browser UI, and carefully scope the loading state updates to the confirmed path of any prompt dialogs.
## 2026-06-22 - [Bootstrap 3 Utility Classes]
**Learning:** Found that Bootstrap 3.3.7 already includes the `.sr-only` class for screen-reader accessibility, so there is no need to manually add custom CSS blocks for it.
**Action:** When adding visually hidden labels for accessibility in Bootstrap 3 environments, leverage the built-in `.sr-only` class instead of writing custom CSS.
