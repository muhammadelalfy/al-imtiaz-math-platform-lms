import { describe, expect, it } from "vitest";
import { portalForPath } from "./portalRoute";

describe("portalForPath", () => {
  it("maps each protected portal login route to its role", () => {
    expect(portalForPath("/control/login")).toBe("super_admin");
    expect(portalForPath("/teacher/login")).toBe("teacher");
    expect(portalForPath("/parent/login")).toBe("parent");
    expect(portalForPath("/student/login")).toBe("student");
  });

  it("keeps the LMS landing route on the teacher portal", () => {
    expect(portalForPath("/")).toBe("teacher");
  });
});
