import { describe, expect, it, vi } from "vitest";
import {
  clearTeacherAcademyName,
  readTeacherAcademyName,
  rememberTeacherAcademyName,
  ZEWAL_LOGO_URL,
  ZEWAL_NAME_AR,
  ZEWAL_PLATFORM_NAME_AR,
} from "./appBrand";

describe("managed Zewal application title", () => {
  it("exposes the configured Vite application title to the frontend", () => {
    expect(import.meta.env.VITE_APP_TITLE).toBe("زويل | Zewal");
    expect(ZEWAL_NAME_AR).toBe("زويل");
    expect(ZEWAL_PLATFORM_NAME_AR).toBe("منصة زويل التعليمية");
    expect(ZEWAL_LOGO_URL).toContain("files.manuscdn.com");
  });

  it("keeps each teacher academy name separate from the shared Zewal brand", () => {
    const store = new Map<string, string>();
    vi.stubGlobal("window", {
      localStorage: {
        getItem: (key: string) => store.get(key) || null,
        setItem: (key: string, value: string) => store.set(key, value),
        removeItem: (key: string) => store.delete(key),
      },
      dispatchEvent: () => true,
    });
    clearTeacherAcademyName();
    rememberTeacherAcademyName({
      role: "teacher",
      academy_name: "الامتياز",
    });
    expect(readTeacherAcademyName()).toBe("الامتياز");
    clearTeacherAcademyName();
    expect(readTeacherAcademyName()).toBeNull();
    vi.unstubAllGlobals();
  });
});
