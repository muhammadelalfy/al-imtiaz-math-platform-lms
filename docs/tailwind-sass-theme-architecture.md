# Tailwind and Sass Theme Architecture

## Scope

The LMS now combines **Tailwind CSS 4** for component-level layout and interaction composition with **Sass** for the shared visual language. This separates the parts of styling that benefit from local, readable utility classes from the parts that must remain globally consistent: theme palettes, semantic tokens, focus behavior, and reusable visual patterns.

## Ownership model

| Styling concern                                                     | Primary owner                    | Rationale                                                                                                           |
| ------------------------------------------------------------------- | -------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| Layout, spacing, responsive stacking, and target size               | Tailwind utilities in JSX        | The visual intent remains adjacent to the rendered control or layout.                                               |
| Theme colors, surface hierarchy, focus rings, and palette changes   | `resources/js/styles/theme.scss` | Sass maps and mixins keep the light/dark contracts centralized and prevent duplicated values.                       |
| Existing specialized editor, diagram, PDF, and legacy module styles | `resources/js/index.css`         | These remain stable while their behavior is not directly affected by the active dashboard and exam-theme migration. |

## Theme contract

The Sass layer defines the light and dark palette maps, emits the corresponding CSS custom properties, and provides small reusable primitives such as `.theme-surface`, `.theme-control`, `.theme-toggle`, and `.theme-primary-action`. The React theme context continues to persist the current preference and applies the `dark` class to the document root.

> **Implementation rule:** New dashboard and exam work should use Tailwind for arrangement and responsive behavior, then consume semantic values through `var(--surface)`, `var(--text)`, `var(--line)`, and related theme properties. New global color literals should not be introduced outside the Sass palette maps.

## Active migrated surfaces

The authenticated theme toggle, exam page header, authoring/layout grid, template library, one-question composer, and question-bank toolbar and action rows use Tailwind utility composition for their current responsive layout and action affordances. Their visual surfaces and light/dark palette still resolve from the Sass-defined semantic tokens.

| Verification                                   | Result                                                                       |
| ---------------------------------------------- | ---------------------------------------------------------------------------- |
| Sass compiler installation and Vite processing | Passed                                                                       |
| TypeScript                                     | Passed                                                                       |
| Frontend test suite                            | Passed: 47 tests across 16 files                                             |
| Production bundle                              | Passed                                                                       |
| Authenticated browser review                   | Dashboard and responsive exam workspace loaded correctly after the migration |

## Maintenance guidance

Use a CSS module or add Sass only when a style is genuinely shared, semantic, or too complex to express cleanly through utilities. Retain `index.css` only for the currently unmigrated legacy modules and third-party integration boundaries. This lets new work follow a Tailwind-first approach without risking an unnecessarily broad rewrite of stable editor, print, and mathematical-rendering styles.
