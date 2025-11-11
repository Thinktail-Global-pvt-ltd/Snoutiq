import React,
{
  createContext,
  useState,
  useEffect,
  useContext,
  useCallback,
  useMemo,
  useRef,
} from "react";
import axios from "axios";
import { mergeNearbyDoctors } from "../utils/nearbyDoctors";
import { socket } from "../pages/socket";

export const AuthContext = createContext();

const CLINIC_NEARBY_API = "https://snoutiq.com/backend/api/nearby-vets";
const DOCTOR_NEARBY_API = "https://snoutiq.com/backend/api/nearby-doctors";

export const AuthProvider = ({ children }) => {
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [chatRoomToken, setChatRoomToken] = useState(null);

  const [nearbyDoctors, setNearbyDoctors] = useState([]);
  const [liveDoctors, setLiveDoctors] = useState([]);
  const [allActiveDoctors, setAllActiveDoctors] = useState([]);

  const isFetchingRef = useRef(false);
  const lastFetchTimeRef = useRef(0);
  const FETCH_COOLDOWN = 5000; // ms

  const memoizedNearbyDoctors = useMemo(() => nearbyDoctors, [nearbyDoctors]);
  const memoizedLiveDoctors = useMemo(() => liveDoctors, [liveDoctors]);

  // Load auth + doctors from localStorage
  useEffect(() => {
    try {
      const savedToken = localStorage.getItem("token");
      const savedUser = localStorage.getItem("user");
      const savedChatRoomToken = localStorage.getItem("chat_room_token");
      const savedDoctors = localStorage.getItem("nearby_doctors");
      const savedLiveDoctors = localStorage.getItem("live_doctors");

      if (savedToken) setToken(savedToken);
      if (savedUser) setUser(JSON.parse(savedUser));
      if (savedChatRoomToken) setChatRoomToken(savedChatRoomToken);

      if (savedDoctors) {
        const parsed = JSON.parse(savedDoctors);
        const normalized =
          Array.isArray(parsed)
            ? parsed
                .map(normalizeDoctorEntry)
                .filter((doctor) => doctor !== null)
            : [];
        setNearbyDoctors(normalized);
      }

      if (savedLiveDoctors) {
        const parsed = JSON.parse(savedLiveDoctors);
        const normalized =
          Array.isArray(parsed)
            ? parsed
                .map(normalizeDoctorEntry)
                .filter((doctor) => doctor !== null)
            : [];
        setLiveDoctors(normalized);
      }
    } catch (error) {
      console.error("Error loading auth data:", error);
    } finally {
      setLoading(false);
    }
  }, []);

  // Socket: listen to active doctors
  useEffect(() => {
    if (!socket) {
      console.warn("Socket not available");
      return;
    }

    console.log("🔌 Setting up socket listeners for active doctors");

    const handleActiveDoctors = (doctorIds) => {
      console.log("📡 Received active-doctors from socket:", doctorIds);

      const numericIds = Array.isArray(doctorIds)
        ? doctorIds
            .map((id) => Number(id))
            .filter((id) => Number.isFinite(id) && id > 0)
        : [];

      setAllActiveDoctors(numericIds);

      // ✅ CRITICAL FIX: Don't modify nearbyDoctors here at all!
      // Just update liveDoctors by filtering existing nearbyDoctors
      // This prevents placeholder creation from overwriting real data
      
      const liveNearbyDoctors = nearbyDoctors.filter((doctor) =>
        numericIds.includes(doctor.id)
      );

      console.log(
        `✅ ${liveNearbyDoctors.length} live doctors found from ${nearbyDoctors.length} nearby:`,
        liveNearbyDoctors.map((d) => `${d.name} (${d.id}) - ₹${d.chat_price || 'NO PRICE'}`)
      );

      setLiveDoctors(liveNearbyDoctors);
      localStorage.setItem(
        "live_doctors",
        JSON.stringify(liveNearbyDoctors)
      );
    };

    const handleDoctorOnline = (data) => {
      console.log(`🟢 Doctor came online: ${data.doctorId}`);
      socket.emit("get-active-doctors");
    };

    const handleDoctorOffline = (data) => {
      console.log(`🔴 Doctor went offline: ${data.doctorId}`);
      socket.emit("get-active-doctors");
    };

    socket.on("active-doctors", handleActiveDoctors);
    socket.on("doctor-online", handleDoctorOnline);
    socket.on("doctor-offline", handleDoctorOffline);

    console.log("🔄 Requesting initial active doctors list");
    socket.emit("get-active-doctors");

    const interval = setInterval(() => {
      socket.emit("get-active-doctors");
    }, 20000);

    return () => {
      console.log("🧹 Cleaning up socket listeners");
      socket.off("active-doctors", handleActiveDoctors);
      socket.off("doctor-online", handleDoctorOnline);
      socket.off("doctor-offline", handleDoctorOffline);
      clearInterval(interval);
    };
  }, [nearbyDoctors]);

  // Keep liveDoctors in sync when nearbyDoctors / allActiveDoctors change
  useEffect(() => {
    if (nearbyDoctors.length > 0 && allActiveDoctors.length > 0) {
      const updatedLiveDoctors = nearbyDoctors.filter((doctor) =>
        allActiveDoctors.includes(doctor.id)
      );

      console.log(
        `🔄 Auto-updating live doctors: ${updatedLiveDoctors.length} live out of ${nearbyDoctors.length} nearby`
      );

      setLiveDoctors(updatedLiveDoctors);
      localStorage.setItem(
        "live_doctors",
        JSON.stringify(updatedLiveDoctors)
      );
    }
  }, [nearbyDoctors, allActiveDoctors]);

  // Fetch nearby doctors (clinics + doctors) with cooldown + abort
  const fetchNearbyDoctors = useCallback(async () => {
    if (!token || !user?.id) {
      console.warn("No token or user ID available");
      return;
    }

    // Skip for doctors themselves
    if (user?.business_status) {
      console.log("ℹ️ User is a doctor, skipping nearby vets fetch");
      return;
    }

    const now = Date.now();
    if (isFetchingRef.current) {
      console.log("🔄 Fetch already in progress, skipping...");
      return;
    }

    if (now - lastFetchTimeRef.current < FETCH_COOLDOWN) {
      const waitMs = FETCH_COOLDOWN - (now - lastFetchTimeRef.current);
      console.log(
        `⏳ Cooldown active, wait ${Math.ceil(waitMs / 1000)}s`
      );
      return;
    }

    isFetchingRef.current = true;
    lastFetchTimeRef.current = now;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000);

    try {
      console.log("🔍 Fetching nearby doctors (clinics + individuals)...");

      const requestConfig = {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        signal: controller.signal,
      };

      const [clinicResult, doctorResult] = await Promise.allSettled([
        axios.get(
          `${CLINIC_NEARBY_API}?user_id=${user.id}`,
          requestConfig
        ),
        axios.get(
          `${DOCTOR_NEARBY_API}?user_id=${user.id}`,
          requestConfig
        ),
      ]);

      clearTimeout(timeoutId);

      const clinicPayload =
        clinicResult.status === "fulfilled"
          ? clinicResult.value.data
          : null;
      const doctorPayload =
        doctorResult.status === "fulfilled"
          ? doctorResult.value.data
          : null;

      const combined = mergeNearbyDoctors(clinicPayload, doctorPayload);

      if (combined.length) {
        console.log(`✅ Found ${combined.length} doctors (merged)`);
        
        // ✅ Log chat_price for debugging
        combined.forEach(doc => {
          console.log(`Doctor ${doc.name} (${doc.id}): chat_price = ${doc.chat_price || 'NOT SET'}`);
        });
        
        updateNearbyDoctors(combined);

        if (socket) {
          setTimeout(() => {
            socket.emit("get-active-doctors");
          }, 1000);
        }
      } else {
        console.warn("⚠️ No doctor data available after merge");
        updateNearbyDoctors([]);
      }
    } catch (error) {
      clearTimeout(timeoutId);

      if (
        error.name !== "AbortError" &&
        error.name !== "CanceledError"
      ) {
        if (error.response?.status !== 404) {
          console.error(
            "❌ Failed to fetch nearby doctors:",
            error.message
          );
        } else {
          console.log(
            "ℹ️ No nearby doctors found (404) - this can be normal"
          );
        }
      } else {
        console.log("🚫 Fetch cancelled");
      }
    } finally {
      isFetchingRef.current = false;
    }
  }, [token, user?.id]);

  // Initial + periodic fetch
  useEffect(() => {
    if (!token || !user?.id) return;

    fetchNearbyDoctors();

    const interval = setInterval(() => {
      fetchNearbyDoctors();
    }, 30 * 1000);

    return () => clearInterval(interval);
  }, [token, user?.id, fetchNearbyDoctors]);

  // Update user in state + localStorage
  const updateUser = async (newUserData) => {
    try {
      setUser((prevUser) => {
        const updatedUser = { ...prevUser, ...newUserData };
        localStorage.setItem("user", JSON.stringify(updatedUser));
        return updatedUser;
      });
    } catch (error) {
      console.error("Error updating user data:", error);
    }
  };

  // Login (ignores backend chat token, clears old chat room token)
  const login = async (userData, jwtToken, initialChatToken = null) => {
    try {
      setUser(userData);
      setToken(jwtToken);
      localStorage.setItem("token", jwtToken);
      localStorage.setItem("user", JSON.stringify(userData));

      if (initialChatToken) {
        console.log(
          "⚠️ Ignoring backend chat token to avoid auto chat creation."
        );
      }

      localStorage.removeItem("chat_room_token");
      setChatRoomToken(null);

      setTimeout(() => {
        if (userData?.business_status !== 1) {
          fetchNearbyDoctors();
        } else {
          console.log(
            "🩺 Doctor account detected, skipping nearby doctors fetch."
          );
        }
      }, 1200);
    } catch (error) {
      console.error("❌ Error during login:", error);
    }
  };

  // Update nearby doctors (normalized & unique)
  const updateNearbyDoctors = useCallback((doctors) => {
    try {
      const uniqueMap = new Map();
      (Array.isArray(doctors) ? doctors : []).forEach((doctor) => {
        const normalized = normalizeDoctorEntry(doctor);
        if (normalized) {
          uniqueMap.set(normalized.id, normalized);
        }
      });

      const uniqueDoctors = Array.from(uniqueMap.values());
      
      // ✅ Log to verify chat_price is preserved
      console.log('📊 Updating nearby doctors with chat_price:');
      uniqueDoctors.forEach(doc => {
        console.log(`  - ${doc.name} (${doc.id}): ₹${doc.chat_price || 'NOT SET'}`);
      });
      
      setNearbyDoctors(uniqueDoctors);
      localStorage.setItem(
        "nearby_doctors",
        JSON.stringify(uniqueDoctors)
      );
    } catch (error) {
      console.error("Error updating nearby doctors:", error);
    }
  }, []);

  // Logout
  const logout = async () => {
    try {
      setUser(null);
      setToken(null);
      setChatRoomToken(null);
      setNearbyDoctors([]);
      setLiveDoctors([]);
      setAllActiveDoctors([]);

      isFetchingRef.current = false;
      lastFetchTimeRef.current = 0;

      const keysToRemove = [
        "token",
        "user",
        "chat_room_token",
        "nearby_doctors",
        "live_doctors",
        "userEmail",
        "googleSub",
        "userId",
        "userLatitude",
        "userLongitude",
      ];
      keysToRemove.forEach((key) => localStorage.removeItem(key));

      if (socket && socket.connected) {
        socket.disconnect();
      }
    } catch (error) {
      console.error("Error during logout:", error);
      setUser(null);
      setToken(null);
      setLiveDoctors([]);
      setAllActiveDoctors([]);
    }
  };

  const authValue = useMemo(
    () => ({
      user,
      token,
      chatRoomToken,
      login,
      logout,
      fetchNearbyDoctors,
      updateNearbyDoctors,
      updateUser,
      nearbyDoctors: memoizedNearbyDoctors,
      liveDoctors: memoizedLiveDoctors,
      allActiveDoctors,
      loading,
      isLoggedIn: !!token,
    }),
    [
      user,
      token,
      chatRoomToken,
      loading,
      memoizedNearbyDoctors,
      memoizedLiveDoctors,
      allActiveDoctors,
      fetchNearbyDoctors,
      updateNearbyDoctors,
    ]
  );

  return (
    <AuthContext.Provider value={authValue}>
      {children}
    </AuthContext.Provider>
  );
};

// Hook
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
};

/* ---------- Helpers ---------- */

// ✅ FIXED: Properly extract and preserve chat_price
function normalizeDoctorEntry(entry = {}) {
  if (!entry || typeof entry !== "object") return null;

  const rawDoctor =
    entry.doctor && typeof entry.doctor === "object" ? entry.doctor : {};

  // ✅ 1) Always prefer the real doctor id used by calls & sockets
  const doctorIdSource =
    entry.doctor_id ??
    rawDoctor.id ??
    rawDoctor.doctor_id ??
    entry.vet_registeration_id ?? // if backend uses this as doctor id anywhere
    entry.id ??                    // LAST fallback only
    null;

  const doctorId = Number(doctorIdSource);

  if (!Number.isFinite(doctorId) || doctorId <= 0) {
    console.warn("❌ Skipping doctor without valid id", entry);
    return null;
  }

  // ✅ Clinic id (separate from doctor id)
  const clinicIdSource =
    entry.clinic_id ??
    rawDoctor.clinic_id ??
    entry.vet_registeration_id ??
    rawDoctor.vet_registeration_id ??
    null;

  const clinicId = Number(clinicIdSource);

  const clinicName =
    entry.clinic_name ??
    rawDoctor.clinic_name ??
    "Veterinary Clinic";

  const doctorName =
    rawDoctor.doctor_name ??
    rawDoctor.name ??
    rawDoctor.full_name ??
    entry.doctor_name ??
    entry.name ??
    `Veterinarian ${doctorId}`;

  const profileImage =
    entry.profile_image ??
    entry.doctor_image ??
    rawDoctor.image ??
    rawDoctor.profile_image ??
    null;

  // ✅ chat_price from all possible sources
  const chatPriceRaw =
    entry.chat_price ??
    entry.price ??
    rawDoctor.chat_price ??
    rawDoctor.price ??
    null;

  const chatPrice =
    chatPriceRaw !== null && chatPriceRaw !== undefined
      ? Number(chatPriceRaw)
      : null;

  if (chatPrice !== null && !Number.isNaN(chatPrice)) {
    console.log(
      `✅ Normalized doctor ${doctorName} (${doctorId}) with chat_price: ₹${chatPrice}`
    );
  } else {
    console.warn(
      `⚠️ No chat_price found for doctor ${doctorName} (${doctorId})`
    );
  }

  const price =
    chatPrice !== null && !Number.isNaN(chatPrice)
      ? chatPrice
      : null;

  return {
    id: doctorId,
    clinic_id: Number.isFinite(clinicId) ? clinicId : null,
    name: doctorName,
    clinic_name: clinicName,
    profile_image: profileImage,
    rating:
      entry.rating !== undefined && entry.rating !== null
        ? Number(entry.rating)
        : null,
    distance:
      entry.distance !== undefined && entry.distance !== null
        ? Number(entry.distance)
        : null,
    chat_price: price,
    price,
    slug:
      entry.slug ??
      rawDoctor.slug ??
      `doctor-${doctorId}`,
    business_status:
      entry.business_status ?? rawDoctor.business_status ?? null,
    email: rawDoctor.email ?? entry.email ?? null,
    mobile: rawDoctor.mobile ?? entry.mobile ?? null,
    bio: entry.bio ?? rawDoctor.bio ?? null,
    specialization:
      entry.specialization ?? rawDoctor.specialization ?? null,
    experience:
      entry.experience ?? rawDoctor.experience ?? null,
    doctor: {
      id: doctorId,
      name: doctorName,
      email: rawDoctor.email ?? entry.email ?? null,
      mobile: rawDoctor.mobile ?? entry.mobile ?? null,
      license: rawDoctor.license ?? entry.license ?? null,
      image: profileImage,
      clinic_id: Number.isFinite(clinicId) ? clinicId : null,
      chat_price: price,
    },
  };
}
