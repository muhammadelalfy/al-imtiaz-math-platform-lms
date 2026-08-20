import type { AuthoringQuestion } from "@/components/ExamQuestionComposer";

type JsonSchemaField = {
  type?: string | string[];
  title?: string;
  description?: string;
  enum?: unknown[];
  default?: unknown;
  properties?: Record<string, JsonSchemaField>;
};

type JsonSchemaDocument = {
  title?: string;
  description?: string;
  properties?: Record<string, JsonSchemaField>;
  required?: string[];
};

export type FormBuilderConversion = {
  questions: AuthoringQuestion[];
  warnings: string[];
};

const fieldLabel = (name: string, field: JsonSchemaField, section?: string) => {
  const label = field.title?.trim() || name.replace(/[_-]+/g, " ");
  return section ? `${section} — ${label}` : label;
};

const richPrompt = (title: string, description?: string) =>
  `<p><strong>${title}</strong>${description ? `<br/>${description}` : ""}</p>`;

const stringChoices = (value?: unknown[]) =>
  Array.isArray(value) ? value.map(String).filter(Boolean) : [];

function convertField(
  name: string,
  field: JsonSchemaField,
  index: number,
  section?: string
): FormBuilderConversion {
  const label = fieldLabel(name, field, section);
  const prompt_html = richPrompt(label, field.description);
  const type = Array.isArray(field.type) ? field.type[0] : field.type;
  const choices = stringChoices(field.enum);

  if (field.properties)
    return convertProperties(field.properties, index, label);
  if (choices.length >= 2) {
    return {
      questions: [
        {
          type: "mcq",
          prompt_html,
          options: choices,
          correct_answer:
            typeof field.default === "string" ? field.default : null,
          points: 1,
          sort_order: index,
        },
      ],
      warnings: [],
    };
  }
  if (type === "boolean") {
    const correct_answer =
      typeof field.default === "boolean"
        ? field.default
          ? "صح"
          : "خطأ"
        : null;
    return {
      questions: [
        {
          type: "true_false",
          prompt_html,
          options: ["صح", "خطأ"],
          correct_answer,
          points: 1,
          sort_order: index,
        },
      ],
      warnings: [],
    };
  }
  if (type === "number" || type === "integer") {
    return {
      questions: [
        {
          type: "math",
          prompt_html,
          options: null,
          correct_answer: field.default == null ? null : String(field.default),
          points: 2,
          sort_order: index,
        },
      ],
      warnings: [],
    };
  }
  if (type === "string" || !type) {
    return {
      questions: [
        {
          type: "essay",
          prompt_html,
          options: null,
          correct_answer: null,
          points: 2,
          sort_order: index,
        },
      ],
      warnings: [],
    };
  }
  return {
    questions: [],
    warnings: [
      `تم تجاهل الحقل «${label}» لأن نوعه (${type}) لا يمكن تحويله تلقائياً إلى سؤال امتحان.`,
    ],
  };
}

function convertProperties(
  properties: Record<string, JsonSchemaField>,
  startIndex = 0,
  section?: string
): FormBuilderConversion {
  return Object.entries(properties).reduce<FormBuilderConversion>(
    (result, [name, field]) => {
      const next = convertField(
        name,
        field,
        startIndex + result.questions.length,
        section
      );
      result.questions.push(...next.questions);
      result.warnings.push(...next.warnings);
      return result;
    },
    { questions: [], warnings: [] }
  );
}

export function convertFormSchemaToExamQuestions(
  schemaText: string
): FormBuilderConversion {
  try {
    const schema = JSON.parse(schemaText) as JsonSchemaDocument;
    if (!schema.properties || Object.keys(schema.properties).length === 0) {
      return {
        questions: [],
        warnings: [
          "أضف حقلاً واحداً على الأقل داخل منشئ النموذج قبل تحويله إلى أسئلة.",
        ],
      };
    }
    const result = convertProperties(schema.properties);
    return {
      ...result,
      questions: result.questions.map((question, index) => ({
        ...question,
        sort_order: index,
      })),
    };
  } catch {
    return {
      questions: [],
      warnings: [
        "تعذر قراءة مخطط النموذج. استخدم معاينة منشئ النموذج ثم أعد المحاولة.",
      ],
    };
  }
}
