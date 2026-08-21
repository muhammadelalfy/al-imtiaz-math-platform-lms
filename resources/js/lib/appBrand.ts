export const ZEWAL_NAME_AR = "زويل";
export const ZEWAL_PLATFORM_NAME_AR = "منصة زويل التعليمية";
export const ZEWAL_LOGO_URL =
  "https://files.manuscdn.com/user_upload_by_module/session_file/310519663473185782/qLYMEkUYKHpFOzUe.svg";

const teacherAcademyNameKey = "zewal-teacher-academy-name";
export const teacherAcademyNameChangedEvent = "zewal:academy-name-changed";

type AcademyIdentity = {
  role?: string;
  academy_name?: string | null;
};

const canUseStorage = () => typeof window !== "undefined";

const emitTeacherAcademyNameChange = () => {
  if (!canUseStorage() || typeof window.dispatchEvent !== "function") return;
  window.dispatchEvent(new Event(teacherAcademyNameChangedEvent));
};

export function readTeacherAcademyName(): string | null {
  if (!canUseStorage()) return null;
  return window.localStorage.getItem(teacherAcademyNameKey)?.trim() || null;
}

export function rememberTeacherAcademyName(identity: AcademyIdentity): void {
  if (!canUseStorage()) return;
  if (identity.role === "teacher" && identity.academy_name?.trim()) {
    window.localStorage.setItem(teacherAcademyNameKey, identity.academy_name.trim());
  } else {
    window.localStorage.removeItem(teacherAcademyNameKey);
  }
  emitTeacherAcademyNameChange();
}

export function clearTeacherAcademyName(): void {
  if (!canUseStorage()) return;
  window.localStorage.removeItem(teacherAcademyNameKey);
  emitTeacherAcademyNameChange();
}
