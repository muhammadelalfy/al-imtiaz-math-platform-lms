import React from "react";
import katex from "katex";
import "katex/dist/katex.min.css";

const inlineMath = /\\\(([\s\S]+?)\\\)/g;
const blockMath = /\$\$([\s\S]+?)\$\$/g;

function renderLatex(
  latex: string,
  displayMode: boolean,
  fallback: string
): string {
  try {
    return katex.renderToString(latex.trim(), {
      displayMode,
      throwOnError: false,
      output: "htmlAndMathml",
    });
  } catch {
    return fallback;
  }
}

/** Converts the stored editor equation markers into KaTeX markup at display time. */
export function renderExamRichHtml(html: string): string {
  return html
    .replace(blockMath, (source, latex: string) =>
      renderLatex(latex, true, source)
    )
    .replace(inlineMath, (source, latex: string) =>
      renderLatex(latex, false, source)
    )
    .replace(/<img\b(?![^>]*\bcrossorigin=)/gi, '<img crossorigin="anonymous"');
}

type Props = {
  html: string;
  className?: string;
};

export default function ExamRichContent({
  html,
  className = "exam-question-prompt",
}: Props) {
  return (
    <div
      className={className}
      dangerouslySetInnerHTML={{ __html: renderExamRichHtml(html) }}
    />
  );
}
