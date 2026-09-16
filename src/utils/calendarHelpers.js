/**
 * Calendar Helper Utilities (Zero-Dependency)
 * Formats appointment timestamps to UTC ISO (YYYYMMDDTHHMMSSZ) for Google Calendar and iCal (.ics).
 */

export const normalizeAppointmentTimeForApi = (slot) => {
  if (!slot) return "10:00:00";
  const raw = typeof slot === "object" ? (slot.start || slot.value || slot.time || slot.label || "") : String(slot);
  const firstPart = raw.split("-")[0]?.trim() || raw;
  const amPmMatch = firstPart.match(/^(\d{1,2})(?::(\d{2}))?\s*(AM|PM)$/i);
  if (amPmMatch) {
    let hours = Number(amPmMatch[1]);
    const minutes = Number(amPmMatch[2] || 0);
    const meridian = amPmMatch[3].toUpperCase();
    if (meridian === "AM") {
      if (hours === 12) hours = 0;
    } else if (hours !== 12) {
      hours += 12;
    }
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:00`;
  }
  const match = String(firstPart || "").match(/(\d{1,2}):(\d{2})(?::(\d{2}))?/);
  if (match) {
    return `${String(match[1]).padStart(2, "0")}:${String(match[2]).padStart(2, "0")}:${String(match[3] || 0).padStart(2, "0")}`;
  }
  return "10:00:00";
};

/**
 * Parses appointment date & time slot to UTC ISO format (YYYYMMDDTHHMMSSZ)
 * Safely parses YYYY-MM-DD in local context to avoid timezone-shift bugs.
 */
export function parseAppointmentUtcDates(dateStr, timeSlotStr) {
  try {
    const rawDate = String(dateStr || "").trim();
    const normalizedTime = normalizeAppointmentTimeForApi(timeSlotStr);
    const [hours, minutes, seconds] = normalizedTime.split(":").map(Number);

    let startDate;
    const ymdMatch = rawDate.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (ymdMatch) {
      const year = Number(ymdMatch[1]);
      const month = Number(ymdMatch[2]) - 1;
      const day = Number(ymdMatch[3]);
      startDate = new Date(year, month, day, hours || 10, minutes || 0, seconds || 0, 0);
    } else {
      startDate = new Date();
      startDate.setHours(hours || 10, minutes || 0, seconds || 0, 0);
    }

    if (isNaN(startDate.getTime())) {
      startDate = new Date();
      startDate.setHours(hours || 10, minutes || 0, seconds || 0, 0);
    }

    // Default duration 30 minutes
    const endDate = new Date(startDate.getTime() + 30 * 60 * 1000);

    const formatUtcIso = (d) =>
      d.toISOString().replace(/-|:|\.\d+/g, "");

    return {
      startUtc: formatUtcIso(startDate),
      endUtc: formatUtcIso(endDate),
    };
  } catch (err) {
    const fallbackStart = new Date();
    const fallbackEnd = new Date(fallbackStart.getTime() + 30 * 60 * 1000);
    const formatUtcIso = (d) => d.toISOString().replace(/-|:|\.\d+/g, "");
    return {
      startUtc: formatUtcIso(fallbackStart),
      endUtc: formatUtcIso(fallbackEnd),
    };
  }
}

/**
 * Generates direct Google Calendar event creation URL
 */
export function getGoogleCalendarUrl({
  title = "SnoutIQ Vet Appointment",
  description = "",
  location = "",
  date,
  timeSlot,
  time,
}) {
  const effectiveTime = timeSlot || time;
  const { startUtc, endUtc } = parseAppointmentUtcDates(date, effectiveTime);

  const params = new URLSearchParams({
    action: "TEMPLATE",
    text: title,
    details: description,
    location: location || "SnoutIQ Online Consultation",
    dates: `${startUtc}/${endUtc}`,
  });

  return `https://calendar.google.com/calendar/render?${params.toString()}`;
}

/**
 * Generates and triggers download of .ics file for Apple / Outlook Calendar
 */
export function downloadIcsFile({
  title = "SnoutIQ Vet Appointment",
  description = "",
  location = "",
  date,
  timeSlot,
  time,
  filename = "snoutiq-appointment.ics",
}) {
  const effectiveTime = timeSlot || time;
  const { startUtc, endUtc } = parseAppointmentUtcDates(date, effectiveTime);
  const nowUtc = new Date().toISOString().replace(/-|:|\.\d+/g, "");

  // Escape special chars in ICS fields
  const cleanTitle = (title || "").replace(/[,;\\]/g, " ");
  const cleanDescription = (description || "").replace(/[,;\\]/g, " ").replace(/\n/g, "\\n");
  const cleanLocation = (location || "").replace(/[,;\\]/g, " ");

  const icsLines = [
    "BEGIN:VCALENDAR",
    "VERSION:2.0",
    "PRODID:-//SnoutIQ//Pet Healthcare Platform//EN",
    "CALSCALE:GREGORIAN",
    "METHOD:PUBLISH",
    "BEGIN:VEVENT",
    `UID:snoutiq-${Date.now()}@snoutiq.com`,
    `DTSTAMP:${nowUtc}`,
    `DTSTART:${startUtc}`,
    `DTEND:${endUtc}`,
    `SUMMARY:${cleanTitle}`,
    `DESCRIPTION:${cleanDescription}`,
    `LOCATION:${cleanLocation}`,
    "STATUS:CONFIRMED",
    "END:VEVENT",
    "END:VCALENDAR",
  ];

  const icsBlob = new Blob([icsLines.join("\r\n")], {
    type: "text/calendar;charset=utf-8",
  });
  const blobUrl = window.URL.createObjectURL(icsBlob);
  const downloadLink = document.createElement("a");
  downloadLink.href = blobUrl;
  downloadLink.setAttribute("download", filename);
  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
  window.URL.revokeObjectURL(blobUrl);
}
