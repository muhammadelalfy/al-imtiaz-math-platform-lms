import { useEffect, useState, useSyncExternalStore } from "react";
import {
  getActiveRequestCount,
  subscribeToRequestActivity,
} from "@/lib/requestActivity";

const REQUEST_DELAY_MS = 140;

export default function GlobalRequestLoader() {
  const activeRequestCount = useSyncExternalStore(
    subscribeToRequestActivity,
    getActiveRequestCount,
    () => 0
  );
  const [visible, setVisible] = useState(false);

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
        <img
          className="global-request-loader__logo"
          src="/manus-storage/al-imtiaz-mark_99680b5d.png"
          alt=""
        />
        <strong>جارٍ تجهيز بيانات الامتياز</strong>
        <span>لحظة واحدة من فضلك</span>
      </div>
    </div>
  );
}
