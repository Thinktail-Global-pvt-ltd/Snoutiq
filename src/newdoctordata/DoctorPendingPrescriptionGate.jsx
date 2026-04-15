import React, { useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { useNewDoctorAuth } from "./NewDoctorAuth";
import CompletePetProfileModal from "./CompletePetProfileModal";
import {
  syncDoctorPendingPrescriptionFromRouteState,
  updateDoctorPendingPrescriptionPatientData,
} from "./doctorPendingPrescriptionService";
import { useDoctorPendingPrescription } from "./useDoctorPendingPrescription";

const DIGITAL_PRESCRIPTION_PATH = "/counsltflow/digital-prescription";
const SKIPPED_PATHS = ["/counsltflow/login", "/counsltflow/onboarding"];

const hasDoctorSession = (auth) =>
  Boolean(
    auth?.onboarding_completed || (auth?.phone_verified && auth?.phone_exists),
  );

const getDoctorStorageId = (auth) =>
  auth?.doctor_id || auth?.doctor?.id || auth?.doctor?.doctor_id || "";

export default function DoctorPendingPrescriptionGate() {
  const navigate = useNavigate();
  const location = useLocation();
  const { auth, hydrated } = useNewDoctorAuth();

  const doctorId = getDoctorStorageId(auth);
  const shouldRun =
    hydrated &&
    hasDoctorSession(auth) &&
    !SKIPPED_PATHS.includes(location.pathname);

  const { pendingPrescription, refresh } = useDoctorPendingPrescription({
    doctorId,
    enabled: shouldRun,
  });

  const isPrescriptionPage = location.pathname === DIGITAL_PRESCRIPTION_PATH;
  const isLocked =
    shouldRun &&
    pendingPrescription.hasPending &&
    pendingPrescription.lockUntilSubmit;
  const showProfileModal =
    isLocked &&
    isPrescriptionPage &&
    pendingPrescription.missingFields.length > 0;

  useEffect(() => {
    if (!shouldRun || !isPrescriptionPage) {
      return;
    }

    syncDoctorPendingPrescriptionFromRouteState(doctorId, location.state);
  }, [doctorId, isPrescriptionPage, location.key, location.state, shouldRun]);

  useEffect(() => {
    if (!isLocked || isPrescriptionPage) {
      return;
    }

    navigate(DIGITAL_PRESCRIPTION_PATH, { replace: true });
  }, [isLocked, isPrescriptionPage, navigate]);

  const handleProfileSave = async (patientData) => {
    updateDoctorPendingPrescriptionPatientData(doctorId, patientData);
    refresh();
  };

  return (
    <>
      {isLocked && !isPrescriptionPage && (
        <div className="fixed inset-0 z-[70] bg-white/75 backdrop-blur-[1px]" />
      )}

      <CompletePetProfileModal
        isOpen={showProfileModal}
        patientData={pendingPrescription.patientData}
        missingFields={pendingPrescription.missingFields}
        onSave={handleProfileSave}
      />
    </>
  );
}
