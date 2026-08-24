import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { describe, expect, it } from "vitest";

const styles = readFileSync(
  fileURLToPath(new URL("../index.css", import.meta.url)),
  "utf8"
);
const dashboardSource = readFileSync(
  fileURLToPath(new URL("./LiveDashboard.tsx", import.meta.url)),
  "utf8"
);

describe("isolated dashboard card system", () => {
  it("keeps live dashboard cards visually separate with shared elevation and space", () => {
    expect(styles).toContain("/* Isolated dashboard-card system");
    expect(styles).toContain(".live-page .live-grid");
    expect(styles).toContain(".live-page .overview-grid");
    expect(styles).toContain(".live-page .live-stats");
    expect(styles).toContain("gap: 20px");
    expect(styles).toContain("border-radius: 22px");
  });

  it("keeps card isolation usable in dark mode, on mobile, and with reduced motion", () => {
    expect(styles).toContain(".dark .live-page .card");
    expect(styles).toContain("@media (max-width: 700px)");
    expect(styles).toContain("@media (hover: hover) and (prefers-reduced-motion: no-preference)");
    expect(styles).toContain("transform: translateY(-3px)");
  });

  it("provides teachers with accessible persistent drag ordering and a reset action", () => {
    expect(dashboardSource).toContain('from "@dnd-kit/core"');
    expect(dashboardSource).toContain('from "@dnd-kit/sortable"');
    expect(dashboardSource).toContain("teacherDashboardLayout()");
    expect(dashboardSource).toContain("updateTeacherDashboardLayout(nextOrder)");
    expect(dashboardSource).toContain("resetTeacherDashboardLayout()");
    expect(dashboardSource).toContain("اسحب لإعادة ترتيب بطاقة");
    expect(styles).toContain(".dashboard-card-drag-handle");
  });

  it("preserves card geometry during loading through shimmer skeletons", () => {
    expect(dashboardSource).toContain("DashboardCardSkeletonGrid");
    expect(dashboardSource).toContain('aria-busy="true"');
    expect(styles).toContain(".dashboard-card-skeleton");
    expect(styles).toContain("@keyframes dashboard-skeleton-shimmer");
    expect(styles).toContain("@media (prefers-reduced-motion: reduce)");
  });
});
