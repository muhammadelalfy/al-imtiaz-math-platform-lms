import { describe, expect, it, vi } from "vitest";
import { BuilderKeyboardBoundary, isBuilderTextEntryTarget, preserveBuilderSpaceKey } from "./ExamFormBuilder";

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

  it("binds the guard at the embedded builder canvas boundary used by field-card editors", () => {
    const boundary = BuilderKeyboardBoundary({ children: null });
    const stopPropagation = vi.fn();
    const handler = boundary.props.onKeyDownCapture as (event: { key: string; target: EventTarget | null; stopPropagation: () => void }) => void;
    handler({ key: " ", target: { tagName: "INPUT", type: "text" } as unknown as EventTarget, stopPropagation });
    expect(boundary.props.className).toBe("exam-form-builder-canvas");
    expect(stopPropagation).toHaveBeenCalledOnce();
  });
});
