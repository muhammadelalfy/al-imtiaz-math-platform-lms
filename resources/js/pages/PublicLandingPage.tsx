import { useEffect, useRef, useState } from "react";
import { useLocation } from "wouter";
import { gsap } from "gsap";
import {
  ArrowLeft,
  BarChart3,
  FlaskConical,
  CheckCircle2,
  CreditCard,
  LoaderCircle,
  ShieldCheck,
  Sparkles,
  Users,
  X,
} from "lucide-react";
import { toast } from "sonner";
import {
  ApiError,
  laravelApi,
  type SubscriptionPackage,
  type DevelopmentMockRegistration,
} from "@/lib/laravelApi";
import "./subscription-platform.scss";

const heroImage = "/manus-storage/al-imtiaz-landing-system-visual_a887ee83.png";

export default function PublicLandingPage() {
  const [, navigate] = useLocation();
  const root = useRef<HTMLElement>(null);
  const [packages, setPackages] = useState<SubscriptionPackage[]>([]);
  const [selected, setSelected] = useState<SubscriptionPackage | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [mocking, setMocking] = useState(false);
  const [mockResult, setMockResult] = useState<DevelopmentMockRegistration | null>(null);
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    organization_name: "",
    tenant_slug: "",
  });

  useEffect(() => {
    const context = gsap.context(() => {
      gsap.from(".landing-reveal", {
        opacity: 0,
        y: 26,
        duration: 0.65,
        stagger: 0.09,
        ease: "power3.out",
      });
      gsap.to(".landing-orbit", {
        rotate: 360,
        duration: 22,
        repeat: -1,
        ease: "none",
      });
    }, root);
    return () => context.revert();
  }, []);

  useEffect(() => {
    void laravelApi
      .publicSubscriptionPackages()
      .then(setPackages)
      .catch(() => toast("تعذر تحميل الباقات حالياً"))
      .finally(() => setLoading(false));
  }, []);

  const begin = (item: SubscriptionPackage) => {
    setSelected(item);
    setForm(current => ({
      ...current,
      tenant_slug: current.tenant_slug || item.code,
    }));
  };

  const register = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!selected) return;
    setSaving(true);
    try {
      const result = await laravelApi.registerTenantTeacher({
        ...form,
        package_id: selected.id,
      });
      toast(result.message);
      setSelected(null);
      navigate("/teacher/login");
    } catch (error) {
      toast(
        error instanceof ApiError ? error.message : "تعذر إنشاء حساب المركز"
      );
    } finally {
      setSaving(false);
    }
  };

  const registerMockTenant = async () => {
    setMocking(true);
    try {
      const result = await laravelApi.createDevelopmentMockTenant();
      setMockResult(result);
      toast("تم تجهيز المركز التجريبي بنجاح");
    } catch (error) {
      toast(error instanceof ApiError ? error.message : "تعذر تجهيز المركز التجريبي");
    } finally {
      setMocking(false);
    }
  };

  return (
    <main className="subscription-landing" ref={root} dir="rtl">
      <div className="landing-mesh landing-orbit" aria-hidden="true" />
      <nav className="landing-nav landing-reveal" aria-label="التنقل الرئيسي">
        <button
          className="landing-brand"
          onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
        >
          <span className="landing-brand-mark">
            <Sparkles size={19} />
          </span>
          <span>
            <b>الامتياز</b>
            <small>في الرياضيات</small>
          </span>
        </button>
        <div className="landing-nav-actions">
          <button
            className="landing-text-action"
            onClick={() => navigate("/teacher/login")}
          >
            دخول المعلم
          </button>
          <button
            className="landing-login"
            onClick={() => navigate("/admin/login")}
          >
            دخول الإدارة <ArrowLeft size={15} />
          </button>
        </div>
      </nav>

      <section className="landing-hero">
        <div className="landing-copy">
          <span className="landing-kicker landing-reveal">
            <Sparkles size={15} /> منصة تشغيل مركزك التعليمية
          </span>
          <h1 className="landing-reveal">
            كل ما يحتاجه مركز الرياضيات.
            <br />
            <em>في مساحة واحدة أنيقة.</em>
          </h1>
          <p className="landing-reveal">
            الحضور الذكي، الاختبارات، الشيتات، المجموعات، والمدفوعات — مع باقة
            واضحة تناسب مرحلة نمو مركزك.
          </p>
          <div className="landing-hero-actions landing-reveal">
            <button
              className="landing-primary"
              onClick={() =>
                document
                  .getElementById("packages")
                  ?.scrollIntoView({ behavior: "smooth" })
              }
            >
              استعرض الباقات <ArrowLeft size={17} />
            </button>
            <button
              className="landing-secondary"
              onClick={() => navigate("/teacher/login")}
            >
              لدي حساب بالفعل
            </button>
            <button
              className="landing-dev-action"
              disabled={mocking}
              onClick={() => void registerMockTenant()}
            >
              {mocking ? <LoaderCircle className="spin" size={17} /> : <FlaskConical size={17} />}
              جرّب تهيئة مركز تجريبي
            </button>
          </div>
          <p className="landing-dev-note landing-reveal">
            تجربة Manus للتطوير فقط: لا تنشئ دفعاً حقيقياً ولا نطاقاً منشوراً.
          </p>
          <div className="landing-trust landing-reveal">
            <span>
              <CheckCircle2 size={16} /> واجهة عربية RTL
            </span>
            <span>
              <CheckCircle2 size={16} /> مزامنة عند الحاجة
            </span>
            <span>
              <CheckCircle2 size={16} /> نمو مع مركزك
            </span>
          </div>
        </div>
        <div className="landing-visual landing-reveal">
          <div className="landing-visual-glow" />
          <div
            className="landing-dashboard-visual"
            aria-label="لوحة مركز تعليمية تعرض مؤشرات الحضور والاختبارات والاشتراكات"
          >
            <div className="landing-dashboard-top">
              <span>لوحة مركزك</span>
              <b>اليوم</b>
            </div>
            <div className="landing-dashboard-main">
              <div className="landing-dashboard-chart">
                <i />
                <i />
                <i />
                <i />
                <i />
                <i />
              </div>
              <div className="landing-dashboard-ring">٨٦٪</div>
            </div>
            <div className="landing-dashboard-stats">
              <span>
                <b>١٨٤</b>طالب
              </span>
              <span>
                <b>٩٣٪</b>حضور
              </span>
              <span>
                <b>١٢</b>اختبار
              </span>
            </div>
          </div>
          <img
            src={heroImage}
            alt="تصور لمنصة تعليمية وإحصاءات رياضية"
            onError={event => {
              event.currentTarget.style.display = "none";
            }}
          />
          <div className="landing-float-card landing-float-top">
            <BarChart3 size={19} />
            <span>
              <b>كل المؤشرات</b>
              <small>في لوحة واحدة</small>
            </span>
          </div>
          <div className="landing-float-card landing-float-bottom">
            <ShieldCheck size={19} />
            <span>
              <b>نظام منظم</b>
              <small>للمعلم والطلاب</small>
            </span>
          </div>
        </div>
      </section>

      <section
        className="landing-value-strip landing-reveal"
        aria-label="قيم النظام"
      >
        <div>
          <Users size={21} />
          <span>
            <b>مركزك منظّم</b>
            <small>طلاب ومجموعات وصفوف</small>
          </span>
        </div>
        <div>
          <BarChart3 size={21} />
          <span>
            <b>قرار أوضح</b>
            <small>مؤشرات الأداء والتحصيل</small>
          </span>
        </div>
        <div>
          <CreditCard size={21} />
          <span>
            <b>باقة واضحة</b>
            <small>حالة اشتراك وتجديد شفافة</small>
          </span>
        </div>
      </section>

      <section className="landing-packages" id="packages">
        <div className="landing-section-heading">
          <span className="landing-kicker">اختر ما يناسب مرحلتك</span>
          <h2>باقات بسيطة. قيمة حقيقية.</h2>
          <p>
            يمكنك البدء الآن، وتظهر تفاصيل اشتراكك وتجديده داخل لوحة المعلم.
          </p>
        </div>
        {loading ? (
          <div className="landing-packages-loading">
            <LoaderCircle className="spin" /> جارٍ تحميل الباقات...
          </div>
        ) : (
          <div className="landing-package-grid">
            {packages.map((item, index) => (
              <article
                className={`landing-package ${index === 1 ? "featured" : ""}`}
                key={item.id}
              >
                {index === 1 && (
                  <span className="landing-popular">الأكثر توازناً</span>
                )}
                <span className="landing-package-tag">{item.tagline}</span>
                <h3>{item.name}</h3>
                <p>{item.description}</p>
                <div className="landing-price">
                  <b>{(item.price_cents / 100).toLocaleString("ar-EG")}</b>
                  <span>جنيه / {item.duration_days} يوم</span>
                </div>
                <ul>
                  {item.features.map(feature => (
                    <li key={feature}>
                      <CheckCircle2 size={16} /> {feature}
                    </li>
                  ))}
                </ul>
                <div className="landing-capacity">
                  <span>{item.teacher_limit} معلم</span>
                  <span>{item.student_limit.toLocaleString("ar-EG")} طالب</span>
                </div>
                <button
                  className={
                    index === 1 ? "landing-primary" : "landing-package-button"
                  }
                  onClick={() => begin(item)}
                >
                  ابدأ مع {item.name} <ArrowLeft size={16} />
                </button>
              </article>
            ))}
          </div>
        )}
      </section>

      <footer className="landing-footer">
        <b>الامتياز في الرياضيات</b>
        <span>نظام مركزك التعليمي، بوضوح وهدوء.</span>
      </footer>

      {selected && (
        <div
          className="landing-dialog-backdrop"
          role="presentation"
          onMouseDown={() => !saving && setSelected(null)}
        >
          <form
            className="landing-dialog"
            onSubmit={register}
            onMouseDown={event => event.stopPropagation()}
          >
            <button
              className="landing-close"
              type="button"
              onClick={() => setSelected(null)}
            >
              <X size={18} />
            </button>
            <span className="landing-kicker">إنشاء مركز جديد</span>
            <h2>ابدأ مع باقة {selected.name}</h2>
            <p>
              يُنشأ حساب المعلم والمركز، ثم يراجع المشرف الأعلى حالة الاشتراك.
            </p>
            <div className="landing-form-grid">
              <label>
                اسم المعلم
                <input
                  required
                  value={form.name}
                  onChange={e => setForm({ ...form, name: e.target.value })}
                />
              </label>
              <label>
                اسم المركز
                <input
                  required
                  value={form.organization_name}
                  onChange={e =>
                    setForm({ ...form, organization_name: e.target.value })
                  }
                />
              </label>
              <label>
                البريد الإلكتروني
                <input
                  type="email"
                  required
                  value={form.email}
                  onChange={e => setForm({ ...form, email: e.target.value })}
                />
              </label>
              <label>
                معرّف المركز
                <input
                  required
                  pattern="[A-Za-z0-9-]{3,80}"
                  value={form.tenant_slug}
                  onChange={e =>
                    setForm({
                      ...form,
                      tenant_slug: e.target.value.toLowerCase(),
                    })
                  }
                />
              </label>
              <label>
                كلمة المرور
                <input
                  type="password"
                  required
                  value={form.password}
                  onChange={e => setForm({ ...form, password: e.target.value })}
                />
              </label>
              <label>
                تأكيد كلمة المرور
                <input
                  type="password"
                  required
                  value={form.password_confirmation}
                  onChange={e =>
                    setForm({ ...form, password_confirmation: e.target.value })
                  }
                />
              </label>
            </div>
            <button className="landing-primary" disabled={saving}>
              {saving ? <LoaderCircle className="spin" /> : "إنشاء الحساب"}{" "}
              <ArrowLeft size={17} />
            </button>
          </form>
        </div>
      )}

      {mockResult && (
        <div className="landing-dialog-backdrop" role="presentation" onMouseDown={() => setMockResult(null)}>
          <section className="landing-dialog landing-mock-result" onMouseDown={event => event.stopPropagation()} aria-labelledby="mock-onboarding-title">
            <button className="landing-close" type="button" onClick={() => setMockResult(null)}>
              <X size={18} />
            </button>
            <span className="landing-kicker"><FlaskConical size={15} /> تجربة Manus مكتملة</span>
            <h2 id="mock-onboarding-title">تم تجهيز مركزك التجريبي</h2>
            <p>{mockResult.message}</p>
            <dl className="landing-mock-status">
              <div><dt>المركز</dt><dd>{mockResult.subscription.tenant?.name}</dd></div>
              <div><dt>معرّف المساحة</dt><dd>{mockResult.subscription.tenant?.database_schema}</dd></div>
              <div><dt>حالة التجهيز</dt><dd>{mockResult.subscription.tenant?.schema_status === "ready" ? "جاهز للتجربة" : "قيد التجهيز"}</dd></div>
              <div><dt>النطاق</dt><dd>{mockResult.subscription.tenant?.login_domain ?? "ينتظر إعداد DNS للإنتاج"}</dd></div>
            </dl>
            <button className="landing-primary" type="button" onClick={() => setMockResult(null)}>فهمت، أغلِق التجربة <CheckCircle2 size={17} /></button>
          </section>
        </div>
      )}
    </main>
  );
}
