import { useEffect, useMemo, useState } from "react";
import {
  Activity,
  CheckCircle2,
  CircleAlert,
  CreditCard,
  Globe2,
  LoaderCircle,
  Plus,
  Save,
  Users,
} from "lucide-react";
import { toast } from "sonner";
import {
  ApiError,
  laravelApi,
  type PlatformOverview,
  type SubscriptionPackage,
  type TenantSubscription,
} from "@/lib/laravelApi";
import "@/pages/subscription-platform.scss";

const emptyPackage = {
  code: "",
  name: "",
  tagline: "",
  description: "",
  price_cents: 0,
  currency: "EGP",
  duration_days: 30,
  teacher_limit: 1,
  student_limit: 100,
  features: [],
  is_active: true,
  sort_order: 99,
};

export default function SuperAdminPlatformPanel() {
  const [overview, setOverview] = useState<PlatformOverview | null>(null);
  const [packages, setPackages] = useState<SubscriptionPackage[]>([]);
  const [subscriptions, setSubscriptions] = useState<TenantSubscription[]>([]);
  const [draft, setDraft] = useState(emptyPackage);
  const [busy, setBusy] = useState(false);
  const [query, setQuery] = useState("");
  const [filter, setFilter] = useState<"all" | "needs_action" | "active">("all");
  const load = async () => {
    setBusy(true);
    try {
      const [nextOverview, nextPackages, nextSubscriptions] = await Promise.all(
        [
          laravelApi.superAdminOverview(),
          laravelApi.superAdminPackages(),
          laravelApi.superAdminSubscriptions(),
        ]
      );
      setOverview(nextOverview);
      setPackages(nextPackages);
      setSubscriptions(nextSubscriptions);
    } catch (error) {
      toast(
        error instanceof ApiError ? error.message : "تعذر تحميل لوحة المنصة"
      );
    } finally {
      setBusy(false);
    }
  };
  useEffect(() => {
    void load();
  }, []);
  const savePackage = async (event: React.FormEvent) => {
    event.preventDefault();
    setBusy(true);
    try {
      await laravelApi.createSubscriptionPackage({
        ...draft,
        features: draft.features.filter(Boolean),
      });
      toast("تمت إضافة الباقة");
      setDraft(emptyPackage);
      await load();
    } catch (error) {
      toast(error instanceof ApiError ? error.message : "تعذر حفظ الباقة");
    } finally {
      setBusy(false);
    }
  };
  const activate = async (subscription: TenantSubscription) => {
    setBusy(true);
    try {
      await laravelApi.updateTenantSubscription(subscription.id, {
        status: "active",
        payment_status: "paid",
        payment_reference: subscription.payment_reference || undefined,
      });
      toast("تم اعتماد الاشتراك");
      await load();
    } catch (error) {
      toast(error instanceof ApiError ? error.message : "تعذر اعتماد الاشتراك");
    } finally {
      setBusy(false);
    }
  };
  const setDomain = async (subscription: TenantSubscription) => {
    const tenant = subscription.tenant;
    if (!tenant) return;
    const domain = window.prompt(
      "اكتب نطاق الدخول الكامل للمركز، مثل academy.example.com",
      tenant.login_domain || ""
    );
    if (domain === null) return;
    setBusy(true);
    try {
      await laravelApi.updateTenantDomain(tenant.id, domain.trim() || null);
      toast("تم حفظ نطاق الدخول");
      await load();
    } catch (error) {
      toast(error instanceof ApiError ? error.message : "تعذر حفظ النطاق");
    } finally {
      setBusy(false);
    }
  };
  const visibleSubscriptions = useMemo(() => {
    const normalizedQuery = query.trim().toLocaleLowerCase("ar-EG");
    return subscriptions.filter(item => {
      const needsAction =
        item.status !== "active" || item.payment_status !== "paid";
      const isActive = item.status === "active" && item.payment_status === "paid";
      const matchesFilter =
        filter === "all" ||
        (filter === "needs_action" && needsAction) ||
        (filter === "active" && isActive);
      const haystack = [
        item.tenant?.name,
        item.tenant?.slug,
        item.tenant?.login_domain,
        item.package?.name,
      ]
        .filter(Boolean)
        .join(" ")
        .toLocaleLowerCase("ar-EG");
      return matchesFilter && (!normalizedQuery || haystack.includes(normalizedQuery));
    });
  }, [filter, query, subscriptions]);
  if (!overview)
    return (
      <div className="live-loading">
        <LoaderCircle className="spin" /> جارٍ تحميل مؤشرات المنصة...
      </div>
    );
  return (
    <section className="platform-workspace live-page">
      <div className="subscription-heading">
        <span className="eyebrow">المشرف الأعلى</span>
        <h2>تشغيل المنصة والاشتراكات</h2>
        <p>تابع صحة النظام، واعتمد اشتراكات المؤسسات، وأدر الباقات والنطاقات.</p>
      </div>
      <div className="platform-health">
        <span>
          <Activity /> قاعدة البيانات: <b>{overview.health.database}</b> ·{" "}
          {overview.health.database_latency_ms}ms
        </span>
        <span>
          <CheckCircle2 /> التخزين: <b>{overview.health.storage}</b>
        </span>
        <span>
          <CircleAlert /> PHP {overview.health.php_version}
        </span>
        <span>
          <Activity /> الطابور ({overview.queue.driver}):{" "}
          <b>{overview.queue.pending_jobs}</b> معلّق
        </span>
        <span className={overview.queue.failed_jobs ? "platform-health-alert" : ""}>
          <CircleAlert /> مهام فاشلة: <b>{overview.queue.failed_jobs}</b>
        </span>
        <span>
          <CheckCircle2 /> ذاكرة العملية: <b>{overview.runtime.memory_peak_mb} MB</b>
        </span>
      </div>
      <div className="subscription-status-grid platform-counts">
        <article>
          <Users />
          <small>المؤسسات</small>
          <b>{overview.counts.tenants}</b>
        </article>
        <article>
          <Users />
          <small>المعلمون</small>
          <b>{overview.counts.teachers}</b>
        </article>
        <article>
          <CheckCircle2 />
          <small>اشتراكات نشطة</small>
          <b>{overview.counts.active_subscriptions}</b>
        </article>
        <article>
          <CreditCard />
          <small>غير مدفوعة</small>
          <b>{overview.counts.unpaid_subscriptions}</b>
        </article>
        <article>
          <CircleAlert />
          <small>تنتهي خلال أسبوع</small>
          <b>{overview.counts.expiring_within_week}</b>
        </article>
      </div>
      <div className="platform-two-column">
        <section className="subscription-detail-card">
          <h3>الباقات الحالية</h3>
          {packages.map(item => (
            <div className="platform-row" key={item.id}>
              <div>
                <b>{item.name}</b>
                <small>
                  {(item.price_cents / 100).toLocaleString("ar-EG")} جنيه ·{" "}
                  {item.duration_days} يوم
                </small>
              </div>
              <span>{item.subscriptions_count || 0} اشتراك</span>
            </div>
          ))}
        </section>
        <form
          className="subscription-detail-card package-create-form"
          onSubmit={savePackage}
        >
          <h3>
            <Plus size={17} /> إضافة باقة
          </h3>
          <input
            required
            placeholder="رمز الباقة، مثال: premium"
            value={draft.code}
            onChange={e => setDraft({ ...draft, code: e.target.value })}
          />
          <input
            required
            placeholder="اسم الباقة"
            value={draft.name}
            onChange={e => setDraft({ ...draft, name: e.target.value })}
          />
          <input
            placeholder="وصف قصير"
            value={draft.tagline || ""}
            onChange={e => setDraft({ ...draft, tagline: e.target.value })}
          />
          <div className="platform-inline-inputs">
            <input
              type="number"
              min="0"
              placeholder="السعر بالقرش"
              value={draft.price_cents}
              onChange={e =>
                setDraft({ ...draft, price_cents: Number(e.target.value) })
              }
            />
            <input
              type="number"
              min="1"
              placeholder="المدة"
              value={draft.duration_days}
              onChange={e =>
                setDraft({ ...draft, duration_days: Number(e.target.value) })
              }
            />
          </div>
          <button className="outline" disabled={busy}>
            <Save size={15} /> حفظ الباقة
          </button>
        </form>
      </div>
      <section className="subscription-detail-card">
        <div className="platform-list-toolbar">
          <div>
            <h3>إدارة المؤسسات والاشتراكات</h3>
            <small>
              {visibleSubscriptions.length} من {subscriptions.length} اشتراك ظاهر
            </small>
          </div>
          <div className="platform-list-controls">
            <input
              aria-label="البحث في المؤسسات والاشتراكات"
              placeholder="بحث باسم المؤسسة أو النطاق"
              value={query}
              onChange={event => setQuery(event.target.value)}
            />
            <select
              aria-label="تصفية الاشتراكات"
              value={filter}
              onChange={event =>
                setFilter(event.target.value as "all" | "needs_action" | "active")
              }
            >
              <option value="all">كل الحالات</option>
              <option value="needs_action">تحتاج إجراء</option>
              <option value="active">نشطة ومدفوعة</option>
            </select>
          </div>
        </div>
        <div className="platform-subscription-list">
          {visibleSubscriptions.map(item => (
            <article className="platform-subscription-row" key={item.id}>
              <div>
                <b>{item.tenant?.name}</b>
                <small>
                  {item.package?.name} ·{" "}
                  {item.payment_status === "paid" ? "مدفوع" : "غير مدفوع"}
                </small>
                <small className={item.status === "active" ? "platform-status-good" : "platform-status-action"}>
                  {item.status === "active" ? "مفعل" : "بانتظار الاعتماد"} · {item.tenant?.domain_status === "active" ? "النطاق مفعل" : "النطاق قيد الإعداد"}
                </small>
                <small>
                  ينتهي:{" "}
                  {item.ends_at
                    ? new Date(item.ends_at).toLocaleDateString("ar-EG")
                    : "قيد الاعتماد"}
                </small>
              </div>
              <div>
                {item.status !== "active" && (
                  <button
                    className="outline"
                    disabled={busy}
                    onClick={() => void activate(item)}
                  >
                    اعتماد وتفعيل
                  </button>
                )}
                <button
                  className="outline"
                  disabled={busy}
                  onClick={() => void setDomain(item)}
                >
                  <Globe2 size={15} /> النطاق
                </button>
              </div>
            </article>
          ))}
          {!visibleSubscriptions.length && (
            <p className="platform-empty-state">لا توجد مؤسسات مطابقة للبحث أو التصفية الحالية.</p>
          )}
        </div>
      </section>
    </section>
  );
}
