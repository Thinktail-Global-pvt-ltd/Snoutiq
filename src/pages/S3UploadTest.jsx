import React, { useRef, useState } from "react";
import axios from "axios";

const BACKEND_BASE_URL =
  import.meta.env.VITE_BACKEND_BASE_URL || "http://127.0.0.1:8000";

const S3UploadTest = () => {
  const fileRef = useRef(null);
  const [status, setStatus] = useState("idle");
  const [message, setMessage] = useState("");
  const [url, setUrl] = useState("");

  const handleUpload = async (event) => {
    event.preventDefault();
    const file = fileRef.current?.files?.[0];
    if (!file) {
      setMessage("Please pick a file before uploading.");
      return;
    }

    const formData = new FormData();
    formData.append("recording", file);
    formData.append("call_id", "s3-test");

    setStatus("uploading");
    setMessage("");
    setUrl("");

    try {
      const res = await axios.post(
        "https://snoutiq.com/backend/api/call-recordings/upload",
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );

      setStatus("done");
      setUrl(res.data.url);
      setMessage(`Uploaded to S3 path ${res.data.path}`);
    } catch (error) {
      console.error(error);
      const responseError = error?.response?.data?.message || error?.response?.data?.error;
      const fallbackError = responseError || error?.message || "Upload failed.";
      setStatus("error");
      setMessage(fallbackError);
    }
  };

  return (
    <div
      style={{
        minHeight: "100vh",
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        background: "#0f172a",
        color: "#fff",
        padding: 24,
      }}
    >
      <form
        onSubmit={handleUpload}
        style={{
          background: "#111827",
          borderRadius: 24,
          padding: 28,
          width: "100%",
          maxWidth: 480,
          boxShadow: "0 24px 50px rgba(15,23,42,0.55)",
          display: "flex",
          flexDirection: "column",
          gap: 16,
        }}
      >
        <h2>S3 Upload Test</h2>
        <p style={{ color: "#96a3b6" }}>
          Use the same upload endpoint as the call page. The response shows you the bucket path/URL.
        </p>
        <input
          ref={fileRef}
          type="file"
          accept="*"
          style={{
            padding: "12px 16px",
            borderRadius: 12,
            border: "1px solid rgba(255,255,255,0.2)",
            background: "rgba(255,255,255,0.04)",
            color: "#fff",
          }}
        />
        <button
          type="submit"
          style={{
            padding: "12px 18px",
            borderRadius: 12,
            border: "none",
            background: "linear-gradient(135deg,#22c55e,#10b981)",
            color: "#fff",
            fontWeight: 600,
            cursor: "pointer",
          }}
        >
          Upload to S3
        </button>
        <div>
          <p>Status: {status}</p>
          {message && <p>{message}</p>}
          {url && (
            <a href={url} target="_blank" rel="noreferrer" style={{ color: "#38bdf8" }}>
              Open stored file
            </a>
          )}
        </div>
      </form>
    </div>
  );
};

export default S3UploadTest;
