import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  // Load .env / .env.local / .env.[mode] entries for use inside this config file.
  // (import.meta.env is NOT available here — it only exists in client source code.)
  // Prefix '' = load ALL entries, including non-VITE_ ones like FRONTEND_PORT.
  // This object stays in Node — never expose it to client code directly.
  const env = loadEnv(mode, __dirname, "");

  return {
    plugins: [vue()],
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "./src"),
      },
    },
    server: {
      port: env.VITE_FRONTEND_PORT,
      proxy: {
        // NOTE: VITE_API_URL already includes the full backend origin
        // (e.g. http://localhost:5000/api), so the proxy target must be the
        // origin WITHOUT the /api suffix — otherwise requests become
        // http://localhost:5000/api/api/... and return 404/401.
        "/api": {
          target: env.VITE_API_URL.replace(/\/api\/?$/, ""),
          changeOrigin: true,
        },
      },
    },
  };
});
