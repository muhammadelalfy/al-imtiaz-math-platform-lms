# CKEditor Plugin Assessment for Complex Math Authoring

## Equation authoring

CKEditor’s official MathType integration is a premium add-on, so it is not appropriate for the already approved GPL-only, self-hosted editor configuration. The free `@isaul32/ckeditor5-math` plugin is ISC-licensed and provides TeX input with MathJax or KaTeX rendering, but its latest published compatibility target is CKEditor 5 version 43.2.0. It must not be added to the current CKEditor 5 version 48.4.0 integration without proving compatibility first. The alternate MathLive plugin is MIT-licensed but similarly targets an older CKEditor 5 release line.

The implementation will therefore retain the current GPL CKEditor build and provide equation authoring through a small local CKEditor-compatible insertion control based on well-scoped LaTeX markup. Math notation will be stored as semantic HTML, rendered with the existing math rendering path, and available in both preview and browser-PDF capture. This avoids adopting an unmaintained, version-mismatched editor plugin or a premium dependency.

## Image authoring

CKEditor 5’s native image, caption, style, resize, and URL-insertion features are open-source editor plugins. Uploading local files would require an application-controlled upload adapter that returns a durable image URL; embedding base64 image bytes in saved exam HTML is intentionally avoided because it substantially enlarges stored question data. This release deliberately configures **URL-only insertion** and removes CKEditor’s computer-upload integration, so the visible image action has one reliable behavior: it stores a durable image URL in question HTML. Rich-content rendering adds `crossorigin="anonymous"` to images, allowing browser-PDF capture when the image host provides CORS headers. Authors are explicitly guided to use public direct image URLs with CORS enabled. A dedicated upload service can be added later if the platform provisions durable, authorized media storage.

## References

[1]: https://ckeditor.com/docs/ckeditor5/latest/features/math-equations.html "CKEditor 5 Math equations and chemical formulas"
[2]: https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/image-upload.html "CKEditor 5 image upload overview"
[3]: https://github.com/isaul32/ckeditor5-math "isaul32/ckeditor5-math"
[4]: https://github.com/Yayure/ckeditor5-mathlive "Yayure/ckeditor5-mathlive"
