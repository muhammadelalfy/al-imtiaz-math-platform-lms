import { useEffect, useState } from "react";
import {
  Box,
  CheckCircle2,
  CreditCard,
  Download,
  PackageCheck,
  RefreshCw,
  ShieldCheck,
  ShoppingBag,
  Trash2,
  WalletCards,
} from "lucide-react";
import { toast } from "sonner";
import {
  ApiError,
  laravelApi,
  type PluginPaymentMethod,
  type PluginPaymentTransaction,
  type PluginProduct,
  type Role,
} from "@/lib/laravelApi";

type Props = { onRefresh?: () => Promise<void>; role?: Role };

const paymentStatusLabel: Record<string, string> = {
  pending: "بانتظار التحويل",
  submitted: "قيد المراجعة",
  approved: "تم الاعتماد",
  rejected: "مرفوض",
};

export default function PluginStorePanel({ onRefresh, role }: Props) {
  const [plugins, setPlugins] = useState<PluginProduct[]>([]);
  const [methods, setMethods] = useState<PluginPaymentMethod[]>([]);
  const [adminMethods, setAdminMethods] = useState<PluginPaymentMethod[]>([]);
  const [reviewQueue, setReviewQueue] = useState<PluginPaymentTransaction[]>(
    []
  );
  const [busy, setBusy] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [checkoutPlugin, setCheckoutPlugin] = useState<PluginProduct | null>(
    null
  );
  const [transaction, setTransaction] =
    useState<PluginPaymentTransaction | null>(null);
  const [reference, setReference] = useState("");
  const [customerNote, setCustomerNote] = useState("");
  const isAdmin = role === "admin";

  const load = async () => {
    setLoading(true);
    try {
      const [catalog, enabledMethods] = await Promise.all([
        laravelApi.plugins(),
        laravelApi.pluginPaymentMethods(),
      ]);
      setPlugins(catalog);
      setMethods(enabledMethods);
      if (isAdmin) {
        const [configuredMethods, pendingPayments] = await Promise.all([
          laravelApi.adminPluginPaymentMethods(),
          laravelApi.pluginPaymentReviewQueue(),
        ]);
        setAdminMethods(configuredMethods);
        setReviewQueue(pendingPayments);
      }
    } catch (error) {
      toast(
        error instanceof ApiError ? error.message : "تعذر تحميل متجر الإضافات"
      );
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, [isAdmin]);

  const run = async (
    id: number,
    action: () => Promise<unknown>,
    success: string
  ) => {
    setBusy(id);
    try {
      await action();
      toast(success);
      await load();
      await onRefresh?.();
    } catch (error) {
      toast(error instanceof ApiError ? error.message : "تعذر تنفيذ العملية");
    } finally {
      setBusy(null);
    }
  };

  const beginCheckout = async (method: PluginPaymentMethod["code"]) => {
    if (!checkoutPlugin) return;
    setBusy(checkoutPlugin.id);
    try {
      const payment = await laravelApi.beginPluginCheckout(
        checkoutPlugin.id,
        method
      );
      setTransaction(payment);
      toast("تم إنشاء طلب الدفع. حوّل القيمة ثم أرسل الرقم المرجعي.");
    } catch (error) {
      toast(error instanceof ApiError ? error.message : "تعذر بدء عملية الدفع");
    } finally {
      setBusy(null);
    }
  };

  const submitReference = async () => {
    if (!transaction) return;
    setBusy(transaction.id);
    try {
      await laravelApi.submitPluginPaymentReference(
        transaction.id,
        reference,
        customerNote || undefined
      );
      toast(
        "تم إرسال رقم العملية للمراجعة. سيتم تفعيل الإضافة بعد اعتماد الدفع."
      );
      setCheckoutPlugin(null);
      setTransaction(null);
      setReference("");
      setCustomerNote("");
      await load();
    } catch (error) {
      toast(
        error instanceof ApiError ? error.message : "تعذر إرسال رقم العملية"
      );
    } finally {
      setBusy(null);
    }
  };

  const updateMethod = async (method: PluginPaymentMethod) => {
    setBusy(method.id);
    try {
      await laravelApi.updatePluginPaymentMethod(method.code, {
        recipient: method.recipient || null,
        instructions: method.instructions || null,
        is_enabled: method.is_enabled,
      });
      toast("تم حفظ إعدادات وسيلة الدفع");
      await load();
    } catch (error) {
      toast(
        error instanceof ApiError ? error.message : "تعذر حفظ إعدادات الدفع"
      );
    } finally {
      setBusy(null);
    }
  };

  const review = async (
    payment: PluginPaymentTransaction,
    action: "approve" | "reject"
  ) => {
    await run(
      payment.id,
      () => laravelApi.reviewPluginPayment(payment.id, action),
      action === "approve"
        ? "تم اعتماد الدفع ومنح امتلاك الإضافة"
        : "تم رفض عملية الدفع"
    );
  };

  return (
    <section className="live-page plugin-store" dir="rtl">
      <div className="page-heading">
        <div>
          <span className="eyebrow">إضافات زويل</span>
          <h2>متجر الوحدات</h2>
          <p>
            اختر وسيلة الدفع، أرسل الرقم المرجعي، ثم تُمنح الإضافة بعد اعتماد
            إدارة المركز.
          </p>
        </div>
        <button className="outline" onClick={() => void load()}>
          <RefreshCw size={15} /> تحديث المتجر
        </button>
      </div>

      {checkoutPlugin && (
        <section
          className="card plugin-payment-sheet"
          aria-label="إتمام دفع الإضافة"
        >
          <div className="plugin-card-top">
            <div>
              <span className="eyebrow">طلب دفع</span>
              <h3>{checkoutPlugin.name}</h3>
            </div>
            <button
              className="outline"
              onClick={() => {
                setCheckoutPlugin(null);
                setTransaction(null);
              }}
            >
              إغلاق
            </button>
          </div>
          {!transaction ? (
            <div className="plugin-payment-methods">
              <p>
                اختر طريقة التحويل المتاحة. بطاقات الائتمان والخصم عبر Stripe
                ستظهر هنا بعد إعداد بيانات Stripe الآمنة من إدارة المشروع.
              </p>
              {methods.map(method => (
                <button
                  key={method.id}
                  className="outline"
                  disabled={busy === checkoutPlugin.id}
                  onClick={() => void beginCheckout(method.code)}
                >
                  <WalletCards size={16} /> {method.label}
                </button>
              ))}
              {methods.length === 0 && (
                <div className="empty-state">
                  لا توجد وسيلة دفع مفعّلة. تواصل مع إدارة المركز.
                </div>
              )}
            </div>
          ) : (
            <div className="plugin-payment-reference">
              <p>
                <strong>{transaction.method?.label}</strong> —{" "}
                {transaction.method?.instructions}
              </p>
              <p>
                بيانات التحويل: <strong>{transaction.method?.recipient}</strong>
              </p>
              <label>
                رقم العملية أو المرجع
                <input
                  value={reference}
                  onChange={event => setReference(event.target.value)}
                  placeholder="مثال: رقم التحويل من المحفظة أو إنستاباي"
                />
              </label>
              <label>
                ملاحظة اختيارية
                <textarea
                  value={customerNote}
                  onChange={event => setCustomerNote(event.target.value)}
                />
              </label>
              <button
                className="primary"
                disabled={!reference.trim() || busy === transaction.id}
                onClick={() => void submitReference()}
              >
                <CheckCircle2 size={15} /> إرسال للمراجعة
              </button>
            </div>
          )}
        </section>
      )}

      {loading ? (
        <div className="live-loading">
          <RefreshCw className="spin" /> جارٍ تحميل الإضافات...
        </div>
      ) : (
        <div className="plugin-grid">
          {plugins.map(plugin => {
            const working = busy === plugin.id;
            return (
              <article className="plugin-card card" key={plugin.id}>
                <div className="plugin-card-icon">
                  <PackageCheck size={24} />
                </div>
                <div className="plugin-card-body">
                  <div className="plugin-card-top">
                    <span className="eyebrow">v{plugin.version}</span>
                    <span
                      className={
                        plugin.core_feature || plugin.installed
                          ? "plugin-status installed"
                          : "plugin-status"
                      }
                    >
                      {plugin.core_feature
                        ? "مضمنة"
                        : plugin.installed
                          ? "مثبتة"
                          : plugin.payment_status
                            ? paymentStatusLabel[plugin.payment_status]
                            : "متاحة"}
                    </span>
                  </div>
                  <h3>{plugin.name}</h3>
                  <p>
                    {plugin.description ||
                      "وحدة تعليمية جاهزة للإضافة إلى لوحة زويل."}
                  </p>
                  <small>
                    <Box size={13} /> {plugin.module_name}
                  </small>
                </div>
                <div className="plugin-card-footer">
                  <strong>
                    {Number(plugin.price) === 0
                      ? "مجانية"
                      : `${plugin.price} جنيه`}
                  </strong>
                  <div className="plugin-actions">
                    {plugin.core_feature ? (
                      <span className="plugin-payment-pending">
                        <CheckCircle2 size={14} /> متاحة داخل النظام
                      </span>
                    ) : plugin.installed ? (
                      <button
                        className="outline danger-text"
                        disabled={working}
                        onClick={() =>
                          void run(
                            plugin.id,
                            () => laravelApi.uninstallPlugin(plugin.id),
                            "تم إلغاء تثبيت الإضافة"
                          )
                        }
                      >
                        <Trash2 size={14} /> إلغاء التثبيت
                      </button>
                    ) : !plugin.purchased ? (
                      Number(plugin.price) === 0 ? (
                        <button
                          className="primary"
                          disabled={working}
                          onClick={() =>
                            void run(
                              plugin.id,
                              () => laravelApi.purchasePlugin(plugin.id),
                              "تم تسجيل امتلاك الإضافة المجانية"
                            )
                          }
                        >
                          <ShoppingBag size={14} /> الحصول مجاناً
                        </button>
                      ) : plugin.payment_status ? (
                        <span className="plugin-payment-pending">
                          <CreditCard size={14} />{" "}
                          {paymentStatusLabel[plugin.payment_status]}
                        </span>
                      ) : (
                        <button
                          className="primary"
                          disabled={working}
                          onClick={() => setCheckoutPlugin(plugin)}
                        >
                          <ShoppingBag size={14} /> اختيار الدفع
                        </button>
                      )
                    ) : (
                      <button
                        className="primary"
                        disabled={working}
                        onClick={() =>
                          void run(
                            plugin.id,
                            () => laravelApi.installPlugin(plugin.id),
                            "تم تثبيت الإضافة وتفعيلها"
                          )
                        }
                      >
                        {working ? (
                          <RefreshCw className="spin" size={14} />
                        ) : (
                          <Download size={14} />
                        )}{" "}
                        تثبيت
                      </button>
                    )}
                  </div>
                </div>
                {plugin.installed && (
                  <div className="plugin-installed-note">
                    <CheckCircle2 size={14} /> الوحدة مفعّلة داخل مجلد Modules
                  </div>
                )}
              </article>
            );
          })}
        </div>
      )}

      {isAdmin && (
        <details className="card plugin-admin-payments">
          <summary>
            <ShieldCheck size={17} /> إعدادات ومراجعة دفع الإضافات
          </summary>
          <p>
            هذا القسم لمدير المركز فقط. تحفظ هنا بيانات التحويل العامة التي
            يراها العميل؛ لا تدخل مفاتيح Stripe أو أي أسرار مزود دفع هنا.
          </p>
          <div className="plugin-payment-methods">
            {adminMethods.map((method, index) => (
              <div className="plugin-method-config" key={method.id}>
                <strong>{method.label}</strong>
                <label>
                  رقم المحفظة أو عنوان إنستاباي
                  <input
                    value={method.recipient || ""}
                    onChange={event =>
                      setAdminMethods(current =>
                        current.map((item, itemIndex) =>
                          itemIndex === index
                            ? { ...item, recipient: event.target.value }
                            : item
                        )
                      )
                    }
                  />
                </label>
                <label>
                  تعليمات التحويل
                  <textarea
                    value={method.instructions || ""}
                    onChange={event =>
                      setAdminMethods(current =>
                        current.map((item, itemIndex) =>
                          itemIndex === index
                            ? { ...item, instructions: event.target.value }
                            : item
                        )
                      )
                    }
                  />
                </label>
                <label className="check-line">
                  <input
                    type="checkbox"
                    checked={method.is_enabled}
                    onChange={event =>
                      setAdminMethods(current =>
                        current.map((item, itemIndex) =>
                          itemIndex === index
                            ? { ...item, is_enabled: event.target.checked }
                            : item
                        )
                      )
                    }
                  />{" "}
                  تفعيل للمستخدمين
                </label>
                <button
                  className="outline"
                  disabled={busy === method.id}
                  onClick={() => void updateMethod(method)}
                >
                  حفظ
                </button>
              </div>
            ))}
          </div>
          <h3>عمليات دفع بانتظار المراجعة</h3>
          {reviewQueue.length === 0 ? (
            <div className="empty-state">لا توجد عمليات بانتظار المراجعة.</div>
          ) : (
            reviewQueue.map(payment => (
              <div className="plugin-review-row" key={payment.id}>
                <span>
                  {payment.plugin?.name} — {payment.amount} {payment.currency}
                </span>
                <span>
                  {payment.method?.label}: {payment.reference}
                </span>
                <div>
                  <button
                    className="primary"
                    onClick={() => void review(payment, "approve")}
                  >
                    اعتماد
                  </button>
                  <button
                    className="outline danger-text"
                    onClick={() => void review(payment, "reject")}
                  >
                    رفض
                  </button>
                </div>
              </div>
            ))
          )}
        </details>
      )}

      {!loading && plugins.length === 0 && (
        <div className="empty-state">لا توجد إضافات متاحة حالياً.</div>
      )}
    </section>
  );
}
