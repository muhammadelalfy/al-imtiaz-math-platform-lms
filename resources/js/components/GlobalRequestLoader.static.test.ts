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
  it("uses the verified Zewal logo with an image-failure fallback mark", () => {
    expect(source).toContain("ZEWAL_LOGO_URL");
    expect(source).toContain("ز");
    expect(source).toContain("onError={() => setLogoFailed(true)}");
    expect(source).toContain("global-request-loader__logo-fallback");
    expect(styles).toContain(".global-request-loader__brand.is-fallback");
  });
});
