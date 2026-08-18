import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it, vi } from "vitest";
import ExamQuestionComposer from "./ExamQuestionComposer";

vi.mock("@tiptap/starter-kit", () => ({ default: {} }));
vi.mock("@tiptap/react", () => ({
  useEditor: () => ({ getHTML: () => "<p>نص</p>", commands: { setContent: vi.fn() }, chain: () => ({ focus: () => ({ toggleBold: () => ({ run: vi.fn() }), toggleItalic: () => ({ run: vi.fn() }), toggleBulletList: () => ({ run: vi.fn() }), toggleCodeBlock: () => ({ run: vi.fn() }) }) }), isActive: () => false }),
  EditorContent: () => React.createElement("div", { className: "editor-content" }),
}));

describe("ExamQuestionComposer rendered controls", () => {
  it("renders the one-question controls and all supported type choices", () => {
    const html = renderToStaticMarkup(<ExamQuestionComposer onChange={vi.fn()} />);
    expect(html).toContain("إضافة سؤال");
    expect(html).toContain("سؤال واحد في كل مرة");
    expect(html).toContain("لم تتم إضافة أسئلة بعد");
    expect(html).not.toContain("نص السؤال — محرر غني");
  });

  it("renders edit, delete, and reorder controls for existing questions", () => {
    const html = renderToStaticMarkup(<ExamQuestionComposer initialQuestions={[
      { id: 1, type: "essay", prompt_html: "<p>الأول</p>", options: null, correct_answer: null, points: 2, sort_order: 0 },
      { id: 2, type: "geometry", prompt_html: "<p>الثاني</p>", options: { shape: "rectangle", dimensions: { width: "6", height: "4" } }, correct_answer: "24", points: 3, sort_order: 1 },
    ]} onChange={vi.fn()} />);
    expect(html).toContain("نقل لأعلى");
    expect(html).toContain("نقل لأسفل");
    expect(html).toContain("تعديل");
    expect(html).toContain("حذف");
    expect(html).toContain("svg");
  });
});
