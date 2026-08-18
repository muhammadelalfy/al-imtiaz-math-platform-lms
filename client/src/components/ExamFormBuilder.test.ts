import { describe, expect, it, vi } from "vitest";
import { isBuilderTextEntryTarget, preserveBuilderSpaceKey } from "./ExamFormBuilder";

describe("visual form-builder keyboard guard", () => {
  it("identifies editable text inputs, textareas, and content-editable elements", () => {
    expect(isBuilderTextEntryTarget({ tagName: "INPUT", type: "text" } as unknown as EventTarget)).toBe(true);
    expect(isBuilderTextEntryTarget({ tagName: "TEXTAREA" } as unknown as EventTarget)).toBe(true);
    expect(isBuilderTextEntryTarget({ tagName: "DIV", isContentEditable: true } as unknown as EventTarget)).toBe(true);
    expect(isBuilderTextEntryTarget({ tagName: "INPUT", type: "checkbox" } as unknown as EventTarget)).toBe(false);
    expect(isBuilderTextEntryTarget({ tagName: "BUTTON" } as unknown as EventTarget)).toBe(false);
  });

  it("stops only a space-key event from bubbling out of editable controls", () => {
    const stopPropagation = vi.fn();
    expect(preserveBuilderSpaceKey({ key: " ", target: { tagName: "INPUT", type: "text" } as unknown as EventTarget, stopPropagation })).toBe(true);
    expect(stopPropagation).toHaveBeenCalledOnce();
    expect(preserveBuilderSpaceKey({ key: "Enter", target: { tagName: "INPUT", type: "text" } as unknown as EventTarget, stopPropagation })).toBe(false);
    expect(preserveBuilderSpaceKey({ key: " ", target: { tagName: "BUTTON" } as unknown as EventTarget, stopPropagation })).toBe(false);
    expect(stopPropagation).toHaveBeenCalledOnce();
  });
});
