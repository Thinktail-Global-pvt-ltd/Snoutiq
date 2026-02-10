import React from "react";
import { useLocation } from "react-router-dom";
import { ConfirmationScreen } from "../screen/Paymentscreen";

const ConsultationBooked = () => {
  const location = useLocation();
  const vet = location.state?.vet || null;

  return <ConfirmationScreen vet={vet} />;
};

export default ConsultationBooked;
