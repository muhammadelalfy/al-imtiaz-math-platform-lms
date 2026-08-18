import { lazy, Suspense, useEffect, useState } from "react";
import { BookOpen, ClipboardList, Target } from "lucide-react";
import { toast } from "sonner";
import { ApiError, laravelApi, type ExamDepartment, type ExamQuestion, type ExamTemplate } from "@/lib/laravelApi";
import ExamPaperPreview from "@/components/ExamPaperPreview";
import ExamQuestionComposer, { type AuthoringQuestion } from "@/components/ExamQuestionComposer";
import QuestionBankPanel from "@/components/QuestionBankPanel";
import ExamTemplateActions from "@/components/ExamTemplateActions";
import { clearExamAuthoringDraft, hasExamDraftContent, readExamAuthoringDraft, saveExamAuthoringDraft, type ExamAuthoringDraft } from "@/lib/examDraftStore";

const ExamFormBuilder = lazy(() => import("@/components/ExamFormBuilder"));

type Props = { onRefresh: () => Promise<void> };

export function appendQuestionToExam(current: AuthoringQuestion[], question: Omit<ExamQuestion, "id">): AuthoringQuestion[] {
  return [...current, { ...question, sort_order: current.length }];
}

export type ExamTemplatePayload = Parameters<typeof laravelApi.createExamTemplate>[0];

export async function persistExamTemplate(editingId: number | null, payload: ExamTemplatePayload) {
  return editingId ? laravelApi.updateExamTemplate(editingId, payload) : laravelApi.createExamTemplate(payload);
}

export function buildExamTemplatePayload(input: { editingId: number | null; departmentId: string; title: string; grade: string; duration: string; instructions: string; watermark: string; printHeader?: string; printFooter?: string; questions: AuthoringQuestion[] }): ExamTemplatePayload {
  return {
    department_id: input.departmentId ? Number(input.departmentId) : null,
    title: input.title.trim(),
    grade: input.grade.trim(),
    duration_minutes: Number(input.duration),
    instructions: input.instructions,
    watermark_text: input.watermark,
    watermark_opacity: 12,
    print_header: input.printHeader?.trim() || null,
    print_footer: input.printFooter?.trim() || null,
    status: "draft" as const,
    questions: input.editingId ? input.questions : input.questions.map(({ id: _id, ...question }) => question),
  };
}

const emptyQuestion: AuthoringQuestion = {
  type: "mcq",
  prompt_html: "<p>اكتب السؤال هنا...</p>",
  options: ["الإجابة الأولى", "الإجابة الثانية"],
  correct_answer: null,
  points: 1,
  sort_order: 0,
};

export default function ExamManagementPanel({ onRefresh }: Props) {
  const [templates, setTemplates] = useState<ExamTemplate[]>([]);
  const [departments, setDepartments] = useState<ExamDepartment[]>([]);
  const [previewTemplate, setPreviewTemplate] = useState<ExamTemplate | null>(null);
  const [title, setTitle] = useState("");
  const [departmentId, setDepartmentId] = useState("");
  const [grade, setGrade] = useState("");
  const [duration, setDuration] = useState("60");
  const [watermark, setWatermark] = useState("الامتياز في الرياضيات");
  const [instructions, setInstructions] = useState("");
  const [printHeader, setPrintHeader] = useState("");
  const [printFooter, setPrintFooter] = useState("");
  const [questions, setQuestions] = useState<AuthoringQuestion[]>([]);
  const [initialQuestions, setInitialQuestions] = useState<ExamQuestion[]>([]);
  const [authoringMode, setAuthoringMode] = useState<"composer" | "form-builder">("composer");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState("");
  const [autosaveStatus, setAutosaveStatus] = useState<"idle" | "saving" | "saved" | "recovered">("idle");
  const [recoverableDraft, setRecoverableDraft] = useState<ExamAuthoringDraft | null>(null);
  const [isQuestionBankOpen, setIsQuestionBankOpen] = useState(false);
  const [deptName, setDeptName] = useState("");
  const [deptSlug, setDeptSlug] = useState("");
  const [editingDeptId, setEditingDeptId] = useState<number | null>(null);

  const load = async () => {
    try {
      const [nextTemplates, nextDepartments] = await Promise.all([laravelApi.examTemplates(), laravelApi.examDepartments()]);
      setTemplates(nextTemplates || []);
      setDepartments(nextDepartments || []);
    } catch (caught) {
      toast(caught instanceof ApiError ? caught.message : "تعذر تحميل قوالب الامتحانات");
    }
  };

  useEffect(() => { void load(); }, []);

  const resetAuthoring = () => {
    clearExamAuthoringDraft();
    setEditingId(null);
    setTitle("");
    setDepartmentId("");
    setGrade("");
    setDuration("60");
    setWatermark("الامتياز في الرياضيات");
    setInstructions("");
    setPrintHeader("");
    setPrintFooter("");
    setQuestions([]);
    setInitialQuestions([]);
    setAuthoringMode("composer");
    setIsQuestionBankOpen(false);
    setMessage("");
    setAutosaveStatus("idle");
  };

  useEffect(() => {
    const stored = readExamAuthoringDraft();
    if (stored && hasExamDraftContent(stored)) setRecoverableDraft(stored);
  }, []);

  useEffect(() => {
    const draft = { editingId, title, departmentId, grade, duration, instructions, watermark, printHeader, printFooter, questions };
    if (!hasExamDraftContent(draft)) return;
    setAutosaveStatus("saving");
    const timer = window.setTimeout(() => {
      saveExamAuthoringDraft(draft);
      setAutosaveStatus("saved");
    }, 700);
    return () => window.clearTimeout(timer);
  }, [departmentId, duration, editingId, grade, instructions, printFooter, printHeader, questions, title, watermark]);

  const restoreDraft = (draft: ExamAuthoringDraft) => {
    setEditingId(draft.editingId); setTitle(draft.title); setDepartmentId(draft.departmentId); setGrade(draft.grade); setDuration(draft.duration);
    setInstructions(draft.instructions); setWatermark(draft.watermark); setPrintHeader(draft.printHeader); setPrintFooter(draft.printFooter); setQuestions(draft.questions);
    setInitialQuestions(draft.questions as ExamQuestion[]); setRecoverableDraft(null); setAutosaveStatus("recovered");
  };

  const save = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!title.trim()) return setMessage("أدخل عنوان الامتحان أولاً.");
    if (!questions.length) return setMessage("أضف سؤالاً واحداً على الأقل قبل الحفظ.");
    setSaving(true);
    setMessage("");
    const payload = buildExamTemplatePayload({ editingId, departmentId, title, grade, duration, instructions, watermark, printHeader, printFooter, questions });
    try {
      if (editingId) {
        await persistExamTemplate(editingId, payload);
        setMessage("تم تحديث الامتحان والأسئلة وترتيبها.");
        clearExamAuthoringDraft();
      } else {
        await persistExamTemplate(null, payload);
        setMessage("تم حفظ قالب الامتحان كمسودة.");
        resetAuthoring();
      }
      await load();
      await onRefresh();
    } catch (caught) {
      setMessage(caught instanceof ApiError ? caught.message : "تعذر حفظ القالب");
    } finally {
      setSaving(false);
    }
  };

  const editTemplate = (template: ExamTemplate) => {
    setEditingId(template.id);
    setTitle(template.title);
    setDepartmentId(template.department_id ? String(template.department_id) : "");
    setGrade(template.grade || "");
    setDuration(String(template.duration_minutes));
    setWatermark(template.watermark_text || "");
    setInstructions(template.instructions || "");
    setPrintHeader(template.print_header || "");
    setPrintFooter(template.print_footer || "");
    setInitialQuestions(template.questions || []);
    setQuestions((template.questions || []).map((question, index) => ({ ...question, sort_order: index })));
    setAuthoringMode("composer");
    setMessage("");
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const setTemplateStatus = async (template: ExamTemplate, status: "published" | "archived") => {
    try {
      await laravelApi.updateExamTemplate(template.id, { status });
      await load();
      toast(status === "published" ? "تم نشر الامتحان للطلاب" : "تم أرشفة الامتحان");
    } catch (caught) {
      toast(caught instanceof ApiError ? caught.message : "تعذر تحديث حالة القالب");
    }
  };

  const removeTemplate = async (id: number) => {
    if (!window.confirm("هل تريد حذف هذا القالب؟")) return;
    try {
      await laravelApi.deleteExamTemplate(id);
      await load();
      toast("تم حذف القالب");
    } catch (caught) {
      toast(caught instanceof ApiError ? caught.message : "تعذر حذف القالب");
    }
  };

  const saveDepartment = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!deptName.trim() || !deptSlug.trim()) return;
    try {
      if (editingDeptId) await laravelApi.updateExamDepartment(editingDeptId, { name: deptName.trim(), slug: deptSlug.trim() });
      else await laravelApi.createExamDepartment({ name: deptName.trim(), slug: deptSlug.trim() });
      setDeptName(""); setDeptSlug(""); setEditingDeptId(null);
      await load();
      toast("تم حفظ القسم");
    } catch (caught) {
      toast(caught instanceof ApiError ? caught.message : "تعذر حفظ القسم");
    }
  };

  const removeDepartment = async (id: number) => {
    if (!window.confirm("هل تريد حذف هذا القسم؟ لا يمكن حذف الأقسام المرتبطة بقوالب.")) return;
    try {
      await laravelApi.deleteExamDepartment(id);
      await load();
      toast("تم حذف القسم");
    } catch (caught) {
      toast(caught instanceof ApiError ? caught.message : "تعذر حذف القسم");
    }
  };

  return (
    <section className="live-page exam-management mx-auto w-full max-w-[1280px]">
      <div className="page-head theme-surface flex flex-col gap-4 rounded-[20px] border p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6"><div><span className="eyebrow">إدارة الامتحانات</span><h2>أنشئ امتحانك بوضوح</h2><p className="muted">ابدأ بالبيانات الأساسية، أضف الأسئلة، ثم احفظ أو انشر من قائمة القوالب.</p></div><div className="exam-page-head-actions flex w-full items-center justify-between gap-3 sm:w-auto sm:justify-end"><span className="live-count">{templates.length} قالب</span><button type="button" className="secondary-button inline-flex min-h-[42px] items-center justify-center rounded-xl px-4 font-extrabold" onClick={resetAuthoring}>امتحان جديد</button></div></div>
      {recoverableDraft && <aside className="exam-draft-recovery" role="status"><div><b>مسودة غير محفوظة متاحة</b><p>آخر حفظ تلقائي كان قبل {new Intl.DateTimeFormat("ar-EG", { hour: "numeric", minute: "2-digit" }).format(recoverableDraft.savedAt)}.</p></div><span><button type="button" className="primary" onClick={() => restoreDraft(recoverableDraft)}>استعادة المسودة</button><button type="button" className="text-button" onClick={() => { clearExamAuthoringDraft(); setRecoverableDraft(null); }}>تجاهل</button></span></aside>}
      <div className="exam-management-grid mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(330px,.72fr)]">
        <form className="card theme-surface exam-authoring-card rounded-2xl border p-4 sm:p-6" onSubmit={save}>
          <div className="card-head"><div><span className="eyebrow">{editingId ? "تعديل امتحان" : "امتحان جديد"}</span><h3>{editingId ? title : "ابدأ من العنوان"}</h3></div><ClipboardList size={20} /></div>
          <details className="exam-authoring-disclosure" open><summary>معلومات الامتحان الأساسية</summary><div className="exam-form-grid"><label className="exam-title-field">عنوان الامتحان<input required value={title} onChange={event => setTitle(event.target.value)} placeholder="اختبار الوحدة الأولى" /></label><label>القسم<select value={departmentId} onChange={event => setDepartmentId(event.target.value)}><option value="">بدون قسم</option>{departments.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label><label>الصف<input value={grade} onChange={event => setGrade(event.target.value)} placeholder="الأول الإعدادي" /></label><label>المدة بالدقائق<input type="number" min="1" max="600" value={duration} onChange={event => setDuration(event.target.value)} /></label></div></details>
          <details className="exam-authoring-disclosure"><summary>إعدادات متقدمة</summary><div className="exam-form-grid"><label>العلامة المائية<input value={watermark} onChange={event => setWatermark(event.target.value)} /></label><label className="exam-title-field">تعليمات الطالب<textarea value={instructions} onChange={event => setInstructions(event.target.value)} placeholder="تعليمات الطالب قبل البدء" /></label><label>رأس الورقة المطبوع<input value={printHeader} onChange={event => setPrintHeader(event.target.value)} placeholder="مثال: اختبار نصف العام — الرياضيات" /></label><label>تذييل الورقة المطبوع<input value={printFooter} onChange={event => setPrintFooter(event.target.value)} placeholder="مثال: مع تمنياتنا بالتوفيق" /></label></div></details>
          <div className="exam-authoring-mode-switch" role="tablist" aria-label="طريقة بناء الأسئلة">
            <button type="button" role="tab" aria-selected={authoringMode === "composer"} className={authoringMode === "composer" ? "active" : ""} onClick={() => setAuthoringMode("composer")}>الأسئلة</button>
            <button type="button" role="tab" aria-selected={authoringMode === "form-builder"} className={authoringMode === "form-builder" ? "active" : ""} onClick={() => setAuthoringMode("form-builder")}>منشئ النموذج</button>
          </div>
          {authoringMode === "composer" ? <ExamQuestionComposer initialQuestions={questions} onChange={setQuestions} /> : <Suspense fallback={<div className="exam-form-builder-loading">جارٍ تجهيز منشئ النموذج...</div>}><ExamFormBuilder onImport={imported => { setQuestions(current => [...current, ...imported].map((question, index) => ({ ...question, sort_order: index }))); setAuthoringMode("composer"); toast(`تمت إضافة ${imported.length} سؤالاً من منشئ النموذج. راجعها الآن في وضع السؤال الواحد.`); }} /></Suspense>}
          <section className={`exam-authoring-disclosure exam-bank-disclosure${isQuestionBankOpen ? " is-open" : ""}`}><button type="button" className="exam-disclosure-trigger" aria-expanded={isQuestionBankOpen} aria-controls="exam-question-bank-panel" onClick={() => setIsQuestionBankOpen(current => !current)}><span>إضافة سؤال من بنك الأسئلة</span><span aria-hidden="true">{isQuestionBankOpen ? "−" : "+"}</span></button>{isQuestionBankOpen && <div id="exam-question-bank-panel"><QuestionBankPanel departments={departments} onSelect={question => { setQuestions(current => appendQuestionToExam(current, question)); setIsQuestionBankOpen(false); }} /></div>}</section>
          <div className="exam-authoring-save-state"><span className={`exam-autosave-status exam-autosave-status--${autosaveStatus}`}>{autosaveStatus === "saving" ? "جارٍ الحفظ التلقائي..." : autosaveStatus === "saved" ? "تم حفظ المسودة تلقائياً على هذا الجهاز" : autosaveStatus === "recovered" ? "تمت استعادة المسودة المحلية" : ""}</span>{message && <p className="qr-result">{message}</p>}</div>
          <button type="submit" className="primary" disabled={saving}>{saving ? "جارٍ الحفظ..." : editingId ? "حفظ بيانات الامتحان" : "حفظ الامتحان كمسودة"}</button>
        </form>
        <div className="card theme-surface exam-template-list rounded-2xl border p-4 sm:p-6 xl:sticky xl:top-5"><div className="card-head"><div><span className="eyebrow">المكتبة</span><h3>القوالب الجاهزة</h3></div><BookOpen size={20} /></div>{templates.length ? templates.map(template => <div className="exam-template-row" key={template.id}><div><b>{template.title}</b><small>{template.grade || "كل الصفوف"} · {template.duration_minutes} دقيقة · {template.questions?.length || 0} سؤال</small></div><ExamTemplateActions status={template.status} onPreview={() => setPreviewTemplate(template)} onEdit={() => editTemplate(template)} onToggleStatus={() => void setTemplateStatus(template, template.status === "published" ? "archived" : "published")} onDelete={() => void removeTemplate(template.id)} /></div>) : <p className="muted">لم يتم إنشاء قوالب بعد.</p>}</div>
      </div>
      <details className="card exam-departments-card"><summary><span><span className="eyebrow">التنظيم</span><b>إدارة أقسام الامتحانات</b></span><Target size={19} /></summary><form className="department-form" onSubmit={saveDepartment}><input value={deptName} onChange={event => setDeptName(event.target.value)} placeholder="اسم القسم" aria-label="اسم القسم" /><input value={deptSlug} onChange={event => setDeptSlug(event.target.value)} placeholder="slug" aria-label="معرف القسم" /><button className="primary">{editingDeptId ? "تحديث القسم" : "إضافة قسم"}</button></form><div className="department-list">{departments.map(department => <div className="department-row" key={department.id}><div><b>{department.name}</b><small>{department.slug}</small></div><span><button type="button" className="text-button" onClick={() => { setEditingDeptId(department.id); setDeptName(department.name); setDeptSlug(department.slug); }}>تعديل</button><button type="button" className="text-button danger-text" onClick={() => void removeDepartment(department.id)}>حذف</button></span></div>)}</div></details>
      {previewTemplate && <ExamPaperPreview template={previewTemplate} onClose={() => setPreviewTemplate(null)} onExportPdf={() => laravelApi.downloadExamPdf(previewTemplate.id)} />}
    </section>
  );
}
