import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { describe, expect, it } from "vitest";

const landingSource = readFileSync(
  fileURLToPath(new URL("./PublicLandingPage.tsx", import.meta.url)),
  "utf8"
);
const landingStyles = readFileSync(
  fileURLToPath(new URL("./subscription-platform.scss", import.meta.url)),
  "utf8"
);

describe("creative subscription landing", () => {
  it("keeps client-growth counters visibly labelled as demonstration data", () => {
    expect(landingSource).toContain("عرض توضيحي للحركة داخل المنصة");
    expect(landingSource).toContain("وليست بيانات عملاء حقيقية");
    expect(landingSource).toContain('data-demo-count="240"');
  });

  it("includes the managed full-platform visual and motion-safe landing hooks", () => {
    expect(landingSource).toContain("al-imtiaz-platform-ecosystem-visual_8bfff868.png");
    expect(landingSource).toContain("prefers-reduced-motion: reduce");
    expect(landingStyles).toContain(".landing-ecosystem-art");
    expect(landingStyles).toContain(".landing-demo-counter-grid");
  });
});
