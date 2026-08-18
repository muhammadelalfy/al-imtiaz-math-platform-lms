import { CKEditor } from "@ckeditor/ckeditor5-react";
import {
  BlockQuote,
  Bold,
  ClassicEditor,
  CodeBlock,
  Essentials,
  Heading,
  Italic,
  Link,
  List,
  Paragraph,
  Undo,
} from "ckeditor5";
import arabicTranslations from "ckeditor5/translations/ar.js";
import "ckeditor5/ckeditor5.css";

type Props = {
  value: string;
  onChange: (value: string) => void;
  ariaLabel?: string;
};

const editorConfig = {
  licenseKey: "GPL",
  language: { ui: "ar", content: "ar" },
  translations: [arabicTranslations],
  plugins: [Essentials, Paragraph, Heading, Bold, Italic, Link, List, BlockQuote, CodeBlock, Undo],
  toolbar: {
    items: ["undo", "redo", "|", "heading", "|", "bold", "italic", "link", "|", "bulletedList", "numberedList", "blockQuote", "codeBlock"],
    shouldNotGroupWhenFull: true,
  },
  placeholder: "اكتب نص السؤال هنا...",
};

export default function CkRichEditorClient({ value, onChange, ariaLabel = "محرر نص السؤال" }: Props) {
  return (
    <div className="ck-rich-editor" dir="rtl" aria-label={ariaLabel}>
      <CKEditor editor={ClassicEditor} config={editorConfig} data={value} onChange={(_event, editor) => onChange(editor.getData())} />
    </div>
  );
}
