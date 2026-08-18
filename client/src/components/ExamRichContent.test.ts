import { describe, expect, it } from "vitest";
import { renderExamRichHtml } from "./ExamRichContent";

describe("ExamRichContent", () => {
  it("renders inline and display LaTeX markers with KaTeX while preserving editor HTML", () => {
    const html = renderExamRichHtml("<p>حل \\(x^2 + 1\\)</p><p>$$\\frac{1}{2}$$</p>");
    expect(html).toContain("katex");
    expect(html).toContain("<p>حل");
  });

  it("preserves inserted image markup and enables anonymous CORS capture for browser PDFs", () => {
    const html = renderExamRichHtml('<figure class="image"><img src="https://example.com/triangle.png" alt="مثلث" /></figure>');
    expect(html).toContain('src="https://example.com/triangle.png"');
    expect(html).toContain('alt="مثلث"');
    expect(html).toContain('crossorigin="anonymous"');
  });
});
