import React, { useId, useMemo, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { ArrowLeft } from "lucide-react";
import { createAppointmentOrder } from "../vetNearMeFlow/bookingFlowApi";
import {
  ASK_ROUTE,
  DEFAULT_IN_CLINIC_STATE,
  hasInClinicIdentifiers,
  IN_CLINIC_MISSING_CONTEXT_MESSAGE,
  IN_CLINIC_PAYMENT_ROUTE,
  IN_CLINIC_PRICING,
  formatInr,
  isValidEmail,
  isValidPhone,
  mergeInClinicStates,
  normalizePhoneInput,
  normalizeTimeInput,
  resolveInClinicState,
} from "./inClinicFlowShared";
import "../vetNearMeFlow/VetNearMeBooking.css";
import { getAskProfile, saveAskProfile } from "./askProfileStorage";

const buildProfileFormState = (contextState) => {
  const profile = getAskProfile();
  return {
    ...DEFAULT_IN_CLINIC_STATE,
    userId: contextState?.userId || profile.userId || "",
    petId: contextState?.petId || profile.petId || "",
    patientName: profile.ownerName || "",
    patientPhone: profile.phone || "",
    patientEmail: contextState?.patientEmail || profile.email || "",
    petName: profile.petName || "",
    date: "",
    timeSlot: "",
    notes: profile.lastProblemText || "",
  };
};

const buildHiddenPrefillFields = (state) => ({
  patientEmail: Boolean(state.patientEmail),
});

export default function InClinicPetDetails({ initialState, onBack, onSubmit }) {
  const navigate = useNavigate();
  const location = useLocation();
  const fieldIdPrefix = useId();
  const today = useMemo(() => new Date().toISOString().slice(0, 10), []);
  const propState =
    initialState && typeof initialState === "object" ? initialState : null;
  const locationState =
    location.state && typeof location.state === "object" ? location.state : null;
  const contextState = useMemo(
    () =>
      resolveInClinicState({
        initialState: propState,
        locationState,
      }),
    [locationState, propState],
  );

  const [formState, setFormState] = useState(() => buildProfileFormState(contextState));
  const [hiddenPrefillFields] = useState(() =>
    buildHiddenPrefillFields(buildProfileFormState(contextState)),
  );
  const [errors, setErrors] = useState({});
  const [submitState, setSubmitState] = useState({
    loading: false,
    type: "",
    text: "",
  });

  const clearError = (field) => {
    setErrors((current) => {
      if (!current[field]) return current;
      const next = { ...current };
      delete next[field];
      return next;
    });
  };

  const updateField = (field, value) => {
    let nextValue = value;
    if (field === "patientPhone") nextValue = normalizePhoneInput(value);
    if (field === "timeSlot") nextValue = normalizeTimeInput(value);

    setFormState((current) => ({ ...current, [field]: nextValue }));
    setSubmitState((current) =>
      current.type || current.text
        ? { loading: false, type: "", text: "" }
        : current,
    );
    clearError(field);
  };

  const showPatientEmailField = !hiddenPrefillFields.patientEmail;

  const validate = (state = formState) => {
    const nextErrors = {};
    if (!String(state.patientName || "").trim()) {
      nextErrors.patientName = "Enter patient name.";
    }
    if (!isValidPhone(state.patientPhone)) {
      nextErrors.patientPhone = "Enter a valid 10 digit phone number.";
    }
    if (!isValidEmail(state.patientEmail)) {
      nextErrors.patientEmail = "Enter a valid email address.";
    }
    if (!String(state.petName || "").trim()) nextErrors.petName = "Enter pet name.";
    if (!String(state.date || "").trim()) nextErrors.date = "Select appointment date.";
    if (!String(state.timeSlot || "").trim()) {
      nextErrors.timeSlot = "Select appointment time.";
    }

    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const handleContinue = async () => {
    if (submitState.loading) return;
    const preparedState = mergeInClinicStates(
      {
        userId: contextState.userId,
        petId: contextState.petId,
      },
      formState,
    );

    if (!validate(preparedState)) return;
    if (!hasInClinicIdentifiers(preparedState)) {
      setSubmitState({
        loading: false,
        type: "error",
        text: IN_CLINIC_MISSING_CONTEXT_MESSAGE,
      });
      return;
    }

    setFormState(preparedState);

    setSubmitState({
      loading: true,
      type: "info",
      text: "Creating secure payment order...",
    });

    try {
      const order = await createAppointmentOrder({
        amount: IN_CLINIC_PRICING.totalAmount,
        userId: preparedState.userId,
        petId: preparedState.petId,
      });

      if (!order.ok || !order.orderId || !order.key) {
        throw new Error(order.error || "Payment order could not be created.");
      }

      const nextState = mergeInClinicStates(preparedState, {
        paymentStatus: "pending",
        paymentKey: order.key,
        paymentOrderId: order.orderId,
        paymentAmountPaise: order.amountPaise,
        paymentCurrency: order.currency || IN_CLINIC_PRICING.currency,
      });

      saveAskProfile({
        ownerName: nextState.patientName,
        phone: nextState.patientPhone,
        email: nextState.patientEmail,
        petName: nextState.petName,
        lastProblemText: nextState.notes,
        ...(nextState.userId ? { userId: nextState.userId } : {}),
        ...(nextState.petId ? { petId: nextState.petId } : {}),
      });

      setSubmitState({
        loading: false,
        type: "success",
        text: "Payment order created. Redirecting to payment...",
      });

      if (onSubmit) {
        onSubmit(nextState);
        return;
      }

      navigate(IN_CLINIC_PAYMENT_ROUTE, { state: nextState });
    } catch (error) {
      setSubmitState({
        loading: false,
        type: "error",
        text: error?.message || "Could not prepare payment. Please try again.",
      });
    }
  };

  const handleBack = () => {
    if (onBack) {
      onBack();
      return;
    }
    navigate(ASK_ROUTE, { replace: true });
  };

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.16),_transparent_24%),linear-gradient(180deg,#f7faff_0%,#edf4ff_46%,#f5f8ff_100%)] pb-20 text-slate-900">
      <div className="sticky top-0 z-30 border-b border-white/70 bg-white/88 backdrop-blur-xl">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 md:px-6">
          <button
            type="button"
            onClick={handleBack}
            className="inline-flex items-center gap-2 rounded-full border border-[#d7e3ff] bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-[#b9cfff] hover:text-[#2457ff]"
          >
            <ArrowLeft size={16} />
            Back
          </button>
        </div>
      </div>

      <div className="mx-auto max-w-5xl px-4 py-6 md:px-6 md:py-8">
        <div className="max-w-3xl">
          <div className="text-sm font-semibold uppercase tracking-[0.22em] text-[#2457ff]">
            In-clinic booking
          </div>
          <h1 className="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
            Enter appointment details
          </h1>
          <p className="mt-2 max-w-2xl text-sm text-slate-600">
            Fill the patient and appointment details, then continue to payment.
          </p>
        </div>

        <div
          className={`mt-6 rounded-[30px] border border-[#d6e3ff] bg-white/95 p-5 shadow-[0_18px_45px_-30px_rgba(37,99,235,0.35)] md:p-7
            [&_.sdiv]:mb-4 [&_.sdiv]:mt-8 [&_.sdiv]:inline-flex [&_.sdiv]:items-center [&_.sdiv]:gap-2 [&_.sdiv]:rounded-full [&_.sdiv]:border [&_.sdiv]:border-[#d7e3ff] [&_.sdiv]:bg-[#f6f9ff] [&_.sdiv]:px-3 [&_.sdiv]:py-1 [&_.sdiv]:text-[11px] [&_.sdiv]:font-semibold [&_.sdiv]:uppercase [&_.sdiv]:tracking-[0.14em] [&_.sdiv]:text-[#2457ff]
            [&_.half]:grid [&_.half]:gap-5 md:[&_.half]:grid-cols-2
            [&_.field]:space-y-2
            [&_label]:block [&_label]:text-[11px] [&_label]:font-semibold [&_label]:uppercase [&_label]:tracking-[0.16em] [&_label]:text-slate-500
            [&_.required-mark]:text-red-500
            [&_input]:w-full [&_input]:rounded-2xl [&_input]:border [&_input]:border-[#d6e3ff] [&_input]:bg-[#fbfdff] [&_input]:px-4 [&_input]:py-3 [&_input]:text-sm [&_input]:text-slate-900 [&_input]:shadow-[0_1px_2px_rgba(15,23,42,0.03)] [&_input]:outline-none [&_input]:transition
            [&_textarea]:w-full [&_textarea]:min-h-[104px] [&_textarea]:resize-none [&_textarea]:rounded-2xl [&_textarea]:border [&_textarea]:border-[#d6e3ff] [&_textarea]:bg-[#fbfdff] [&_textarea]:px-4 [&_textarea]:py-3 [&_textarea]:text-sm [&_textarea]:text-slate-900 [&_textarea]:shadow-[0_1px_2px_rgba(15,23,42,0.03)] [&_textarea]:outline-none [&_textarea]:transition
            [&_input:focus]:border-[#2457ff] [&_input:focus]:ring-4 [&_input:focus]:ring-[#4f6bff]/12
            [&_textarea:focus]:border-[#2457ff] [&_textarea:focus]:ring-4 [&_textarea:focus]:ring-[#4f6bff]/12
            [&_.input-error]:border-red-300 [&_.input-error]:ring-4 [&_.input-error]:ring-red-100
            [&_.field-error]:text-xs [&_.field-error]:font-medium [&_.field-error]:text-red-600
            [&_.fhint]:text-xs [&_.fhint]:text-slate-500
            [&_.cta]:mt-7 [&_.cta]:inline-flex [&_.cta]:w-full [&_.cta]:items-center [&_.cta]:justify-center [&_.cta]:rounded-2xl [&_.cta]:bg-[linear-gradient(135deg,#2457ff_0%,#1d4ed8_100%)] [&_.cta]:px-4 [&_.cta]:py-4 [&_.cta]:text-sm [&_.cta]:font-semibold [&_.cta]:text-white [&_.cta]:shadow-[0_18px_35px_-18px_rgba(37,99,235,0.75)] hover:[&_.cta]:translate-y-[-1px] disabled:[&_.cta]:cursor-not-allowed disabled:[&_.cta]:opacity-60
            [&_.cta-note]:mt-3 [&_.cta-note]:text-center [&_.cta-note]:text-xs [&_.cta-note]:text-slate-500`}
        >


          {showPatientEmailField ? (
            <>
                    <div className="sdiv">Patient details</div>
            <div className="field">
              <label htmlFor={`${fieldIdPrefix}-patientEmail`}>
                Patient email <span className="required-mark">*</span>
              </label>
              <input
                id={`${fieldIdPrefix}-patientEmail`}
                type="email"
                className={errors.patientEmail ? "input-error" : ""}
                value={formState.patientEmail}
                onChange={(event) => updateField("patientEmail", event.target.value)}
                placeholder="rahul@example.com"
              />
              {errors.patientEmail ? (
                <div className="field-error">{errors.patientEmail}</div>
              ) : null}
            </div>
            </>
          ) : null}

          <div className="sdiv">Appointment details</div>
          <div className="half">

            <div className="field">
              <label htmlFor={`${fieldIdPrefix}-date`}>
                Appointment Date <span className="required-mark">*</span>
              </label>
              <input
                id={`${fieldIdPrefix}-date`}
                type="date"
                min={today}
                className={errors.date ? "input-error" : ""}
                value={formState.date}
                onChange={(event) => updateField("date", event.target.value)}
              />
              {errors.date ? <div className="field-error">{errors.date}</div> : null}
            </div>
          </div>

          <div className="half">
            <div className="field">
              <label htmlFor={`${fieldIdPrefix}-timeSlot`}>
                Time slot <span className="required-mark">*</span>
              </label>
              <input
                id={`${fieldIdPrefix}-timeSlot`}
                type="time"
                className={errors.timeSlot ? "input-error" : ""}
                value={formState.timeSlot}
                onChange={(event) => updateField("timeSlot", event.target.value)}
              />
              {errors.timeSlot ? (
                <div className="field-error">{errors.timeSlot}</div>
              ) : (
                <div className="fhint">Payment page will submit this as `HH:mm:ss`.</div>
              )}
            </div>
          </div>


          {submitState.text ? (
            <div
              className={`mt-4 rounded-2xl border px-4 py-3 text-sm font-medium ${
                submitState.type === "success"
                  ? "border-emerald-200 bg-emerald-50 text-emerald-700"
                  : submitState.type === "error"
                    ? "border-red-200 bg-red-50 text-red-700"
                    : "border-blue-200 bg-blue-50 text-blue-700"
              }`}
            >
              {submitState.text}
            </div>
          ) : null}

          <button
            type="button"
            className="cta"
            onClick={handleContinue}
            disabled={submitState.loading}
          >
            {submitState.loading
              ? "Preparing payment..."
              : `Continue to pay Rs ${formatInr(IN_CLINIC_PRICING.totalAmount)} \u2192`}
          </button>
        </div>
      </div>
    </div>
  );
}
