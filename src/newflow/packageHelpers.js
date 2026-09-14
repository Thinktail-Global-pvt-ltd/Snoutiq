export const formatMoney = (value) => {
  if (value === null || value === undefined || value === "") return null;
  const amount = Number(value);
  if (!Number.isFinite(amount) || amount <= 0) return null;
  return `₹${amount.toLocaleString("en-IN")}`;
};

export const formatLabel = (value) =>
  String(value || "")
    .replace(/_/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());

export const PACKAGE_DETAILS = {
  puppy_vaccination_package: {
    title: "Puppy Complete Vaccination Pack",
    category: "Vaccination",
    petType: "Dog",
    badge: "Most Popular",
    description: "Essential primary immunization shots against canine distemper, parvovirus & rabies with health card.",
    inclusions: [
      "DHPPiL Polyvalent Core Vaccine",
      "Anti-Rabies Primary Immunization",
      "Complete Physical & Vitals Check",
      "Deworming Protocol & Pet Record Card",
    ],
  },
  adult_dog_vaccination_package: {
    title: "Adult Dog Annual Health & Booster",
    category: "Vaccination",
    petType: "Dog",
    badge: "Annual Protection",
    description: "Yearly polyvalent booster and anti-rabies defense to ensure uninterrupted canine immunity.",
    inclusions: [
      "Annual DHPPiL 9-in-1 Booster",
      "Anti-Rabies Annual Shot",
      "Full Clinical Body & Dental Exam",
      "Preventive Health & Diet Consultation",
    ],
  },
  kitten_vaccination_package: {
    title: "Kitten Starter Vaccination Pack",
    category: "Vaccination",
    petType: "Cat",
    badge: "Kitten Starter",
    description: "Core Tricat (FVRCP) protection against feline panleukopenia, herpes & calici viruses plus rabies.",
    inclusions: [
      "FVRCP Tricat Core Vaccine",
      "Feline Anti-Rabies Shot",
      "Pediatric Vitals & Growth Check",
      "Deworming Dose & Kitten Booklet",
    ],
  },
  adult_cat_vaccination_package: {
    title: "Adult Cat Annual Booster Pack",
    category: "Vaccination",
    petType: "Cat",
    badge: "Annual Protection",
    description: "Yearly feline booster maintaining immunity against common infectious respiratory and viral pathogens.",
    inclusions: [
      "Annual FVRCP Booster Shot",
      "Anti-Rabies Booster",
      "Coat, Weight & Dental Checkup",
      "Nutrition & Wellness Guidance",
    ],
  },
  dog_vaccination_package: {
    title: "Dog Core Vaccination Package",
    category: "Vaccination",
    petType: "Dog",
    badge: "Essential Care",
    description: "Complete core vaccination schedule protecting against fatal viral and bacterial diseases.",
    inclusions: [
      "Core 9-in-1 / 7-in-1 Immunization Shot",
      "Anti-Rabies Vaccine",
      "Complete Physical Wellness Examination",
      "Official Vaccination Certificate",
    ],
  },
  cat_vaccination_package: {
    title: "Cat Core Vaccination Package",
    category: "Vaccination",
    petType: "Cat",
    badge: "Essential Care",
    description: "Complete core feline vaccination including respiratory and rabies protection.",
    inclusions: [
      "FVRCP Core Inoculation",
      "Anti-Rabies Vaccine",
      "Full Clinical Checkup",
      "Feline Health Card",
    ],
  },
  dog_vaccination_male_package: {
    title: "Male Dog Vaccination Package",
    category: "Vaccination",
    petType: "Dog",
    badge: "Specialized Plan",
    description: "Tailored immunization and health assessment designed for male dogs.",
    inclusions: [
      "Core Vaccine & Rabies Booster",
      "Physical & Reproductive Checkup",
      "Weight, Coat & Vitals Check",
      "Deworming & Health Card",
    ],
  },
  dog_vaccination_female_package: {
    title: "Female Dog Vaccination Package",
    category: "Vaccination",
    petType: "Dog",
    badge: "Specialized Plan",
    description: "Comprehensive immunization and preventive screening for female dogs.",
    inclusions: [
      "Core Polyvalent Vaccine & Rabies",
      "Physical & Reproductive Health Check",
      "Vitals & Dental Examination",
      "Vaccination Record Card",
    ],
  },
  cat_vaccination_male_package: {
    title: "Cat Vaccination Male Package",
    category: "Vaccination",
    petType: "Cat",
    badge: "Specialized Plan",
    description: "Specialized feline immunization and preventive checkup for male cats.",
    inclusions: [
      "Tricat Core Vaccine & Rabies Shot",
      "Complete Physical Examination",
      "Dedicated Doctor Consultation",
      "Health Records Update",
    ],
  },
  cat_vaccination_female_package: {
    title: "Cat Vaccination Female Package",
    category: "Vaccination",
    petType: "Cat",
    badge: "Specialized Plan",
    description: "Tailored vaccination and preventive health screening for female cats.",
    inclusions: [
      "Tricat Core Vaccine & Rabies Shot",
      "Complete Physical Examination",
      "Dedicated Doctor Consultation",
      "Health Records Update",
    ],
  },
  dog_neutering: {
    title: "Dog Neutering",
    category: "Surgery & Neutering",
    petType: "Dog",
    badge: "Specialized Plan",
    description: "Comprehensive surgical sterilization package designed for optimum pet wellness.",
    inclusions: [
      "Pre-Procedure Clinical Examination",
      "Sterile Surgical Procedure by Senior Vet",
      "Anesthesia & Vital Signs Monitoring",
      "Post-Op Pain Relief & Recovery Kit",
    ],
  },
  dog_neutering_female: {
    title: "Female Dog Spaying (Sterilization)",
    category: "Surgery & Neutering",
    petType: "Dog",
    badge: "Safe Surgery",
    description: "Advanced surgical ovariohysterectomy preventing heat cycles, pyometra (uterine infection) & mammary tumors.",
    inclusions: [
      "Pre-Surgical Clinical Evaluation",
      "Safe Anesthesia & Vitals Monitoring",
      "Sterile Surgical Procedure by Senior Vet",
      "Post-Op Pain Relief & Recovery Dressing",
    ],
  },
  dog_neutering_male: {
    title: "Male Dog Castration / Neutering",
    category: "Surgery & Neutering",
    petType: "Dog",
    badge: "Sterilization",
    description: "Safe surgical castration reducing testicular cancer risks, territorial marking & roaming tendencies.",
    inclusions: [
      "Pre-Operative Vitals Screening",
      "Surgical Castration by Experienced Surgeon",
      "Post-Operative Antibiotics & Pain Control",
      "Wound Care & Suture Removal Guidance",
    ],
  },
  cat_neutering: {
    title: "Cat Neutering",
    category: "Surgery & Neutering",
    petType: "Cat",
    badge: "Specialized Plan",
    description: "Minimally invasive feline sterilization for improved behavior and longevity.",
    inclusions: [
      "Pre-Op Clinical Health Check",
      "Safe Feline Anesthesia Protocol",
      "Precision Sterile Spaying Surgery",
      "Post-Op Antibiotics & Recovery Kit",
    ],
  },
  cat_neutering_female: {
    title: "Female Cat Spaying (Sterilization)",
    category: "Surgery & Neutering",
    petType: "Cat",
    badge: "Safe Surgery",
    description: "Minimally invasive spaying procedure eliminating loud heat calling, uterine infections & pregnancy.",
    inclusions: [
      "Pre-Op Clinical Health Check",
      "Safe Feline Anesthesia Protocol",
      "Precision Sterile Spaying Surgery",
      "Post-Op Antibiotics & Recovery Kit",
    ],
  },
  cat_neutering_male: {
    title: "Male Cat Castration / Neutering",
    category: "Surgery & Neutering",
    petType: "Cat",
    badge: "Sterilization",
    description: "Gentle sterilization for tomcats that stops pungent urine spraying, fighting, and wanderlust.",
    inclusions: [
      "Pre-Procedure Health Screening",
      "Quick & Gentle Castration Procedure",
      "Pain Relief & Recovery Injections",
      "Post-Surgical Care Instructions",
    ],
  },
};

export const extractPackageItems = (packages = []) => {
  const items = [];
  const list = Array.isArray(packages) ? packages : [packages].filter(Boolean);

  list.forEach((pack, packIndex) => {
    if (!pack || typeof pack !== "object") return;
    Object.entries(pack).forEach(([key, value]) => {
      if (!key.endsWith("_price")) return;
      if (value === null || value === undefined || value === "") return;
      const numPrice = Math.round(Number(value));
      if (isNaN(numPrice) || numPrice <= 0) return;
      const formatted = `₹${numPrice.toLocaleString("en-IN")}`;

      const baseKey = key.replace("_price", "");
      const meta = PACKAGE_DETAILS[baseKey] || {
        title: formatLabel(baseKey),
        category: baseKey.includes("vaccination")
          ? "Vaccination"
          : baseKey.includes("neutering") || baseKey.includes("surgery")
          ? "Surgery & Neutering"
          : "Health Care",
        petType: baseKey.includes("dog") || baseKey.includes("puppy")
          ? "Dog"
          : baseKey.includes("cat") || baseKey.includes("kitten")
          ? "Cat"
          : "Pet",
        badge: "Specialized Plan",
        description: "Comprehensive veterinary care package designed for optimum pet wellness.",
        inclusions: [
          "Complete Physical Examination",
          "Dedicated Doctor Consultation",
          "Health Records Update",
        ],
      };

      items.push({
        id: `${pack.id || packIndex}-${key}`,
        key: baseKey,
        price: numPrice,
        rawPrice: value,
        formattedPrice: formatted,
        doctorId: pack.doctor_id,
        doctorName: pack.doctor_name,
        clinicId: pack.clinic_id,
        ...meta,
      });
    });
  });

  return items;
};
