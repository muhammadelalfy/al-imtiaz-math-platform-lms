import { useEffect, useState, useSyncExternalStore } from "react";
import {
  getActiveRequestCount,
  subscribeToRequestActivity,
} from "@/lib/requestActivity";
import {
  readTeacherAcademyName,
  teacherAcademyNameChangedEvent,
  ZEWAL_LOGO_URL,
  ZEWAL_PLATFORM_NAME_AR,
} from "@/lib/appBrand";

const REQUEST_DELAY_MS = 140;

export default function GlobalRequestLoader() {
  const activeRequestCount = useSyncExternalStore(
    subscribeToRequestActivity,
    getActiveRequestCount,
    () => 0
  );
  const [visible, setVisible] = useState(false);
  const [logoFailed, setLogoFailed] = useState(false);
  const [academyName, setAcademyName] = useState(readTeacherAcademyName);

  useEffect(() => {
    const refreshAcademyName = () => setAcademyName(readTeacherAcademyName());
    window.addEventListener(teacherAcademyNameChangedEvent, refreshAcademyName);
    return () =>
      window.removeEventListener(
        teacherAcademyNameChangedEvent,
        refreshAcademyName
      );
  }, []);

  useEffect(() => {
    if (!activeRequestCount) {
      setVisible(false);
      return;
    }

    const timer = window.setTimeout(() => setVisible(true), REQUEST_DELAY_MS);
    return () => window.clearTimeout(timer);
  }, [activeRequestCount]);

  if (!visible) return null;

  return (
    <div className="global-request-loader" role="status" aria-live="polite">
      <div className="global-request-loader__card">
        <span className="global-request-loader__orbit" aria-hidden="true" />
        <div
          className={`global-request-loader__brand${logoFailed ? " is-fallback" : ""}`}
          aria-hidden="true"
        >
          <span className="global-request-loader__logo-fallback">ز</span>
          {!logoFailed && (
            <img
              className="global-request-loader__logo"
              src={ZEWAL_LOGO_URL}
              alt=""
              onError={() => setLogoFailed(true)}
            />
          )}
        </div>
        <strong>
          جارٍ تجهيز بيانات {academyName || ZEWAL_PLATFORM_NAME_AR}
        </strong>
        <span className="global-request-loader__platform">
          {academyName ? `بإدارة ${ZEWAL_PLATFORM_NAME_AR}` : "لحظة واحدة من فضلك"}
        </span>
      </div>
    </div>
  );
}
