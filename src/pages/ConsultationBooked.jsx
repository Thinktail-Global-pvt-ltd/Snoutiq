import React from "react";
import { useLocation } from "react-router-dom";
import { ConfirmationScreen } from "../screen/Paymentscreen";

const ConsultationBooked = () => {
  const location = useLocation();
  const vet = location.state?.vet || null;
  const skipConversion = location.state?.skipConversion || false;

  return <ConfirmationScreen vet={vet} skipConversion={skipConversion} />;
};

export default ConsultationBooked;
