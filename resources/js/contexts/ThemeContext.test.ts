import { describe, expect, it } from "vitest";
import { nextTheme, themeFromStorage } from "./ThemeContext";

describe("theme helpers", () => {
  it("uses a stored supported theme and safely falls back for invalid values", () => {
    expect(themeFromStorage("dark", "light")).toBe("dark");
    expect(themeFromStorage("light", "dark")).toBe("light");
    expect(themeFromStorage("contrast", "light")).toBe("light");
    expect(themeFromStorage(null, "dark")).toBe("dark");
  });

  it("switches between the two supported themes", () => {
    expect(nextTheme("light")).toBe("dark");
    expect(nextTheme("dark")).toBe("light");
  });
});
