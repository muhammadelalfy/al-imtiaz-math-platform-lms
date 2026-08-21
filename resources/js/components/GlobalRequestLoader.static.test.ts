import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { describe, expect, it } from "vitest";

const source = readFileSync(
  fileURLToPath(new URL("./GlobalRequestLoader.tsx", import.meta.url)),
  "utf8"
);
const styles = readFileSync(
  fileURLToPath(new URL("../index.css", import.meta.url)),
  "utf8"
);

describe("global request loader branding", () => {
  it("uses the managed Al-Imtiaz logo with an image-failure fallback mark", () => {
    expect(source).toContain("al-imtiaz-mark_99680b5d.png");
    expect(source).toContain("onError={() => setLogoFailed(true)}");
    expect(source).toContain("global-request-loader__logo-fallback");
    expect(styles).toContain(".global-request-loader__brand.is-fallback");
  });
});
