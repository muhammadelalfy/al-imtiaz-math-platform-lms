import React from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import HomePage from "./page";

describe("HomePage", () => {
  it("selects the Arabic teacher portal shell", () => {
    expect(HomePage()).toMatchObject({
      props: { initialPortal: "teacher" },
    });
  });
});
