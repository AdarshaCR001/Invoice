## 2026-06-13 - [Custom Validation, Loading States, and Modal Accessibility]
**Learning:** Found that custom validation using jQuery is needed to prevent default browser validation UI from showing up. Also learned that when using SweetAlert for confirmations, moving the button state change into the `.then(result => { if (result.isConfirmed) { ... } })` block is safer than changing it beforehand, otherwise you need to handle the state reset in the `else` block if the user cancels.
**Action:** When adding validation or loading states to jQuery-based forms, always set `novalidate` on the form tag to suppress the browser UI, and carefully scope the loading state updates to the confirmed path of any prompt dialogs.

## 2024-05-18 - [Table Action Button Accessibility]
**Learning:** Data tables often repeat generic action buttons (like "Edit" or "Download") on every row. Screen reader users navigating out of context will just hear the generic text repeatedly, which violates WCAG guidelines.
**Action:** When adding or reviewing action buttons in data tables, always add an `aria-label` attribute that includes a unique identifier for the row (like the item name, company name, or ID) to provide clear context for screen reader users.
