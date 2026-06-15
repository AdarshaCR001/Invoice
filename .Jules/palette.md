## 2026-06-13 - [Custom Validation, Loading States, and Modal Accessibility]
**Learning:** Found that custom validation using jQuery is needed to prevent default browser validation UI from showing up. Also learned that when using SweetAlert for confirmations, moving the button state change into the `.then(result => { if (result.isConfirmed) { ... } })` block is safer than changing it beforehand, otherwise you need to handle the state reset in the `else` block if the user cancels.
**Action:** When adding validation or loading states to jQuery-based forms, always set `novalidate` on the form tag to suppress the browser UI, and carefully scope the loading state updates to the confirmed path of any prompt dialogs.

## 2026-06-13 - [Data Table Action Button Accessibility]
**Learning:** Found that repeated action buttons inside data tables (like "Edit" or "Download") create a poor screen reader experience because they lack context ("Edit", "Edit", "Edit").
**Action:** Always add contextual `aria-label` attributes to action buttons inside data tables, incorporating the unique identifier or name from that specific row (e.g., `aria-label="Edit invoice 1024"`).
