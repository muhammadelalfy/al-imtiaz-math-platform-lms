# CKEditor 5 decision for exam authoring

CKEditor 5 offers an official React integration and separately configurable UI and content languages, including Arabic UI support. The platform uses the native `ckeditor5` package with `@ckeditor/ckeditor5-react`, Arabic translations, and an RTL editor surface in the exam composer. [1] [2]

The project owner explicitly approved the **CKEditor 5 free edition**. Accordingly, the installed self-hosted build uses the CKEditor 5 **GPL** license key and is treated as GPL-licensed code for distribution purposes. A future release must therefore either remain compatible with GPL obligations or replace this integration with a commercially licensed CKEditor build before distributing the application under incompatible proprietary terms. [3]

The implementation uses only free, self-hosted CKEditor plugins required by the exam workflow: headings, basic formatting, links, lists, quotes, code blocks, and undo/redo. It does not depend on CKEditor premium services or cloud-hosted collaboration features. The editor is split into a browser-only lazy component so lightweight Node-based test suites can continue to exercise authoring/payload logic without loading the CKEditor DOM runtime.

## References

[1]: https://ckeditor.com/docs/ckeditor5/latest/getting-started/installation/self-hosted/react/react-default-npm.html "Using CKEditor 5 with React"
[2]: https://ckeditor.com/docs/ckeditor5/latest/getting-started/setup/ui-language.html "Setting the UI language"
[3]: https://ckeditor.com/docs/ckeditor5/latest/getting-started/licensing/license-and-legal.html "CKEditor licensing"
