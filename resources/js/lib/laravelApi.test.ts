import { beforeEach, describe, expect, it, vi } from "vitest";
import { indexedDB } from "fake-indexeddb";
import { isQueuedOfflineOperation, laravelApi } from "./laravelApi";

const storage = new Map<string, string>();

beforeEach(() => {
  storage.clear();
  vi.stubGlobal("window", {
    indexedDB,
    localStorage: {
      getItem: (key: string) => storage.get(key) ?? null,
      setItem: (key: string, value: string) => storage.set(key, value),
      removeItem: (key: string) => storage.delete(key),
    },
  });
  vi.stubGlobal("navigator", { onLine: true });
  vi.restoreAllMocks();
});

describe("laravelApi", () => {
  it("stores the Sanctum token after login and sends it on protected requests", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            user: {
              id: 1,
              name: "Teacher",
              email: "teacher@example.com",
              role: "teacher",
            },
            token: "sanctum-token",
          }),
          { status: 200 }
        )
      )
      .mockResolvedValueOnce(
        new Response(JSON.stringify({ data: [] }), { status: 200 })
      );
    vi.stubGlobal("fetch", fetchMock);

    await laravelApi.login({
      email: "teacher@example.com",
      password: "secret",
    });
    await laravelApi.attendance();

    expect(storage.get("al-imtiaz-laravel-token")).toBe("sanctum-token");
    expect(fetchMock.mock.calls[1]?.[1]).toMatchObject({
      headers: expect.objectContaining({
        Authorization: "Bearer sanctum-token",
        Accept: "application/json",
      }),
    });
  });

  it("unwraps collection responses through the shared collection helper", async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      new Response(JSON.stringify({ data: [{ id: 1, name: "طالب" }] }), {
        status: 200,
      })
    );
    vi.stubGlobal("fetch", fetchMock);

    await expect(laravelApi.students()).resolves.toEqual([
      { id: 1, name: "طالب" },
    ]);
    expect(fetchMock).toHaveBeenCalledWith("/api/students", expect.anything());
  });

  it("maps teacher dashboard layout preferences to read, save, and reset endpoints", async () => {
    const order = [
      "payments",
      "learning_flow",
      "attendance",
      "exam_performance",
    ];
    const fetchMock = vi.fn().mockImplementation(
      () => new Response(JSON.stringify({ card_order: order }), { status: 200 })
    );
    vi.stubGlobal("fetch", fetchMock);

    await expect(laravelApi.teacherDashboardLayout()).resolves.toEqual({
      card_order: order,
    });
    await expect(laravelApi.updateTeacherDashboardLayout(order)).resolves.toEqual({
      card_order: order,
    });
    await laravelApi.resetTeacherDashboardLayout();

    expect(
      fetchMock.mock.calls.map(([url, init]) => [url, init?.method])
    ).toEqual([
      ["/api/teacher/dashboard-layout", undefined],
      ["/api/teacher/dashboard-layout", "PUT"],
      ["/api/teacher/dashboard-layout", "DELETE"],
    ]);
    expect(fetchMock.mock.calls[1]?.[1]?.body).toBe(
      JSON.stringify({ card_order: order })
    );
  });

  it("maps attendance and exam CRUD operations to the Laravel resources", async () => {
    const fetchMock = vi
      .fn()
      .mockImplementation(
        () => new Response(JSON.stringify({ id: 4 }), { status: 200 })
      );
    vi.stubGlobal("fetch", fetchMock);

    await laravelApi.createAttendance({
      student_id: 2,
      date_at: "2026-08-15",
      status: "present",
      note: null,
    });
    await laravelApi.updateExam(7, { score: 18 });
    await laravelApi.deleteAttendance(4);

    expect(
      fetchMock.mock.calls.map(([url, init]) => [url, init?.method])
    ).toEqual([
      ["/api/attendance", "POST"],
      ["/api/exams/7", "PUT"],
      ["/api/attendance/4", "DELETE"],
    ]);
  });

  it("maps QR generation and scan requests to the protected Laravel endpoints", async () => {
    const fetchMock = vi.fn().mockImplementation(
      () =>
        new Response(
          JSON.stringify({
            student_id: 2,
            payload: "q".repeat(64),
            generated_at: "2026-08-15T10:00:00Z",
            already_recorded: false,
            attendance: { id: 8 },
          }),
          { status: 200 }
        )
    );
    vi.stubGlobal("fetch", fetchMock);

    await laravelApi.studentQr(2);
    await laravelApi.scanAttendance("q".repeat(64));

    expect(
      fetchMock.mock.calls.map(([url, init]) => [url, init?.method])
    ).toEqual([
      ["/api/students/2/qr", undefined],
      ["/api/attendance/scan", "POST"],
    ]);
  });

  it("surfaces Laravel authorization failures as ApiError status values", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        new Response(JSON.stringify({ message: "Forbidden" }), {
          status: 403,
        })
      )
    );
    await expect(laravelApi.exams()).rejects.toMatchObject({
      status: 403,
      message: "Forbidden",
    });
  });

  it("maps role-specific logins to dedicated Laravel portals", async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      new Response(
        JSON.stringify({
          user: {
            id: 1,
            name: "مدير",
            email: "admin@test.local",
            role: "admin",
          },
          token: "admin-token",
          login_type: "admin",
        }),
        { status: 200 }
      )
    );
    vi.stubGlobal("fetch", fetchMock);
    await laravelApi.loginAsRole("admin", {
      email: "admin@test.local",
      password: "Secret123!",
    });
    expect(fetchMock.mock.calls[0]?.[0]).toBe("/api/auth/admin/login");
  });

  it("maps teacher login and staff authorization CRUD to the guarded Laravel endpoints", async () => {
    const fetchMock = vi.fn().mockImplementation(
      () =>
        new Response(
          JSON.stringify({
            user: {
              id: 4,
              name: "معلم",
              email: "teacher@test.local",
              role: "teacher",
            },
            token: "teacher-token",
            permissions: [],
            roles: [],
            staff: [],
            id: 4,
          }),
          { status: 200 }
        )
    );
    vi.stubGlobal("fetch", fetchMock);

    await laravelApi.loginAsRole("teacher", {
      email: "teacher@test.local",
      password: "Secret123!",
    });
    await laravelApi.authorizationCatalog();
    await laravelApi.createAuthorizationPermission({
      name: "worksheets.review",
      label: "مراجعة الشيتات",
    });
    await laravelApi.createAuthorizationRole({
      name: "worksheet-reviewer",
      label: "مراجع الشيتات",
      permission_ids: [4],
    });
    await laravelApi.syncStaffAuthorizationRoles(9, [5]);

    expect(
      fetchMock.mock.calls.map(([url, init]) => [url, init?.method])
    ).toEqual([
      ["/api/auth/teacher/login", "POST"],
      ["/api/staff/authorization/catalog", undefined],
      ["/api/staff/authorization/permissions", "POST"],
      ["/api/staff/authorization/roles", "POST"],
      ["/api/staff/authorization/staff/9/roles", "PUT"],
    ]);
  });

  it("queues supported recorded operations when the browser is offline", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        new Response(
          JSON.stringify({
            user: {
              id: 17,
              name: "معلم",
              email: "teacher@test.local",
              role: "teacher",
            },
            token: "offline-token",
          }),
          { status: 200 }
        )
      )
    );
    await laravelApi.login({
      email: "teacher@test.local",
      password: "Secret123!",
    });
    vi.stubGlobal("navigator", { onLine: false });
    const result = await laravelApi.createAttendance({
      student_id: 10,
      date_at: "2026-08-20T08:00:00Z",
      status: "present",
    });

    expect(isQueuedOfflineOperation(result)).toBe(true);
  });

  it("does not queue unsupported destructive mutations when the browser is offline", async () => {
    vi.stubGlobal("navigator", { onLine: false });
    vi.stubGlobal("fetch", vi.fn());
    await expect(laravelApi.deleteAttendance(10)).rejects.toMatchObject({
      status: 0,
    });
  });

  it("maps group membership, group-targeted campaigns, and dynamic channel settings to guarded endpoints", async () => {
    const fetchMock = vi
      .fn()
      .mockImplementation(
        () => new Response(JSON.stringify({ id: 7, data: [] }), { status: 200 })
      );
    vi.stubGlobal("fetch", fetchMock);

    await laravelApi.academicGroups();
    await laravelApi.academicGroup(7);
    await laravelApi.syncAcademicGroupStudents(7, [2, 3]);
    await laravelApi.createNotificationCampaign({
      audience: "academic_group",
      academic_group_id: 7,
      title: "مراجعة",
      body: "موعد المراجعة غداً.",
      channels: ["in_app"],
    });
    await laravelApi.notificationChannels();
    await laravelApi.updateNotificationChannel(3, {
      is_enabled: true,
      settings: { sender_label: "الامتياز" },
    });

    expect(
      fetchMock.mock.calls.map(([url, init]) => [url, init?.method])
    ).toEqual([
      ["/api/staff/academic-groups", undefined],
      ["/api/staff/academic-groups/7", undefined],
      ["/api/staff/academic-groups/7/students", "PUT"],
      ["/api/staff/notifications", "POST"],
      ["/api/staff/notification-channels", undefined],
      ["/api/staff/notification-channels/3", "PUT"],
    ]);
  });
});

it("maps exam template CRUD and monitored session actions to Laravel endpoints", async () => {
  const fetchMock = vi
    .fn()
    .mockImplementation(
      () =>
        new Response(
          JSON.stringify({ data: [], id: 3, camera_required: true }),
          { status: 200 }
        )
    );
  vi.stubGlobal("fetch", fetchMock);
  await laravelApi.examTemplates();
  await laravelApi.createExamTemplate({
    department_id: null,
    title: "جبر",
    grade: "أولى",
    duration_minutes: 30,
    instructions: "ابدأ",
    watermark_text: "الامتياز",
    watermark_opacity: 12,
    status: "draft",
    questions: [],
  });
  await laravelApi.updateExamTemplate(3, { status: "published" });
  await laravelApi.deleteExamTemplate(3);
  await laravelApi.startExamSession(3);
  await laravelApi.recordExamEvent(4, "focus_lost");
  await laravelApi.saveExamAnswer(4, 8, "٤");
  await laravelApi.submitExam(4);
  expect(
    fetchMock.mock.calls.map(([url, init]) => [url, init?.method])
  ).toEqual([
    ["/api/exam-templates", undefined],
    ["/api/exam-templates", "POST"],
    ["/api/exam-templates/3", "PUT"],
    ["/api/exam-templates/3", "DELETE"],
    ["/api/exam-templates/3/start", "POST"],
    ["/api/exam-sessions/4/events", "POST"],
    ["/api/exam-sessions/4/answers", "POST"],
    ["/api/exam-sessions/4/submit", "POST"],
  ]);
});

describe("exam PDF download", () => {
  it("downloads the protected PDF blob with the Sanctum token", async () => {
    storage.set("al-imtiaz-laravel-token", "student-token");
    const fetchMock = vi
      .fn()
      .mockResolvedValue(new Response(new Blob(["%PDF-1.7"]), { status: 200 }));
    const click = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
    vi.stubGlobal("URL", {
      createObjectURL: vi.fn(() => "blob:exam"),
      revokeObjectURL: vi.fn(),
    });
    vi.stubGlobal("document", { createElement: vi.fn(() => ({ click })) });

    await laravelApi.downloadExamPdf(12);

    expect(fetchMock).toHaveBeenCalledWith(
      "/api/exam-templates/12/pdf",
      expect.objectContaining({
        headers: {
          Accept: "application/pdf",
          Authorization: "Bearer student-token",
        },
      })
    );
    expect(click).toHaveBeenCalled();
  });
});

describe("question bank API", () => {
  it("maps searchable CRUD operations to the Laravel resource", async () => {
    const fetchMock = vi
      .fn()
      .mockImplementation(
        () =>
          new Response(JSON.stringify({ data: [], id: 11 }), { status: 200 })
      );
    vi.stubGlobal("fetch", fetchMock);
    await laravelApi.questionBank({ search: "هندسة", type: "geometry" });
    await laravelApi.createQuestionBankQuestion({
      type: "math",
      title: "جبر",
      grade: "أولى",
      prompt_html: "<p>سؤال</p>",
      options: { notation: "س^2" },
      correct_answer: "٩",
      points: 2,
      sort_order: 0,
      tags: "جبر",
      is_active: true,
      department_id: null,
    });
    await laravelApi.updateQuestionBankQuestion(11, { title: "معدل" });
    await laravelApi.deleteQuestionBankQuestion(11);
    expect(
      fetchMock.mock.calls.map(([url, init]) => [url, init?.method])
    ).toEqual([
      [
        "/api/question-bank?search=%D9%87%D9%86%D8%AF%D8%B3%D8%A9&type=geometry",
        undefined,
      ],
      ["/api/question-bank", "POST"],
      ["/api/question-bank/11", "PUT"],
      ["/api/question-bank/11", "DELETE"],
    ]);
  });
});

describe("plugin payment API", () => {
  it("maps Fawry checkout, reference submission, and administrator review requests", async () => {
    const fetchMock = vi
      .fn()
      .mockImplementation(
        () =>
          new Response(
            JSON.stringify({ data: [], id: 17, status: "pending" }),
            { status: 200 }
          )
      );
    vi.stubGlobal("fetch", fetchMock);

    await laravelApi.pluginPaymentMethods();
    await laravelApi.beginPluginCheckout(6, "fawry");
    await laravelApi.submitPluginPaymentReference(
      17,
      "FAWRY-REF-17",
      "تم الدفع"
    );
    await laravelApi.adminPluginPaymentMethods();
    await laravelApi.updatePluginPaymentMethod("fawry", {
      recipient: "77881",
      instructions: "ادفع لدى فوري",
      is_enabled: true,
    });
    await laravelApi.pluginPaymentReviewQueue();
    await laravelApi.reviewPluginPayment(17, "approve", "تمت المراجعة");

    expect(
      fetchMock.mock.calls.map(([url, init]) => [url, init?.method])
    ).toEqual([
      ["/api/plugin-payment-methods", undefined],
      ["/api/plugins/6/checkout", "POST"],
      ["/api/plugin-payments/17/reference", "POST"],
      ["/api/admin/plugin-payment-methods", undefined],
      ["/api/admin/plugin-payment-methods/fawry", "PUT"],
      ["/api/admin/plugin-payments/review-queue", undefined],
      ["/api/admin/plugin-payments/17/approve", "POST"],
    ]);
    expect(fetchMock.mock.calls[1]?.[1]?.body).toBe(
      JSON.stringify({ payment_method: "fawry" })
    );
  });
});

describe("subscription platform API", () => {
  it("maps public packages, teacher registration, teacher status, and super-admin operations", async () => {
    const fetchMock = vi.fn().mockImplementation(
      () =>
        new Response(JSON.stringify({ data: [], id: 21, health: {} }), {
          status: 200,
        })
    );
    vi.stubGlobal("fetch", fetchMock);

    await laravelApi.publicSubscriptionPackages();
    await laravelApi.registerTenantTeacher({
      name: "منى",
      email: "mona@example.test",
      password: "TeacherSecure!2026",
      password_confirmation: "TeacherSecure!2026",
      organization_name: "مركز منى",
      tenant_slug: "mona-math",
      package_id: 3,
    });
    await laravelApi.createDevelopmentMockTenant();
    await laravelApi.teacherSubscription();
    await laravelApi.superAdminOverview();
    await laravelApi.superAdminPackages();
    await laravelApi.superAdminSubscriptions();
    await laravelApi.updateTenantSubscription(21, {
      status: "active",
      payment_status: "paid",
    });
    await laravelApi.updateTenantDomain(7, "academy.example.com");

    expect(
      fetchMock.mock.calls.map(([url, init]) => [url, init?.method])
    ).toEqual([
      ["/api/public/subscription-packages", undefined],
      ["/api/public/teacher-register", "POST"],
      ["/api/public/mock-tenant-registration", "POST"],
      ["/api/teacher/subscription", undefined],
      ["/api/super-admin/overview", undefined],
      ["/api/super-admin/packages", undefined],
      ["/api/super-admin/subscriptions", undefined],
      ["/api/super-admin/subscriptions/21", "PUT"],
      ["/api/super-admin/tenants/7/domain", "PUT"],
    ]);
  });
});
