import { describe, expect, it } from "vitest";
import { questionBankSnapshot } from "./QuestionBankPanel";
import type { QuestionBankQuestion } from "@/lib/laravelApi";

describe("question bank selection", () => {
  it("copies typed question content into an exam snapshot without the bank id", () => {
    const item: QuestionBankQuestion = {
      id: 41,
      type: "geometry",
      title: "مساحة مستطيل",
      grade: "الأول الإعدادي",
      prompt_html: "<p>احسب المساحة.</p>",
      options: { shape: "rectangle", dimensions: { width: "6", height: "4" } },
      correct_answer: "24",
      points: 4,
      sort_order: 7,
      tags: "هندسة",
      is_active: true,
      department_id: 2,
    };
    const snapshot = questionBankSnapshot(item);
    expect(snapshot).toMatchObject({
      type: "geometry",
      prompt_html: item.prompt_html,
      options: item.options,
      points: 4,
      sort_order: 0,
    });
    expect(snapshot).not.toHaveProperty("id");
  });

  it("preserves rich equation and CORS-enabled image HTML in the copied exam snapshot", () => {
    const richHtml =
      '<p>حل \\(x^2 + 1\\)</p><figure class="image"><img src="https://placehold.co/600x400/png?text=Math+Diagram" alt="رسم رياضي" /></figure>';
    const item: QuestionBankQuestion = {
      id: 42,
      type: "math",
      title: "سؤال غني",
      grade: "الثاني الإعدادي",
      prompt_html: richHtml,
      options: { notation: "\\frac{x}{2}" },
      correct_answer: null,
      points: 3,
      sort_order: 0,
      tags: "معادلات، صور",
      is_active: true,
      department_id: null,
    };

    expect(questionBankSnapshot(item)).toMatchObject({
      type: "math",
      prompt_html: richHtml,
      options: { notation: "\\frac{x}{2}" },
      points: 3,
    });
  });
});
