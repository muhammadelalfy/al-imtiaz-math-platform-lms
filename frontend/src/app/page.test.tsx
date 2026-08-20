import React from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import HomePage from "./page";

describe("HomePage", () => {
  it("selects the Arabic administrator portal shell", () => {
    expect(HomePage()).toMatchObject({
      props: { initialPortal: "admin" },
    });
  });
});
