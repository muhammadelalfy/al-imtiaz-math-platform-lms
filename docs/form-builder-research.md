# Visual form-builder evaluation

The second exam-authoring mode uses **React JSON Schema Form Builder** from Ginkgo Bioworks. It is an Apache-2.0 visual JSON-Schema builder that lists React 19 and MUI 7 as requirements, supports dragging and editing fields, and exposes schema/UI-schema changes through a typed callback. It therefore fits the existing React 19 stack without creating a proprietary runtime dependency. [1]

SurveyJS Creator was also evaluated because it is a widely used React drag-and-drop form creator with JSON-schema output and localization support. However, its creator component requires a commercial developer license for use in an application, so it was not selected for this zero-additional-license implementation. [2]

The adapter only converts the domain-safe subset needed by the Laravel exam contract: enumerations become multiple-choice, booleans become true/false, numbers become math, and strings become essays. Nested objects are flattened with their section label. Unsupported fields are reported before import rather than silently stored as an invalid exam question. Imported questions remain ordinary editable exam snapshots and enter the existing ordered template-persistence path.

During live verification, the new `question_bank_questions` migration was found not to be applied to the active local SQLite database. The migration was applied and the guarded Arabic demo seeder was rerun under the local environment to restore representative bank questions.

## References

[1]: https://github.com/ginkgobioworks/react-json-schema-form-builder "React JSON Schema Form Builder repository"
[2]: https://surveyjs.io/licensing "SurveyJS licensing"
