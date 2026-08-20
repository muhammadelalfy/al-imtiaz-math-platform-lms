import { useEffect, useMemo, useState } from "react";
import {
  Bell,
  CheckSquare,
  Layers3,
  Plus,
  RefreshCw,
  Save,
  Send,
  Users,
} from "lucide-react";
import { toast } from "sonner";
import {
  laravelApi,
  type ApiUser,
  type AcademicGroup,
  type NotificationAudience,
  type NotificationAudienceCatalog,
  type NotificationCampaign,
  type NotificationChannel,
  type NotificationChannelSetting,
  type Student,
} from "@/lib/laravelApi";

const audienceLabels: Record<NotificationAudience, string> = {
  all_parents: "كل أولياء الأمور",
  all_students: "كل الطلاب",
  selected: "طلاب أو أولياء أمور محددون",
  grade: "صف دراسي محدد",
  academic_group: "مجموعة دراسية محددة",
};

export default function NotificationManagementPanel({
  canManageChannels,
  canManageGroups,
  canSendNotifications,
}: {
  canManageChannels: boolean;
  canManageGroups: boolean;
  canSendNotifications: boolean;
}) {
  const [groups, setGroups] = useState<AcademicGroup[]>([]);
  const [students, setStudents] = useState<Student[]>([]);
  const [catalog, setCatalog] = useState<NotificationAudienceCatalog | null>(
    null
  );
  const [campaigns, setCampaigns] = useState<NotificationCampaign[]>([]);
  const [channels, setChannels] = useState<NotificationChannelSetting[]>([]);
  const [activeGroup, setActiveGroup] = useState<AcademicGroup | null>(null);
  const [groupName, setGroupName] = useState("");
  const [groupGrade, setGroupGrade] = useState("");
  const [selectedStudentIds, setSelectedStudentIds] = useState<number[]>([]);
  const [audience, setAudience] = useState<NotificationAudience>("all_parents");
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [grade, setGrade] = useState("");
  const [academicGroupId, setAcademicGroupId] = useState<number | undefined>();
  const [recipientIds, setRecipientIds] = useState<number[]>([]);
  const [selectedChannels, setSelectedChannels] = useState<
    NotificationChannel[]
  >(["in_app"]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const load = async () => {
    setLoading(true);
    try {
      const [
        groupRows,
        studentRows,
        audienceCatalog,
        campaignRows,
        channelRows,
      ] = await Promise.all([
        canManageGroups ? laravelApi.academicGroups() : Promise.resolve([]),
        canManageGroups ? laravelApi.students() : Promise.resolve([]),
        laravelApi.notificationAudienceCatalog(),
        laravelApi.notificationCampaigns(),
        canManageChannels
          ? laravelApi.notificationChannels()
          : Promise.resolve([]),
      ]);
      setGroups(groupRows);
      setStudents(studentRows);
      setCatalog(audienceCatalog);
      setCampaigns(campaignRows);
      setChannels(channelRows);
    } catch (error) {
      toast.error(
        error instanceof Error
          ? error.message
          : "تعذر تحميل بيانات المجموعات والإشعارات"
      );
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, [canManageChannels, canManageGroups, canSendNotifications]);

  const eligibleStudents = useMemo(
    () =>
      students.filter(student => !groupGrade || student.grade === groupGrade),
    [students, groupGrade]
  );
  const activeEnabledChannels = useMemo(
    () => (catalog?.channels || []).filter(channel => channel.is_enabled),
    [catalog]
  );

  const saveGroup = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!groupName.trim() || !groupGrade.trim()) return;
    setSaving(true);
    try {
      const result = activeGroup
        ? await laravelApi.updateAcademicGroup(activeGroup.id, {
            name: groupName.trim(),
            grade: groupGrade.trim(),
          })
        : await laravelApi.createAcademicGroup({
            name: groupName.trim(),
            grade: groupGrade.trim(),
          });
      setActiveGroup(result);
      setSelectedStudentIds(
        result.students?.map((student: Pick<Student, "id">) => student.id) || []
      );
      toast.success(activeGroup ? "تم تحديث المجموعة" : "تم إنشاء المجموعة");
      await load();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "تعذر حفظ المجموعة");
    } finally {
      setSaving(false);
    }
  };

  const openGroup = async (group: AcademicGroup) => {
    try {
      const detail = await laravelApi.academicGroup(group.id);
      setActiveGroup(detail);
      setGroupName(detail.name);
      setGroupGrade(detail.grade);
      setSelectedStudentIds(
        detail.students?.map((student: Pick<Student, "id">) => student.id) || []
      );
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : "تعذر تحميل أعضاء المجموعة"
      );
    }
  };

  const saveMembers = async () => {
    if (!activeGroup) return;
    setSaving(true);
    try {
      const group = await laravelApi.syncAcademicGroupStudents(
        activeGroup.id,
        selectedStudentIds
      );
      setActiveGroup(group);
      toast.success("تم تحديث أعضاء المجموعة");
      await load();
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : "تعذر تحديث الأعضاء"
      );
    } finally {
      setSaving(false);
    }
  };

  const submitCampaign = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!title.trim() || !body.trim()) return;
    setSaving(true);
    try {
      const campaign = await laravelApi.createNotificationCampaign({
        audience,
        title: title.trim(),
        body: body.trim(),
        ...(audience === "grade" ? { grade } : {}),
        ...(audience === "academic_group"
          ? { academic_group_id: academicGroupId }
          : {}),
        ...(audience === "selected" ? { recipient_ids: recipientIds } : {}),
        channels: selectedChannels,
      });
      setCampaigns(current => [campaign, ...current]);
      setTitle("");
      setBody("");
      toast.success("تمت جدولة الإشعار للتسليم");
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : "تعذر إرسال الإشعار"
      );
    } finally {
      setSaving(false);
    }
  };

  const updateChannel = async (
    channel: NotificationChannelSetting,
    changes: Pick<NotificationChannelSetting, "is_enabled" | "settings">
  ) => {
    try {
      const updated = await laravelApi.updateNotificationChannel(
        channel.id,
        changes
      );
      setChannels(current =>
        current.map(item => (item.id === updated.id ? updated : item))
      );
      await load();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "تعذر تحديث القناة");
    }
  };

  if (loading)
    return (
      <div className="live-loading">
        <RefreshCw className="spin" /> جارٍ تحميل مركز الإشعارات...
      </div>
    );

  return (
    <section className="live-page notification-management-panel">
      <div className="live-hero compact">
        <div>
          <span className="eyebrow">المجموعات والإشعارات</span>
          <h2>تواصل منظم مع الطلاب وأولياء الأمور</h2>
          <p>
            أنشئ مجموعات دراسية، أدر أعضاءها دفعة واحدة، ثم أرسل الإشعارات عبر
            القنوات المصرح بها.
          </p>
        </div>
        <Bell size={46} />
      </div>
      <div className="live-grid notification-grid">
        {canManageGroups && (
          <form className="card form-card" onSubmit={saveGroup}>
            <div className="card-head">
              <h3>
                <Layers3 size={18} /> المجموعات الدراسية
              </h3>
              <button
                type="button"
                className="outline"
                onClick={() => {
                  setActiveGroup(null);
                  setGroupName("");
                  setGroupGrade("");
                  setSelectedStudentIds([]);
                }}
              >
                <Plus size={15} /> جديدة
              </button>
            </div>
            <label>
              اسم المجموعة
              <input
                value={groupName}
                onChange={event => setGroupName(event.target.value)}
                placeholder="مثال: مجموعة المتفوقين"
                required
              />
            </label>
            <label>
              الصف الدراسي
              <input
                list="group-grades"
                value={groupGrade}
                onChange={event => setGroupGrade(event.target.value)}
                placeholder="اختر أو اكتب الصف"
                required
              />
              <datalist id="group-grades">
                {catalog?.grades.map((item: string) => (
                  <option value={item} key={item} />
                ))}
              </datalist>
            </label>
            <button className="primary" disabled={saving}>
              <Save size={16} />{" "}
              {activeGroup ? "حفظ المجموعة" : "إنشاء المجموعة"}
            </button>
            <div className="group-list">
              {groups.map((group: AcademicGroup) => (
                <button
                  type="button"
                  key={group.id}
                  className={
                    activeGroup?.id === group.id
                      ? "group-row active"
                      : "group-row"
                  }
                  onClick={() => void openGroup(group)}
                >
                  <span>
                    {group.name}
                    <small>{group.grade}</small>
                  </span>
                  <b>{group.students_count || 0} طالب</b>
                </button>
              ))}
            </div>
            {activeGroup && (
              <div className="member-editor">
                <div className="card-head">
                  <h4>أعضاء {activeGroup.name}</h4>
                  <button
                    type="button"
                    className="outline"
                    onClick={() => void saveMembers()}
                    disabled={saving}
                  >
                    <CheckSquare size={15} /> حفظ الأعضاء
                  </button>
                </div>
                <p>
                  يظهر طلاب الصف المحدد فقط، ويمكنك الإضافة أو الإزالة بالاختيار
                  المتعدد.
                </p>
                <div className="member-checks">
                  {eligibleStudents.map(student => (
                    <label key={student.id}>
                      <input
                        type="checkbox"
                        checked={selectedStudentIds.includes(student.id)}
                        onChange={() =>
                          setSelectedStudentIds(current =>
                            current.includes(student.id)
                              ? current.filter(id => id !== student.id)
                              : [...current, student.id]
                          )
                        }
                      />
                      {student.name}
                    </label>
                  ))}
                </div>
              </div>
            )}
          </form>
        )}
        {canSendNotifications && (
          <form className="card form-card" onSubmit={submitCampaign}>
            <div className="card-head">
              <h3>
                <Send size={18} /> إنشاء إشعار
              </h3>
              <span className="soft-badge">طابور آمن</span>
            </div>
            <label>
              الجمهور
              <select
                value={audience}
                onChange={event =>
                  setAudience(event.target.value as NotificationAudience)
                }
              >
                {(Object.keys(audienceLabels) as NotificationAudience[]).map(
                  key => (
                    <option value={key} key={key}>
                      {audienceLabels[key]}
                    </option>
                  )
                )}
              </select>
            </label>
            {audience === "grade" && (
              <label>
                الصف
                <select
                  value={grade}
                  onChange={event => setGrade(event.target.value)}
                  required
                >
                  <option value="">اختر الصف</option>
                  {catalog?.grades.map((item: string) => (
                    <option value={item} key={item}>
                      {item}
                    </option>
                  ))}
                </select>
              </label>
            )}
            {audience === "academic_group" && (
              <label>
                المجموعة
                <select
                  value={academicGroupId || ""}
                  onChange={event =>
                    setAcademicGroupId(Number(event.target.value) || undefined)
                  }
                  required
                >
                  <option value="">اختر المجموعة</option>
                  {catalog?.academic_groups.map((group: AcademicGroup) => (
                    <option value={group.id} key={group.id}>
                      {group.grade} — {group.name} ({group.students_count || 0})
                    </option>
                  ))}
                </select>
              </label>
            )}
            {audience === "selected" && (
              <label>
                المستلمون
                <select
                  multiple
                  value={recipientIds.map(String)}
                  onChange={event =>
                    setRecipientIds(
                      Array.from(event.target.selectedOptions).map(option =>
                        Number(option.value)
                      )
                    )
                  }
                >
                  {catalog?.recipients.map(
                    (person: Pick<ApiUser, "id" | "name" | "role">) => (
                      <option key={person.id} value={person.id}>
                        {person.name} — {person.role}
                      </option>
                    )
                  )}
                </select>
              </label>
            )}
            <label>
              العنوان
              <input
                value={title}
                onChange={event => setTitle(event.target.value)}
                maxLength={120}
                required
              />
            </label>
            <label>
              الرسالة
              <textarea
                value={body}
                onChange={event => setBody(event.target.value)}
                rows={4}
                required
              />
            </label>
            <fieldset>
              <legend>قنوات التسليم</legend>
              {activeEnabledChannels.map(
                (
                  channel: Pick<
                    NotificationChannelSetting,
                    "code" | "label" | "is_enabled" | "is_provider_ready"
                  >
                ) => (
                  <label key={channel.code}>
                    <input
                      type="checkbox"
                      checked={selectedChannels.includes(channel.code)}
                      onChange={() =>
                        setSelectedChannels(current =>
                          current.includes(channel.code)
                            ? current.filter(code => code !== channel.code)
                            : [...current, channel.code]
                        )
                      }
                    />
                    {channel.label}
                    {channel.code !== "in_app" && !channel.is_provider_ready
                      ? " — ينتظر اعتماد المزوّد"
                      : ""}
                  </label>
                )
              )}
            </fieldset>
            <button
              className="primary"
              disabled={saving || selectedChannels.length === 0}
            >
              <Send size={16} /> جدولة الإشعار
            </button>
          </form>
        )}
      </div>
      {canManageChannels && (
        <div className="card channel-settings">
          <div className="card-head">
            <h3>إعدادات القنوات</h3>
            <small>
              يتحكم المعلم في الخيارات غير السرية فقط. مفاتيح المزوّدين لا تظهر
              هنا.
            </small>
          </div>
          {channels.map(channel => (
            <div className="channel-row" key={channel.id}>
              <div>
                <b>{channel.label}</b>
                <small>
                  {channel.code === "in_app"
                    ? "قناة داخل المنصة وجاهزة دائماً"
                    : channel.is_provider_ready
                      ? "بيانات المزوّد معتمدة"
                      : "تحتاج مفاتيح مزوّد آمنة من الإدارة"}
                </small>
              </div>
              <label>
                <input
                  type="checkbox"
                  checked={channel.is_enabled}
                  disabled={channel.code === "in_app"}
                  onChange={event =>
                    void updateChannel(channel, {
                      is_enabled: event.target.checked,
                      settings: channel.settings,
                    })
                  }
                />
                تفعيل
              </label>
              <input
                value={channel.settings.sender_label || ""}
                placeholder="اسم المرسل"
                onChange={event =>
                  void updateChannel(channel, {
                    is_enabled: channel.is_enabled,
                    settings: {
                      ...channel.settings,
                      sender_label: event.target.value,
                    },
                  })
                }
              />
            </div>
          ))}
        </div>
      )}
      <div className="card">
        <div className="card-head">
          <h3>آخر الإشعارات</h3>
          <Users size={18} />
        </div>
        {campaigns.map(campaign => (
          <div className="live-row" key={campaign.id}>
            <span>
              <b>{campaign.title}</b>
              <small>
                {audienceLabels[campaign.audience]} · {campaign.recipient_count}{" "}
                مستلم
              </small>
            </span>
            <small>{campaign.channels.join("، ")}</small>
          </div>
        ))}
      </div>
    </section>
  );
}
