import React, { useContext, useEffect, useState } from "react";
import { AuthContext } from "../auth/AuthContext";

const steps = [
  { id: 0, label: "Documents KYC" },
];

const KYC_STATUS_LABELS = {
  NOT_STARTED: "Not Started",
  IN_REVIEW: "In Review",
  VERIFIED: "Verified",
  REJECTED: "Rejected",
};

// 👇 yaha apna correct backend base path daalna (license_document ke liye)
const LICENSE_PREVIEW_BASE_URL = "https://snoutiq.com/backend/";
// agar already full URL aa raha ho to isko "" bhi rakh sakta hai

const ClinicKyc = () => {
  const { user } = useContext(AuthContext);

  const [currentStep, setCurrentStep] = useState(0);
  const [kycStatus, setKycStatus] = useState("NOT_STARTED");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [apiMessage, setApiMessage] = useState("");

  // ================== DOCUMENT STATE: SIRF DOCS ==================
  const [documents, setDocuments] = useState({
    aadhaarFront: {
      label: "Aadhaar Card (Front)",
      status: "PENDING",
      file: null,
      previewUrl: null,
      required: true,
    },
    aadhaarBack: {
      label: "Aadhaar Card (Back)",
      status: "PENDING",
      file: null,
      previewUrl: null,
      required: true,
    },
    panCard: {
      label: "PAN Card",
      status: "PENDING",
      file: null,
      previewUrl: null,
      required: true,
    },
    bankProof: {
      label: "Bank Proof (Cancelled Cheque / Passbook)",
      status: "PENDING",
      file: null,
      previewUrl: null,
      required: true,
    },
    gstCertificate: {
      label: "GST Certificate",
      status: "PENDING",
      file: null,
      previewUrl: null,
      required: false, // optional if GST not registered
    },
    clinicLicense: {
      label: "Clinic Registration / Licence",
      status: "PENDING",
      file: null,
      previewUrl: null,
      required: true,
    },
  });

  // ================== PREFILL FROM USER (LICENSE) ==================
  useEffect(() => {
    if (!user) return;
    console.log("Clinic user:", user);

    if (user.license_document) {
      const fullUrl = user.license_document.startsWith("http")
        ? user.license_document
        : `${LICENSE_PREVIEW_BASE_URL}${user.license_document}`;

      setDocuments((prev) => ({
        ...prev,
        clinicLicense: {
          ...prev.clinicLicense,
          status: "VERIFIED",
          previewUrl: fullUrl,
          file: null,
        },
      }));

      setKycStatus((prevStatus) =>
        prevStatus === "NOT_STARTED" ? "IN_REVIEW" : prevStatus
      );
    }
  }, [user]);

  // ================== HANDLERS ==================
  const handleFileChange = (key, e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const previewUrl = file.type.startsWith("image/")
      ? URL.createObjectURL(file)
      : null;

    setDocuments((prev) => ({
      ...prev,
      [key]: {
        ...prev[key],
        file,
        previewUrl,
        status: prev[key].status || "PENDING",
      },
    }));
  };

  const handleDocStatusChange = (key, value) => {
    setDocuments((prev) => ({
      ...prev,
      [key]: {
        ...prev[key],
        status: value,
      },
    }));
  };

  const handleSubmit = async () => {
    setIsSubmitting(true);
    setApiMessage("");
    setKycStatus("IN_REVIEW");

    try {
      const payload = new FormData();

      // clinic id
      if (user?.clinic_id || user?.id) {
        payload.append("clinic_id", user.clinic_id || user.id);
      }

      // sare docs + status
      Object.entries(documents).forEach(([key, doc]) => {
        if (doc.file) {
          payload.append(key, doc.file);
        }
        payload.append(`${key}_status`, doc.status);
      });

      // 🔹 Real API call yaha:
      // const res = await fetch("https://snoutiq.com/backend/api/clinic/kyc-documents", {
      //   method: "POST",
      //   body: payload,
      //   credentials: "include",
      // });
      // const data = await res.json();
      // console.log("KYC DOC response:", data);

      await new Promise((resolve) => setTimeout(resolve, 1200));
      console.log("KYC DOC payload sent", payload);

      setApiMessage("Document KYC submitted successfully. It is now under review.");
      // Backend se VERIFIED aaye to:
      // setKycStatus("VERIFIED");
    } catch (err) {
      console.error(err);
      setApiMessage("Something went wrong while submitting document KYC.");
      setKycStatus("REJECTED");
    } finally {
      setIsSubmitting(false);
    }
  };

  const kycBadgeClass = (() => {
    switch (kycStatus) {
      case "VERIFIED":
        return "bg-emerald-100 text-emerald-700 border border-emerald-200";
      case "IN_REVIEW":
        return "bg-yellow-100 text-yellow-700 border border-yellow-200";
      case "REJECTED":
        return "bg-red-100 text-red-700 border border-red-200";
      default:
        return "bg-gray-100 text-gray-600 border border-gray-200";
    }
  })();

  // ================== STEP RENDER (SINGLE STEP) ==================
  const renderStep = () => {
    return (
      <div className="space-y-4">
        {Object.entries(documents).map(([key, doc]) => (
          <div
            key={key}
            className="flex flex-col md:flex-row md:items-center justify-between gap-4 border rounded-lg p-3"
          >
            <div className="flex-1">
              <p className="text-sm font-medium text-gray-800 flex items-center gap-1">
                {doc.label}
                {doc.required && (
                  <span className="text-red-500 text-xs font-semibold">*</span>
                )}
              </p>
              <p className="text-xs text-gray-500">
                Upload clear, readable copy. JPG, PNG or PDF.
              </p>

              <div className="mt-2 flex flex-wrap items-center gap-3">
                <label className="inline-flex items-center px-3 py-1.5 bg-gray-50 border border-dashed border-gray-300 rounded-md text-xs font-medium text-gray-700 cursor-pointer hover:bg-gray-100">
                  <span>Choose file</span>
                  <input
                    type="file"
                    className="hidden"
                    onChange={(e) => handleFileChange(key, e)}
                    accept="image/*,.pdf"
                  />
                </label>

                {doc.file && (
                  <span className="text-xs text-gray-600 truncate max-w-[200px]">
                    {doc.file.name}
                  </span>
                )}

                {doc.previewUrl && (
                  <img
                    src={doc.previewUrl}
                    alt="preview"
                    className="h-12 w-12 rounded object-cover border"
                  />
                )}
              </div>
            </div>

            <div className="flex flex-col items-start md:items-end gap-2">
              <select
                value={doc.status}
                onChange={(e) => handleDocStatusChange(key, e.target.value)}
                className="text-xs border rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="PENDING">PENDING</option>
                <option value="VERIFIED">VERIFIED</option>
                <option value="REJECTED">REJECTED</option>
              </select>

              <span
                className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                  ${
                    doc.status === "VERIFIED"
                      ? "bg-emerald-100 text-emerald-700"
                      : doc.status === "REJECTED"
                      ? "bg-red-100 text-red-700"
                      : "bg-yellow-100 text-yellow-700"
                  }
                `}
              >
                {doc.status}
              </span>
            </div>
          </div>
        ))}
      </div>
    );
  };

  // ================== MAIN RENDER ==================
  return (
    <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
      <div className="max-w-5xl mx-auto">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Clinic Document KYC</h1>
            <p className="text-sm text-gray-500">
              Upload mandatory documents for clinic verification & payouts.
            </p>
          </div>

          <div
            className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${kycBadgeClass}`}
          >
            <span
              className={`w-2 h-2 rounded-full mr-2 ${
                kycStatus === "VERIFIED"
                  ? "bg-emerald-500"
                  : kycStatus === "IN_REVIEW"
                  ? "bg-yellow-500"
                  : kycStatus === "REJECTED"
                  ? "bg-red-500"
                  : "bg-gray-400"
              }`}
            />
            {KYC_STATUS_LABELS[kycStatus]}
          </div>
        </div>

        {/* Card */}
        <div className="bg-white shadow-sm rounded-xl border border-gray-100">
          {/* Stepper (single step but future-safe) */}
          <div className="border-b border-gray-100 px-4 sm:px-6 py-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2 text-sm text-gray-600">
                {steps.map((step) => {
                  const isActive = currentStep === step.id;
                  return (
                    <div key={step.id} className="flex items-center">
                      <div
                        className={`w-7 h-7 flex items-center justify-center rounded-full text-xs font-semibold
                          ${
                            isActive
                              ? "bg-blue-600 text-white"
                              : "bg-gray-100 text-gray-500"
                          }
                        `}
                      >
                        {step.id + 1}
                      </div>
                      <span
                        className={`ml-2 text-xs sm:text-sm ${
                          isActive
                            ? "text-blue-700 font-medium"
                            : "text-gray-600"
                        }`}
                      >
                        {step.label}
                      </span>
                    </div>
                  );
                })}
              </div>

              <p className="text-xs text-gray-400">
                Step {currentStep + 1} of {steps.length}
              </p>
            </div>
          </div>

          {/* Content */}
          <div className="px-4 sm:px-6 py-6">{renderStep()}</div>

          {/* Footer */}
          <div className="border-t border-gray-100 px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div className="text-xs text-gray-400">
              All documents are encrypted and securely stored.
            </div>
            <div className="flex justify-end gap-2">
              <button
                type="button"
                disabled={isSubmitting}
                onClick={handleSubmit}
                className={`px-4 py-1.5 text-sm rounded-lg text-white ${
                  isSubmitting
                    ? "bg-blue-400 cursor-not-allowed"
                    : "bg-blue-600 hover:bg-blue-700"
                }`}
              >
                {isSubmitting ? "Submitting..." : "Submit Document KYC"}
              </button>
            </div>
          </div>
        </div>

        {apiMessage && (
          <div className="mt-4 text-sm text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-4 py-2">
            {apiMessage}
          </div>
        )}
      </div>
    </div>
  );
};

export default ClinicKyc;
