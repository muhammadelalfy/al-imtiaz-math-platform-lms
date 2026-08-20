import { useEffect, useState } from "react";
import {
  CalendarClock,
  CheckCircle2,
  CreditCard,
  LoaderCircle,
  Sparkles,
} from "lucide-react";
import { toast } from "sonner";
import {
  ApiError,
  laravelApi,
  type TeacherSubscriptionStatus,
} from "@/lib/laravelApi";
import "@/pages/subscription-platform.css";

export default function TeacherSubscriptionPanel() {
  const [state, setState] = useState<TeacherSubscriptionStatus | null>(null);
  useEffect(() => {
    void laravelApi
      .teacherSubscription()
      .then(result => {
        setState(result);
        if (
          result.show_expiry_reminder &&
          result.subscription &&
          result.subscription.days_remaining !== null
        ) {
          toast(
            `تذكير: ينتهي اشتراكك خلال ${result.subscription.days_remaining} أيام. راجع الإدارة لتجديد الباقة.`
          );
        }
      })
      .catch(error =>
        toast(
          error instanceof ApiError
            ? error.message
            : "تعذر تحميل تفاصيل الاشتراك"
        )
      );
  }, []);
  if (!state)
    return (
      <div className="live-loading">
        <LoaderCircle className="spin" /> جارٍ تحميل الاشتراك...
      </div>
    );
  const subscription = state.subscription;
  if (!subscription)
    return (
      <section className="subscription-empty">
        <Sparkles />
        <h2>لا يوجد اشتراك مرتبط بالمركز</h2>
        <p>تواصل مع المشرف الأعلى لإضافة باقة مركزك.</p>
      </section>
    );
  return (
    <section className="subscription-workspace live-page">
      <div className="subscription-heading">
        <span className="eyebrow">اشتراك مركزك</span>
        <h2>{subscription.package?.name}</h2>
        <p>
          {subscription.package?.tagline ||
            "تابع باقتك وفترة التجديد من مكان واحد."}
        </p>
      </div>
      {state.show_expiry_reminder && (
        <div className="subscription-reminder">
          <CalendarClock />
          <div>
            <b>تذكير بالتجديد</b>
            <span>
              ينتهي اشتراك المركز خلال {subscription.days_remaining} أيام.
            </span>
          </div>
        </div>
      )}
      <div className="subscription-status-grid">
        <article>
          <CalendarClock />
          <small>تاريخ الانتهاء</small>
          <b>
            {subscription.ends_at
              ? new Date(subscription.ends_at).toLocaleDateString("ar-EG")
              : "قيد التحديد"}
          </b>
        </article>
        <article>
          <CreditCard />
          <small>حالة الدفع</small>
          <b>
            {subscription.payment_status === "paid"
              ? "مدفوع"
              : "بانتظار السداد"}
          </b>
        </article>
        <article>
          <CheckCircle2 />
          <small>الأيام المتبقية</small>
          <b>{subscription.days_remaining ?? "—"} يوم</b>
        </article>
      </div>
      <div className="subscription-detail-card">
        <h3>تفاصيل الباقة</h3>
        <div className="subscription-features">
          {subscription.package?.features.map(feature => (
            <span key={feature}>
              <CheckCircle2 size={15} /> {feature}
            </span>
          ))}
        </div>
        <small>
          السعة: {subscription.package?.teacher_limit} معلم ·{" "}
          {subscription.package?.student_limit?.toLocaleString("ar-EG")} طالب
        </small>
      </div>
    </section>
  );
}
