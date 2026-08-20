import { indexedDB } from "fake-indexeddb";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
  cacheOfflineSnapshot,
  clearOfflineScope,
  enqueueOfflineOperation,
  isExpired,
  offlineScopeKey,
  readOfflineOperations,
  readOfflineSnapshot,
} from "./offlineStore";

const teacherScope = { userId: 4, role: "teacher" as const };
const studentScope = { userId: 8, role: "student" as const };

beforeEach(() => {
  vi.stubGlobal("window", { indexedDB });
});

afterEach(async () => {
  await clearOfflineScope(teacherScope);
  await clearOfflineScope(studentScope);
  vi.unstubAllGlobals();
});

describe("offlineStore", () => {
  it("namespaces durable records by authenticated user and role", async () => {
    await cacheOfflineSnapshot(teacherScope, { students: [{ id: 1 }] });
    await cacheOfflineSnapshot(studentScope, { students: [{ id: 2 }] });

    await expect(readOfflineSnapshot(teacherScope)).resolves.toEqual({
      students: [{ id: 1 }],
    });
    await expect(readOfflineSnapshot(studentScope)).resolves.toEqual({
      students: [{ id: 2 }],
    });
    expect(offlineScopeKey(teacherScope)).not.toBe(
      offlineScopeKey(studentScope)
    );
  });

  it("keeps typed outbox operations in creation order and clears only their account scope", async () => {
    await enqueueOfflineOperation(teacherScope, {
      type: "attendance.create",
      data: { student_id: 1, status: "present" },
      occurredAt: "2026-08-20T08:00:00Z",
    });
    await enqueueOfflineOperation(teacherScope, {
      type: "payment.create",
      data: { student_id: 1, amount: 200, status: "paid" },
      occurredAt: "2026-08-20T08:01:00Z",
    });
    await enqueueOfflineOperation(studentScope, {
      type: "worksheet_submission.submit",
      data: { assignment_id: 2 },
      occurredAt: "2026-08-20T08:02:00Z",
    });

    await expect(readOfflineOperations(teacherScope)).resolves.toMatchObject([
      { type: "attendance.create", status: "queued" },
      { type: "payment.create", status: "queued" },
    ]);

    await clearOfflineScope(teacherScope);

    await expect(readOfflineOperations(teacherScope)).resolves.toEqual([]);
    await expect(readOfflineOperations(studentScope)).resolves.toHaveLength(1);
  });

  it("identifies expired values without depending on browser clock behavior", () => {
    expect(isExpired(100, 50, 151)).toBe(true);
    expect(isExpired(100, 50, 150)).toBe(false);
  });
});
