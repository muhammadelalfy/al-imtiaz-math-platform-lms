import { useEffect, useState } from "react";
import { Bell, Check, RefreshCw } from "lucide-react";
import { laravelApi, type InAppNotification } from "@/lib/laravelApi";

export default function NotificationInbox() {
  const [items, setItems] = useState<InAppNotification[]>([]);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    laravelApi
      .notificationInbox()
      .then(setItems)
      .catch(() => setItems([]))
      .finally(() => setLoading(false));
  }, []);
  const markRead = async (item: InAppNotification) => {
    if (item.read_at) return;
    const updated = await laravelApi.markNotificationRead(item.id);
    setItems(current =>
      current.map(entry => (entry.id === updated.id ? updated : entry))
    );
  };
  return (
    <div className="card notification-inbox">
      <div className="card-head">
        <h3>
          <Bell size={18} /> الإشعارات
        </h3>
        {loading && <RefreshCw className="spin" size={17} />}
      </div>
      {items.length === 0 && !loading ? (
        <p>لا توجد إشعارات جديدة.</p>
      ) : (
        items.slice(0, 6).map(item => (
          <button
            type="button"
            key={item.id}
            className={
              item.read_at ? "notification-item read" : "notification-item"
            }
            onClick={() => void markRead(item)}
          >
            <span>
              <b>{item.title}</b>
              <small>{item.body}</small>
            </span>
            {item.read_at ? (
              <Check size={16} />
            ) : (
              <span className="unread-dot" />
            )}
          </button>
        ))
      )}
    </div>
  );
}
