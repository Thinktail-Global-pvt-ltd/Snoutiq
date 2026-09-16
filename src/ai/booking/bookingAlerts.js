import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
import snoutiq_app_icon from "../../assets/snoutiq_app_icon.png";

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

export function confirmPaymentStart({ amount, title = "Continue to payment", text, confirmButtonText = "Pay now", cancelButtonText = "Cancel" } = {}) {
  const isMobile = typeof window !== "undefined" && window.innerWidth < 640;
  return Swal.fire({
    imageUrl: snoutiq_app_icon,
    imageWidth: isMobile ? 48 : 56,
    imageHeight: isMobile ? 48 : 56,
    imageAlt: "SnoutIQ",
    width: isMobile ? "86%" : "360px",
    padding: "0.75rem 1rem",
    title: `<span style="font-size: ${isMobile ? "0.95rem" : "1.05rem"}; font-weight: 700; color: #0f172a; display: block; margin-top: 2px;">${title}</span>`,
    html: `<p style="font-size: ${isMobile ? "0.8rem" : "0.85rem"}; color: #475569; margin-top: 3px; line-height: 1.35;">${text || `Pay Rs ${amount}.`}</p>`,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText,
    confirmButtonColor: primary,
    cancelButtonColor: "#64748b",
    customClass: {
      popup: "!rounded-3xl shadow-2xl !p-4",
      image: "!rounded-full !object-cover !m-0 !mx-auto shadow-sm border border-slate-100 ring-2 ring-blue-100",
      title: "!p-0 !m-0",
      htmlContainer: "!p-0 !m-0 !mt-2",
      actions: "!mt-3 !mb-1 !gap-2",
      confirmButton: "!rounded-xl !font-bold !text-xs sm:!text-sm !px-5 !py-2 shadow-xs",
      cancelButton: "!rounded-xl !font-bold !text-xs sm:!text-sm !px-5 !py-2",
    },
  });
}



