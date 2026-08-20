import { useEffect, useState } from "react";
import {
  KeyRound,
  Pencil,
  Plus,
  RefreshCw,
  ShieldCheck,
  Trash2,
  UserRoundCog,
  Users,
} from "lucide-react";
import { toast } from "sonner";
import {
  ApiError,
  laravelApi,
  type AuthorizationCatalog,
  type AuthorizationPermission,
  type AuthorizationRole,
} from "@/lib/laravelApi";

type Props = { canAssignStaffRoles: boolean };

const blankPermission = { name: "", label: "", description: "" };
const blankRole = {
  name: "",
  label: "",
  description: "",
  permission_ids: [] as number[],
};

export default function AuthorizationManagementPanel({
  canAssignStaffRoles,
}: Props) {
  const [catalog, setCatalog] = useState<AuthorizationCatalog | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState("");
  const [permissionForm, setPermissionForm] = useState(blankPermission);
  const [roleForm, setRoleForm] = useState(blankRole);
  const [editingPermission, setEditingPermission] =
    useState<AuthorizationPermission | null>(null);
  const [editingRole, setEditingRole] = useState<AuthorizationRole | null>(
    null
  );

  const reload = async () => {
    setLoading(true);
    try {
      setCatalog(await laravelApi.authorizationCatalog());
    } catch (caught) {
      toast(
        caught instanceof ApiError ? caught.message : "تعذر تحميل الصلاحيات"
      );
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void reload();
  }, []);

  const savePermission = async (event: React.FormEvent) => {
    event.preventDefault();
    setBusy("permission");
    try {
      if (editingPermission) {
        await laravelApi.updateAuthorizationPermission(
          editingPermission.id,
          permissionForm
        );
      } else {
        await laravelApi.createAuthorizationPermission(permissionForm);
      }
      setPermissionForm(blankPermission);
      setEditingPermission(null);
      toast("تم حفظ الصلاحية");
      await reload();
    } catch (caught) {
      toast(caught instanceof ApiError ? caught.message : "تعذر حفظ الصلاحية");
    } finally {
      setBusy("");
    }
  };

  const saveRole = async (event: React.FormEvent) => {
    event.preventDefault();
    setBusy("role");
    try {
      if (editingRole) {
        await laravelApi.updateAuthorizationRole(editingRole.id, roleForm);
      } else {
        await laravelApi.createAuthorizationRole(roleForm);
      }
      setRoleForm(blankRole);
      setEditingRole(null);
      toast("تم حفظ الدور");
      await reload();
    } catch (caught) {
      toast(caught instanceof ApiError ? caught.message : "تعذر حفظ الدور");
    } finally {
      setBusy("");
    }
  };

  const remove = async (kind: "role" | "permission", id: number) => {
    if (!window.confirm("هل تريد الحذف؟ لا يمكن التراجع عن هذا الإجراء."))
      return;
    setBusy(`${kind}-${id}`);
    try {
      if (kind === "role") await laravelApi.deleteAuthorizationRole(id);
      else await laravelApi.deleteAuthorizationPermission(id);
      toast("تم الحذف");
      await reload();
    } catch (caught) {
      toast(caught instanceof ApiError ? caught.message : "تعذر الحذف");
    } finally {
      setBusy("");
    }
  };

  const syncStaffRoles = async (staffId: number, roleIds: number[]) => {
    setBusy(`staff-${staffId}`);
    try {
      await laravelApi.syncStaffAuthorizationRoles(staffId, roleIds);
      toast("تم تحديث أدوار الموظف");
      await reload();
    } catch (caught) {
      toast(
        caught instanceof ApiError ? caught.message : "تعذر تحديث أدوار الموظف"
      );
    } finally {
      setBusy("");
    }
  };

  const permissions = catalog?.permissions ?? [];
  const customRoles = (catalog?.roles ?? []).filter(role => !role.is_system);

  if (loading) {
    return (
      <section
        className="live-page flex min-h-64 items-center justify-center gap-2"
        dir="rtl"
      >
        <RefreshCw className="spin" size={20} /> جارٍ تجهيز مركز الصلاحيات...
      </section>
    );
  }

  return (
    <section className="live-page space-y-5" dir="rtl">
      <div className="page-head">
        <div>
          <span className="eyebrow">حماية الوصول</span>
          <h2>الأدوار والصلاحيات</h2>
          <p className="mt-1 max-w-2xl text-sm leading-6 text-[var(--muted-foreground)]">
            أنشئ أدواراً وصلاحيات مخصصة للعاملين. عناصر النظام محمية ولا يمكن
            تعديلها من هذه اللوحة.
          </p>
        </div>
        <button className="outline" type="button" onClick={() => void reload()}>
          <RefreshCw size={15} /> تحديث
        </button>
      </div>

      <div className="grid gap-5 xl:grid-cols-2">
        <form className="card space-y-4 p-5" onSubmit={savePermission}>
          <div className="card-head">
            <div>
              <span className="eyebrow">مورد الوصول</span>
              <h3>{editingPermission ? "تعديل صلاحية" : "إضافة صلاحية"}</h3>
            </div>
            <KeyRound size={21} />
          </div>
          <label className="block text-sm font-bold">
            الاسم البرمجي
            <input
              className="mt-1 w-full"
              required
              dir="ltr"
              placeholder="worksheets.review"
              value={permissionForm.name}
              onChange={event =>
                setPermissionForm({
                  ...permissionForm,
                  name: event.target.value,
                })
              }
            />
          </label>
          <label className="block text-sm font-bold">
            الاسم الظاهر
            <input
              className="mt-1 w-full"
              required
              placeholder="مراجعة الشيتات"
              value={permissionForm.label}
              onChange={event =>
                setPermissionForm({
                  ...permissionForm,
                  label: event.target.value,
                })
              }
            />
          </label>
          <label className="block text-sm font-bold">
            وصف مختصر
            <textarea
              className="mt-1 min-h-20 w-full"
              value={permissionForm.description}
              onChange={event =>
                setPermissionForm({
                  ...permissionForm,
                  description: event.target.value,
                })
              }
            />
          </label>
          <div className="flex flex-wrap gap-2">
            <button className="primary" disabled={busy === "permission"}>
              <Plus size={16} />{" "}
              {editingPermission ? "حفظ التعديل" : "إضافة صلاحية"}
            </button>
            {editingPermission && (
              <button
                className="outline"
                type="button"
                onClick={() => {
                  setEditingPermission(null);
                  setPermissionForm(blankPermission);
                }}
              >
                إلغاء
              </button>
            )}
          </div>
        </form>

        <form className="card space-y-4 p-5" onSubmit={saveRole}>
          <div className="card-head">
            <div>
              <span className="eyebrow">مجموعة الوصول</span>
              <h3>{editingRole ? "تعديل دور" : "إضافة دور"}</h3>
            </div>
            <ShieldCheck size={21} />
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="text-sm font-bold">
              الاسم البرمجي
              <input
                className="mt-1 w-full"
                required
                dir="ltr"
                placeholder="worksheet-reviewer"
                value={roleForm.name}
                onChange={event =>
                  setRoleForm({ ...roleForm, name: event.target.value })
                }
              />
            </label>
            <label className="text-sm font-bold">
              الاسم الظاهر
              <input
                className="mt-1 w-full"
                required
                placeholder="مراجع الشيتات"
                value={roleForm.label}
                onChange={event =>
                  setRoleForm({ ...roleForm, label: event.target.value })
                }
              />
            </label>
          </div>
          <label className="block text-sm font-bold">
            وصف الدور
            <input
              className="mt-1 w-full"
              value={roleForm.description}
              onChange={event =>
                setRoleForm({ ...roleForm, description: event.target.value })
              }
            />
          </label>
          <fieldset className="rounded-2xl border border-[var(--border)] p-3">
            <legend className="px-1 text-sm font-bold">الصلاحيات</legend>
            <div className="grid gap-2 sm:grid-cols-2">
              {permissions.map(permission => {
                const checked = roleForm.permission_ids.includes(permission.id);
                const protectedPermission =
                  permission.is_system && !canAssignStaffRoles;
                return (
                  <label
                    className={`flex cursor-pointer items-start gap-2 rounded-xl p-2 text-sm ${protectedPermission ? "opacity-55" : "hover:bg-[var(--muted)]"}`}
                    key={permission.id}
                  >
                    <input
                      type="checkbox"
                      disabled={protectedPermission}
                      checked={checked}
                      onChange={() =>
                        setRoleForm({
                          ...roleForm,
                          permission_ids: checked
                            ? roleForm.permission_ids.filter(
                                id => id !== permission.id
                              )
                            : [...roleForm.permission_ids, permission.id],
                        })
                      }
                    />
                    <span>
                      <b>{permission.label}</b>
                      {permission.is_system && (
                        <small className="mr-1 text-xs">محمي</small>
                      )}
                    </span>
                  </label>
                );
              })}
            </div>
          </fieldset>
          <div className="flex flex-wrap gap-2">
            <button className="primary" disabled={busy === "role"}>
              <Plus size={16} /> {editingRole ? "حفظ التعديل" : "إضافة دور"}
            </button>
            {editingRole && (
              <button
                className="outline"
                type="button"
                onClick={() => {
                  setEditingRole(null);
                  setRoleForm(blankRole);
                }}
              >
                إلغاء
              </button>
            )}
          </div>
        </form>
      </div>

      <div className="grid gap-5 xl:grid-cols-2">
        <div className="card p-5">
          <div className="card-head">
            <div>
              <span className="eyebrow">الكتالوج</span>
              <h3>الصلاحيات المتاحة</h3>
            </div>
            <KeyRound size={20} />
          </div>
          <div className="mt-3 space-y-2">
            {permissions.map(permission => (
              <div className="live-row gap-3" key={permission.id}>
                <div className="min-w-0">
                  <b>{permission.label}</b>
                  <small className="mt-1 block truncate" dir="ltr">
                    {permission.name}
                  </small>
                </div>
                <div className="flex shrink-0 gap-1">
                  {permission.is_system ? (
                    <span className="rounded-full bg-[var(--muted)] px-2 py-1 text-xs font-bold">
                      محمي
                    </span>
                  ) : (
                    <>
                      <button
                        className="icon-button"
                        aria-label={`تعديل ${permission.label}`}
                        onClick={() => {
                          setEditingPermission(permission);
                          setPermissionForm({
                            name: permission.name,
                            label: permission.label,
                            description: permission.description || "",
                          });
                        }}
                      >
                        <Pencil size={15} />
                      </button>
                      <button
                        className="icon-button danger-text"
                        aria-label={`حذف ${permission.label}`}
                        disabled={busy === `permission-${permission.id}`}
                        onClick={() => void remove("permission", permission.id)}
                      >
                        <Trash2 size={15} />
                      </button>
                    </>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="card p-5">
          <div className="card-head">
            <div>
              <span className="eyebrow">أدوار مخصصة</span>
              <h3>الأدوار القابلة للإدارة</h3>
            </div>
            <ShieldCheck size={20} />
          </div>
          <div className="mt-3 space-y-2">
            {customRoles.length === 0 ? (
              <p className="py-5 text-sm text-[var(--muted-foreground)]">
                أضف دوراً مخصصاً لتبدأ توزيع الصلاحيات.
              </p>
            ) : (
              customRoles.map(role => (
                <div className="live-row gap-3" key={role.id}>
                  <div className="min-w-0">
                    <b>{role.label}</b>
                    <small className="mt-1 block">
                      {role.permissions.length} صلاحيات ·{" "}
                      {role.description || "دون وصف"}
                    </small>
                  </div>
                  <div className="flex shrink-0 gap-1">
                    <button
                      className="icon-button"
                      aria-label={`تعديل ${role.label}`}
                      onClick={() => {
                        setEditingRole(role);
                        setRoleForm({
                          name: role.name,
                          label: role.label,
                          description: role.description || "",
                          permission_ids: role.permission_ids,
                        });
                      }}
                    >
                      <Pencil size={15} />
                    </button>
                    <button
                      className="icon-button danger-text"
                      aria-label={`حذف ${role.label}`}
                      disabled={busy === `role-${role.id}`}
                      onClick={() => void remove("role", role.id)}
                    >
                      <Trash2 size={15} />
                    </button>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      {canAssignStaffRoles && catalog && (
        <div className="card p-5">
          <div className="card-head">
            <div>
              <span className="eyebrow">إسناد إداري</span>
              <h3>أدوار العاملين</h3>
              <p className="mt-1 text-sm text-[var(--muted-foreground)]">
                الأدوار النظامية محفوظة. يمكنك إسناد الأدوار المخصصة فقط.
              </p>
            </div>
            <Users size={20} />
          </div>
          <div className="mt-4 grid gap-3 lg:grid-cols-2">
            {catalog.staff.map(staff => (
              <StaffRoleAssignment
                key={staff.id}
                staff={staff}
                roles={customRoles}
                busy={busy === `staff-${staff.id}`}
                onSave={roleIds => void syncStaffRoles(staff.id, roleIds)}
              />
            ))}
          </div>
        </div>
      )}
    </section>
  );
}

function StaffRoleAssignment({
  staff,
  roles,
  busy,
  onSave,
}: {
  staff: AuthorizationCatalog["staff"][number];
  roles: AuthorizationRole[];
  busy: boolean;
  onSave: (roleIds: number[]) => void;
}) {
  const [selected, setSelected] = useState(
    staff.role_ids.filter(id => roles.some(role => role.id === id))
  );

  useEffect(() => {
    setSelected(
      staff.role_ids.filter(id => roles.some(role => role.id === id))
    );
  }, [staff, roles]);

  return (
    <article className="rounded-2xl border border-[var(--border)] p-4">
      <div className="flex items-center justify-between gap-3">
        <div className="flex items-center gap-2">
          <span className="avatar">
            <UserRoundCog size={16} />
          </span>
          <div>
            <b>{staff.name}</b>
            <small className="block">
              {staff.base_role === "admin" ? "مدير النظام" : "مدرس"}
            </small>
          </div>
        </div>
        <button
          className="outline"
          disabled={busy}
          onClick={() => onSave(selected)}
        >
          حفظ
        </button>
      </div>
      <div className="mt-3 flex flex-wrap gap-2">
        {roles.map(role => (
          <label
            className="flex items-center gap-1 rounded-full border border-[var(--border)] px-2 py-1 text-xs"
            key={role.id}
          >
            <input
              type="checkbox"
              checked={selected.includes(role.id)}
              onChange={() =>
                setSelected(current =>
                  current.includes(role.id)
                    ? current.filter(id => id !== role.id)
                    : [...current, role.id]
                )
              }
            />
            {role.label}
          </label>
        ))}
      </div>
    </article>
  );
}
