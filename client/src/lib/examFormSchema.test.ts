import { describe, expect, it } from "vitest";
import { convertFormSchemaToExamQuestions } from "./examFormSchema";

describe("convertFormSchemaToExamQuestions", () => {
  it("maps supported visual-form fields into ordered Laravel exam questions", () => {
    const result = convertFormSchemaToExamQuestions(JSON.stringify({
      properties: {
        choice: { title: "اختر الإجابة", type: "string", enum: ["أ", "ب"], default: "ب" },
        valid: { title: "العبارة صحيحة", type: "boolean", default: true },
        value: { title: "احسب الناتج", type: "number", default: 12 },
        explanation: { title: "اشرح الحل", type: "string" },
      },
    }));
    expect(result.warnings).toEqual([]);
    expect(result.questions).toEqual([
      expect.objectContaining({ type: "mcq", options: ["أ", "ب"], correct_answer: "ب", sort_order: 0 }),
      expect.objectContaining({ type: "true_false", options: ["صح", "خطأ"], correct_answer: "صح", sort_order: 1 }),
      expect.objectContaining({ type: "math", correct_answer: "12", sort_order: 2 }),
      expect.objectContaining({ type: "essay", sort_order: 3 }),
    ]);
  });

  it("flattens nested sections and reports unsupported field types", () => {
    const result = convertFormSchemaToExamQuestions(JSON.stringify({
      properties: {
        algebra: { title: "الجبر", type: "object", properties: { solve: { title: "حل المعادلة", type: "string" } } },
        upload: { title: "أرفق ملفاً", type: "array" },
      },
    }));
    expect(result.questions).toEqual([expect.objectContaining({ type: "essay", prompt_html: expect.stringContaining("الجبر — حل المعادلة") })]);
    expect(result.warnings).toEqual([expect.stringContaining("أرفق ملفاً")]);
  });

  it("returns an authoring warning for empty or invalid schema JSON", () => {
    expect(convertFormSchemaToExamQuestions("{").warnings).toEqual([expect.stringContaining("تعذر قراءة")]);
    expect(convertFormSchemaToExamQuestions(JSON.stringify({ properties: {} })).warnings).toEqual([expect.stringContaining("أضف حقلاً")]);
  });
});
