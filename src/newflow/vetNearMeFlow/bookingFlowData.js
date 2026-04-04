export const BOOKING_FLOW_ROUTES = {
  lead: "/vet-near-me-delhi-ncr",
  petDetails: "/vet-near-me-delhi-ncr/pet-details",
  payment: "/vet-near-me-delhi-ncr/payment",
  success: "/vet-near-me-delhi-ncr/success",
};

export const BOOKING_FLOW_STORAGE_KEY = "snoutiq-vet-near-me-booking";

export const BOOKING_PRICING = {
  currentPrice: 999,
  originalPrice: 1249,
  discountAmount: 250,
};

export const DEFAULT_BOOKING_STATE = {
  lead: {
    name: "",
    phone: "",
    species: "",
    area: "",
    reason: "",
  },
  pet: {
    petName: "",
    breed: "",
    dob: "",
    sex: "",
    issue: "",
    symptoms: [],
    vaccinationStatus: "",
    deworming: "",
    history: "",
    medications: "",
    allergies: "",
    notes: "",
  },
  booking: {
    bookingReference: "",
  },
  progress: {
    leadSubmitted: false,
    petDetailsSubmitted: false,
    paymentCompleted: false,
  },
};

export const PET_TYPE_OPTIONS = ["Dog", "Cat", "Other"];

export const AREA_OPTIONS = [
  "Gurgaon",
  "Noida",
  "South Delhi",
  "Dwarka",
  "Faridabad",
  "Greater Noida",
  "Other NCR area",
];

export const REASON_OPTIONS = [
  "My pet is unwell / sick",
  "Vaccination",
  "Annual health check",
  "Deworming / tick treatment",
  "Wound or injury",
  "Puppy / kitten first check",
  "Other",
];

export const SEX_OPTIONS = [
  "Male",
  "Female",
  "Male (neutered)",
  "Female (spayed)",
];

export const VACCINATION_OPTIONS = [
  "Up to date",
  "Overdue / not sure",
  "Not vaccinated",
];

export const SYMPTOM_OPTIONS = [
  { value: "Not eating", label: "Not eating / low appetite" },
  { value: "Vomiting", label: "Vomiting" },
  { value: "Loose stools", label: "Loose stools / diarrhoea" },
  { value: "Lethargy", label: "Lethargy / low energy" },
  { value: "Fever", label: "Fever / feels warm" },
  { value: "Skin/itching", label: "Skin issue / itching / rash" },
  { value: "Wound", label: "Wound or injury" },
  { value: "Cough/sneeze", label: "Coughing / sneezing" },
  { value: "Limping", label: "Limping / difficulty moving" },
];

export const TRUST_PILLS = [
  "Only experienced, BVSc vets",
  "High-rated vets only",
  "Written visit summary",
  "Pet health record saved",
  "No surprise charges",
  "100% refund guarantee",
];

export const FEATURES = [
  {
    icon: "🩺",
    title: "Full examination, not a five-minute glance",
    body: "History, vitals and a thorough physical check — the same structured exam a good clinic would do, at your home, with time for your questions.",
  },
  {
    icon: "💊",
    title: "Medicines brought to your door",
    body: "The vet arrives with essential medicines and equipment. Up to ₹200 of in-visit medicines are included; anything additional is discussed before it's given.",
  },
  {
    icon: "📋",
    title: "Written visit report sent to you",
    body: "Diagnosis, treatment given, care plan and warning signs to watch for — documented and shared so you have it in writing, not just in your memory.",
  },
  {
    icon: "📁",
    title: "Your pet's health record, always saved",
    body: "Every home visit is logged to your Snoutiq profile. Vaccines, prescriptions and follow-ups in one place — not scattered across WhatsApp chats.",
  },
  {
    icon: "📍",
    title: "Vet near you across all of Delhi NCR",
    body: "Home visit vets available in Gurgaon, South & West Delhi, Noida, Dwarka, Faridabad and Greater Noida. Same-day visits in most areas.",
  },
  {
    icon: "🔒",
    title: "Honest if your pet needs a clinic",
    body: "If your case genuinely needs X-rays, surgery or hospitalisation, the vet will tell you directly and guide you to the right facility — not push unnecessary home care.",
  },
];

export const NETWORK_BADGES = [
  "Partner clinics in Gurgaon, South Delhi, West Delhi",
  "X-ray, lab and surgery referrals via partner clinics",
  "Seamless handover from home visit to clinic when needed",
];

export const STANDARD_CHECKS = [
  {
    title: "Proven home-visit track record",
    body: "Vets with hundreds of successful home visits — or strong equivalent clinic experience with dogs and cats in real-life situations.",
  },
  {
    title: "Consistently high ratings from pet parents",
    body: "We review feedback regularly. Only vets who maintain strong ratings stay active on Snoutiq.",
  },
  {
    title: "BVSc & AH degree, VCI-registered",
    body: "Qualifications and registration are verified before any vet joins the platform. No exceptions.",
  },
  {
    title: "Follows our home-visit protocol",
    body: "A standard checklist for history, examination and documentation — so your pet gets a complete visit, not a rushed drop-in.",
  },
  {
    title: "Explains everything before leaving",
    body: "Diagnosis, treatment options, costs and daily care in plain language — with time for your questions.",
  },
];

export const FEATURED_VETS = [
  {
    initials: "AK",
    name: "Dr. Ananya Kapoor",
    credentials: "BVSc & AH · 8+ yrs · Gurgaon",
    tags: ["4.9★ rated", "Canine medicine", "Allergies & skin"],
    statLine1: "1,500+",
    statLine2: "90%+",
  },
  {
    initials: "RS",
    name: "Dr. Rohan Singh",
    credentials: "BVSc & AH · 6 yrs · South Delhi",
    tags: ["4.9★ rated", "Cats & anxious pets", "Fear-free"],
    statLine1: "1,200+",
    statLine2: "93%+",
  },
  {
    initials: "SM",
    name: "Dr. Sana Malik",
    credentials: "BVSc & AH · 10 yrs · Noida",
    tags: ["5.0★ rated", "Internal medicine", "Puppy care"],
    statLine1: "2,000+",
    statLine2: "95%+",
  },
  {
    initials: "VB",
    name: "Dr. Vikram Bose",
    credentials: "BVSc & AH · 7 yrs · Noida / Gurgaon",
    tags: ["4.8★ rated", "Emergency cases", "Senior pets"],
    statLine1: "1,800+",
    statLine2: "88%+",
  },
];

export const VALUE_ROWS = [
  {
    label: "Who actually comes to your home",
    good: "Experienced BVSc vet only",
    bad: "Sometimes a compounder, nurse or support staff sent alone",
  },
  {
    label: "Qualifications verified?",
    good: "Yes — degree & VCI registration checked before onboarding",
    bad: "Rarely verified; you take their word for it",
  },
  {
    label: "Visit behaviour",
    good: "On-time, structured examination, time for your questions",
    bad: "Often rushed, minimal explanation, quick injection and leave",
  },
  {
    label: "Written documentation",
    good: "Written report + care plan shared after every visit",
    bad: "Usually nothing in writing",
  },
  {
    label: "Clinical protocol",
    good: "Standard home-visit checklist, reviewed by senior vets",
    bad: "No standard process — depends entirely on the individual",
  },
  {
    label: "Pet health record",
    good: "Saved to your Snoutiq profile after every visit",
    bad: "Scattered across WhatsApp chats and loose papers",
  },
  {
    label: "Medicine transparency",
    good: "Up to ₹200 included; extras discussed before administering",
    bad: "Costs often unclear until after the visit is done",
  },
  {
    label: "Communication after booking",
    good: "Dedicated Pet Parent Assistant — confirms vet, tracks movement, updates you at every step until vet arrives",
    bad: "No updates after booking. You're left wondering if anyone is coming",
  },
];

export const HOW_IT_WORKS_STEPS = [
  {
    title: "Fill the form or call us",
    body: "Submit your query — pet type, area, what you need. Takes under a minute. No long forms, no account required upfront.",
  },
  {
    title: "Your Pet Parent Assistant is assigned",
    body: "A dedicated Snoutiq assistant takes ownership of your case. They call you, confirm the details and handle everything from here — you don't have to chase anyone.",
  },
  {
    title: "Vet confirmed and on the way",
    body: "Your assistant identifies the nearest suitable vet, confirms the visit and shares the payment link. Once paid, you're notified when the vet starts moving towards you.",
  },
  {
    title: "Vet at your door within 60 minutes",
    body: "Target is vet at home within 60 minutes of booking confirmation. Your assistant updates you throughout — no silence, no dropped ball, no anxious waiting.",
  },
];

export const COVERAGE_AREAS = [
  "Gurgaon",
  "South Delhi",
  "West Delhi",
  "East Delhi",
  "Dwarka",
  "Noida",
  "Greater Noida",
  "Faridabad",
  "More areas",
];

export const REVIEWS = [
  {
    quote: '"I was sceptical — but the difference was obvious."',
    body: "The vet took a proper history, examined Max thoroughly and gave me a written note at the end. The whole thing felt very considered. We've now booked twice more.",
    name: "Ankita R., Gurgaon",
    petTag: "Dog · Labrador",
  },
  {
    quote: '"My cat is terrified of travel. This solved it completely."',
    body: "Momo used to skip annual check-ups because the carrier is a nightmare. Vet came home, Momo was calm the whole time. We're now on a quarterly schedule.",
    name: "Siddharth M., South Delhi",
    petTag: "Cat",
  },
  {
    quote: '"They were honest when Bruno needed a clinic."',
    body: "Bruno had a breathing issue. The vet assessed him, said clearly he needed an X-ray and told me exactly which clinic to go to. That honesty is rare — I trust them because of it.",
    name: "Ritu K., Noida",
    petTag: "Dog · Indie",
  },
];

export const FAQ_ITEMS = [
  {
    q: "Is a home vet visit as thorough as going to a clinic?",
    a: "For most situations — yes. Vaccinations, health checks, deworming, fever and illness assessment, wound care and many skin issues can all be handled at home by a qualified vet. For cases needing X-rays, surgery or hospitalisation, you still need a clinic — and our vets will tell you clearly if that's the case.",
  },
  {
    q: "How quickly can I get a vet near me?",
    a: "We prioritise based on urgency and your area. In most core Delhi NCR areas — Gurgaon, South Delhi, Noida — same-day visits are regularly arranged. For urgent cases, call us directly so we can assess and guide you to the safest option.",
  },
  {
    q: "What does the ₹999 fee include?",
    a: "The fee covers the vet's home visit, full examination, basic treatment, up to ₹200 of essential medicines used during the visit, a written report and your pet's record saved on Snoutiq. Any additional medicines or tests are discussed with you before you decide — no surprise charges.",
  },
  {
    q: "Do you have vets near me for cats as well?",
    a: "Yes — experienced vets for both dogs and cats across all Delhi NCR areas. For other animals, fill the form and mention your pet type; we'll confirm availability with our network.",
  },
  {
    q: "How do payments and refunds work?",
    a: "After our team calls you to confirm, we send a secure online payment link. If after payment we are unable to confirm a suitable vet for your area, we initiate a 100% refund immediately — no questions asked.",
  },
];
