import React,{useEffect,useState} from 'react'

const NewDoctorDigitalPrescription = () => {
      const [prescriptionSubmitting, setPrescriptionSubmitting] = useState(false);
        const [prescriptionError, setPrescriptionError] = useState("");
          const createPrescriptionForm = (transaction = null) => ({
    visitCategory: "Online Consultation",
    consultationCategory: "Online Consultation",
    consultMode: "video",
    medicalStatus: "",
    caseSeverity: "general",
    prognosis: "fair",
    notes: getTransactionReportedSymptoms(transaction),
    historySnapshot: getTransactionReportedSymptoms(transaction),
    doctorTreatment: "",
    diagnosis: "",
    diagnosisStatus: "",
    treatmentPlan: "",
    homeCare: "",
    followUpRequired: "yes",
    followUpDate: "",
    followUpMode: "online",
    followUpNotes: "",
    systemAffectedId: "",
    temperature: "",
    weight: getTransactionWeightInput(transaction),
    mucousMembrane: "",
    dehydrationLevel: "",
    abdominalPainReaction: "",
    auscultation: "",
    physicalExamOther: "",
    medications: [
      {
        name: "",
        dosage: "",
        frequency: "",
        duration: "",
        timing: [],
        foodRelation: "",
        instructions: "",
      },
    ],
    recordFile: null,
  });
  return (
    <div>
            <div className="mx-auto w-full max-w-5xl rounded-2xl border border-gray-200 bg-[#f8fafc] shadow-sm overflow-hidden lg:overflow-visible">
              <div className="bg-white px-4 py-2.5 md:px-5 md:py-3 flex items-center justify-between border-b border-gray-200">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center">
                    <FileText size={20} className="text-blue-600" />
                  </div>
                  <div>
                    <p className="text-gray-500 text-[11px] font-semibold uppercase tracking-wide">
                      Consultation Prescription
                    </p>
                    <h3 className="text-gray-900 font-semibold">
                      Medical Record
                    </h3>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={() => setPrescriptionView("edit")}
                    className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition ${
                      prescriptionView === "edit"
                        ? "bg-blue-600 text-white"
                        : "border border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                    }`}
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    onClick={() => setPrescriptionView("preview")}
                    className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition ${
                      prescriptionView === "preview"
                        ? "bg-blue-600 text-white"
                        : "border border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                    }`}
                  >
                    Preview
                  </button>
                  <button
                    onClick={closePrescriptionModal}
                    className="w-8 h-8 rounded-full border border-gray-200 hover:bg-gray-100 flex items-center justify-center transition-colors"
                  >
                    <X size={16} className="text-gray-600" />
                  </button>
                </div>
              </div>

              <form
                onSubmit={handlePrescriptionSubmit}
                className="p-3.5 md:p-4"
              >
                {prescriptionView === "edit" ? (
                  <div className="grid gap-4 lg:grid-cols-3 lg:items-start">
                    <div className="lg:col-span-2 space-y-4">
                      <div className="bg-gray-50 rounded-xl border border-gray-200 p-3 space-y-3">
                        <div className="flex items-center justify-between">
                          <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Patient Snapshot
                          </p>
                          <span
                            className={`px-3 py-1.5 rounded-full text-xs font-medium border ${statusClass(activeTransaction?.status)}`}
                          >
                            {statusLabel(activeTransaction?.status)}
                          </span>
                        </div>
                        <div className="space-y-3">
                          <div className="grid gap-3 md:grid-cols-2">
                            <div className="rounded-xl border border-gray-200 bg-white p-3">
                              <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                Pet Information
                              </p>
                              <p className="mt-1 text-sm font-semibold text-gray-800">
                                {resolvePetName(activeTransaction)} /{" "}
                                {activeTransaction?.pet?.breed ||
                                  "Not available"}{" "}
                                / {petAgeLabel} /{" "}
                                {petWeightLabel}
                              </p>
                              <div className="mt-2 grid grid-cols-2 gap-2 text-[11px] text-gray-600">
                                <p>
                                  <span className="font-semibold text-gray-700">
                                    Type:
                                  </span>{" "}
                                  {petTypeLabel}
                                </p>
                                <p>
                                  <span className="font-semibold text-gray-700">
                                    Gender:
                                  </span>{" "}
                                  {petGenderLabel}
                                </p>
                              </div>
                              <div className="mt-2 flex flex-wrap gap-2">
                                <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                  Vaccinated: {vaccinationLabel}
                                </span>
                                <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                  Neutered: {neuterLabel}
                                </span>
                                <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                  Dewormed: {dewormingLabel}
                                </span>
                              </div>
                            </div>
                            <div className="space-y-3">
                              <div className="rounded-xl border border-gray-200 bg-white p-3">
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                  Owner Name
                                </p>
                                <p className="mt-1 text-sm font-semibold text-gray-800">
                                  {activeTransaction?.user?.name ||
                                    "Pet Parent"}
                                </p>
                              </div>
                              <div className="rounded-xl border border-gray-200 bg-white p-3">
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                  Location
                                </p>
                                <p className="mt-1 text-sm font-semibold text-gray-800">
                                  {consultationLocationLabel}
                                </p>
                              </div>
                            </div>
                          </div>

                          {/* <div className="grid gap-3 md:grid-cols-2">
                        <div className="rounded-xl border border-[#dce4ff] bg-[#eef2ff] p-3">
                          <p className="text-[10px] font-semibold uppercase tracking-wide text-[#6366f1]">
                            3. Category
                          </p>
                          <p className="mt-1 text-sm font-semibold text-[#1f2937]">
                            {prescriptionForm.consultationCategory ||
                              "General Consultation"}
                          </p>
                        </div>
                        <div className="rounded-xl border border-gray-200 bg-white p-3">
                          <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                            4. Medical Status
                          </p>
                          <p className="mt-1 text-sm font-semibold text-gray-800">
                            {prescriptionForm.medicalStatus || "Ongoing"}
                          </p>
                        </div>
                      </div> */}
                        </div>
                      </div>

                      {/* Consultation Basics */}
                      <div className="bg-white border border-gray-200 rounded-xl p-3.5 space-y-3">
                        <h4 className="font-medium text-gray-900 flex items-center gap-2">
                          <FileText size={16} className="text-[#0B4D67]" />
                          Consultation Details
                        </h4>
                        <div className="grid gap-3 sm:grid-cols-2">
                          <div className="space-y-1">
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Consultation Category{" "}
                              <span className="text-rose-500">*</span>
                            </label>
                            <select
                              value={prescriptionForm.consultationCategory}
                              onChange={(event) => {
                                const nextCategory = event.target.value;
                                const nextConsultMode =
                                  isInClinicConsultationCategory(nextCategory)
                                    ? "in_clinic"
                                    : "video";
                                setPrescriptionForm((prev) => ({
                                  ...prev,
                                  consultationCategory: nextCategory,
                                  visitCategory: nextCategory,
                                  consultMode: nextConsultMode,
                                }));
                              }}
                              className={INPUT_BASE_CLASS}
                            >
                               <option value="Online Consultation">
                                Online Consultation
                              </option>
                              <option value="General Consultation">
                                In-Clinic Consultation
                              </option>
                             
                              {/* <option value="Follow-up">Follow-up</option>
                              <option value="Emergency">Emergency</option> */}
                            </select>
                          </div>
                          {/* <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Consult Mode
                            </label>
                            <div className="grid grid-cols-2 gap-2">
                              {PRESCRIPTION_CONSULT_MODE_OPTIONS.map((mode) => (
                                <button
                                  key={mode.value}
                                  type="button"
                                  onClick={() =>
                                    setPrescriptionForm((prev) => ({
                                      ...prev,
                                      consultMode: mode.value,
                                    }))
                                  }
                                  className={`inline-flex h-[42px] items-center justify-center rounded-xl border px-3 text-xs font-semibold transition ${
                                    prescriptionForm.consultMode === mode.value
                                      ? "border-blue-600 bg-blue-50 text-blue-700"
                                      : "border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                                  }`}
                                >
                                  {mode.label}
                                </button>
                              ))}
                            </div>
                          </div> */}
                          <div className="space-y-1">
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                             Tag this case as chronic
                            </label>
                            <label className="flex w-full min-h-[44px] items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-700 transition hover:border-[#0B4D67]/30 hover:bg-white md:min-h-[46px] md:px-4 md:rounded-xl">
                              <input
                                type="checkbox"
                                checked={
                                  prescriptionForm.medicalStatus === "Chronic"
                                }
                                onChange={toggleChronicMedicalStatus}
                                className="h-4 w-4 rounded border-gray-300 text-[#0B4D67] focus:ring-[#0B4D67]/30"
                              />
                              <span>Chronic</span>
                            </label>
                          </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-3">
                          <label className="block text-xs font-semibold text-slate-700 mb-1">
                            History
                          </label>
                          <textarea
                            ref={historyTextareaRef}
                            value={prescriptionForm.historySnapshot}
                            onChange={updatePrescriptionField(
                              "historySnapshot",
                              PRESCRIPTION_TEXTAREA_MAX_LENGTH,
                            )}
                            rows={3}
                            maxLength={PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                            placeholder="Pet parent-reported history or concern."
                            className={`${INPUT_BASE_CLASS} min-h-[110px] resize-none overflow-hidden border-slate-200 bg-white text-xs leading-5 focus:border-slate-400 focus:ring-slate-200 md:min-h-[96px]`}
                          />
                          <div className="mt-1 flex items-center justify-between gap-2">
                            <p className="text-[11px] text-slate-500">
                              Prefilled from the pet parent details and editable
                              by the vet.
                            </p>
                            <p className="whitespace-nowrap text-[11px] text-slate-500">
                              {prescriptionForm.historySnapshot.length} /{" "}
                              {PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                            </p>
                          </div>
                          <p className="mt-1 text-right text-[11px] text-slate-500">
                            {getRemainingCharacters(
                              prescriptionForm.historySnapshot,
                            )}{" "}
                            characters left
                          </p>
                        </div>

                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-3">
                          <label className="block text-xs font-semibold text-blue-700 mb-1">
                            Clinical Notes / Tentative Diagnosis by Vet{" "}
                            <span className="text-rose-500">*</span>
                          </label>
                          <textarea
                            ref={diagnosisTextareaRef}
                            value={prescriptionForm.diagnosis}
                            onChange={updatePrescriptionField(
                              "diagnosis",
                              PRESCRIPTION_TEXTAREA_MAX_LENGTH,
                            )}
                            rows={3}
                            maxLength={PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                            placeholder="Possible gastritis, dehydration..."
                            className={`${INPUT_BASE_CLASS} min-h-[110px] resize-none overflow-hidden border-blue-200 bg-white text-xs leading-5 focus:border-blue-400 focus:ring-blue-100 md:min-h-[96px]`}
                          />
                          <div className="mt-1 flex items-center justify-end">
                            <p className="whitespace-nowrap text-[11px] text-blue-700">
                              {prescriptionForm.diagnosis.length} /{" "}
                              {PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                            </p>
                          </div>
                          <p className="mt-1 text-right text-[11px] text-blue-700">
                            {getRemainingCharacters(prescriptionForm.diagnosis)}{" "}
                            characters left
                          </p>
                        </div>
                        <div className="grid sm:grid-cols-2 gap-3 rounded-xl border border-blue-200 bg-blue-50/50 p-3">
                          {/* <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Case Severity
                            </label>
                            <select
                              value={prescriptionForm.caseSeverity}
                              onChange={updatePrescriptionField("caseSeverity")}
                              className={INPUT_BASE_CLASS}
                            >
                              <option value="general">General</option>
                              <option value="moderate">Moderate</option>
                              <option value="critical">Critical</option>
                            </select>
                          </div> */}
                          <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Prognosis{" "}
                              <span className="text-rose-500">*</span>
                            </label>
                            <select
                              value={prescriptionForm.prognosis}
                              onChange={updatePrescriptionField("prognosis")}
                              className={INPUT_BASE_CLASS}
                            >
                              {PRESCRIPTION_PROGNOSIS_OPTIONS.map((item) => (
                                <option key={item.value} value={item.value}>
                                  {item.label}
                                </option>
                              ))}
                            </select>
                          </div>
                        </div>
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3">
                          <label className="block text-xs font-semibold text-emerald-700 mb-1">
                            Vet Advice / Home Care Tips{" "}
                            <span className="text-rose-500">*</span>
                          </label>
                          <textarea
                            ref={adviceTextareaRef}
                            value={prescriptionForm.homeCare}
                            onChange={updatePrescriptionField(
                              "homeCare",
                              PRESCRIPTION_TEXTAREA_MAX_LENGTH,
                            )}
                            rows={3}
                            maxLength={PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                            placeholder="Feed small frequent meals, ensure hydration..."
                            className={`${INPUT_BASE_CLASS} min-h-[110px] resize-none overflow-hidden border-emerald-200 bg-white text-xs leading-5 focus:border-emerald-400 focus:ring-emerald-100 md:min-h-[96px]`}
                          />
                          <div className="mt-1 flex items-center justify-end">
                            <p className="whitespace-nowrap text-[11px] text-emerald-700">
                              {prescriptionForm.homeCare.length} /{" "}
                              {PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                            </p>
                          </div>
                          <p className="mt-1 text-right text-[11px] text-emerald-700">
                            {getRemainingCharacters(prescriptionForm.homeCare)}{" "}
                            characters left
                          </p>
                        </div>
                      </div>

                      {isGeneralConsultation ? (
                        <div className="bg-white border border-gray-200 rounded-xl p-3.5 space-y-3">
                        <h4 className="font-medium text-gray-900 flex items-center gap-2">
                          <Stethoscope size={16} className="text-[#0B4D67]" />
                          Physical Examination
                        </h4>
                        <div className="grid gap-3 sm:grid-cols-2">
                          <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Temperature (F){" "}
                              <span className="text-rose-500">*</span>
                            </label>
                            <input
                              type="number"
                              step="0.1"
                              value={prescriptionForm.temperature}
                              onChange={updatePrescriptionField("temperature")}
                              onKeyDown={blockNumberInput}
                              onWheel={handleNumberWheel}
                              placeholder="102.0"
                              className={INPUT_BASE_CLASS}
                            />
                          </div>
                          <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Weight (kg){" "}
                              <span className="text-rose-500">*</span>
                            </label>
                            <input
                              type="number"
                              step="0.1"
                              value={prescriptionForm.weight}
                              onChange={updatePrescriptionField("weight")}
                              onKeyDown={blockNumberInput}
                              onWheel={handleNumberWheel}
                              placeholder="18.4"
                              className={INPUT_BASE_CLASS}
                            />
                          </div>
                          <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Mucous Membrane{" "}
                              <span className="text-rose-500">*</span>
                            </label>
                            <select
                              value={prescriptionForm.mucousMembrane}
                              onChange={updatePrescriptionField("mucousMembrane")}
                              className={INPUT_BASE_CLASS}
                            >
                              <option value="">Select mucous membrane</option>
                              {PRESCRIPTION_MUCOUS_MEMBRANE_OPTIONS.map((item) => (
                                <option key={item.value} value={item.value}>
                                  {item.label}
                                </option>
                              ))}
                            </select>
                          </div>
                          <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Dehydration Level{" "}
                              <span className="text-rose-500">*</span>
                            </label>
                            <select
                              value={prescriptionForm.dehydrationLevel}
                              onChange={updatePrescriptionField("dehydrationLevel")}
                              className={INPUT_BASE_CLASS}
                            >
                              <option value="">Select dehydration level</option>
                              {PRESCRIPTION_DEHYDRATION_LEVEL_OPTIONS.map(
                                (item) => (
                                  <option key={item.value} value={item.value}>
                                    {item.label}
                                  </option>
                                ),
                              )}
                            </select>
                          </div>
                          <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Abdominal Pain Reaction{" "}
                              <span className="text-rose-500">*</span>
                            </label>
                            <select
                              value={prescriptionForm.abdominalPainReaction}
                              onChange={updatePrescriptionField("abdominalPainReaction")}
                              className={INPUT_BASE_CLASS}
                            >
                              <option value="">Select pain response</option>
                              {PRESCRIPTION_ABDOMINAL_PAIN_OPTIONS.map((item) => (
                                <option key={item.value} value={item.value}>
                                  {item.label}
                                </option>
                              ))}
                            </select>
                          </div>
                          <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Auscultation{" "}
                              <span className="text-rose-500">*</span>
                            </label>
                            <select
                              value={prescriptionForm.auscultation}
                              onChange={updatePrescriptionField("auscultation")}
                              className={INPUT_BASE_CLASS}
                            >
                              <option value="">Select auscultation result</option>
                              {PRESCRIPTION_AUSCULTATION_OPTIONS.map((item) => (
                                <option key={item.value} value={item.value}>
                                  {item.label}
                                </option>
                              ))}
                            </select>
                          </div>
                        </div>
                        <div>
                          <label className="block text-xs font-medium text-gray-500 mb-1">
                            Physical exam notes{" "}
                            <span className="text-rose-500">*</span>
                          </label>
                          <textarea
                            value={prescriptionForm.physicalExamOther}
                            onChange={updatePrescriptionField("physicalExamOther")}
                            rows={2}
                            placeholder="Mild abdominal guarding, discomfort on palpation..."
                            className={`${INPUT_BASE_CLASS} resize-none text-xs`}
                          />
                        </div>
                        </div>
                      ) : null}

                      {/* Medications */}
                      <div className="bg-white border border-gray-200 rounded-xl p-3.5 space-y-3">
                        <h4 className="font-medium text-gray-900 flex items-center gap-2">
                          <Pill size={16} className="text-[#0B4D67]" />
                          10. Medications
                        </h4>
                        <div className="space-y-2.5">
                          {prescriptionForm.medications.map(
                            (medication, index) => (
                              <div
                                key={index}
                                className="rounded-xl border border-gray-200 bg-gray-50 p-3"
                              >
                                <div className="flex items-center justify-between gap-2">
                                  <button
                                    type="button"
                                    onClick={() =>
                                      setActiveMedicationIndex((prev) =>
                                        prev === index ? -1 : index,
                                      )
                                    }
                                    className="text-left"
                                  >
                                    <p className="text-sm font-semibold text-gray-800">
                                      Medication {index + 1}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                      {medication.name ||
                                        "Add medicine details"}
                                    </p>
                                  </button>
                                  <button
                                    type="button"
                                    onClick={() => removeMedication(index)}
                                    className="rounded-full border border-stone-200 px-3 py-1.5 text-xs text-stone-500 hover:bg-stone-100"
                                  >
                                    Remove
                                  </button>
                                </div>

                                {activeMedicationIndex === index ? (
                                  <div className="mt-3 space-y-3 rounded-xl border border-gray-200 bg-white p-3">
                                    <div>
                                      <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                                        Medicine Name
                                      </label>
                                      <input
                                        type="text"
                                        value={medication.name}
                                        onChange={updateMedication(
                                          index,
                                          "name",
                                        )}
                                        placeholder="e.g., Amoxicillin"
                                        className={INPUT_BASE_CLASS}
                                      />
                                    </div>

                                    <div className="grid grid-cols-2 gap-2.5">
                                      <div>
                                        <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                                          Dosage
                                        </label>
                                        <input
                                          type="text"
                                          value={medication.dosage}
                                          onChange={updateMedication(
                                            index,
                                            "dosage",
                                          )}
                                          placeholder="e.g., 1 tab"
                                          className={INPUT_BASE_CLASS}
                                        />
                                      </div>
                                      <div>
                                        <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                                          Duration
                                        </label>
                                        <input
                                          type="text"
                                          value={medication.duration}
                                          onChange={updateMedication(
                                            index,
                                            "duration",
                                          )}
                                          placeholder="e.g., 7 days"
                                          className={INPUT_BASE_CLASS}
                                        />
                                      </div>
                                    </div>

                                    <div>
                                      <p className="mb-2 text-xs font-semibold text-gray-700">
                                        Frequency
                                      </p>
                                      <div className="flex flex-wrap gap-2">
                                        {PRESCRIPTION_FREQUENCY_OPTIONS.map(
                                          (frequency) => (
                                            <button
                                              key={frequency}
                                              type="button"
                                              onClick={() =>
                                                setMedicationField(
                                                  index,
                                                  "frequency",
                                                  frequency,
                                                )
                                              }
                                              className={`rounded-full border px-3 py-1.5 text-xs font-semibold transition ${
                                                medication.frequency ===
                                                frequency
                                                  ? "border-blue-600 bg-blue-50 text-blue-700"
                                                  : "border-gray-200 bg-white text-gray-600 hover:border-blue-300"
                                              }`}
                                            >
                                              {frequency}
                                            </button>
                                          ),
                                        )}
                                      </div>
                                    </div>

                                    <div>
                                      <p className="mb-2 text-xs font-semibold text-gray-700">
                                        Timing (select one or more)
                                      </p>
                                      <div className="flex flex-wrap gap-2">
                                        {PRESCRIPTION_TIMING_OPTIONS.map(
                                          (timing) => (
                                            <button
                                              key={timing}
                                              type="button"
                                              onClick={() =>
                                                toggleMedicationTiming(
                                                  index,
                                                  timing,
                                                )
                                              }
                                              className={`rounded-full border px-3 py-1.5 text-xs font-semibold transition ${
                                                Array.isArray(
                                                  medication.timing,
                                                ) &&
                                                medication.timing.includes(
                                                  timing,
                                                )
                                                  ? "border-blue-600 bg-blue-50 text-blue-700"
                                                  : "border-gray-200 bg-white text-gray-600 hover:border-blue-300"
                                              }`}
                                            >
                                              {timing}
                                            </button>
                                          ),
                                        )}
                                      </div>
                                    </div>

                                    <div>
                                      <p className="mb-2 text-xs font-semibold text-gray-700">
                                        Food Relation
                                      </p>
                                      <div className="flex flex-wrap gap-2">
                                        {PRESCRIPTION_FOOD_RELATION_OPTIONS.map(
                                          (foodRelation) => (
                                            <button
                                              key={foodRelation}
                                              type="button"
                                              onClick={() =>
                                                setMedicationField(
                                                  index,
                                                  "foodRelation",
                                                  foodRelation,
                                                )
                                              }
                                              className={`rounded-full border px-3 py-1.5 text-xs font-semibold transition ${
                                                medication.foodRelation ===
                                                foodRelation
                                                  ? "border-blue-600 bg-blue-50 text-blue-700"
                                                  : "border-gray-200 bg-white text-gray-600 hover:border-blue-300"
                                              }`}
                                            >
                                              {foodRelation}
                                            </button>
                                          ),
                                        )}
                                      </div>
                                    </div>

                                    <div>
                                      <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                                        Additional Instruction (optional)
                                      </label>
                                      <input
                                        type="text"
                                        value={medication.instructions || ""}
                                        onChange={updateMedication(
                                          index,
                                          "instructions",
                                        )}
                                        placeholder="e.g., Give before sleep"
                                        className={INPUT_BASE_CLASS}
                                      />
                                    </div>

                                    <div className="flex justify-end gap-2">
                                      <button
                                        type="button"
                                        onClick={() =>
                                          closeMedicationEditor(index)
                                        }
                                        className="inline-flex h-[42px] items-center justify-center rounded-xl border border-gray-200 bg-gray-100 px-4 text-xs font-semibold text-gray-600 hover:bg-gray-200"
                                      >
                                        Cancel
                                      </button>
                                      <button
                                        type="button"
                                        onClick={() =>
                                          closeMedicationEditor(index)
                                        }
                                        className="inline-flex h-[42px] items-center justify-center rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white hover:bg-blue-700"
                                      >
                                        Save
                                      </button>
                                    </div>
                                  </div>
                                ) : (
                                  <div className="mt-3 flex flex-wrap gap-2 text-xs text-gray-600">
                                    <span className="rounded-full border border-gray-200 bg-white px-2.5 py-1">
                                      {medication.dosage || "Dosage N/A"}
                                    </span>
                                    <span className="rounded-full border border-gray-200 bg-white px-2.5 py-1">
                                      {medication.duration || "Duration N/A"}
                                    </span>
                                    <span className="rounded-full border border-gray-200 bg-white px-2.5 py-1">
                                      {medication.frequency || "Frequency N/A"}
                                    </span>
                                    {Array.isArray(medication.timing) &&
                                    medication.timing.length > 0 ? (
                                      <span className="rounded-full border border-gray-200 bg-white px-2.5 py-1">
                                        {medication.timing.join(", ")}
                                      </span>
                                    ) : null}
                                    {medication.foodRelation ? (
                                      <span className="rounded-full border border-gray-200 bg-white px-2.5 py-1">
                                        {medication.foodRelation}
                                      </span>
                                    ) : null}
                                  </div>
                                )}
                              </div>
                            ),
                          )}
                        </div>
                        <button
                          type="button"
                          onClick={addMedication}
                          className="inline-flex h-[42px] w-full items-center justify-center rounded-full border border-stone-200 px-4 text-xs font-semibold text-blue-600 hover:bg-blue-50 sm:w-auto"
                        >
                          + Add Medicine
                        </button>
                      </div>

                      <div className="bg-white border border-gray-200 rounded-xl p-3.5 space-y-3">
                        <h4 className="font-medium text-gray-900 flex items-center gap-2">
                          <Calendar size={16} className="text-[#0B4D67]" />
                          Follow-up
                        </h4>
                        <div className="grid sm:grid-cols-2 gap-3">
                          <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Follow-up Required
                            </label>
                            <div className="flex gap-2">
                              {["yes", "no"].map((value) => (
                                <button
                                  key={value}
                                  type="button"
                                  onClick={() =>
                                    setPrescriptionForm((prev) => ({
                                      ...prev,
                                      followUpRequired: value,
                                    }))
                                  }
                                  className={`inline-flex h-[42px] flex-1 items-center justify-center rounded-xl border px-3 text-xs font-semibold uppercase transition ${
                                    prescriptionForm.followUpRequired === value
                                      ? "border-blue-600 bg-blue-50 text-blue-700"
                                      : "border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                                  }`}
                                >
                                  {value}
                                </button>
                              ))}
                            </div>
                          </div>
                          <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">
                              Follow-up Date
                              {prescriptionForm.followUpRequired === "yes" ? (
                                <>
                                  {" "}
                                  <span className="text-rose-500">*</span>
                                </>
                              ) : null}
                            </label>
                            <input
                              type="date"
                              value={prescriptionForm.followUpDate}
                              onChange={updatePrescriptionField("followUpDate")}
                              disabled={
                                prescriptionForm.followUpRequired !== "yes"
                              }
                              className={`${INPUT_BASE_CLASS} ${
                                prescriptionForm.followUpRequired !== "yes"
                                  ? "cursor-not-allowed bg-gray-100 text-gray-400"
                                  : ""
                              }`}
                            />
                          </div>
                        </div>
                        <div>
                          <label className="block text-xs font-medium text-gray-500 mb-1">
                            Follow-up Mode
                          </label>
                          <div className="grid grid-cols-2 gap-2">
                            {[
                              { value: "online", label: "Online" },
                              { value: "in_clinic", label: "In-Clinic" },
                            ].map((mode) => (
                              <button
                                key={mode.value}
                                type="button"
                                onClick={() =>
                                  setPrescriptionForm((prev) => ({
                                    ...prev,
                                    followUpMode: mode.value,
                                  }))
                                }
                                disabled={
                                  prescriptionForm.followUpRequired !== "yes"
                                }
                                className={`inline-flex h-[42px] items-center justify-center rounded-xl border px-3 text-xs font-semibold transition ${
                                  prescriptionForm.followUpMode === mode.value
                                    ? "border-blue-600 bg-blue-50 text-blue-700"
                                    : "border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                                } ${
                                  prescriptionForm.followUpRequired !== "yes"
                                    ? "cursor-not-allowed opacity-60"
                                    : ""
                                }`}
                              >
                                {mode.label}
                              </button>
                            ))}
                          </div>
                        </div>
                        {/* <div>
                          <label className="block text-xs font-medium text-gray-500 mb-1">
                            Follow-up Notes
                          </label>
                          <textarea
                            value={prescriptionForm.followUpNotes}
                            onChange={updatePrescriptionField("followUpNotes")}
                            rows={2}
                            disabled={prescriptionForm.followUpRequired !== "yes"}
                            placeholder="Recheck appetite, hydration, and stool pattern..."
                            className={`${INPUT_BASE_CLASS} resize-none text-xs ${
                              prescriptionForm.followUpRequired !== "yes"
                                ? "cursor-not-allowed bg-gray-100 text-gray-400"
                                : ""
                            }`}
                          />
                        </div> */}
                      </div>
                      <div className="rounded-xl border border-stone-100 bg-white p-3.5 space-y-3 shadow-sm">
                        <div className="flex items-center gap-2 text-sm font-semibold text-stone-700">
                          <FileText size={16} className="text-[#0B4D67]" />
                          System Affected
                        </div>
                        <div className="relative" ref={affectedSystemMenuRef}>
                          <label className="block text-xs font-medium text-gray-500 mb-1">
                            Select system affected{" "}
                            <span className="text-rose-500">*</span>
                          </label>
                          <button
                            type="button"
                            onClick={() =>
                              setIsAffectedSystemMenuOpen((prev) => !prev)
                            }
                            className={`${INPUT_BASE_CLASS} flex items-center justify-between gap-3 text-left`}
                          >
                            <span
                              className={
                                selectedAffectedSystem
                                  ? "text-gray-700"
                                  : "text-gray-400"
                              }
                            >
                              {selectedAffectedSystem?.name ||
                                (affectedSystemsLoading
                                  ? "Loading systems..."
                                  : "Select system affected")}
                            </span>
                            <ChevronDown
                              size={16}
                              className={`shrink-0 text-gray-400 transition-transform ${
                                isAffectedSystemMenuOpen ? "rotate-180" : ""
                              }`}
                            />
                          </button>

                          {isAffectedSystemMenuOpen ? (
                            <div className="absolute left-0 right-0 top-full z-30 mt-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                              <div className="border-b border-gray-100 p-2">
                                <div className="relative">
                                  <Search
                                    size={14}
                                    className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                                  />
                                  <input
                                    type="text"
                                    value={affectedSystemQuery}
                                    onChange={(event) =>
                                      setAffectedSystemQuery(event.target.value)
                                    }
                                    placeholder="Search system affected..."
                                    autoFocus
                                    className="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-700 outline-none transition focus:border-[#0B4D67] focus:bg-white focus:ring-2 focus:ring-[#0B4D67]/20"
                                  />
                                </div>
                              </div>

                              <div className="max-h-56 overflow-y-auto p-1.5">
                                <button
                                  type="button"
                                  onClick={() => {
                                    setPrescriptionForm((prev) => ({
                                      ...prev,
                                      systemAffectedId: "",
                                    }));
                                    setIsAffectedSystemMenuOpen(false);
                                  }}
                                  className={`flex w-full items-start rounded-lg px-3 py-2 text-left text-sm transition ${
                                    !prescriptionForm.systemAffectedId
                                      ? "bg-blue-50 text-blue-700"
                                      : "text-gray-600 hover:bg-gray-50"
                                  }`}
                                >
                                  Select system affected
                                </button>

                                {filteredAffectedSystems.length > 0 ? (
                                  filteredAffectedSystems.map((system) => (
                                    <button
                                      key={system.id}
                                      type="button"
                                      onClick={() => {
                                        setPrescriptionForm((prev) => ({
                                          ...prev,
                                          systemAffectedId: String(system.id),
                                        }));
                                        setIsAffectedSystemMenuOpen(false);
                                      }}
                                      className={`flex w-full items-start rounded-lg px-3 py-2 text-left text-sm transition ${
                                        String(
                                          prescriptionForm.systemAffectedId,
                                        ) === String(system.id)
                                          ? "bg-blue-50 text-blue-700"
                                          : "text-gray-700 hover:bg-gray-50"
                                      }`}
                                    >
                                      {system.name}
                                    </button>
                                  ))
                                ) : (
                                  <div className="px-3 py-2 text-sm text-gray-500">
                                    No matching systems
                                  </div>
                                )}
                              </div>
                            </div>
                          ) : null}
                        </div>
                      </div>
                      <div className="rounded-xl border border-stone-100 bg-white p-3.5 space-y-3 shadow-sm">
                        <div className="flex items-center gap-2 text-sm font-semibold text-stone-700">
                          <Upload size={16} /> Attach Record (optional)
                        </div>
                        <label className="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-stone-200 bg-stone-50 px-4 py-5 text-center text-xs text-stone-500 hover:border-[#3998de] hover:text-[#3998de]">
                          <input
                            type="file"
                            accept=".pdf,.png,.jpg,.jpeg"
                            onChange={handleRecordFile}
                            className="hidden"
                          />
                          <span className="font-semibold">
                            Upload report file
                          </span>
                          <span className="text-[10px] text-stone-400">
                            {prescriptionForm.recordFile?.name ||
                              "PDF, PNG, JPG supported"}
                          </span>
                        </label>
                      </div>

              
                    </div>

                    <aside className="space-y-3 self-start lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto lg:pr-1">
                      <div className="rounded-xl border border-stone-100 bg-white p-3.5 shadow-sm">
                        <div className="text-xs uppercase text-stone-400">
                          Consult Summary
                        </div>
                        <div className="mt-3 space-y-2 text-sm text-stone-700">
                          <div className="flex items-center justify-between">
                            <span>Reference</span>
                            <span className="max-w-[140px] truncate font-semibold text-stone-900">
                              {activeTransaction?.reference ||
                                activeTransaction?.metadata?.order_id ||
                                "NA"}
                            </span>
                          </div>
                          <div className="flex items-center justify-between">
                            <span>Current Payment</span>
                            <span className="font-semibold text-stone-900">
                              {formatAmount(
                                activeTransaction?.payment_to_doctor_inr ??
                                  (activeTransaction?.payment_to_doctor_paise
                                    ? activeTransaction.payment_to_doctor_paise /
                                      100
                                    : 0),
                              )}
                            </span>
                          </div>
                          <div className="flex items-center justify-between">
                            <span>GST (18%)</span>
                            <span className="font-semibold text-stone-900">
                              {formatAmount(
                                activeTransaction?.gst_deduction_inr ??
                                  (activeTransaction?.gst_deduction_paise
                                    ? activeTransaction.gst_deduction_paise /
                                      100
                                    : 0),
                              )}
                            </span>
                          </div>
                          <div className="flex items-center justify-between">
                            <span>Flat Deduction</span>
                            <span className="font-semibold text-stone-900">
                              {formatAmount(
                                activeTransaction?.flat_deduction_inr ??
                                  (activeTransaction?.flat_deduction_paise
                                    ? activeTransaction.flat_deduction_paise /
                                      100
                                    : 0),
                              )}
                            </span>
                          </div>
                          <div className="flex items-center justify-between">
                            <span>Actual Earnings</span>
                            <span className="font-semibold text-stone-900">
                              {formatAmount(
                                activeTransaction?.actual_earnings_inr ??
                                  activeTransaction?.amount_after_deduction_inr ??
                                  activeTransaction?.amount_inr ??
                                  0,
                              )}
                            </span>
                          </div>
                          <div className="flex items-center justify-between">
                            <span>Gross</span>
                            <span className="font-semibold text-stone-900">
                              {formatAmount(
                                activeTransaction?.gross_amount_inr ??
                                  (activeTransaction?.amount_paise
                                    ? activeTransaction.amount_paise / 100
                                    : 0),
                              )}
                            </span>
                          </div>
                          <div className="flex items-center justify-between">
                            <span>Date</span>
                            <span className="font-semibold text-stone-900">
                              {formatDate(activeTransaction?.created_at)}
                            </span>
                          </div>
                        </div>
                      </div>

                      <div className="rounded-xl border border-amber-200 bg-amber-50 p-3.5 shadow-sm">
                        <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-800">
                          <AlertCircle size={14} />
                          Final Check Before Sending
                        </div>
                        <p className="mt-2 text-xs leading-relaxed text-amber-800/90">
                          Please complete the highlighted diagnosis, home care,
                          and medications section for a complete digital
                          prescription.
                        </p>
                      </div>

                      {prescriptionError ? (
                        <div className="flex items-start gap-2 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-600">
                          <AlertCircle size={16} />
                          <span>{prescriptionError}</span>
                        </div>
                      ) : null}

                      <div className="flex flex-col gap-2">
                        <button
                          type="button"
                          onClick={() => setPrescriptionView("preview")}
                          className="inline-flex h-[42px] items-center justify-center rounded-full border border-blue-200 bg-blue-50 px-5 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                        >
                          Preview
                        </button>
                        <button
                          type="submit"
                          disabled={prescriptionSubmitting}
                          className={`inline-flex h-[42px] items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-orange-400 px-6 text-sm font-semibold text-white shadow-sm hover:from-orange-600 hover:to-orange-500 ${
                            prescriptionSubmitting
                              ? "opacity-60 cursor-not-allowed"
                              : ""
                          }`}
                        >
                          {prescriptionSubmitting
                            ? "Sending..."
                            : "Send Prescription"}
                        </button>
                        <button
                          type="button"
                          onClick={closePrescriptionModal}
                          className="inline-flex h-[42px] items-center justify-center rounded-full border border-stone-200 px-5 text-xs font-semibold text-stone-500 hover:bg-stone-50"
                        >
                          Cancel
                        </button>
                      </div>
                    </aside>
                  </div>
                ) : (
                  <div className="mx-auto max-w-5xl space-y-5">
                    <div className="overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-[0_22px_55px_rgba(15,23,42,0.12)]">
                      <div className="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-5 py-5 md:px-7">
                        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                          <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl text-white shadow-sm">
                              <img
                                src="/favicon.png"
                                alt="SnoutIQ favicon"
                                className="object-contain border-2 border-slate-200 rounded-xl bg-white p-1"
                              />
                            </div>
                            <div>
                              <p className="text-lg font-bold text-slate-900">
                                Snoutiq Digital Prescription
                              </p>
                              <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                                Prepared by{" "}
                                {assignedDoctorName}
                              </p>
                            </div>
                          </div>
                          <div className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-right">
                            <p className="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                              Date
                            </p>
                            <p className="text-sm font-bold text-slate-900">
                              {new Date().toLocaleDateString("en-US")}
                            </p>
                            <p className="mt-0.5 text-[10px] text-slate-500">
                              Ref:{" "}
                              {activeTransaction?.reference ||
                                activeTransaction?.metadata?.order_id ||
                                "N/A"}
                            </p>
                          </div>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
                          <span className="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-700">
                            {statusLabel(activeTransaction?.status)}
                          </span>
                          <span className="rounded-full border border-[#b7d3df] bg-[#e9f5fa] px-3 py-1 text-[11px] font-semibold text-[#0B4D67]">
                            {prescriptionForm.consultationCategory ||
                              "General Consultation"}
                          </span>
                          <span className="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600">
                            Powered by SnoutIQ
                          </span>
                        </div>
                      </div>

                      <div className="space-y-6 p-5 md:p-7">
                        <div className="grid gap-4 lg:grid-cols-2">
                          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                              Patient Profile
                            </p>
                            <div className="mt-3 space-y-2 text-sm text-slate-700">
                              <p>
                                <span className="font-semibold text-slate-900">
                                  Pet:
                                </span>{" "}
                                {resolvePetName(activeTransaction)} /{" "}
                                {activeTransaction?.pet?.breed ||
                                  "Not available"}{" "}
                                / {petAgeLabel} / {petWeightLabel}
                              </p>
                              <p>
                                <span className="font-semibold text-slate-900">
                                  Type / Gender:
                                </span>{" "}
                                {petTypeLabel} / {petGenderLabel}
                              </p>
                              <p>
                                <span className="font-semibold text-slate-900">
                                  Owner:
                                </span>{" "}
                                {activeTransaction?.user?.name || "Pet Parent"}
                              </p>
                              {/* <p>
                                <span className="font-semibold text-slate-900">
                                  Location:
                                </span>{" "}
                                {consultationLocationLabel}
                              </p> */}
                              {/* <p>
                                <span className="font-semibold text-slate-900">
                                  Clinic:
                                </span>{" "}
                                {clinicNameLabel} ({clinicCityLabel})
                              </p> */}
                            </div>
                            <div className="mt-3 flex flex-wrap gap-2">
                              <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                                Vaccinated: {vaccinationLabel}
                              </span>
                              <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                                Neutered: {neuterLabel}
                              </span>
                              <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                                Dewormed: {dewormingLabel}
                              </span>
                            </div>
                          </div>

                          <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                              Clinical Summary
                            </p>
                            <div className="mt-3 grid grid-cols-2 gap-3 text-sm">
                              {prescriptionForm.medicalStatus === "Chronic" ? (
                                <div>
                                  <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                    Medical Status
                                  </p>
                                  <p className="mt-1 font-semibold text-slate-900">
                                    {prescriptionForm.medicalStatus}
                                  </p>
                                </div>
                              ) : null}
                              <div>
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                  Case Severity
                                </p>
                                <p className="mt-1 font-semibold text-slate-900">
                                  {formatPetText(
                                    prescriptionForm.caseSeverity || "General",
                                  )}
                                </p>
                              </div>
                              <div>
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                  Prognosis
                                </p>
                                <p className="mt-1 font-semibold text-slate-900">
                                  {prognosisLabel}
                                </p>
                              </div>
                              <div>
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                  Follow-Up
                                </p>
                                <p className="mt-1 font-semibold text-slate-900">
                                  {followUpDisplayLabel}
                                </p>
                              </div>
                            </div>
                            <div className="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                              <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                History
                              </p>
                              <p className="mt-1 text-sm font-medium text-slate-800">
                                {historySnapshotLabel}
                              </p>
                            </div>
                            <div className="mt-3 grid grid-cols-2 gap-3 text-sm">
                              <div>
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                  Consult Mode
                                </p>
                                <p className="mt-1 font-semibold text-slate-900">
                                  {consultModeLabel}
                                </p>
                              </div>
                              {selectedAffectedSystem ? (
                                <div>
                                  <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                    System Affected
                                  </p>
                                  <p className="mt-1 font-semibold text-slate-900">
                                    {selectedAffectedSystem.name}
                                  </p>
                                </div>
                              ) : null}
                            </div>
                          </div>
                        </div>

                        {isGeneralConsultation ? (
                          <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                              Physical Examination
                            </p>
                            <div className="mt-3 flex flex-wrap gap-2">
                              {prescriptionForm.temperature ? (
                                <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-700">
                                  Temp: {prescriptionForm.temperature} F
                                </span>
                              ) : null}
                              {prescriptionForm.weight ? (
                                <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-700">
                                  Weight: {prescriptionForm.weight} kg
                                </span>
                              ) : null}
                              {prescriptionForm.mucousMembrane ? (
                                <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-700">
                                  Mucous membrane: {mucousMembraneLabel}
                                </span>
                              ) : null}
                              {prescriptionForm.dehydrationLevel ? (
                                <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-700">
                                  Dehydration: {dehydrationLevelLabel}
                                </span>
                              ) : null}
                              {prescriptionForm.abdominalPainReaction ? (
                                <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-700">
                                  Abdominal pain: {abdominalPainLabel}
                                </span>
                              ) : null}
                              {prescriptionForm.auscultation ? (
                                <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-700">
                                  Auscultation: {auscultationLabel}
                                </span>
                              ) : null}
                            </div>
                            {prescriptionForm.physicalExamOther ? (
                              <p className="mt-3 text-sm leading-relaxed text-slate-700">
                                {prescriptionForm.physicalExamOther}
                              </p>
                            ) : (
                              <p className="mt-3 text-sm leading-relaxed text-slate-500">
                                No additional physical examination notes added.
                              </p>
                            )}
                          </div>
                        ) : null}

                        <div className="rounded-2xl border border-slate-200 bg-white p-4">
                          <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                            Medications & Treatment
                          </p>
                          <div className="mt-3 space-y-3">
                            {prescriptionForm.medications
                              .filter(
                                (medication) =>
                                  medication.name ||
                                  medication.dosage ||
                                  medication.frequency ||
                                  medication.duration ||
                                  medication.foodRelation ||
                                  (Array.isArray(medication.timing) &&
                                    medication.timing.length > 0) ||
                                  medication.instructions,
                              )
                              .map((medication, index) => (
                                <div
                                  key={index}
                                  className="rounded-xl border border-slate-200 bg-slate-50 p-3"
                                >
                                  <div className="flex items-start justify-between gap-3">
                                    <div>
                                      <p className="text-sm font-semibold text-slate-900">
                                        {medication.name || "Unnamed Medicine"}
                                      </p>
                                      <p className="text-xs text-slate-600">
                                        {medication.dosage || "-"} |{" "}
                                        {medication.duration || "-"}
                                      </p>
                                    </div>
                                    <span className="rounded-full bg-[#e9f5fa] px-2.5 py-1 text-[10px] font-semibold text-[#0B4D67]">
                                      {medication.frequency || "Frequency N/A"}
                                    </span>
                                  </div>
                                  <div className="mt-2 flex flex-wrap gap-2">
                                    {Array.isArray(medication.timing) &&
                                    medication.timing.length > 0 ? (
                                      <span className="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-medium text-slate-600">
                                        {medication.timing.join(", ")}
                                      </span>
                                    ) : null}
                                    {medication.foodRelation ? (
                                      <span className="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-medium text-slate-600">
                                        {medication.foodRelation}
                                      </span>
                                    ) : null}
                                  </div>
                                  {medication.instructions ? (
                                    <p className="mt-2 text-xs text-slate-600">
                                      {medication.instructions}
                                    </p>
                                  ) : null}
                                </div>
                              ))}
                            {prescriptionForm.medications.every(
                              (medication) =>
                                !medication.name &&
                                !medication.dosage &&
                                !medication.frequency &&
                                !medication.duration &&
                                !medication.foodRelation &&
                                (!Array.isArray(medication.timing) ||
                                  medication.timing.length === 0) &&
                                !medication.instructions,
                            ) ? (
                              <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                                No medications added.
                              </div>
                            ) : null}
                          </div>
                        </div>

                        <div className="rounded-2xl border border-[#b7d3df] bg-[#f0f9fc] p-4">
                          <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-[#0B4D67]">
                            Vet Advice
                          </p>
                          <p className="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">
                            {prescriptionForm.homeCare ||
                              "Standard care recommended."}
                          </p>
                        </div>

                        <div className="grid gap-4 border-t border-slate-200 pt-5 md:grid-cols-2">
                          <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p className="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                              Follow-Up Plan
                            </p>
                            <p className="mt-1 text-sm font-semibold text-slate-900">
                              {followUpDisplayLabel}
                              {prescriptionForm.followUpRequired === "yes" ? (
                                <span className="text-[#0B4D67]">
                                  {" "}
                                  ({followUpModeLabel})
                                </span>
                              ) : null}
                            </p>
                            {prescriptionForm.followUpNotes ? (
                              <p className="mt-2 text-xs text-slate-600">
                                {prescriptionForm.followUpNotes}
                              </p>
                            ) : null}
                          </div>
                          <div className="rounded-xl border border-slate-200 bg-white p-3 text-right">
                            <p className="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                              Attending Veterinarian
                            </p>
                            <p className="mt-1 text-sm font-semibold text-slate-900">
                              {assignedDoctorName}
                            </p>
                            <p className="text-xs text-slate-500">
                              License: {doctorLicenseLabel}
                            </p>
                            <p className="text-xs text-slate-500">
                              Clinic: {clinicNameLabel}
                            </p>
                            <p className="mt-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                              Powered by SnoutIQ
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>

                    {prescriptionError ? (
                      <div className="flex items-start gap-2 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-600">
                        <AlertCircle size={16} />
                        <span>{prescriptionError}</span>
                      </div>
                    ) : null}

                    <div className="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1.45fr]">
                      <button
                        type="button"
                        onClick={() => setPrescriptionView("edit")}
                        className="rounded-2xl border border-gray-200 bg-white px-6 py-3 text-base font-semibold text-gray-600 hover:bg-gray-50"
                      >
                        Edit Details
                      </button>
                      <button
                        type="submit"
                        disabled={prescriptionSubmitting}
                        className={`rounded-2xl bg-gradient-to-r from-[#4f46e5] to-[#4338ca] px-6 py-3 text-base font-semibold text-white shadow-[0_10px_30px_rgba(79,70,229,0.35)] hover:from-[#4338ca] hover:to-[#3730a3] ${
                          prescriptionSubmitting
                            ? "opacity-60 cursor-not-allowed"
                            : ""
                        }`}
                      >
                        {prescriptionSubmitting ? "Sending..." : "Save & Share"}
                      </button>
                    </div>
                    <p className="text-center text-[11px] text-gray-500">
                      Save. (Upon save the prescription will be shared to the
                      pet parent in app)
                    </p>
                  </div>
                )}
              </form>
            </div></div>
  )
}

export default NewDoctorDigitalPrescription