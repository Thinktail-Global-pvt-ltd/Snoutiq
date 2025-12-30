#!/usr/bin/env node
/**
 * Export Laravel API routes to scripts/k6/routes.json
 *
 * Usage:
 *   ROUTE_LIST_CMD="php artisan route:list --json" node scripts/k6/export_routes.js
 *
 * Notes:
 * - Runs the route:list command from the backend/ directory.
 * - Keeps only API routes (uri starts with api/ or middleware contains "api").
 * - Skips known debug/internal routes.
 */
import { execSync } from "child_process";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const repoRoot = path.resolve(__dirname, "..", "..");
const backendDir = path.join(repoRoot, "backend");
const outputPath = path.resolve(
  process.env.ROUTES_OUT || path.join(__dirname, "routes.json")
);

const ROUTE_LIST_CMD = process.env.ROUTE_LIST_CMD || "php artisan route:list --json";

const debugSkipPatterns = [
  /^telescope/i,
  /^horizon/i,
  /^_ignition/i,
  /^up$/i,
  /^debug/i,
  /^dev\//i,
  /^test/i,
  /^broadcasting\//i,
  /^sanctum\//i,
  /^storage\//i,
];

function loadRoutes() {
  try {
    const raw = execSync(ROUTE_LIST_CMD, {
      cwd: backendDir,
      stdio: ["ignore", "pipe", "inherit"],
      encoding: "utf8",
    });
    return JSON.parse(raw);
  } catch (err) {
    console.error("Failed to run route:list. Set ROUTE_LIST_CMD if PHP is elsewhere.", err?.message || err);
    process.exit(1);
  }
}

function isApiRoute(route) {
  const uri = route?.uri || "";
  if (!uri) return false;

  const cleaned = uri.replace(/^\//, "");
  const middleware = Array.isArray(route?.middleware)
    ? route.middleware
    : route?.middleware
    ? [route.middleware]
    : [];

  const hasApiMiddleware = middleware.some((m) => String(m).toLowerCase().includes("api"));
  const isApiUri =
    cleaned.startsWith("api/") ||
    cleaned.startsWith("backend/api") ||
    cleaned === "api" ||
    cleaned === "backend/api";

  const hitsDebug = debugSkipPatterns.some((re) => re.test(cleaned));

  return !hitsDebug && (isApiUri || hasApiMiddleware);
}

function normalizeRoute(route) {
  const uri = `/${(route.uri || "").replace(/^\/+/, "")}`;
  const middleware = Array.isArray(route?.middleware)
    ? route.middleware
    : route?.middleware
    ? [route.middleware]
    : [];

  const methods = String(route?.method || "")
    .split("|")
    .filter(Boolean);

  return {
    methods,
    uri,
    name: route?.name || null,
    middleware,
    domain: route?.domain || null,
  };
}

function main() {
  const routes = loadRoutes();
  const apiRoutes = routes.filter(isApiRoute).map(normalizeRoute);

  apiRoutes.sort((a, b) => a.uri.localeCompare(b.uri) || (a.name || "").localeCompare(b.name || ""));

  fs.writeFileSync(outputPath, JSON.stringify(apiRoutes, null, 2));
  console.log(`Wrote ${apiRoutes.length} routes to ${outputPath}`);
}

main();
