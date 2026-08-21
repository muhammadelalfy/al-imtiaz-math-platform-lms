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

  it("uses ScrollTrigger for section storytelling and retains a labelled WhatsApp action", () => {
    expect(landingSource).toContain('from "gsap/ScrollTrigger"');
    expect(landingSource).toContain("gsap.registerPlugin(ScrollTrigger)");
    expect(landingSource).toContain("landing-scroll-section");
    expect(landingSource).toContain("landing-whatsapp");
    expect(landingSource).toContain("افتح واتساب لبدء محادثة");
    expect(landingStyles).toContain(".landing-whatsapp");
  });

  it("keeps mock counters and illustrative artwork static", () => {
    expect(landingSource).toContain('<strong data-demo-count="3">٣</strong>');
    expect(landingSource).toContain('<strong data-demo-count="240">٢٤٠</strong>');
    expect(landingSource).toContain('<strong data-demo-count="18">١٨</strong>');
    expect(landingSource).not.toContain('gsap.to(".landing-visual"');
    expect(landingSource).not.toContain('gsap.to(".landing-ecosystem-art"');
    expect(landingSource).not.toContain('gsap.to(".landing-demo-counter-grid"');
    expect(landingSource).toContain('className="landing-demo-metrics"');
    expect(landingSource).toContain('className="landing-ecosystem"');
  });
});
