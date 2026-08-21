import {
  clearOfflineScope,
  enqueueOfflineOperation,
  readOfflineOperations,
  removeOfflineOperation,
  replaceOfflineOperation,
  type OfflineOperationType,
  type OfflineScope,
} from "./offlineStore";

export type Role = "admin" | "teacher" | "parent" | "student";

export type ApiUser = {
  id: number;
  name: string;
  email: string;
  role: Role;
  can_manage_authorization?: boolean;
  can_send_notifications?: boolean;
  can_manage_groups?: boolean;
  can_manage_notification_channels?: boolean;
  is_super_admin?: boolean;
  student_account?: { student?: Student } | null;
};
export type AuthorizationPermission = {
  id: number;
  name: string;
  label: string;
  description?: string | null;
  is_system: boolean;
};
export type AuthorizationRole = {
  id: number;
  name: string;
  label: string;
  description?: string | null;
  is_system: boolean;
  permissions: AuthorizationPermission[];
  permission_ids: number[];
};
export type AuthorizationStaff = {
  id: number;
  name: string;
  email: string;
  base_role: "admin" | "teacher";
  roles: AuthorizationRole[];
  role_ids: number[];
};
export type AuthorizationCatalog = {
  permissions: AuthorizationPermission[];
  roles: AuthorizationRole[];
  staff: AuthorizationStaff[];
};
export type Student = {
  id: number;
  name: string;
  group: string;
  grade: string;
  phone: string;
  parent_phone?: string | null;
  status: "excellent" | "average" | "weak";
  assignments_count?: number;
  attendance_records_count?: number;
  exam_results_count?: number;
  payments_count?: number;
};
export type Worksheet = {
  id: number;
  title: string;
  subject: string;
  grade: string;
  status: "draft" | "published";
  assignments_count?: number;
  submitted_count?: number;
  assignments?: Assignment[];
};
export type PluginProduct = {
  id: number;
  slug: string;
  name: string;
  description?: string | null;
  version: string;
  module_name: string;
  price: string;
  purchased: boolean;
  installed: boolean;
  installed_module?: string | null;
  payment_status?: "pending" | "submitted" | null;
  metadata?: Record<string, unknown> | null;
};
export type PluginPaymentMethod = {
  id: number;
  code: "vodafone_cash" | "instapay" | "fawry";
  label: string;
  recipient?: string | null;
  instructions?: string | null;
  is_enabled: boolean;
};
export type PluginPaymentTransaction = {
  id: number;
  status: "pending" | "submitted" | "approved" | "rejected";
  amount: string;
  currency: string;
  reference?: string | null;
  customer_note?: string | null;
  review_note?: string | null;
  reviewed_at?: string | null;
  fulfilled_at?: string | null;
  plugin?: PluginProduct;
  method?: PluginPaymentMethod;
};
export type Assignment = {
  id: number;
  status: "assigned" | "in_progress" | "submitted" | "graded";
  score?: number | null;
  max_score?: number | null;
  feedback?: string | null;
  worksheet?: Worksheet;
  student?: Student;
};
export type Attendance = {
  id: number;
  student_id: number;
  date_at: string;
  attendance_date?: string | null;
  status: "present" | "absent" | "late";
  note?: string | null;
  student?: Student;
};
export type StudentQr = {
  student_id: number;
  payload: string;
  generated_at: string;
};
export type ExamResult = {
  id: number;
  student_id: number;
  title: string;
  score: number;
  max_score: number;
  taken_at: string;
  student?: Student;
};
export type ExamDepartment = {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  is_active: boolean;
};
export type GeometryDiagramSpec = {
  shape: "rectangle" | "triangle" | "circle" | "angle";
  dimensions: Record<string, string>;
  labels?: Record<string, string>;
};
export type MathQuestionOptions = { notation?: string; latex?: string };
export type ExamQuestion = {
  id?: number;
  type: "mcq" | "true_false" | "essay" | "math" | "geometry";
  prompt_html: string;
  options?: string[] | GeometryDiagramSpec | MathQuestionOptions | null;
  correct_answer?: string | null;
  points: number;
  sort_order?: number;
};
export type ExamTemplate = {
  id: number;
  department_id?: number | null;
  title: string;
  grade?: string | null;
  duration_minutes: number;
  instructions?: string | null;
  watermark_text?: string | null;
  watermark_opacity: number;
  print_header?: string | null;
  print_footer?: string | null;
  status: "draft" | "published" | "archived";
  department?: ExamDepartment | null;
  questions: ExamQuestion[];
};
export type QuestionBankQuestion = Omit<ExamQuestion, "id"> & {
  id: number;
  title?: string | null;
  grade?: string | null;
  tags?: string | null;
  is_active: boolean;
  department_id?: number | null;
  department?: ExamDepartment | null;
};
export type ExamSession = {
  id: number;
  template_id: number;
  student_id: number;
  status: "ready" | "active" | "submitted" | "flagged" | "expired";
  started_at?: string | null;
  submitted_at?: string | null;
  camera_required: boolean;
  fullscreen_required: boolean;
  focus_loss_count: number;
  template: ExamTemplate;
  answers: { id: number; question_id: number; answer?: string | null }[];
};

export type Payment = {
  id: number;
  student_id: number;
  amount: number;
  status: "pending" | "paid" | "overdue";
  due_at: string;
  paid_at?: string | null;
  note?: string | null;
  student?: Student;
};

export type AcademicGroup = {
  id: number;
  grade: string;
  name: string;
  is_active: boolean;
  students_count?: number;
  students?: Pick<Student, "id" | "name" | "grade" | "group">[];
};

export type NotificationChannel = "in_app" | "whatsapp" | "sms";
export type NotificationAudience =
  | "all_parents"
  | "all_students"
  | "selected"
  | "grade"
  | "academic_group";
export type NotificationChannelSetting = {
  id: number;
  code: NotificationChannel;
  label: string;
  is_enabled: boolean;
  is_provider_ready: boolean;
  settings: {
    sender_label?: string | null;
    template_name?: string | null;
    auto_create_group?: boolean;
  };
  updated_at?: string | null;
};
export type NotificationCampaign = {
  id: number;
  audience: NotificationAudience;
  grade?: string | null;
  academic_group_id?: number | null;
  title: string;
  body: string;
  recipient_count: number;
  channels: NotificationChannel[];
  queued_at?: string | null;
  completed_at?: string | null;
};
export type InAppNotification = {
  id: string;
  title: string;
  body: string;
  campaign_id?: number | null;
  created_at?: string | null;
  read_at?: string | null;
};
export type NotificationAudienceCatalog = {
  grades: string[];
  recipients: Pick<ApiUser, "id" | "name" | "role">[];
  academic_groups: AcademicGroup[];
  channels: Pick<
    NotificationChannelSetting,
    "code" | "label" | "is_enabled" | "is_provider_ready"
  >[];
};
export type OfflineSyncSnapshot = {
  generated_at: string;
  scope: { user_id: number; role: Role; student_id: number | null };
  students: Student[];
  worksheets: Worksheet[];
  attendance: Attendance[];
  exams: ExamResult[];
  payments: Payment[];
};
export type QueuedOfflineOperation = {
  queued: true;
  operationId: string;
  type: OfflineOperationType;
};
export type OfflineSyncSummary = {
  applied: number;
  rejected: number;
  conflicts: number;
  pending: number;
};

const API_URL = (process.env.NEXT_PUBLIC_LARAVEL_API_URL || "/api").replace(
  /\/$/,
  ""
);
const TOKEN_KEY = "al-imtiaz-laravel-token";
let activeOfflineScope: OfflineScope | null = null;

function saveToken(token: string): void {
  window.localStorage.setItem(TOKEN_KEY, token);
}

function rememberOfflineScope(user: ApiUser): void {
  activeOfflineScope = { userId: user.id, role: user.role };
}

export function offlineScopeForUser(user: ApiUser): OfflineScope {
  return { userId: user.id, role: user.role };
}

export function isQueuedOfflineOperation(
  result: unknown
): result is QueuedOfflineOperation {
  return (
    typeof result === "object" &&
    result !== null &&
    "queued" in result &&
    (result as { queued?: unknown }).queued === true
  );
}

async function requestCollection<T>(path: string): Promise<T[]> {
  const result = await request<{ data: T[] } | T[]>(path);
  return Array.isArray(result) ? result : result.data;
}

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string
  ) {
    super(message);
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const token = window.localStorage.getItem(TOKEN_KEY);
  const headers = {
    Accept: "application/json",
    "Content-Type": "application/json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(init.headers || {}),
  };
  if (!navigator.onLine) throw new ApiError(0, "لا يوجد اتصال بالخادم حالياً.");
  try {
    const response = await fetch(`${API_URL}${path}`, { ...init, headers });
    const body = await response.json().catch(() => null);
    if (!response.ok)
      throw new ApiError(response.status, body?.message || "تعذر إتمام الطلب");
    return body as T;
  } catch (error) {
    if (!(error instanceof ApiError))
      throw new ApiError(0, "تعذر الاتصال بالخادم.");
    throw error;
  }
}

async function queueSupportedOperation(
  type: OfflineOperationType,
  data: Record<string, unknown>,
  baseUpdatedAt?: string
): Promise<QueuedOfflineOperation> {
  if (!activeOfflineScope)
    throw new ApiError(0, "سجّل الدخول قبل حفظ العمليات للعمل دون اتصال.");
  const operation = await enqueueOfflineOperation(activeOfflineScope, {
    type,
    data,
    occurredAt: new Date().toISOString(),
    ...(baseUpdatedAt ? { baseUpdatedAt } : {}),
  });
  return { queued: true, operationId: operation.id, type };
}

async function createOrQueue<T>(
  path: string,
  type: OfflineOperationType,
  data: Record<string, unknown>,
  baseUpdatedAt?: string
): Promise<T | QueuedOfflineOperation> {
  try {
    return await request<T>(path, {
      method: "POST",
      body: JSON.stringify(data),
    });
  } catch (error) {
    if (error instanceof ApiError && error.status === 0)
      return queueSupportedOperation(type, data, baseUpdatedAt);
    throw error;
  }
}

export async function syncOfflineQueue(): Promise<OfflineSyncSummary> {
  if (!navigator.onLine || !activeOfflineScope)
    return { applied: 0, rejected: 0, conflicts: 0, pending: 0 };
  const operations = (await readOfflineOperations(activeOfflineScope)).filter(
    operation => operation.status === "queued"
  );
  if (!operations.length)
    return { applied: 0, rejected: 0, conflicts: 0, pending: 0 };
  try {
    const result = await request<{
      data: {
        operations: {
          id: string;
          outcome: "applied" | "duplicate" | "rejected" | "conflict";
          error_code?: string | null;
        }[];
      };
    }>("/sync/operations", {
      method: "POST",
      body: JSON.stringify({
        operations: operations.map(operation => ({
          id: operation.id,
          type: operation.type,
          occurred_at: operation.occurredAt,
          ...(operation.baseUpdatedAt
            ? { base_updated_at: operation.baseUpdatedAt }
            : {}),
          data: operation.data,
        })),
      }),
    });
    let applied = 0;
    let rejected = 0;
    let conflicts = 0;
    for (const outcome of result.data.operations) {
      const operation = operations.find(item => item.id === outcome.id);
      if (!operation) continue;
      if (outcome.outcome === "applied" || outcome.outcome === "duplicate") {
        await removeOfflineOperation(operation.id);
        applied += 1;
      } else {
        await replaceOfflineOperation({
          ...operation,
          status: outcome.outcome,
          errorCode: outcome.error_code || undefined,
          retryCount: operation.retryCount + 1,
        });
        if (outcome.outcome === "conflict") conflicts += 1;
        else rejected += 1;
      }
    }
    const pending = (await readOfflineOperations(activeOfflineScope)).filter(
      operation => operation.status === "queued"
    ).length;
    return { applied, rejected, conflicts, pending };
  } catch {
    return {
      applied: 0,
      rejected: 0,
      conflicts: 0,
      pending: operations.length,
    };
  }
}

export const laravelApi = {
  getToken: () => window.localStorage.getItem(TOKEN_KEY),
  async login(payload: { email: string; password: string }) {
    const result = await request<{ user: ApiUser; token: string }>(
      "/auth/login",
      { method: "POST", body: JSON.stringify(payload) }
    );
    saveToken(result.token);
    rememberOfflineScope(result.user);
    return result.user;
  },
  async loginAsRole(
    role: "admin" | "teacher" | "parent" | "student",
    payload: { email: string; password: string }
  ) {
    const result = await request<{
      user: ApiUser;
      token: string;
      login_type: string;
    }>(`/auth/${role}/login`, {
      method: "POST",
      body: JSON.stringify(payload),
    });
    saveToken(result.token);
    rememberOfflineScope(result.user);
    return result.user;
  },
  async register(payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: "parent" | "student";
  }) {
    const result = await request<{ user: ApiUser; token: string }>(
      "/auth/register",
      { method: "POST", body: JSON.stringify(payload) }
    );
    saveToken(result.token);
    rememberOfflineScope(result.user);
    return result.user;
  },
  async me() {
    const user = await request<ApiUser>("/auth/me");
    rememberOfflineScope(user);
    return user;
  },
  async authorizationCatalog() {
    return request<AuthorizationCatalog>("/staff/authorization/catalog");
  },
  async createAuthorizationPermission(
    payload: Pick<AuthorizationPermission, "name" | "label" | "description">
  ) {
    return request<AuthorizationPermission>(
      "/staff/authorization/permissions",
      {
        method: "POST",
        body: JSON.stringify(payload),
      }
    );
  },
  async updateAuthorizationPermission(
    id: number,
    payload: Partial<
      Pick<AuthorizationPermission, "name" | "label" | "description">
    >
  ) {
    return request<AuthorizationPermission>(
      `/staff/authorization/permissions/${id}`,
      { method: "PUT", body: JSON.stringify(payload) }
    );
  },
  async deleteAuthorizationPermission(id: number) {
    return request<void>(`/staff/authorization/permissions/${id}`, {
      method: "DELETE",
    });
  },
  async createAuthorizationRole(
    payload: Pick<
      AuthorizationRole,
      "name" | "label" | "description" | "permission_ids"
    >
  ) {
    return request<AuthorizationRole>("/staff/authorization/roles", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
  async updateAuthorizationRole(
    id: number,
    payload: Partial<
      Pick<
        AuthorizationRole,
        "name" | "label" | "description" | "permission_ids"
      >
    >
  ) {
    return request<AuthorizationRole>(`/staff/authorization/roles/${id}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
  },
  async deleteAuthorizationRole(id: number) {
    return request<void>(`/staff/authorization/roles/${id}`, {
      method: "DELETE",
    });
  },
  async syncStaffAuthorizationRoles(userId: number, roleIds: number[]) {
    return request<AuthorizationStaff>(
      `/staff/authorization/staff/${userId}/roles`,
      { method: "PUT", body: JSON.stringify({ role_ids: roleIds }) }
    );
  },
  async logout() {
    await request("/auth/logout", { method: "POST" });
    if (activeOfflineScope) await clearOfflineScope(activeOfflineScope);
    activeOfflineScope = null;
    window.localStorage.removeItem(TOKEN_KEY);
  },
  async students(
    filters: { grade?: string; group?: string; search?: string } = {}
  ) {
    const query = new URLSearchParams(
      Object.entries(filters)
        .filter(([, value]) => value)
        .map(([key, value]) => [key, String(value)])
    ).toString();
    const result = await request<{ data: Student[] }>(
      `/students${query ? `?${query}` : ""}`
    );
    return result.data;
  },
  async student(studentId: number) {
    return request<Student>(`/students/${studentId}`);
  },
  async createStudent(payload: Omit<Student, "id">) {
    return request<Student>("/students", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
  async updateStudent(id: number, payload: Partial<Student>) {
    return request<Student>(`/students/${id}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
  },
  async deleteStudent(id: number) {
    return request<void>(`/students/${id}`, { method: "DELETE" });
  },
  async studentQr(studentId: number) {
    return request<StudentQr>(`/students/${studentId}/qr`);
  },
  async worksheets() {
    return requestCollection<Worksheet>("/worksheets");
  },
  async attendance() {
    return requestCollection<Attendance>("/attendance");
  },
  async exams() {
    return requestCollection<ExamResult>("/exams");
  },
  async payments() {
    return requestCollection<Payment>("/payments");
  },
  async offlineSnapshot() {
    const result = await request<{ data: OfflineSyncSnapshot }>(
      "/sync/snapshot"
    );
    return result.data;
  },
  async examDepartments() {
    return requestCollection<ExamDepartment>("/exam-departments");
  },
  async createExamDepartment(
    payload: Pick<ExamDepartment, "name" | "slug"> & { description?: string }
  ) {
    return request<ExamDepartment>("/exam-departments", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
  async updateExamDepartment(id: number, payload: Partial<ExamDepartment>) {
    return request<ExamDepartment>(`/exam-departments/${id}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
  },
  async deleteExamDepartment(id: number) {
    return request<void>(`/exam-departments/${id}`, { method: "DELETE" });
  },
  async examTemplates() {
    const result = await request<{ data: ExamTemplate[] }>("/exam-templates");
    return result.data;
  },
  async questionBank(
    filters: {
      search?: string;
      type?: ExamQuestion["type"];
      grade?: string;
    } = {}
  ) {
    const query = new URLSearchParams(
      Object.entries(filters)
        .filter(([, value]) => value)
        .map(([key, value]) => [key, String(value)])
    ).toString();
    const result = await request<{ data: QuestionBankQuestion[] }>(
      `/question-bank${query ? `?${query}` : ""}`
    );
    return result.data;
  },
  async createQuestionBankQuestion(
    payload: Omit<QuestionBankQuestion, "id" | "department" | "created_by">
  ) {
    return request<QuestionBankQuestion>("/question-bank", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
  async updateQuestionBankQuestion(
    id: number,
    payload: Partial<QuestionBankQuestion>
  ) {
    return request<QuestionBankQuestion>(`/question-bank/${id}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
  },
  async deleteQuestionBankQuestion(id: number) {
    return request<void>(`/question-bank/${id}`, { method: "DELETE" });
  },
  async downloadExamPdf(templateId: number) {
    const token = window.localStorage.getItem(TOKEN_KEY);
    const response = await fetch(
      `${API_URL}/exam-templates/${templateId}/pdf`,
      {
        headers: {
          Accept: "application/pdf",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
      }
    );
    if (!response.ok) {
      const body = await response.json().catch(() => null);
      throw new ApiError(
        response.status,
        body?.message || "تعذر تحميل ملف PDF"
      );
    }
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = `exam-${templateId}.pdf`;
    anchor.click();
    URL.revokeObjectURL(url);
  },
  async createExamTemplate(
    payload: Omit<ExamTemplate, "id" | "department" | "questions"> & {
      questions: Omit<ExamQuestion, "id">[];
    }
  ) {
    return request<ExamTemplate>("/exam-templates", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
  async updateExamTemplate(
    id: number,
    payload: Partial<Omit<ExamTemplate, "id" | "department" | "questions">> & {
      questions?: ExamQuestion[];
    }
  ) {
    return request<ExamTemplate>(`/exam-templates/${id}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
  },
  async deleteExamTemplate(id: number) {
    return request<void>(`/exam-templates/${id}`, { method: "DELETE" });
  },
  async startExamSession(templateId: number) {
    return request<ExamSession>(`/exam-templates/${templateId}/start`, {
      method: "POST",
    });
  },
  async recordExamEvent(
    sessionId: number,
    type: string,
    metadata?: Record<string, unknown>
  ) {
    return request(`/exam-sessions/${sessionId}/events`, {
      method: "POST",
      body: JSON.stringify({ type, metadata }),
    });
  },
  async saveExamAnswer(sessionId: number, questionId: number, answer: string) {
    return request(`/exam-sessions/${sessionId}/answers`, {
      method: "POST",
      body: JSON.stringify({ question_id: questionId, answer }),
    });
  },
  async submitExam(sessionId: number) {
    return request<ExamSession>(`/exam-sessions/${sessionId}/submit`, {
      method: "POST",
    });
  },
  async createAttendance(payload: Omit<Attendance, "id" | "student">) {
    return createOrQueue<Attendance>(
      "/attendance",
      "attendance.create",
      payload
    );
  },
  async scanAttendance(payload: string) {
    return request<{ already_recorded: boolean; attendance: Attendance }>(
      "/attendance/scan",
      { method: "POST", body: JSON.stringify({ payload }) }
    );
  },
  async updateAttendance(id: number, payload: Partial<Attendance>) {
    return request<Attendance>(`/attendance/${id}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
  },
  async deleteAttendance(id: number) {
    return request<void>(`/attendance/${id}`, { method: "DELETE" });
  },
  async createExam(payload: Omit<ExamResult, "id" | "student">) {
    return createOrQueue<ExamResult>("/exams", "exam_result.create", payload);
  },
  async updateExam(id: number, payload: Partial<ExamResult>) {
    return request<ExamResult>(`/exams/${id}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
  },
  async deleteExam(id: number) {
    return request<void>(`/exams/${id}`, { method: "DELETE" });
  },
  async createPayment(payload: Omit<Payment, "id" | "student">) {
    return createOrQueue<Payment>("/payments", "payment.create", payload);
  },
  async submitWorksheet(
    assignmentId: number,
    payload: Pick<Assignment, "score" | "max_score" | "feedback">,
    baseUpdatedAt?: string
  ) {
    return createOrQueue<Assignment>(
      `/assignments/${assignmentId}/submit`,
      "worksheet_submission.submit",
      { assignment_id: assignmentId, ...payload },
      baseUpdatedAt
    );
  },
  async updatePayment(id: number, payload: Partial<Payment>) {
    return request<Payment>(`/payments/${id}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
  },
  async deletePayment(id: number) {
    return request<void>(`/payments/${id}`, { method: "DELETE" });
  },
  async academicGroups() {
    return requestCollection<AcademicGroup>("/staff/academic-groups");
  },
  async academicGroup(id: number) {
    return request<AcademicGroup>(`/staff/academic-groups/${id}`);
  },
  async createAcademicGroup(
    payload: Pick<AcademicGroup, "grade" | "name"> & { is_active?: boolean }
  ) {
    return request<AcademicGroup>("/staff/academic-groups", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
  async updateAcademicGroup(
    id: number,
    payload: Partial<Pick<AcademicGroup, "grade" | "name" | "is_active">>
  ) {
    return request<AcademicGroup>(`/staff/academic-groups/${id}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
  },
  async deleteAcademicGroup(id: number) {
    return request<void>(`/staff/academic-groups/${id}`, { method: "DELETE" });
  },
  async syncAcademicGroupStudents(id: number, studentIds: number[]) {
    return request<AcademicGroup>(`/staff/academic-groups/${id}/students`, {
      method: "PUT",
      body: JSON.stringify({ student_ids: studentIds }),
    });
  },
  async notificationAudienceCatalog() {
    return request<NotificationAudienceCatalog>(
      "/staff/notifications/audience-catalog"
    );
  },
  async notificationCampaigns() {
    return requestCollection<NotificationCampaign>("/staff/notifications");
  },
  async createNotificationCampaign(payload: {
    audience: NotificationAudience;
    title: string;
    body: string;
    grade?: string;
    academic_group_id?: number;
    recipient_ids?: number[];
    channels?: NotificationChannel[];
  }) {
    return request<NotificationCampaign>("/staff/notifications", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
  async notificationInbox() {
    return requestCollection<InAppNotification>("/notifications");
  },
  async markNotificationRead(id: string) {
    return request<InAppNotification>(`/notifications/${id}/read`, {
      method: "POST",
    });
  },
  async notificationChannels() {
    return requestCollection<NotificationChannelSetting>(
      "/staff/notification-channels"
    );
  },
  async updateNotificationChannel(
    id: number,
    payload: Pick<NotificationChannelSetting, "is_enabled" | "settings">
  ) {
    return request<NotificationChannelSetting>(
      `/staff/notification-channels/${id}`,
      {
        method: "PUT",
        body: JSON.stringify(payload),
      }
    );
  },
  async reportSummary() {
    return request<{
      students: number;
      attendance: Record<string, number>;
      exams: { score: number; max_score: number };
      payments: Payment[];
    }>("/reports/summary");
  },
  async plugins() {
    return requestCollection<PluginProduct>("/plugins");
  },
  async purchasePlugin(id: number) {
    return request(`/plugins/${id}/purchase`, { method: "POST" });
  },
  async pluginPaymentMethods() {
    return requestCollection<PluginPaymentMethod>("/plugin-payment-methods");
  },
  async beginPluginCheckout(
    id: number,
    payment_method: PluginPaymentMethod["code"]
  ) {
    return request<PluginPaymentTransaction>(`/plugins/${id}/checkout`, {
      method: "POST",
      body: JSON.stringify({ payment_method }),
    });
  },
  async pluginPayments() {
    return requestCollection<PluginPaymentTransaction>("/plugin-payments");
  },
  async submitPluginPaymentReference(
    paymentId: number,
    reference: string,
    customer_note?: string
  ) {
    return request<PluginPaymentTransaction>(
      `/plugin-payments/${paymentId}/reference`,
      { method: "POST", body: JSON.stringify({ reference, customer_note }) }
    );
  },
  async adminPluginPaymentMethods() {
    return requestCollection<PluginPaymentMethod>(
      "/admin/plugin-payment-methods"
    );
  },
  async updatePluginPaymentMethod(
    code: PluginPaymentMethod["code"],
    input: Pick<
      PluginPaymentMethod,
      "recipient" | "instructions" | "is_enabled"
    >
  ) {
    return request<PluginPaymentMethod>(
      `/admin/plugin-payment-methods/${code}`,
      {
        method: "PUT",
        body: JSON.stringify(input),
      }
    );
  },
  async pluginPaymentReviewQueue() {
    return requestCollection<PluginPaymentTransaction>(
      "/admin/plugin-payments/review-queue"
    );
  },
  async reviewPluginPayment(
    paymentId: number,
    action: "approve" | "reject",
    review_note?: string
  ) {
    return request<PluginPaymentTransaction>(
      `/admin/plugin-payments/${paymentId}/${action}`,
      { method: "POST", body: JSON.stringify({ review_note }) }
    );
  },
  async installPlugin(id: number) {
    return request<{
      module: { module_name: string; version: string };
      message: string;
    }>(`/plugins/${id}/install`, { method: "POST" });
  },
  async uninstallPlugin(id: number) {
    return request(`/plugins/${id}/install`, { method: "DELETE" });
  },
};
