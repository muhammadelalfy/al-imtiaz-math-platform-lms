import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { describe, expect, it } from "vitest";

const examManagementSource = readFileSync(
  fileURLToPath(new URL("./ExamManagementPanel.tsx", import.meta.url)),
  "utf8"
);
const stylesheetSource = readFileSync(
  fileURLToPath(new URL("../index.css", import.meta.url)),
  "utf8"
);
const compactStylesheet = stylesheetSource.replace(/\s+/g, "");

describe("simplified exam authoring layout", () => {
  it("keeps authoring and templates in one page flow instead of a desktop side rail", () => {
    expect(examManagementSource).toContain(
      "exam-management-grid mt-5 grid grid-cols-1 gap-5"
    );
    expect(examManagementSource).not.toContain("xl:grid-cols");
    expect(examManagementSource).not.toContain("xl:sticky");
  });

  it("removes the constrained scrolling contract from templates and form-builder canvas", () => {
    expect(compactStylesheet).toContain(
      ".exam-template-list{position:static;top:auto;max-height:none;overflow:visible"
    );
    expect(compactStylesheet).toContain(
      ".exam-form-builder-canvas{overflow:visible;min-height:0;}"
    );
  });
});
