import { describe, expect, it } from "vitest";
import { hasExamDraftContent } from "./examDraftStore";

const baseDraft = {
  editingId: null,
  title: "",
  departmentId: "",
  grade: "",
  duration: "60",
  instructions: "",
  watermark: "الامتياز في الرياضيات",
  printHeader: "",
  printFooter: "",
  questions: [],
};

describe("exam draft autosave eligibility", () => {
  it("does not persist an untouched blank authoring form", () => {
    expect(hasExamDraftContent(baseDraft)).toBe(false);
  });

  it("persists authored metadata, instructions, or committed questions", () => {
    expect(hasExamDraftContent({ ...baseDraft, title: "مراجعة الجبر" })).toBe(true);
    expect(hasExamDraftContent({ ...baseDraft, instructions: "اقرأ الأسئلة بعناية" })).toBe(true);
    expect(hasExamDraftContent({ ...baseDraft, questions: [{ type: "essay", prompt_html: "<p>برهن</p>", options: null, correct_answer: null, points: 2, sort_order: 0 }] })).toBe(true);
  });
});
