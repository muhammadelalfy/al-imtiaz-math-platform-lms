import { expect, test } from "@playwright/test";

test("renders the Next.js Arabic LMS login shell", async ({ page }) => {
  await page.goto("/");
  await expect(
    page.getByRole("heading", { name: "بوابة المدرس" })
  ).toBeVisible();
  await expect(page.getByLabel("البريد الإلكتروني")).toBeVisible();
});
