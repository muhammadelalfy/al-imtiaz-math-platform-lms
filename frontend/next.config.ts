import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  poweredByHeader: false,
  allowedDevOrigins: [
    "127.0.0.1",
    "3001-ix5gynalwzb8tef71u0ao-2778324b.us3.manus.computer",
  ],
  async rewrites() {
    const laravelOrigin =
      process.env.LARAVEL_INTERNAL_ORIGIN ?? "http://127.0.0.1:5173";

    return [
      {
        source: "/api/:path*",
        destination: `${laravelOrigin}/api/:path*`,
      },
    ];
  },
};

export default nextConfig;
