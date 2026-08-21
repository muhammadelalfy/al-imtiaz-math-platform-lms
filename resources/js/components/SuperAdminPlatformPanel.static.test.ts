import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { describe, expect, it } from "vitest";

const panelSource = readFileSync(
  fileURLToPath(new URL("./SuperAdminPlatformPanel.tsx", import.meta.url)),
  "utf8"
);
const apiSource = readFileSync(
  fileURLToPath(new URL("../lib/laravelApi.ts", import.meta.url)),
  "utf8"
);
const styles = readFileSync(
  fileURLToPath(new URL("../pages/subscription-platform.scss", import.meta.url)),
  "utf8"
);
const appSource = readFileSync(
  fileURLToPath(new URL("../LmsApp.tsx", import.meta.url)),
  "utf8"
);
const dashboardSource = readFileSync(
  fileURLToPath(new URL("../pages/LiveDashboard.tsx", import.meta.url)),
  "utf8"
);

describe("super-admin control plane", () => {
  it("renders only real overview telemetry contracts", () => {
    expect(apiSource).toContain("pending_jobs: number");
    expect(apiSource).toContain("failed_jobs: number");
    expect(apiSource).toContain("memory_peak_mb: number");
    expect(panelSource).toContain("overview.queue.pending_jobs");
    expect(panelSource).toContain("overview.queue.failed_jobs");
    expect(panelSource).toContain("overview.runtime.memory_peak_mb");
  });

  it("keeps tenant oversight searchable, filterable, and actionable", () => {
    expect(panelSource).toContain("visibleSubscriptions");
    expect(panelSource).toContain("needs_action");
    expect(panelSource).toContain("بحث باسم المؤسسة أو النطاق");
    expect(panelSource).toContain("اعتماد وتفعيل");
    expect(panelSource).toContain("لا توجد مؤسسات مطابقة");
  });

  it("preserves responsive status and filter control styling", () => {
    expect(styles).toContain(".platform-list-toolbar");
    expect(styles).toContain(".platform-status-good");
    expect(styles).toContain(".platform-health-alert");
    expect(styles).toContain(".platform-list-controls input");
  });

  it("keeps the control route locked to super-admin authentication", () => {
    expect(appSource).toContain('<LiveDashboard initialPortal="super_admin" lockPortal />');
    expect(dashboardSource).toContain("lockPortal && !authenticatedUser.is_super_admin");
    expect(dashboardSource).toContain("!lockPortal && (");
  });
});
