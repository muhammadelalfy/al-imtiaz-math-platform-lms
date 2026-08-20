"use client";

import dynamic from "next/dynamic";
import type { PortalRoute } from "../lib/portalRoute";

const NextLmsShell = dynamic(
  () => import("./NextLmsShell").then(module => module.NextLmsShell),
  {
    ssr: false,
    loading: () => (
      <main className="live-loading" dir="rtl">
        <p>جارٍ تجهيز المنصة...</p>
      </main>
    ),
  }
);

export function LmsRouteClient({ initialPortal }: { initialPortal: PortalRoute }) {
  return <NextLmsShell initialPortal={initialPortal} />;
}
