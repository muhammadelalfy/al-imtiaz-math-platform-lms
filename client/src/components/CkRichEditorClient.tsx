import React, { useEffect, useRef, useState } from "react";
import { CKEditor } from "@ckeditor/ckeditor5-react";
import { BlockQuote } from "@ckeditor/ckeditor5-block-quote";
import { Bold, Italic } from "@ckeditor/ckeditor5-basic-styles";
import { ClassicEditor } from "@ckeditor/ckeditor5-editor-classic";
import { CodeBlock } from "@ckeditor/ckeditor5-code-block";
import { Essentials } from "@ckeditor/ckeditor5-essentials";
import { Heading } from "@ckeditor/ckeditor5-heading";
import { Image, ImageCaption, ImageInsertViaUrl, ImageResize, ImageStyle, ImageTextAlternative, ImageToolbar } from "@ckeditor/ckeditor5-image";
import { Link } from "@ckeditor/ckeditor5-link";
import { List } from "@ckeditor/ckeditor5-list";
import { Paragraph } from "@ckeditor/ckeditor5-paragraph";
import { Undo } from "@ckeditor/ckeditor5-undo";
import arabicTranslations from "ckeditor5/translations/ar.js";
import "ckeditor5/ckeditor5.css";
import "mathlive";

type Props = {
  value: string;
  onChange: (value: string) => void;
  ariaLabel?: string;
};

type EditorInstance = {
  getData: () => string;
  model: {
    change: (callback: (writer: { createText: (text: string) => unknown }) => void) => void;
    insertContent: (content: unknown, selection: unknown) => void;
    document: { selection: unknown };
  };
};

type MathfieldElement = HTMLElement & {
  value: string;
  mathVirtualKeyboardPolicy: "auto" | "manual" | "sandboxed";
  focus: () => void;
};

const editorConfig = {
  licenseKey: "GPL",
  language: { ui: "ar", content: "ar" },
  translations: [arabicTranslations],
  plugins: [
    Essentials, Paragraph, Heading, Bold, Italic, Link, List, BlockQuote, CodeBlock, Undo,
    Image, ImageCaption, ImageInsertViaUrl, ImageResize, ImageStyle, ImageTextAlternative, ImageToolbar,
  ],
  toolbar: {
    items: ["undo", "redo", "|", "heading", "|", "bold", "italic", "link", "|", "bulletedList", "numberedList", "blockQuote", "codeBlock", "|", "insertImage"],
    shouldNotGroupWhenFull: true,
  },
  image: {
    toolbar: ["toggleImageCaption", "imageTextAlternative", "|", "imageStyle:inline", "imageStyle:block", "imageStyle:side", "|", "resizeImage"],
  },
  placeholder: "اكتب نص السؤال هنا...",
};

export default function CkRichEditorClient({ value, onChange, ariaLabel = "محرر نص السؤال" }: Props) {
  const [editor, setEditor] = useState<EditorInstance | null>(null);
  const [equationOpen, setEquationOpen] = useState(false);
  const [displayMode, setDisplayMode] = useState(false);
  const [latex, setLatex] = useState("x^2 + y^2 = z^2");
  const mathfieldHostRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (!equationOpen || !mathfieldHostRef.current) return;
    const mathfield = document.createElement("math-field") as MathfieldElement;
    mathfield.value = latex;
    mathfield.mathVirtualKeyboardPolicy = "manual";
    mathfield.setAttribute("aria-label", "محرر معادلة رياضية");
    mathfield.setAttribute("virtual-keyboard-mode", "manual");
    const syncLatex = () => setLatex(mathfield.value);
    mathfield.addEventListener("input", syncLatex);
    mathfieldHostRef.current.replaceChildren(mathfield);
    mathfield.focus();
    return () => mathfield.removeEventListener("input", syncLatex);
  }, [equationOpen]);

  const insertEquation = () => {
    if (!editor || !latex.trim()) return;
    const marker = displayMode ? `\n$$${latex.trim()}$$\n` : `\\(${latex.trim()}\\)`;
    editor.model.change(writer => editor.model.insertContent(writer.createText(marker), editor.model.document.selection));
    onChange(editor.getData());
    setEquationOpen(false);
  };

  return (
    <div className="ck-rich-editor" dir="rtl" aria-label={ariaLabel}>
      <CKEditor editor={ClassicEditor} config={editorConfig} data={value} onReady={instance => setEditor(instance as unknown as EditorInstance)} onChange={(_event, instance) => onChange(instance.getData())} />
      <div className="ck-rich-editor-extras">
        <button type="button" className="ck-equation-trigger" onClick={() => setEquationOpen(true)}>إدراج معادلة</button>
        <small>استخدم رابط صورة عام مباشر يدعم CORS لضمان ظهور الرسم في المعاينة وملف PDF.</small>
      </div>
      {equationOpen && (
        <div className="ck-equation-dialog" role="dialog" aria-modal="true" aria-label="إدراج معادلة رياضية">
          <div className="ck-equation-dialog-card">
            <div><span className="eyebrow">رياضيات متقدمة</span><h4>إدراج معادلة LaTeX</h4><p className="muted">اكتب المعادلة أو استخدم لوحة الرموز؛ ستُعرض في المعاينة وملف PDF.</p></div>
            <div className="mathlive-host" ref={mathfieldHostRef} />
            <label className="ck-equation-display"><input type="checkbox" checked={displayMode} onChange={event => setDisplayMode(event.target.checked)} /> عرض المعادلة في سطر مستقل</label>
            <div className="ck-equation-actions"><button type="button" className="primary" onClick={insertEquation}>إدراج المعادلة</button><button type="button" className="text-button" onClick={() => setEquationOpen(false)}>إلغاء</button></div>
          </div>
        </div>
      )}
    </div>
  );
}
