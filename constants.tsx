import { Vet, Symptom } from "./types";
import { Dog, Cat, Bird, Rat } from "lucide-react";
import React from "react";

/**
 * ✅ Excel/CSV (dummy_vet_form_responses.csv) ka **saara data** yahan add kar diya hai
 * ✅ Purana hardcoded VETS data remove
 *
 * NOTE:
 * - `image` placeholders hain (picsum). Real profile images aayen to replace kar dena.
 * - Sheet ke extra columns bhi objects me add kiye gaye hain (clinicName, intro, whatsapp, etc).
 * - TS type mismatch avoid karne ke liye array ko `as Vet[]` cast kiya gaya hai.
 */
export const VETS: Vet[] = ([
  {
    "id": "1",
    "name": "Dr. Ankit Sharma",
    "image": "https://picsum.photos/seed/ankit-clinic-com/200/200",
    "qualification": "BVSc",
    "experience": 8,
    "languages": ["English", "Hindi"],
    "rating": 4.9,
    "consultations": 0,
    "priceDay": 399,
    "priceNight": 599,
    "isOnline": true,
    "specialties": ["cat", "dog"],
    "clinicName": "Ankit Pet Clinic",
    "intro": "Caring vet with 8 years experience",
    "whatsapp": "9876543210",
    "email": "ankit@clinic.com",
    "responseTimeDay": "5-10 mins",
    "responseTimeNight": "10-15 mins",
    "dnd": "2–4 PM",
    "followUp3Days": "Yes",
    "payoutUpi": "ankit@upi",
    "agreement": "Agreed",
    "submittedAt": "2026-02-06 10:01:12"
  },
  {
    "id": "2",
    "name": "Dr. Riya Verma",
    "image": "https://picsum.photos/seed/riya-verma-vetmail-in/200/200",
    "qualification": "MVSc",
    "experience": 5,
    "languages": ["English", "Hindi"],
    "rating": 4.9,
    "consultations": 0,
    "priceDay": 449,
    "priceNight": 649,
    "isOnline": true,
    "specialties": ["dog"],
    "clinicName": "Happy Paws Clinic",
    "intro": "Specialist in dogs",
    "whatsapp": "9123456789",
    "email": "riya.verma@vetmail.in",
    "responseTimeDay": "8 mins",
    "responseTimeNight": "12 mins",
    "dnd": "1–2 PM",
    "followUp3Days": "No",
    "payoutUpi": "riya@upi",
    "agreement": "Agreed",
    "submittedAt": "2026-02-06 10:03:40"
  },
  {
    "id": "3",
    "name": "Dr. Mohit Singh",
    "image": "https://picsum.photos/seed/mohit-singh-petcare-org/200/200",
    "qualification": "BVSc & AH",
    "experience": 12,
    "languages": ["English", "Hindi"],
    "rating": 4.9,
    "consultations": 0,
    "priceDay": 349,
    "priceNight": 549,
    "isOnline": true,
    "specialties": ["bird", "cat", "dog"],
    "clinicName": "PetCare Center",
    "intro": "Experienced vet for multi-pet consults",
    "whatsapp": "9988776655",
    "email": "mohit.singh@petcare.org",
    "responseTimeDay": "6-9 mins",
    "responseTimeNight": "15 mins",
    "dnd": "3–5 PM",
    "followUp3Days": "Yes",
    "payoutUpi": "mohit@upi",
    "agreement": "Agreed",
    "submittedAt": "2026-02-06 10:07:10"
  },
  {
    "id": "4",
    "name": "Dr. Neha Gupta",
    "image": "https://picsum.photos/seed/neha-gupta-clinic-net/200/200",
    "qualification": "MVSc (Medicine)",
    "experience": 4,
    "languages": ["English", "Hindi"],
    "rating": 4.9,
    "consultations": 0,
    "priceDay": 399,
    "priceNight": 0,
    "isOnline": true,
    "specialties": ["exotic"],
    "clinicName": "Exotic Vet Hub",
    "intro": "Exotic pets specialist",
    "whatsapp": "9000011111",
    "email": "neha.gupta@clinic.net",
    "responseTimeDay": "10 mins",
    "responseTimeNight": "",
    "dnd": "",
    "followUp3Days": "Yes",
    "payoutUpi": "neha@upi",
    "agreement": "Agreed",
    "submittedAt": "2026-02-06 10:11:22"
  },
  {
    "id": "5",
    "name": "Dr. Arjun Mehta",
    "image": "https://picsum.photos/seed/arjun-mehta-vets-co/200/200",
    "qualification": "BVSc",
    "experience": 7,
    "languages": ["English", "Hindi"],
    "rating": 4.9,
    "consultations": 0,
    "priceDay": 499,
    "priceNight": 699,
    "isOnline": true,
    "specialties": ["cat", "dog"],
    "clinicName": "City Vet Care",
    "intro": "Friendly & fast consultations",
    "whatsapp": "9555566666",
    "email": "arjun.mehta@vets.co",
    "responseTimeDay": "7 mins",
    "responseTimeNight": "14 mins",
    "dnd": "2–3 PM",
    "followUp3Days": "No",
    "payoutUpi": "arjun@upi",
    "agreement": "Agreed",
    "submittedAt": "2026-02-06 10:15:05"
  }
]) as Vet[];

export const SYMPTOMS: Symptom[] = [
  "Not eating",
  "Vomiting/Loose motion",
  "Low energy",
  "Skin/Itching",
  "Limping",
  "Other",
];

export const SPECIALTY_ICONS: Record<string, React.ReactNode> = {
  dog: <Dog size={14} />,
  cat: <Cat size={14} />,
  bird: <Bird size={14} />,
  exotic: <Rat size={14} />,
};
