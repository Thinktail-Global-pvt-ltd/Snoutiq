"use client";

import { useEffect, useMemo, useState } from "react";
import {
  ArrowLeft,
  Search,
  ChevronRight,
  Phone,
  Clock3,
  PawPrint,
} from "lucide-react";
import { useNavigate } from "react-router-dom";
import { useNewDoctorAuth } from "./NewDoctorAuth";
import {
  buildExistingParentFlowSearch,
  writeStoredDoctorSelectedParent,
} from "./selectedParentStorage";

const DOCTOR_USERS_URL = "https://snoutiq.com/backend/api/doctor/users";

const normalizeId = (value) => {
  const next = String(value ?? "").trim();
  return next && next !== "null" && next !== "undefined" ? next : "";
};

const formatDate = (value) => {
  if (!value) return "N/A";
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return String(value);
  return parsed.toLocaleDateString("en-IN", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const getInitialLetter = (value, fallback = "P") => {
  const text = String(value || "").trim();
  if (!text) return fallback;
  return text.charAt(0).toUpperCase();
};

const normalizeDoctorUsers = (payload = []) => {
  if (!Array.isArray(payload)) return [];

  return payload.flatMap((user) => {
    const pets = Array.isArray(user?.pets) ? user.pets : [];

    if (!pets.length) {
      return [
        {
          id: `${user?.id || "user"}-no-pet`,
          routeId: `${user?.id || "user"}-no-pet`,
          userId: normalizeId(user?.id),
          name: user?.name || "Pet Parent",
          phone: user?.phone || "",
          email: user?.email || "",
          city: user?.city || "",
          petCount: 0,
          petId: "",
          petName: user?.pet_name || "",
          breed: user?.breed || "",
          petGender: user?.pet_gender || "",
          petAge: user?.pet_age || "",
          petWeight: user?.pet_weight || user?.weight || "",
          petType: "",
          pets: [],
          lastDate: user?.updated_at || user?.created_at || "",
          consults: 0,
          revenue: 0,
          history: [],
        },
      ];
    }

    return pets.map((pet) => ({
      id: `${user?.id || "user"}-${pet?.id || "pet"}`,
      routeId: `${user?.id || "user"}-${pet?.id || "pet"}`,
      userId: normalizeId(user?.id),
      name: user?.name || "Pet Parent",
      phone: user?.phone || "",
      email: user?.email || "",
      city: user?.city || "",
      petCount: pets.length,
      petId: normalizeId(pet?.id),
      petName: pet?.name || user?.pet_name || "",
      breed: pet?.breed || user?.breed || "",
      petGender: pet?.pet_gender || user?.pet_gender || "",
      petAge: pet?.pet_age ?? pet?.pet_age_months ?? user?.pet_age ?? "",
      petWeight:
        pet?.weight ??
        pet?.pet_weight ??
        pet?.weight_kg ??
        user?.pet_weight ??
        user?.weight ??
        "",
      petType: pet?.pet_type || "",
      pets,
      selectedPet: pet,
      lastDate: pet?.updated_at || user?.updated_at || user?.created_at || "",
      consults: 0,
      revenue: 0,
      history: [],
    }));
  });
};

export default function NewDoctorSearchView() {
  const navigate = useNavigate();
  const { auth } = useNewDoctorAuth();

  const doctorId = normalizeId(
    auth?.vet_registeration_id ||
      auth?.vet_registeration?.id ||
      auth?.doctor?.vet_registeration_id,
  );

  const [query, setQuery] = useState("");
  const [parents, setParents] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [searchError, setSearchError] = useState("");

  useEffect(() => {
    let active = true;
    const controller = new AbortController();

    const fetchDoctorUsers = async () => {
      if (!doctorId) {
        if (active) {
          setParents([]);
          setSearchError("Doctor session not found.");
        }
        return;
      }

      try {
        setIsLoading(true);
        setSearchError("");

        const url = new URL(DOCTOR_USERS_URL);
        url.searchParams.set("doctor_id", doctorId);

        const headers = {
          Accept: "application/json",
        };

        const authToken = auth?.token || auth?.access_token;
        if (authToken) {
          headers.Authorization = `Bearer ${authToken}`;
        }

        const response = await fetch(url.toString(), {
          method: "GET",
          headers,
          signal: controller.signal,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload?.success === false) {
          throw new Error(payload?.message || "Failed to fetch pet parents.");
        }

        if (!active) return;

        setParents(normalizeDoctorUsers(payload?.data));
      } catch (error) {
        if (error?.name === "AbortError") return;
        if (!active) return;

        setParents([]);
        setSearchError(
          error?.message || "Unable to load pet parent list right now.",
        );
      } finally {
        if (active) {
          setIsLoading(false);
        }
      }
    };

    fetchDoctorUsers();

    return () => {
      active = false;
      controller.abort();
    };
  }, [auth?.access_token, auth?.token, doctorId]);

  const filteredParents = useMemo(() => {
    const value = query.trim().toLowerCase();
    if (!value) return parents;

    return parents.filter((item) => {
      const haystack = [
        item.name,
        item.phone,
        item.email,
        item.petName,
        item.breed,
        item.city,
      ]
        .map((entry) => String(entry || "").toLowerCase())
        .join(" ");

      return haystack.includes(value);
    });
  }, [parents, query]);

  const handleOpenProfile = (parent) => {
    writeStoredDoctorSelectedParent(parent);

    navigate(`/counsltflow/new-request${buildExistingParentFlowSearch()}`, {
      state: {
        parent,
        existingParentFlow: true,
      },
    });
  };

  return (
    <div className="min-h-screen bg-[#f3f4f6] flex flex-col">
      <div className="flex items-center gap-3 px-5 h-[68px] bg-[#16a34a] text-white shadow-[0_2px_12px_rgba(0,0,0,0.08)]">
        <button type="button" onClick={() => navigate(-1)}>
          <ArrowLeft size={22} />
        </button>
        <h1 className="text-[18px] font-bold">Search Pet Parent</h1>
      </div>

      <div className="px-3 pt-4 pb-2">
        <div className="flex items-center gap-2 rounded-[16px] border border-[#d9dce3] bg-white px-4 h-[48px] shadow-sm">
          <Search size={18} className="text-[#9aa3b2]" />
          <input
            type="text"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search by name, number, pet..."
            className="w-full bg-transparent outline-none text-[15px] text-[#334155] placeholder:text-[#8b95a7]"
          />
        </div>
      </div>

      <div className="flex-1 px-3 py-1 space-y-3">
        {isLoading ? (
          <div className="flex items-center justify-center rounded-[18px] border border-[#dddddd] bg-white px-4 py-10 text-[14px] text-[#98a2b3]">
            Loading pet parents...
          </div>
        ) : searchError ? (
          <div className="flex items-center justify-center rounded-[18px] border border-red-100 bg-red-50 px-4 py-10 text-[14px] text-red-600">
            {searchError}
          </div>
        ) : filteredParents.length > 0 ? (
          filteredParents.map((item) => (
            <button
              key={item.id}
              type="button"
              onClick={() => handleOpenProfile(item)}
              className="w-full rounded-[18px] border border-[#dddddd] bg-white px-4 py-4 text-left shadow-[0_2px_8px_rgba(15,23,42,0.05)]"
            >
              <div className="flex items-center gap-4">
                <div className="flex h-[54px] w-[54px] shrink-0 items-center justify-center rounded-full bg-[#dff2e6] text-[24px] font-bold text-[#22c55e]">
                  {getInitialLetter(item.name)}
                </div>

                <div className="min-w-0 flex-1">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <p className="truncate text-[14px] font-bold text-[#0f172a]">
                        {item.name}
                      </p>

                      <p className="mt-1 flex items-center gap-1 text-[12px] text-[#667085]">
                        <Phone size={12} />
                        {item.phone || "No number"}
                      </p>
                    </div>

                    <div className="shrink-0 rounded-full bg-[#eefbf2] px-2 py-[3px] text-[11px] font-semibold text-[#22c55e]">
                      active
                    </div>
                  </div>

                  <div className="mt-2 flex items-end justify-between gap-3">
                    <div>
                      <p className="flex items-center gap-1 text-[12px] text-[#667085]">
                        <PawPrint size={12} />
                        {item.petName || "Pet"} • {item.breed || "Breed NA"}
                      </p>
                      <p className="mt-1 text-[12px] text-[#98a2b3]">
                        Last: {formatDate(item.lastDate)}
                      </p>
                    </div>

                    <div className="flex items-center gap-3">
                      <p className="text-[20px] leading-none text-[#cfd4dc]">
                        <ChevronRight size={18} />
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </button>
          ))
        ) : (
          <div className="flex items-center justify-center rounded-[18px] border border-dashed border-[#d7dbe3] bg-white px-4 py-10 text-[14px] text-[#98a2b3]">
            No results found
          </div>
        )}
      </div>
    </div>
  );
}
