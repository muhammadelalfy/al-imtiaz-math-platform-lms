import { describe, expect, it } from "vitest";
import { portalForPath } from "./portalRoute";

describe("portalForPath", () => {
  it("maps each protected portal login route to its role", () => {
    expect(portalForPath("/admin/login")).toBe("admin");
    expect(portalForPath("/parent/login")).toBe("parent");
    expect(portalForPath("/student/login")).toBe("student");
  });

  it("keeps the LMS landing route on the administrator portal", () => {
    expect(portalForPath("/")).toBe("admin");
  });
});
