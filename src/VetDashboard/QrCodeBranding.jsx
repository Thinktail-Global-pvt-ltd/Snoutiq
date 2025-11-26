import React, { useState } from "react";
import {
  Save,
  Upload,
  Image as ImageIcon,
  QrCode,
  Palette,
} from "lucide-react";

const PRESET_COLORS = ["#2563eb", "#0d9488", "#dc2626", "#9333ea", "#ea580c"];

const QrCodeBranding = () => {
  const [logoFile, setLogoFile] = useState(null);
  const [logoPreview, setLogoPreview] = useState(null);

  const [qrFile, setQrFile] = useState(null);
  const [qrPreview, setQrPreview] = useState(null);

  const [primaryColor, setPrimaryColor] = useState("#2563eb");
  const [customColor, setCustomColor] = useState("#2563eb");

  const [clinicName, setClinicName] = useState("Demo Clinic");
  const [tagline, setTagline] = useState("Scan to book a quick video consult");

  const [isSaving, setIsSaving] = useState(false);
  const [alert, setAlert] = useState({ type: "", message: "" });

  const handleLogoChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setLogoFile(file);
    if (file.type.startsWith("image/")) {
      setLogoPreview(URL.createObjectURL(file));
    } else {
      setLogoPreview(null);
    }
  };

  const handleQrChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setQrFile(file);
    if (file.type.startsWith("image/")) {
      setQrPreview(URL.createObjectURL(file));
    } else {
      setQrPreview(null);
    }
  };

  const handleSelectColor = (color) => {
    setPrimaryColor(color);
    setCustomColor(color);
  };

  const handleCustomColorChange = (e) => {
    const value = e.target.value;
    setCustomColor(value);
    setPrimaryColor(value || primaryColor);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSaving(true);
    setAlert({ type: "", message: "" });

    try {
      const formData = new FormData();
      if (logoFile) formData.append("logo", logoFile);
      if (qrFile) formData.append("qr_code", qrFile);
      formData.append("primary_color", primaryColor);
      formData.append("clinic_name", clinicName);
      formData.append("tagline", tagline);

      // 🔹 Yaha apna real API call lagana:
      // const res = await fetch("https://snoutiq.com/backend/api/clinic/branding", {
      //   method: "POST",
      //   body: formData,
      //   credentials: "include",
      // });
      // const data = await res.json();

      // Demo delay
      await new Promise((resolve) => setTimeout(resolve, 1000));
      console.log("Branding payload sent:", formData);

      setAlert({
        type: "success",
        message: "Branding settings saved (dummy). Connect to your API here.",
      });
    } catch (error) {
      console.error(error);
      setAlert({
        type: "error",
        message: "Failed to save branding. Please try again.",
      });
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
      <div className="max-w-5xl mx-auto space-y-6 animate-in fade-in duration-500">
        {/* Page header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              QR Code & Branding
            </h1>
            <p className="text-sm text-gray-500">
              Configure your clinic logo, QR code and brand colors shown on
              booking pages & printables.
            </p>
          </div>
        </div>

        {alert.message && (
          <div
            className={`rounded-lg px-4 py-3 text-sm border ${
              alert.type === "success"
                ? "bg-emerald-50 text-emerald-800 border-emerald-100"
                : "bg-red-50 text-red-800 border-red-100"
            }`}
          >
            {alert.message}
          </div>
        )}

        <form
          onSubmit={handleSubmit}
          className="bg-white p-6 sm:p-8 rounded-2xl border border-gray-100 shadow-sm space-y-8"
        >
          {/* Logo Section */}
          <div className="flex flex-col md:flex-row md:items-start gap-6">
            <div className="flex flex-col items-center gap-3">
              <div className="w-24 h-24 bg-gray-50 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300 overflow-hidden">
                {logoPreview ? (
                  <img
                    src={logoPreview}
                    alt="Clinic logo preview"
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <ImageIcon className="text-gray-400" size={32} />
                )}
              </div>
              <label className="text-xs text-gray-500 text-center">
                Recommended: Square logo, PNG/JPG
              </label>
            </div>

            <div className="flex-1">
              <h3 className="font-semibold text-gray-800 mb-1">Clinic Logo</h3>
              <p className="text-sm text-gray-500 mb-4">
                This logo will be visible on invoices, patient reminders and
                your booking page.
              </p>

              <label className="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium hover:bg-gray-50 cursor-pointer">
                <Upload size={16} />
                <span>Upload Logo</span>
                <input
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={handleLogoChange}
                />
              </label>

              {logoFile && (
                <p className="mt-2 text-xs text-gray-500">
                  Selected: <span className="font-medium">{logoFile.name}</span>
                </p>
              )}
            </div>
          </div>

          <hr className="border-gray-100" />

          {/* QR Code Section */}
          <div className="flex flex-col md:flex-row md:items-start gap-6">
            <div className="flex flex-col items-center gap-3">
              <div className="w-24 h-24 bg-gray-50 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300 overflow-hidden">
                {qrPreview ? (
                  <img
                    src={qrPreview}
                    alt="QR preview"
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <QrCode className="text-gray-400" size={32} />
                )}
              </div>
              <label className="text-xs text-gray-500 text-center">
                Static QR for booking / WhatsApp / website
              </label>
            </div>

            <div className="flex-1">
              <h3 className="font-semibold text-gray-800 mb-1">Clinic QR Code</h3>
              <p className="text-sm text-gray-500 mb-4">
                Upload a QR code that patients can scan to book appointments,
                open WhatsApp, or visit your microsite.
              </p>

              <label className="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium hover:bg-gray-50 cursor-pointer">
                <Upload size={16} />
                <span>Upload QR Code</span>
                <input
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={handleQrChange}
                />
              </label>

              {qrFile && (
                <p className="mt-2 text-xs text-gray-500">
                  Selected: <span className="font-medium">{qrFile.name}</span>
                </p>
              )}
            </div>
          </div>

          <hr className="border-gray-100" />

          {/* Clinic Name & Tagline */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">
                Clinic Name on Branding
              </label>
              <input
                type="text"
                value={clinicName}
                onChange={(e) => setClinicName(e.target.value)}
                className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:outline-none text-sm"
                placeholder="Snoutiq Veterinary Partners"
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">
                Tagline / Subtext
              </label>
              <input
                type="text"
                value={tagline}
                onChange={(e) => setTagline(e.target.value)}
                className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:outline-none text-sm"
                placeholder="Scan to book a quick appointment"
              />
            </div>
          </div>

          {/* Brand Colors */}
          <div>
            <div className="flex items-center gap-2 mb-4">
              <Palette size={18} className="text-gray-500" />
              <h3 className="font-semibold text-gray-800">Brand Color</h3>
            </div>

            <div className="flex flex-wrap gap-5 items-center">
              {PRESET_COLORS.map((color) => (
                <button
                  type="button"
                  key={color}
                  onClick={() => handleSelectColor(color)}
                  className="flex flex-col items-center gap-2 cursor-pointer group focus:outline-none"
                >
                  <span
                    className={`w-10 h-10 rounded-full shadow-sm ring-2 ${
                      primaryColor === color
                        ? "ring-offset-2 ring-blue-500"
                        : "ring-transparent group-hover:ring-gray-300 group-hover:ring-offset-2"
                    } transition-all`}
                    style={{ backgroundColor: color }}
                  ></span>
                  <span className="text-[11px] text-gray-500 font-mono">
                    {color}
                  </span>
                </button>
              ))}

              <div className="flex items-center gap-3">
                <div
                  className="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 text-xs"
                  style={{ backgroundColor: customColor || "#ffffff" }}
                >
                  C
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-medium text-gray-600">
                    Custom HEX
                  </label>
                  <input
                    type="text"
                    value={customColor}
                    onChange={handleCustomColorChange}
                    placeholder="#2563eb"
                    className="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-mono focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Live Preview */}
          <div>
            <h3 className="font-semibold text-gray-800 mb-3">
              Live Preview (Booking Card)
            </h3>
            <div className="bg-gray-50 rounded-2xl p-4 sm:p-6 border border-gray-100 flex flex-col sm:flex-row gap-4 sm:gap-6 items-center">
              <div className="flex flex-col items-center gap-3">
                <div className="w-16 h-16 bg-white rounded-xl flex items-center justify-center shadow-sm border border-gray-100 overflow-hidden">
                  {logoPreview ? (
                    <img
                      src={logoPreview}
                      alt="Logo preview"
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <ImageIcon className="text-gray-300" size={28} />
                  )}
                </div>
                <div className="text-center">
                  <p className="text-sm font-semibold text-gray-900">
                    {clinicName || "Clinic Name"}
                  </p>
                  <p className="text-xs text-gray-500">
                    {tagline || "Scan to book instantly"}
                  </p>
                </div>
              </div>

              <div className="flex-1 flex flex-col sm:flex-row items-center sm:items-center justify-between gap-4">
                <div className="flex flex-col items-center gap-2">
                  <div className="w-28 h-28 bg-white rounded-xl flex items-center justify-center shadow-sm border border-gray-100 overflow-hidden">
                    {qrPreview ? (
                      <img
                        src={qrPreview}
                        alt="QR preview"
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <QrCode className="text-gray-300" size={48} />
                    )}
                  </div>
                  <p className="text-[11px] text-gray-500">
                    Point camera to scan
                  </p>
                </div>

                <button
                  type="button"
                  style={{ backgroundColor: primaryColor }}
                  className="w-full sm:w-auto px-5 py-2.5 rounded-full text-xs font-semibold text-white shadow-sm hover:opacity-90 transition"
                >
                  Book Appointment
                </button>
              </div>
            </div>
          </div>

          {/* Save Button */}
          <div className="flex justify-end pt-2">
            <button
              type="submit"
              disabled={isSaving}
              className={`flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-medium text-white shadow-sm transition-colors ${
                isSaving
                  ? "bg-blue-400 cursor-not-allowed"
                  : "bg-blue-600 hover:bg-blue-700"
              }`}
            >
              <Save size={16} />
              {isSaving ? "Saving..." : "Save Branding"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default QrCodeBranding;
