import React, { useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { useNewDoctorAuth } from "./NewDoctorAuth";
import CompletePetProfileModal from "./CompletePetProfileModal";
import {
  hasDoctorPendingPrescriptionRouteState,
  stripDoctorPendingPrescriptionRouteState,
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
    Boolean(doctorId) &&
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
  const shouldRedirectToPrescription = isLocked && !isPrescriptionPage;
  const showProfileModal =
    isLocked &&
    isPrescriptionPage &&
    pendingPrescription.missingFields.length > 0;

  useEffect(() => {
    if (!shouldRun || !isPrescriptionPage) {
      return;
    }

    if (!hasDoctorPendingPrescriptionRouteState(location.state)) {
      return;
    }

    syncDoctorPendingPrescriptionFromRouteState(doctorId, location.state);
    navigate(
      {
        pathname: location.pathname,
        search: location.search,
        hash: location.hash,
      },
      {
        replace: true,
        state: stripDoctorPendingPrescriptionRouteState(location.state),
      },
    );
  }, [
    doctorId,
    isPrescriptionPage,
    location.hash,
    location.pathname,
    location.search,
    location.state,
    navigate,
    shouldRun,
  ]);

  useEffect(() => {
    if (!shouldRedirectToPrescription) {
      return;
    }

    navigate(DIGITAL_PRESCRIPTION_PATH, { replace: true });
  }, [navigate, shouldRedirectToPrescription]);

  const handleProfileSave = async (patientData) => {
    updateDoctorPendingPrescriptionPatientData(doctorId, patientData);
    refresh();
  };

  return (
    <>
      <CompletePetProfileModal
        isOpen={showProfileModal}
        patientData={pendingPrescription.patientData}
        missingFields={pendingPrescription.missingFields}
        onSave={handleProfileSave}
      />
    </>
  );
}
