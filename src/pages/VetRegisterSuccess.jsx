import React from "react";
import { useNavigate } from "react-router-dom";
import { CheckCircle2 } from "lucide-react";
import { Button } from "../components/Button";

const VetRegisterSuccess = () => {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-calm-bg flex items-center justify-center px-4 py-12 md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <div className="w-full max-w-xl rounded-3xl border border-stone-100 bg-white p-8 text-center shadow-lg md:p-10">
        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
          <CheckCircle2 size={32} />
        </div>
        <h1 className="text-2xl font-bold text-stone-800 md:text-3xl">
          Application submitted
        </h1>
        <p className="mt-3 text-sm text-stone-500 md:text-base">
          Thank you for registering as a Snoutiq partner. Our team will review
          your application and activate your profile within 24-48 hours.
        </p>
        <div className="mt-6 flex justify-center">
          <Button
            onClick={() => navigate("/auth", { state: { mode: "login" } })}
            className="w-full max-w-xs md:text-lg md:py-4 md:rounded-2xl"
          >
            Go to Login
          </Button>
        </div>
      </div>
    </div>
  );
};

export default VetRegisterSuccess;
