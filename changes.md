# Elodin Schema Changes

## 0.3

### Added

- Added repeatable post-level schema blocks to supported content editors.
- Added per-post controls for enabled state, schema type, and explicit global-schema replacement.
- Added starter template insertion and placeholder insertion to post-level schema blocks.
- Added post-level front-end rendering so local schema can output on singular content without requiring a global schema entry.

### Changed

- Changed post-level schema output to run only on singular views.
- Changed the post-level JSON-LD editor workflow to keep CodeMirror-backed textareas synchronized during editing and save.
- Changed the post-level editor styling to better indicate the JSON-LD paste area and give the visible CodeMirror surface a clearer minimum height.

### Fixed

- Fixed a bug that prevented post-level schema from outputting when no global schema posts existed.
- Fixed post-level schema rendering so local overrides do not apply on non-singular templates.
- Fixed unused preview metabox markup and removed an unused preview helper function.

## 0.2

### Added

- Added a dedicated Schema custom post type under `Settings` rather than as a top-level content item.
- Added a side-by-side JSON-LD editing workspace with live preview support.
- Added URL-based preview rendering so schema can be evaluated against a specific front-end context.
- Added placeholder insertion support for site and post data.
- Added starter templates for quicker JSON-LD authoring.
- Added JSON-LD validation with admin warnings when saved content is invalid.
- Added an enabled toggle in both the edit screen and the schema overview screen.
- Added notes support for internal documentation on each schema entry.
- Added GitHub-based plugin update checking.
- Added a `changes.md` file to track release-level changes.

### Changed

- Changed the plugin to accept JSON-LD only rather than generic script content.
- Changed default targeting language from page-oriented wording to `Entire site`.
- Changed front-end output to render valid schema in `wp_head`.
- Changed the main editor from a standard metabox into a larger custom editing workspace beneath the title field.
- Changed the admin JavaScript and CSS to load from dedicated asset files instead of inline output.
- Changed the overview screen columns to surface enabled state, schema type, notes, target, and validation more clearly.
- Changed the overview enabled control to use an inline AJAX toggle.
- Changed asset versioning for admin CSS and JS to use file modification times so admin changes are easier to pick up during development.

### Improved

- Improved JSON editing with the WordPress code editor and JSON-aware highlighting.
- Improved editor readability with horizontal scrolling rather than forced wrapping.
- Improved placeholder insertion so selected text can be replaced directly at the cursor position.
- Improved preview behavior so it updates reliably from the current editor contents and preview URL.
- Improved layout and spacing across the schema editor workspace and settings UI.
- Improved archive-table rendering and styling for the enabled control.
- Improved schema list usability by surfacing note excerpts in the overview table.

### Removed

- Removed the separate output-location setting for now.
- Removed priority controls for schema output for now.
- Removed unnecessary copy-to-clipboard feedback around placeholder insertion.
