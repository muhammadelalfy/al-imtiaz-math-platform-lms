import type { Metadata } from "next";
import "../lms/index.css";
import "../lms/styles/theme.scss";

export const metadata: Metadata = {
  title: "الامتياز في الرياضيات",
  description: "منصة الامتياز في الرياضيات لإدارة التعلم والمتابعة.",
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="ar" dir="rtl" suppressHydrationWarning>
      <body>{children}</body>
    </html>
  );
}
