export type PortalRoute = "teacher" | "parent" | "student" | "super_admin";

export function portalForPath(pathname: string): PortalRoute {
  if (pathname === "/control/login") return "super_admin";
  if (pathname === "/teacher/login") return "teacher";
  if (pathname === "/parent/login") return "parent";
  if (pathname === "/student/login") return "student";

  return "teacher";
}
