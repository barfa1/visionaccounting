import { defineConfig, Plugin } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => ({
  server: {
    host: "::",
    port: 8080,
    // Dev proxy: if you run a local PHP dev server (php -S localhost:8000 -t server/php),
    // Vite will forward PHP endpoint requests to it so the client can call /enrollment.php etc.
    proxy: {
      "/enrollment.php": { target: "http://localhost:8000", changeOrigin: true, secure: false },
      "/enrollment_mail.php": { target: "http://localhost:8000", changeOrigin: true, secure: false },
      "/enrollments.php": { target: "http://localhost:8000", changeOrigin: true, secure: false },
      "/enrollments_download.php": { target: "http://localhost:8000", changeOrigin: true, secure: false },
      "/contact.php": { target: "http://localhost:8000", changeOrigin: true, secure: false },
      "/messages_download.php": { target: "http://localhost:8000", changeOrigin: true, secure: false }
    }
  },
  build: {
    outDir: "dist/spa",
  },
  plugins: [react()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./client"),
      "@shared": path.resolve(__dirname, "./shared"),
    },
  },
}));
