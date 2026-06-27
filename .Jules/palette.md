## 2026-06-13 - [Custom Validation, Loading States, and Modal Accessibility]
**Learning:** Found that custom validation using jQuery is needed to prevent default browser validation UI from showing up. Also learned that when using SweetAlert for confirmations, moving the button state change into the `.then(result => { if (result.isConfirmed) { ... } })` block is safer than changing it beforehand, otherwise you need to handle the state reset in the `else` block if the user cancels.
**Action:** When adding validation or loading states to jQuery-based forms, always set `novalidate` on the form tag to suppress the browser UI, and carefully scope the loading state updates to the confirmed path of any prompt dialogs.

## 2024-05-18 - Table Empty States in Vanilla PHP
**Learning:** When adding empty states to standard HTML `<table>` elements without a frontend framework, wrapping the empty state message in a single `<tr>` with a `colspan` spanning all table headers ensures it renders correctly within the `<tbody>` structure without breaking the table layout.
**Action:** Always verify the number of `<th>` elements in the table header and set the `colspan` of the empty state `<td>` to match, ensuring consistent layout across empty and populated states.
