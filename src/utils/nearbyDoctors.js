export function mergeNearbyDoctors(clinicPayload, doctorPayload) {
  const clinics = buildClinicMap(clinicPayload);
  const doctorMap = buildDoctorMap(doctorPayload, clinics);
  synthesizeDoctorsFromClinics(doctorMap, clinicPayload, clinics);

  return Array.from(doctorMap.values()).sort((a, b) => {
    const distanceA =
      a.distance !== undefined && a.distance !== null ? a.distance : Infinity;
    const distanceB =
      b.distance !== undefined && b.distance !== null ? b.distance : Infinity;
    return distanceA - distanceB;
  });
}

function buildClinicMap(payload) {
  const clinics = new Map();

  if (!payload || !Array.isArray(payload.data)) {
    return clinics;
  }

  payload.data.forEach((rawClinic) => {
    const clinic = extractClinic(rawClinic);
    if (clinic) {
      clinics.set(clinic.clinic_id, clinic);
    }
  });

  return clinics;
}

function buildDoctorMap(payload, clinics) {
  const doctorMap = new Map();

  if (!payload || !Array.isArray(payload.data)) {
    return doctorMap;
  }

  payload.data.forEach((rawDoctor) => {
    const doctorId = toNumber(
      rawDoctor?.doctor_id ?? rawDoctor?.id ?? rawDoctor?.doctor?.id
    );
    if (!doctorId || doctorMap.has(doctorId)) {
      return;
    }

    const clinicId = toNumber(
      rawDoctor?.clinic_id ??
        rawDoctor?.vet_registeration_id ??
        rawDoctor?.doctor?.clinic_id
    );
    const clinic = clinicId ? clinics.get(clinicId) : undefined;

    doctorMap.set(
      doctorId,
      normaliseDoctor(rawDoctor, clinic, doctorId, clinicId)
    );
  });

  return doctorMap;
}

function synthesizeDoctorsFromClinics(doctorMap, clinicPayload, clinics) {
  if (
    !clinicPayload ||
    typeof clinicPayload.available_doctors_by_vet !== "object" ||
    clinicPayload.available_doctors_by_vet === null
  ) {
    return;
  }

  Object.entries(clinicPayload.available_doctors_by_vet).forEach(
    ([clinicKey, doctorEntries]) => {
      const clinicId = toNumber(clinicKey);
      if (!clinicId || !Array.isArray(doctorEntries)) {
        return;
      }

      const clinic = clinics.get(clinicId);
      doctorEntries.forEach((entry) => {
        const doctorId = extractDoctorId(entry);
        if (!doctorId || doctorMap.has(doctorId)) {
          return;
        }

        doctorMap.set(
          doctorId,
          normaliseDoctor({ clinic_id: clinicId }, clinic, doctorId, clinicId)
        );
      });
    }
  );
}

function extractClinic(raw = {}) {
  const clinicId = toNumber(
    raw.clinic_id ?? raw.vet_registeration_id ?? raw.vet_id ?? raw.id
  );
  if (!clinicId) {
    return null;
  }

  return {
    ...raw,
    clinic_id: clinicId,
    name: raw.name ?? raw.business_status ?? "Veterinary Clinic",
    business_status:
      raw.business_status ?? raw.name ?? "Veterinary Clinic",
    rating:
      raw.rating !== undefined && raw.rating !== null
        ? Number(raw.rating)
        : null,
    distance:
      raw.distance !== undefined && raw.distance !== null
        ? Number(raw.distance)
        : null,
    chat_price:
      raw.chat_price !== undefined && raw.chat_price !== null
        ? Number(raw.chat_price)
        : null,
    open_now: raw.open_now ?? null,
    profile_image: raw.image ?? raw.profile_image ?? null,
  };
}

function normaliseDoctor(source = {}, clinic, doctorId, clinicId) {
  const rawDoctor =
    source.doctor && typeof source.doctor === "object" ? source.doctor : {};

  const clinicName =
    source.clinic_name ??
    clinic?.business_status ??
    clinic?.name ??
    "Veterinary Clinic";

  const doctorName =
    rawDoctor.doctor_name ??
    rawDoctor.name ??
    rawDoctor.full_name ??
    source.name ??
    source.doctor_name ??
    `Veterinarian • ${clinicName}`;

  const profileImage =
    source.profile_image ??
    source.doctor_image ??
    rawDoctor.image ??
    clinic?.profile_image ??
    clinic?.image ??
    null;

  const rating =
    source.rating !== undefined && source.rating !== null
      ? Number(source.rating)
      : clinic?.rating !== undefined && clinic?.rating !== null
      ? Number(clinic.rating)
      : null;

  const distance =
    source.distance !== undefined && source.distance !== null
      ? Number(source.distance)
      : clinic?.distance !== undefined && clinic?.distance !== null
      ? Number(clinic.distance)
      : null;

  const chatPrice =
    source.chat_price ??
    source.video_consult_price ??
    source.consult_price ??
    clinic?.chat_price ??
    null;

  const availability =
    source.toggle_availability ??
    source.is_available ??
    source.is_online ??
    source.open_now ??
    rawDoctor.toggle_availability ??
    null;

  const slugCandidate =
    source.slug ??
    rawDoctor.slug ??
    clinic?.slug ??
    clinic?.public_id ??
    clinicId ??
    doctorId;

  return {
    id: doctorId,
    clinic_id: clinicId,
    name: doctorName,
    clinic_name: clinicName,
    business_status:
      source.business_status ?? clinic?.business_status ?? doctorName,
    profile_image: profileImage,
    rating,
    distance,
    chat_price: chatPrice !== null ? Number(chatPrice) : null,
    open_now: source.open_now ?? clinic?.open_now ?? null,
    toggle_availability: availability,
    slug: String(slugCandidate).toLowerCase(),
    doctor: {
      id: doctorId,
      name: doctorName,
      email: rawDoctor.email ?? source.email ?? null,
      mobile: rawDoctor.mobile ?? source.mobile ?? null,
      license: rawDoctor.license ?? source.license ?? null,
      image: profileImage,
      clinic_id: clinicId,
    },
  };
}

function toNumber(value) {
  if (value === null || value === undefined) {
    return null;
  }
  const num = Number(value);
  return Number.isFinite(num) ? num : null;
}

function extractDoctorId(entry) {
  if (Array.isArray(entry)) {
    return extractDoctorId(entry[0]);
  }

  if (entry && typeof entry === "object") {
    return toNumber(
      entry.doctor_id ?? entry.id ?? entry.value ?? entry.key ?? null
    );
  }

  return toNumber(entry);
}
