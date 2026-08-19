import React, { lazy, Suspense } from "react";

type Props = {
  value: string;
  onChange: (value: string) => void;
  ariaLabel?: string;
};

const CkRichEditorClient = lazy(() => import("./CkRichEditorClient"));

/**
 * CKEditor emits and consumes HTML, matching the persisted Laravel
 * `prompt_html` contract used by authoring, previews, and browser-PDF export.
 */
export default function CkRichEditor({
  value,
  onChange,
  ariaLabel = "محرر نص السؤال",
}: Props) {
  return (
    <Suspense
      fallback={
        <div className="rich-editor" aria-label={ariaLabel}>
          جارٍ تحميل CKEditor...
        </div>
      }
    >
      <CkRichEditorClient
        value={value}
        onChange={onChange}
        ariaLabel={ariaLabel}
      />
    </Suspense>
  );
}
