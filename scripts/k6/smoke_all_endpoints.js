// README: run commands
// BASE_URL="https://snoutiq.com/backend" k6 run scripts/k6/smoke_all_endpoints.js
// AUTH_MODE=bearer EMAIL="you@example.com" PASSWORD="secret" k6 run scripts/k6/smoke_all_endpoints.js
// ALLOW_MUTATIONS=true PAYLOADS_FILE=path/to/payloads.json k6 run scripts/k6/smoke_all_endpoints.js

import http from "k6/http";
import { check, sleep } from "k6";
import { SharedArray } from "k6/data";
import { Counter, Trend } from "k6/metrics";

const BASE_URL = (__ENV.BASE_URL || "https://snoutiq.com/backend").replace(/\/$/, "");
const AUTH_MODE = (__ENV.AUTH_MODE || "none").toLowerCase();
const LOGIN_URL = __ENV.LOGIN_URL || "/api/login";
const EMAIL = __ENV.EMAIL;
const PASSWORD = __ENV.PASSWORD;
const AUTH_ROLE = __ENV.AUTH_ROLE || null;
const ALLOW_MUTATIONS = (__ENV.ALLOW_MUTATIONS || "false").toLowerCase() === "true";
const PAYLOADS_FILE = __ENV.PAYLOADS_FILE;
const PAYLOADS_JSON = __ENV.PAYLOADS_JSON;
const PARAMS_FILE = __ENV.PARAMS_FILE;
const PARAMS_JSON = __ENV.PARAMS_JSON;
const ROUTES_FILE = __ENV.ROUTES_FILE || "scripts/k6/routes.json";
const SKIP_PARAM_ROUTES = (__ENV.SKIP_PARAM_ROUTES || "false").toLowerCase() === "true";

const SAFE_METHODS = ["GET", "HEAD"];
const MUTATING_METHODS = ["POST", "PUT", "PATCH", "DELETE"];

const routes = new SharedArray("routes", () => loadJson(ROUTES_FILE, []));
const payloadTemplates = new SharedArray("payloadTemplates", () => [loadPayloads()]);
const paramsMap = new SharedArray("paramsMap", () => [loadParams()]);

const testedRoutes = new Counter("tested_routes");
const skippedRoutes = new Counter("skipped_routes");
const passedRoutes = new Counter("passed_routes");
const failedRoutes = new Counter("failed_routes");
const routeDuration = new Trend("route_duration", true);

const summary = {
  totalRoutes: routes.length,
  tested: 0,
  passed: 0,
  failed: 0,
  skipped: [],
  failures: [],
  slow: [],
};

export const options = {
  scenarios: {
    smoke: {
      executor: "shared-iterations",
      vus: 1,
      iterations: 1,
    },
  },
};

export function setup() {
  if (AUTH_MODE !== "bearer") return { token: null, loginStatus: null, loginFailed: false };
  if (!EMAIL || !PASSWORD) {
    console.error("AUTH_MODE=bearer set but EMAIL or PASSWORD missing; running without auth.");
    return { token: null, loginStatus: null, loginFailed: true };
  }

  const url = buildUrl(LOGIN_URL);
  const loginBody = { email: EMAIL, password: PASSWORD };
  if (AUTH_ROLE) loginBody.role = AUTH_ROLE;

  const payload = JSON.stringify(loginBody);
  const res = http.post(url, payload, {
    headers: { "Content-Type": "application/json" },
    timeout: "10s",
  });

  const token = extractToken(res);
  if (!token) {
    console.error(`Login failed (status ${res.status}). Continuing without auth.`);
  }

  return {
    token,
    loginStatus: res.status,
    loginFailed: !token,
  };
}

function loadJson(path, fallback) {
  try {
    return JSON.parse(open(path));
  } catch (err) {
    console.error(`Failed to load JSON from ${path}: ${err}`);
    return fallback;
  }
}

function loadPayloads() {
  if (PAYLOADS_JSON) {
    try {
      return JSON.parse(PAYLOADS_JSON);
    } catch (err) {
      console.error(`Failed to parse PAYLOADS_JSON: ${err}`);
    }
  }
  if (PAYLOADS_FILE) {
    return loadJson(PAYLOADS_FILE, {});
  }
  return {};
}

function loadParams() {
  if (PARAMS_JSON) {
    try {
      return JSON.parse(PARAMS_JSON);
    } catch (err) {
      console.error(`Failed to parse PARAMS_JSON: ${err}`);
    }
  }
  if (PARAMS_FILE) {
    return loadJson(PARAMS_FILE, {});
  }
  return {};
}

function selectMethod(methods = []) {
  const upper = methods.map((m) => String(m).toUpperCase());
  const safe = SAFE_METHODS.find((m) => upper.includes(m));
  if (safe) return safe;
  const mutating = MUTATING_METHODS.find((m) => upper.includes(m));
  return mutating || upper[0] || null;
}

function shouldSkipMutation(method) {
  return MUTATING_METHODS.includes(method) && !ALLOW_MUTATIONS;
}

function getPayloadFor(route, method) {
  const templates = payloadTemplates[0] || {};
  return templates[`${method} ${route.uri}`] || null;
}

function buildUrl(uri = "") {
  const normalized = uri.startsWith("/") ? uri : `/${uri}`;
  return `${BASE_URL}${normalized}`;
}

function fillPathParams(uri = "") {
  return uri.replace(/{([^}]+)}/g, (_, key) => valueForParam(key));
}

function valueForParam(key) {
  const params = paramsMap[0] || {};
  if (Object.prototype.hasOwnProperty.call(params, key)) {
    return params[key];
  }
  const lower = String(key).toLowerCase();
  if (lower.includes("uuid")) return "00000000-0000-0000-0000-000000000001";
  if (lower.includes("slug")) return "test";
  if (lower.includes("token")) return "sample-token";
  if (lower.includes("email")) return "test@example.com";
  if (lower.includes("phone")) return "1234567890";
  return "1";
}

function hasParams(uri = "") {
  return uri.includes("{") && uri.includes("}");
}

function isAuthMiddleware(route = {}) {
  const mids = Array.isArray(route.middleware) ? route.middleware : route.middleware ? [route.middleware] : [];
  return mids.some((m) => {
    const lower = String(m).toLowerCase();
    return lower.includes("auth") || lower.includes("token");
  });
}

function extractToken(res) {
  try {
    const body = res.json();
    if (body?.token) return body.token;
    if (body?.access_token) return body.access_token;
    if (body?.data?.token) return body.data.token;
  } catch (err) {
    // Non-JSON response; ignore.
  }
  return null;
}

function isSuccessStatus(status, method) {
  if (status >= 200 && status < 400) return true;
  if ((method === "GET" || method === "HEAD") && status === 422) return true;
  return false;
}

export default function main(data) {
  const baseHeaders = {};
  if (data?.token) {
    baseHeaders.Authorization = `Bearer ${data.token}`;
  }

  for (const route of routes) {
    const method = selectMethod(route.methods);
    if (!method) {
      summary.skipped.push({ uri: route.uri, reason: "no_method" });
      skippedRoutes.add(1, { reason: "no_method" });
      continue;
    }

    if (SKIP_PARAM_ROUTES && hasParams(route.uri)) {
      summary.skipped.push({ uri: route.uri, method, reason: "param_route_skipped" });
      skippedRoutes.add(1, { reason: "param_route_skipped" });
      continue;
    }

    if (shouldSkipMutation(method)) {
      summary.skipped.push({ uri: route.uri, method, reason: "mutation_disabled" });
      skippedRoutes.add(1, { reason: "mutation_disabled" });
      continue;
    }

    const isMutation = MUTATING_METHODS.includes(method);
    let body = null;
    if (isMutation) {
      const payload = getPayloadFor(route, method);
      if (!payload) {
        summary.skipped.push({ uri: route.uri, method, reason: "payload_missing" });
        skippedRoutes.add(1, { reason: "payload_missing" });
        continue;
      }
      body = JSON.stringify(payload);
    }

    const uriWithParams = fillPathParams(route.uri);
    const url = buildUrl(uriWithParams);

    const headers = { ...baseHeaders };
    if (isMutation) {
      headers["Content-Type"] = headers["Content-Type"] || "application/json";
    }

    const res = http.request(method, url, body, {
      headers,
      timeout: "10s",
    });
    const status = res.status || 0;
    const isAuthStatus = status === 401 || status === 403;

    summary.tested += 1;
    testedRoutes.add(1);

    if (res.timings?.duration > 1500) {
      summary.slow.push({ method, url, duration: res.timings.duration });
    }
    if (res.timings?.duration) {
      routeDuration.add(res.timings.duration, {
        endpoint: `${method} ${route.uri}`,
      });
    }

    if (isAuthStatus && (!data?.token || data?.loginFailed)) {
      summary.skipped.push({ uri: route.uri, method, reason: "auth_required" });
      skippedRoutes.add(1, { reason: "auth_required" });
      continue;
    }

    const ok = isSuccessStatus(status, method);
    check(res, {
      [`${method} ${route.uri} => ${status}`]: () => ok,
    });

    if (ok) {
      summary.passed += 1;
      passedRoutes.add(1);
    } else {
      summary.failed += 1;
      failedRoutes.add(1);
      summary.failures.push({
        method,
        url,
        status,
        reason: isAuthStatus ? "auth_failed" : "http_error",
      });
    }

    sleep(0.1);
  }
}

export function handleSummary(data) {
  const total = routes.length;
  const metrics = data?.metrics || {};
  const tested = metrics.tested_routes?.values?.count || summary.tested;
  const skippedCount = metrics.skipped_routes?.values?.count || summary.skipped.length;
  const passed = metrics.passed_routes?.values?.count || summary.passed;
  const failed = metrics.failed_routes?.values?.count || summary.failed;

  const failuresFromChecks = [];
  const rootChecks = data?.root_group?.checks || [];
  rootChecks.forEach((chk) => {
    if (chk.passes === 0 && chk.fails > 0) {
      failuresFromChecks.push({ name: chk.name });
    }
  });

  const slowTop = summary.slow.sort((a, b) => b.duration - a.duration).slice(0, 10);

  const lines = [
    "---- API Smoke Summary ----",
    `Total routes: ${total}`,
    `Tested: ${tested}`,
    `Passed: ${passed}`,
    `Failed: ${failed}`,
    `Skipped: ${skippedCount}`,
  ];

  const failureList = summary.failures.length ? summary.failures : failuresFromChecks;
  if (failureList.length) {
    lines.push("Failures:");
    failureList.forEach((f) => {
      if (f.method && f.url) {
        lines.push(`  - ${f.status || ""} ${f.method} ${f.url} (${f.reason || "http_error"})`);
      } else {
        lines.push(`  - ${f.name}`);
      }
    });
  }

  if (summary.skipped.length) {
    lines.push("Skipped (reason):");
    summary.skipped.forEach((s) => {
      lines.push(`  - ${s.method || ""} ${s.uri} => ${s.reason}`);
    });
  }

  if (slowTop.length) {
    lines.push("Top slow endpoints (>1500ms):");
    slowTop.forEach((s) => {
      lines.push(`  - ${s.duration.toFixed(1)}ms ${s.method} ${s.url}`);
    });
  }

  return {
    stdout: lines.join("\n") + "\n",
    "scripts/k6/failures.json": JSON.stringify(summary.failures, null, 2),
  };
}
