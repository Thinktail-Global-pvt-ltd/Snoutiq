import React, { useRef, useState } from "react";
import axios from "axios";

const BACKEND_BASE_URL =
  import.meta.env.VITE_BACKEND_BASE_URL || "https://snoutiq.com/backend";

const CsvUploadPage = () => {
  const fileRef = useRef(null);
  const [uploading, setUploading] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState("");

  const handleSubmit = async (event) => {
    event.preventDefault();
    const file = fileRef.current?.files?.[0];

    if (!file) {
      setError("Pick a CSV file to upload.");
      setResult(null);
      return;
    }

    setUploading(true);
    setError("");
    setResult(null);

    const formData = new FormData();
    formData.append("file", file);

    try {
      const response = await axios.post(
        `${BACKEND_BASE_URL}/api/csv/upload`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );

      setResult(response.data);
    } catch (err) {
      const message =
        err?.response?.data?.message ||
        err?.response?.data?.error ||
        err?.message ||
        "Upload failed.";
      setError(message);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 text-white flex items-center justify-center px-4">
      <form
        onSubmit={handleSubmit}
        className="w-full max-w-xl rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow-2xl backdrop-blur-sm"
      >
        <div className="mb-5">
          <p className="text-2xl font-semibold">Upload CSV</p>
          <p className="text-sm text-slate-400 mt-1">
            Choose a .csv file and we&apos;ll stash it on the server under{" "}
            <code className="text-slate-200">storage/app/csv-uploads</code>.
          </p>
        </div>

        <div className="flex flex-col gap-3">
          <label
            className="text-sm font-medium text-slate-200"
            htmlFor="csv-input"
          >
            CSV file
          </label>
          <input
            id="csv-input"
            ref={fileRef}
            type="file"
            accept=".csv,text/csv"
            className="w-full rounded-lg border border-slate-700 bg-slate-800/60 px-4 py-3 text-sm text-white file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-500 file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <button
          type="submit"
          disabled={uploading}
          className="mt-6 inline-flex items-center justify-center rounded-lg bg-emerald-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-70"
        >
          {uploading ? "Uploading..." : "Upload CSV"}
        </button>

        <div className="mt-6 space-y-2 text-sm text-slate-200">
          {error && <p className="text-red-400">{error}</p>}
          {result && (
            <>
              <p className="font-medium text-emerald-400">Saved!</p>
              <p>Path: {result.path}</p>
              {result.absolute_path && (
                <p>Server path: {result.absolute_path}</p>
              )}
              {result.original_name && <p>Original name: {result.original_name}</p>}
              {typeof result.size_bytes === "number" && (
                <p>Size: {(result.size_bytes / 1024).toFixed(1)} KB</p>
              )}
            </>
          )}
        </div>
      </form>
    </div>
  );
};

export default CsvUploadPage;
