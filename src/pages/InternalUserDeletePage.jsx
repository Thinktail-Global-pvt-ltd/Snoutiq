import React, { useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { apiBaseUrl } from "../lib/api";
import { loadVetAuth } from "../lib/vetAuth";

const INTERNAL_DELETE_ROUTE = "/__ops/company-user-archive-r4k9d2x";

const normalizePhone = (value) => {
  const digits = String(value || "").replace(/\D/g, "");
  if (!digits) return "";
  if (digits.length === 10) return `91${digits}`;
  if (digits.startsWith("91")) return digits;
  return digits;
};

const firstFilled = (...values) => {
  for (const value of values) {
    if (value === undefined || value === null) continue;
    if (typeof value === "string" && !value.trim()) continue;
    return value;
  }
  return "";
};

const extractUserRecord = (payload) => {
  if (!payload || typeof payload !== "object") return null;

  if (Array.isArray(payload)) return payload[0] || null;
  if (Array.isArray(payload?.data)) return payload.data[0] || null;
  if (payload?.user && typeof payload.user === "object") return payload.user;
  if (payload?.data && typeof payload.data === "object" && !Array.isArray(payload.data)) {
    return payload.data;
  }

  if ("id" in payload || "user_id" in payload || "phone" in payload) return payload;
  return null;
};

const safeStringify = (value) => {
  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return "";
  }
};

async function parseResponse(res) {
  const text = await res.text();
  if (!text) return { data: null, raw: "" };

  try {
    return { data: JSON.parse(text), raw: text };
  } catch {
    return { data: null, raw: text };
  }
}

export default function InternalUserDeletePage() {
  const navigate = useNavigate();
  const auth = useMemo(() => loadVetAuth(), []);
  const apiBase = useMemo(() => apiBaseUrl(), []);
  const authToken =
    auth?.token ||
    auth?.access_token ||
    auth?.doctor?.token ||
    auth?.doctor?.access_token ||
    "";

  const authLabel =
    firstFilled(
      auth?.doctor?.doctor_name,
      auth?.doctor_name,
      auth?.doctor?.name,
      auth?.email,
      auth?.phone
    ) || "Authenticated company user";

  const [phone, setPhone] = useState("");
  const [lookupLoading, setLookupLoading] = useState(false);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [error, setError] = useState("");
  const [status, setStatus] = useState("");
  const [userPayload, setUserPayload] = useState(null);
  const [userRecord, setUserRecord] = useState(null);
  const [deletePath, setDeletePath] = useState("");
  const [confirmText, setConfirmText] = useState("");

  const normalizedPhone = normalizePhone(phone);
  const userId = firstFilled(userRecord?.id, userRecord?.user_id, userRecord?.userId);
  const defaultDeletePath = userId ? `/api/users/${encodeURIComponent(userId)}` : "";
  const effectiveDeletePath = deletePath.trim() || defaultDeletePath;
  const confirmPhrase = normalizedPhone ? `DELETE ${normalizedPhone}` : "DELETE 91xxxxxxxxxx";
  const canDelete =
    Boolean(authToken) &&
    Boolean(userRecord) &&
    Boolean(effectiveDeletePath) &&
    confirmText.trim() === confirmPhrase;

  const authHeaders = (withBody = false) => {
    const headers = {
      Accept: "application/json",
      ...(withBody ? { "Content-Type": "application/json" } : {}),
    };

    if (authToken) {
      headers.Authorization = `Bearer ${authToken}`;
    }

    return headers;
  };

  const resetLookupState = () => {
    setUserPayload(null);
    setUserRecord(null);
    setDeletePath("");
    setConfirmText("");
  };

  const handleLookup = async () => {
    if (!authToken) {
      setError("Company login required before this tool can be used.");
      return;
    }

    if (!normalizedPhone) {
      setError("Enter a valid phone number first.");
      return;
    }

    setLookupLoading(true);
    setError("");
    setStatus("");
    resetLookupState();

    try {
      const res = await fetch(
        `${apiBase}/api/users/by-phone?phone=${encodeURIComponent(normalizedPhone)}`,
        {
          method: "GET",
          headers: authHeaders(),
        }
      );

      const { data, raw } = await parseResponse(res);
      if (!res.ok) {
        const message =
          data?.message || data?.error || raw || `Lookup failed with HTTP ${res.status}`;
        throw new Error(message);
      }

      const record = extractUserRecord(data);
      if (!record) {
        throw new Error("User record not found in lookup response.");
      }

      const nextUserId = firstFilled(record?.id, record?.user_id, record?.userId);
      setUserPayload(data ?? raw);
      setUserRecord(record);
      setDeletePath(nextUserId ? `/api/users/${encodeURIComponent(nextUserId)}` : "");
      setStatus("User found. Review details carefully before deleting.");
    } catch (lookupError) {
      setError(lookupError?.message || "Unable to fetch user by phone.");
    } finally {
      setLookupLoading(false);
    }
  };

  const attemptDelete = async (path, method, body) => {
    const res = await fetch(`${apiBase}${path}`, {
      method,
      headers: authHeaders(Boolean(body)),
      body: body ? JSON.stringify(body) : undefined,
    });

    const parsed = await parseResponse(res);
    return { ok: res.ok, status: res.status, ...parsed };
  };

  const handleDelete = async () => {
    if (!canDelete) return;

    setDeleteLoading(true);
    setError("");
    setStatus("");

    try {
      const primaryPath = effectiveDeletePath.startsWith("/")
        ? effectiveDeletePath
        : `/${effectiveDeletePath}`;

      let result = await attemptDelete(primaryPath, "DELETE");

      if (!result.ok && (result.status === 404 || result.status === 405)) {
        result = await attemptDelete(primaryPath, "POST", { _method: "DELETE" });
      }

      if (!result.ok) {
        const message =
          result.data?.message ||
          result.data?.error ||
          result.raw ||
          `Delete failed with HTTP ${result.status}`;
        throw new Error(message);
      }

      setStatus("User deleted successfully.");
      setUserPayload(result.data ?? result.raw);
      setConfirmText("");
    } catch (deleteError) {
      setError(deleteError?.message || "Unable to delete the selected user.");
    } finally {
      setDeleteLoading(false);
    }
  };

  if (!authToken) {
    return (
      <div className="min-h-screen bg-slate-100 px-4 py-10">
        <div className="mx-auto max-w-2xl rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
            Internal Tool
          </p>
          <h1 className="mt-3 text-2xl font-black text-slate-900">
            Company access required
          </h1>
          <p className="mt-3 text-sm leading-6 text-slate-600">
            This page is not linked publicly. It also requires an authenticated
            company or vet session before any user lookup or delete action can run.
          </p>
          <div className="mt-6 flex flex-wrap gap-3">
            <button
              type="button"
              onClick={() => navigate("/auth", { state: { from: INTERNAL_DELETE_ROUTE } })}
              className="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
            >
              Login First
            </button>
            <button
              type="button"
              onClick={() => navigate("/", { replace: true })}
              className="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
              Go Home
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top,_#eff6ff,_#f8fafc_50%,_#e2e8f0)] px-4 py-8 sm:px-6">
      <div className="mx-auto max-w-5xl">
        <div className="rounded-[28px] border border-slate-200 bg-white/95 p-6 shadow-[0_25px_80px_-45px_rgba(15,23,42,0.45)] sm:p-8">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">
                Internal User Ops
              </p>
              <h1 className="mt-2 text-2xl font-black text-slate-900 sm:text-3xl">
                Find user by phone and delete account
              </h1>
              <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Hidden route plus authenticated company session. Lookup uses the
                phone endpoint, then deletion runs against the resolved user id.
              </p>
            </div>

            <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
              Logged in as <span className="font-bold">{authLabel}</span>
            </div>
          </div>

          <div className="mt-8 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section className="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
              <h2 className="text-lg font-black text-slate-900">1. Lookup user</h2>
              <p className="mt-2 text-sm text-slate-600">
                Enter mobile number in India format. The tool normalizes `10-digit`
                input to `91xxxxxxxxxx`.
              </p>

              <label className="mt-5 block text-sm font-semibold text-slate-700">
                Phone number
              </label>
              <input
                type="text"
                value={phone}
                onChange={(event) => setPhone(event.target.value)}
                placeholder="9990830671 or 919990830671"
                className="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-200"
              />
              <p className="mt-2 text-xs text-slate-500">
                Normalized: <span className="font-semibold text-slate-800">{normalizedPhone || "-"}</span>
              </p>

              <div className="mt-5 flex flex-wrap gap-3">
                <button
                  type="button"
                  onClick={handleLookup}
                  disabled={lookupLoading}
                  className="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                >
                  {lookupLoading ? "Looking up..." : "Lookup user"}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setPhone("");
                    setError("");
                    setStatus("");
                    resetLookupState();
                  }}
                  className="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                  Reset
                </button>
              </div>

              {error ? (
                <div className="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                  {error}
                </div>
              ) : null}

              {status ? (
                <div className="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                  {status}
                </div>
              ) : null}
            </section>

            <section className="rounded-[24px] border border-slate-200 bg-white p-5">
              <h2 className="text-lg font-black text-slate-900">2. Review user</h2>
              {userRecord ? (
                <div className="mt-4 space-y-3 text-sm text-slate-700">
                  <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div className="grid gap-3 sm:grid-cols-2">
                      <div>
                        <p className="text-xs uppercase tracking-wide text-slate-500">User ID</p>
                        <p className="mt-1 font-semibold text-slate-900">{String(userId || "-")}</p>
                      </div>
                      <div>
                        <p className="text-xs uppercase tracking-wide text-slate-500">Phone</p>
                        <p className="mt-1 font-semibold text-slate-900">
                          {String(firstFilled(userRecord?.phone, userRecord?.mobile, normalizedPhone) || "-")}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs uppercase tracking-wide text-slate-500">Name</p>
                        <p className="mt-1 font-semibold text-slate-900">
                          {String(firstFilled(userRecord?.name, userRecord?.full_name, userRecord?.owner_name) || "-")}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs uppercase tracking-wide text-slate-500">Email</p>
                        <p className="mt-1 font-semibold text-slate-900">
                          {String(firstFilled(userRecord?.email, userRecord?.owner_email) || "-")}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-slate-700">
                      Delete API path
                    </label>
                    <input
                      type="text"
                      value={deletePath}
                      onChange={(event) => setDeletePath(event.target.value)}
                      placeholder="/api/users/123"
                      className="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-200"
                    />
                    <p className="mt-2 text-xs text-slate-500">
                      Default path is resolved from the looked-up user id. Keep it
                      unchanged unless your backend uses a different delete route.
                    </p>
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-slate-700">
                      Type exact confirmation phrase
                    </label>
                    <input
                      type="text"
                      value={confirmText}
                      onChange={(event) => setConfirmText(event.target.value)}
                      placeholder={confirmPhrase}
                      className="mt-2 w-full rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-100"
                    />
                    <p className="mt-2 text-xs text-slate-500">
                      Required phrase: <span className="font-semibold text-slate-900">{confirmPhrase}</span>
                    </p>
                  </div>

                  <button
                    type="button"
                    onClick={handleDelete}
                    disabled={!canDelete || deleteLoading}
                    className="w-full rounded-2xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-rose-300"
                  >
                    {deleteLoading ? "Deleting user..." : "Delete user permanently"}
                  </button>
                </div>
              ) : (
                <div className="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                  Run a phone lookup first. User details and delete controls will appear here.
                </div>
              )}
            </section>
          </div>

          <section className="mt-6 rounded-[24px] border border-slate-200 bg-slate-950 p-5 text-slate-100">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <h2 className="text-lg font-black">Response preview</h2>
              <span className="rounded-full border border-slate-700 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-300">
                {effectiveDeletePath || "/api/users/:id"}
              </span>
            </div>
            <pre className="mt-4 max-h-[420px] overflow-auto rounded-2xl bg-black/30 p-4 text-xs leading-6 text-slate-200">
              {safeStringify(userPayload || userRecord || { message: "No payload loaded yet." })}
            </pre>
          </section>
        </div>
      </div>
    </div>
  );
}
