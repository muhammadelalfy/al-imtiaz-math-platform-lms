import type { AuthoringQuestion } from "@/components/ExamQuestionComposer";

const EXAM_DRAFT_KEY = "al-imtiaz-exam-authoring-draft:v1";

export type ExamAuthoringDraft = {
  editingId: number | null;
  title: string;
  departmentId: string;
  grade: string;
  duration: string;
  instructions: string;
  watermark: string;
  printHeader: string;
  printFooter: string;
  questions: AuthoringQuestion[];
  savedAt: number;
};

export function hasExamDraftContent(draft: Omit<ExamAuthoringDraft, "savedAt">): boolean {
  return Boolean(draft.title.trim() || draft.instructions.trim() || draft.questions.length);
}

export function readExamAuthoringDraft(): ExamAuthoringDraft | null {
  try {
    const raw = window.localStorage.getItem(EXAM_DRAFT_KEY);
    if (!raw) return null;
    const draft = JSON.parse(raw) as ExamAuthoringDraft;
    return Array.isArray(draft.questions) && typeof draft.savedAt === "number" ? draft : null;
  } catch {
    return null;
  }
}

export function saveExamAuthoringDraft(draft: Omit<ExamAuthoringDraft, "savedAt">): ExamAuthoringDraft {
  const saved = { ...draft, savedAt: Date.now() };
  window.localStorage.setItem(EXAM_DRAFT_KEY, JSON.stringify(saved));
  return saved;
}

export function clearExamAuthoringDraft(): void {
  window.localStorage.removeItem(EXAM_DRAFT_KEY);
}
