import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

const primary = "#2563eb";

export function showBookingError(message, title = "Booking failed") {
  return Swal.fire({
    icon: "error",
    title,
    text: String(message || "Please try again."),
    confirmButtonColor: primary,
  });
}

export function showBookingSuccess(message, title = "Appointment booked successfully") {
  return Swal.fire({
    icon: "success",
    title,
    text: String(message || "Your booking is confirmed."),
    confirmButtonColor: primary,
  });
}

export function showBookingWarning(message, title = "Verification pending") {
  return Swal.fire({
    icon: "warning",
    title,
    text: String(message || "We are verifying your booking."),
    confirmButtonColor: primary,
  });
}

export function showBookingInfo(message, title = "Booking update") {
  return Swal.fire({
    icon: "info",
    title,
    text: String(message || ""),
    confirmButtonColor: primary,
  });
}

export function confirmPaymentStart({ amount, title = "Continue to payment", text } = {}) {
  return Swal.fire({
    icon: "question",
    title,
    text: text || `You will pay Rs ${amount}.`,
    showCancelButton: true,
    confirmButtonText: "Pay now",
    cancelButtonText: "Cancel",
    confirmButtonColor: primary,
  });
}
