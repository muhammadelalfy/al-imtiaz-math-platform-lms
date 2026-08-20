# Responsive Exam Workspace and Theme System

## Purpose

This update gives the Arabic RTL LMS a single, persistent light/dark theme preference and refines the exam workspace for clearer use on desktop, tablet, and phone-sized screens. The implementation deliberately keeps the existing exam authoring behavior, rich-media editor, autosave, preview, and question-bank workflow intact while improving hierarchy, contrast, target size, and layout resilience.

## Theme contract

The application stores the selected theme in `localStorage` under the `theme` key. The `ThemeProvider` adds the `dark` class to the document root, so all shared surfaces can respond through semantic CSS variables rather than duplicating component-level colors.

| Token group       | Light treatment                      | Dark treatment                  | Intended use                                                 |
| ----------------- | ------------------------------------ | ------------------------------- | ------------------------------------------------------------ |
| Workspace         | Bright blue-white canvas             | Deep navy canvas                | Page background and application frame                        |
| Surface           | White and soft slate cards           | Layered navy cards              | Forms, panels, library rows, and dialogs                     |
| Accent            | Teal with a deeper teal action state | Mint-teal with high contrast    | Primary calls to action, focus states, and active navigation |
| Supporting accent | Copper-gold                          | Warm gold                       | Editorial and mathematical highlights                        |
| Text              | Deep ink with muted slate            | Near-white with muted blue-gray | Arabic readability and hierarchy                             |

The toggle appears in the authenticated dashboard header beside connectivity and refresh controls, avoiding overlap with the primary interface. Login screens retain a floating version of the same control. The control supplies an explicit Arabic accessible name that describes the next available theme.

## Responsive exam behavior

The exam workspace now uses a more deliberate two-column desktop layout, with the template library kept in view while an instructor authors a paper. At tablet width, the library becomes a compact two-column collection below the authoring panel. At phone width, all authoring controls stack vertically, action buttons become easy-to-tap full-width controls, and the template library becomes a single flowing list.

| Viewport range | Authoring layout                           | Library behavior                                  | Interaction refinement                                              |
| -------------- | ------------------------------------------ | ------------------------------------------------- | ------------------------------------------------------------------- |
| Desktop        | Main composer plus sticky template library | Independently scrollable, in view while authoring | Clear card hierarchy and grouped actions                            |
| Tablet         | Single authoring flow                      | Two-column template grid                          | Reduced visual density without losing metadata                      |
| Phone          | One-column, touch-first flow               | Single template list                              | 42–48px primary controls, stacked editing and question-bank actions |

The Arabic login surface was also tested at a 390px width. It now constrains the authentication card to the viewport, preserves its centered brand presentation, and prevents accidental horizontal scrolling.

## Validation record

| Check                     | Result                                                                                  |
| ------------------------- | --------------------------------------------------------------------------------------- |
| Theme state helper tests  | Passed; persisted values, fallbacks, and light/dark switching are covered               |
| TypeScript check          | Passed                                                                                  |
| Frontend test suite       | Passed: 44 tests across 15 files                                                        |
| Frontend formatting check | Passed                                                                                  |
| Production bundle         | Passed                                                                                  |
| Browser desktop review    | Exam workspace loaded with the modern action hierarchy and visible header theme control |
| Browser phone review      | Login surface remained centered and contained at 390 × 844 pixels                       |
