import { createInertiaApp } from "@inertiajs/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { createRoot } from "react-dom/client";
import RootApp from "./LmsApp";
import GlobalRequestLoader from "./components/GlobalRequestLoader";
import "./index.css";
import "./styles/theme.scss";

const queryClient = new QueryClient();

createInertiaApp({
  resolve: () => RootApp,
  setup({ el, App, props }) {
    createRoot(el).render(
      <QueryClientProvider client={queryClient}>
        <App {...props} />
        <GlobalRequestLoader />
      </QueryClientProvider>
    );
  },
});
