"use client";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useState } from "react";
import ErrorBoundary from "@/components/ErrorBoundary";
import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { ThemeProvider } from "@/contexts/ThemeContext";
import LiveDashboard from "@/pages/LiveDashboard";
import type { PortalRoute } from "../lib/portalRoute";

export function NextLmsShell({
  initialPortal,
  lockPortal = false,
}: {
  initialPortal: PortalRoute;
  lockPortal?: boolean;
}) {
  const [queryClient] = useState(() => new QueryClient());

  return (
    <QueryClientProvider client={queryClient}>
      <ErrorBoundary>
        <ThemeProvider defaultTheme="light" switchable>
          <TooltipProvider>
            <Toaster />
            <LiveDashboard initialPortal={initialPortal} lockPortal={lockPortal} />
          </TooltipProvider>
        </ThemeProvider>
      </ErrorBoundary>
    </QueryClientProvider>
  );
}
