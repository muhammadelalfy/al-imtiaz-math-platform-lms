import React, { useMemo, useState, type ReactNode } from "react";
import {
  FormBuilder,
  type Mods,
} from "@ginkgo-bioworks/react-json-schema-form-builder";
import { Braces, CheckCircle2, FileWarning } from "lucide-react";
import type { AuthoringQuestion } from "./ExamQuestionComposer";
import { convertFormSchemaToExamQuestions } from "@/lib/examFormSchema";

type Props = {
  onImport: (questions: AuthoringQuestion[]) => void;
};

const starterSchema = JSON.stringify(
  {
    title: "نموذج اختبار جديد",
    description: "أضف حقولاً ثم حوّلها إلى أسئلة قابلة للتحرير.",
    properties: {
      question_one: {
        title: "اكتب نص السؤال هنا",
        type: "string",
        description:
          "استخدم الإجابة الطويلة للأسئلة المقالية أو أضف خيارات للاختيار من متعدد.",
      },
    },
  },
  null,
  2
);

const starterUiSchema = JSON.stringify(
  { question_one: { "ui:widget": "textarea" } },
  null,
  2
);

const arabicMods: Mods = {
  labels: {
    formNameLabel: "عنوان النموذج",
    formDescriptionLabel: "وصف النموذج",
    objectNameLabel: "المعرف الداخلي",
    displayNameLabel: "نص السؤال",
    descriptionLabel: "إرشاد أو وصف",
    inputTypeLabel: "نوع الحقل",
    addElementLabel: "إضافة حقل",
    addSectionLabel: "إضافة قسم",
  },
  deactivatedFormInputs: ["time", "password", "array"],
};

type KeyboardEventLike = {
  key: string;
  target: EventTarget | null;
  stopPropagation: () => void;
};

export function isBuilderTextEntryTarget(target: EventTarget | null): boolean {
  const element = target as {
    tagName?: string;
    type?: string;
    isContentEditable?: boolean;
    closest?: (selector: string) => unknown;
  } | null;
  if (!element) return false;
  if (
    element.isContentEditable ||
    element.closest?.("[contenteditable='true']")
  )
    return true;
  const tag = element.tagName?.toUpperCase();
  if (tag === "TEXTAREA") return true;
  if (tag !== "INPUT") return false;
  return ![
    "button",
    "checkbox",
    "color",
    "file",
    "hidden",
    "image",
    "radio",
    "range",
    "reset",
    "submit",
  ].includes((element.type || "text").toLowerCase());
}

export function preserveBuilderSpaceKey(event: KeyboardEventLike): boolean {
  if (event.key !== " " || !isBuilderTextEntryTarget(event.target))
    return false;
  event.stopPropagation();
  return true;
}

export function BuilderKeyboardBoundary({ children }: { children: ReactNode }) {
  return (
    <div
      className="exam-form-builder-canvas"
      onKeyDownCapture={preserveBuilderSpaceKey}
    >
      {children}
    </div>
  );
}

export default function ExamFormBuilder({ onImport }: Props) {
  const [schema, setSchema] = useState(starterSchema);
  const [uiSchema, setUiSchema] = useState(starterUiSchema);
  const conversion = useMemo(
    () => convertFormSchemaToExamQuestions(schema),
    [schema]
  );

  return (
    <section className="exam-form-builder" dir="rtl">
      <header className="exam-form-builder-head">
        <div>
          <span className="eyebrow">الخيار الثاني للبناء</span>
          <h3>
            <Braces size={18} aria-hidden="true" /> منشئ النموذج بالسحب والإفلات
          </h3>
          <p className="muted">
            أنشئ حقول النموذج بصرياً، ثم حوّل الأنواع المدعومة إلى أسئلة امتحان
            قابلة للمراجعة في وضع السؤال الواحد.
          </p>
        </div>
        <div className="exam-form-builder-count">
          <CheckCircle2 size={15} aria-hidden="true" />{" "}
          {conversion.questions.length} سؤال قابل للتحويل
        </div>
      </header>
      <BuilderKeyboardBoundary>
        <FormBuilder
          schema={schema}
          uischema={uiSchema}
          mods={arabicMods}
          className="exam-form-builder-mui"
          onChange={(nextSchema, nextUiSchema) => {
            setSchema(nextSchema);
            setUiSchema(nextUiSchema);
          }}
        />
      </BuilderKeyboardBoundary>
      {conversion.warnings.length > 0 && (
        <div className="exam-form-builder-warnings" role="status">
          <FileWarning size={16} aria-hidden="true" />
          <div>
            {conversion.warnings.map(warning => (
              <p key={warning}>{warning}</p>
            ))}
          </div>
        </div>
      )}
      <footer className="exam-form-builder-actions">
        <p className="muted">
          التحويل المدعوم: خيارات أو قائمة → اختيار من متعدد، صح/خطأ → صح أو
          خطأ، رقم → مسألة رياضية، نص قصير/طويل → سؤال مقالي.
        </p>
        <button
          type="button"
          className="primary"
          disabled={conversion.questions.length === 0}
          onClick={() => onImport(conversion.questions)}
        >
          تحويل وإضافة الأسئلة للامتحان
        </button>
      </footer>
    </section>
  );
}
