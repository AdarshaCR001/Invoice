## 2026-06-13 - [Custom Validation, Loading States, and Modal Accessibility]
**Learning:** Found that custom validation using jQuery is needed to prevent default browser validation UI from showing up. Also learned that when using SweetAlert for confirmations, moving the button state change into the `.then(result => { if (result.isConfirmed) { ... } })` block is safer than changing it beforehand, otherwise you need to handle the state reset in the `else` block if the user cancels.
**Action:** When adding validation or loading states to jQuery-based forms, always set `novalidate` on the form tag to suppress the browser UI, and carefully scope the loading state updates to the confirmed path of any prompt dialogs.

## 2024-06-28 - [Add autofocus and SR label to login input]
**Learning:** HTML `autofocus` combined with `<label class="sr-only">` significantly improves accessibility and UX for single-input login forms without visual clutter or extra JavaScript.
**Action:** Use native HTML `autofocus` attribute for the primary input on specialized pages like logins, and ensure visually hidden labels use Bootstrap's `.sr-only` class for screen readers.
