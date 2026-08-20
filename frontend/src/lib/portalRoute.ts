export type PortalRoute = "admin" | "parent" | "student";

export function portalForPath(pathname: string): PortalRoute {
  if (pathname === "/parent/login") return "parent";
  if (pathname === "/student/login") return "student";

  return "admin";
}
