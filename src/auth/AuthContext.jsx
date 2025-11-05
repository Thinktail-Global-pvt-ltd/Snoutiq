import React, {
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
  const FETCH_COOLDOWN = 5000; 

  const memoizedNearbyDoctors = useMemo(() => nearbyDoctors, [nearbyDoctors]);
  const memoizedLiveDoctors = useMemo(() => liveDoctors, [liveDoctors]);

  // 🟢 Load data from localStorage on mount
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
        setNearbyDoctors(
          Array.isArray(parsed)
            ? parsed
                .map(normalizeDoctorEntry)
                .filter((doctor) => doctor !== null)
            : []
        );
      }
      if (savedLiveDoctors) setLiveDoctors(JSON.parse(savedLiveDoctors));
    } catch (error) {
      console.error("Error loading auth data:", error);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!socket) {
      console.warn("Socket not available");
      return;
    }

    console.log("🔌 Setting up socket listeners for active doctors");

    const handleActiveDoctors = (doctorIds) => {
      console.log("📡 Received ALL active-doctors from socket:", doctorIds);

      const numericIds = Array.isArray(doctorIds)
        ? doctorIds
            .map((id) => Number(id))
            .filter((id) => Number.isFinite(id) && id > 0)
        : [];

      setAllActiveDoctors(numericIds);

      const updatedNearby = ensureDoctorsForActiveIds(nearbyDoctors, numericIds);
      if (updatedNearby !== nearbyDoctors) {
        setNearbyDoctors(updatedNearby);
        localStorage.setItem("nearby_doctors", JSON.stringify(updatedNearby));
      }

      const liveNearbyDoctors = updatedNearby.filter((doctor) =>
        numericIds.includes(doctor.id)
      );

      console.log(
        `✅ ${liveNearbyDoctors.length} live doctors found from ${updatedNearby.length} nearby:`,
        liveNearbyDoctors.map((d) => `${d.name} (${d.id})`)
      );

      setLiveDoctors(liveNearbyDoctors);
      localStorage.setItem("live_doctors", JSON.stringify(liveNearbyDoctors));
    };

    const handleDoctorOnline = (data) => {
      console.log(`🟢 Doctor came online: ${data.doctorId}`);
      // Re-fetch active doctors when a doctor comes online
      socket.emit("get-active-doctors");
    };

    const handleDoctorOffline = (data) => {
      console.log(`🔴 Doctor went offline: ${data.doctorId}`);
      // Re-fetch active doctors when a doctor goes offline
      socket.emit("get-active-doctors");
    };

    // Set up socket listeners
    socket.on("active-doctors", handleActiveDoctors);
    socket.on("doctor-online", handleDoctorOnline);
    socket.on("doctor-offline", handleDoctorOffline);

    // Initial request for active doctors
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

  useEffect(() => {
    if (nearbyDoctors.length > 0 && allActiveDoctors.length > 0) {
      const updatedLiveDoctors = nearbyDoctors.filter(doctor => 
        allActiveDoctors.includes(doctor.id)
      );
      
      console.log(`🔄 Auto-updating live doctors: ${updatedLiveDoctors.length} live out of ${nearbyDoctors.length} nearby`);
      setLiveDoctors(updatedLiveDoctors);
      localStorage.setItem("live_doctors", JSON.stringify(updatedLiveDoctors));
    }
  }, [nearbyDoctors, allActiveDoctors]);

  // 🟢 Fetch nearby doctors from API with debouncing
  const fetchNearbyDoctors = useCallback(async () => {
    if (!token || !user?.id) {
      console.warn("No token or user ID available");
      return;
    }

    // ✅ Don't fetch nearby doctors if user is a doctor (they don't have nearby vets)
    if (user?.business_status) {
      console.log("ℹ️ User is a doctor, skipping nearby vets fetch");
      return;
    }

    // ✅ Prevent duplicate calls
    const now = Date.now();
    if (isFetchingRef.current) {
      console.log("🔄 Fetch already in progress, skipping...");
      return;
    }

    // ✅ Cooldown check
    if (now - lastFetchTimeRef.current < FETCH_COOLDOWN) {
      console.log(`⏳ Cooldown active, wait ${Math.ceil((FETCH_COOLDOWN - (now - lastFetchTimeRef.current)) / 1000)}s`);
      return;
    }

    isFetchingRef.current = true;
    lastFetchTimeRef.current = now;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000);

    try {
      console.log("🔍 Fetching nearby doctors...");

      const requestConfig = {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        signal: controller.signal,
      };

      const [clinicResult, doctorResult] = await Promise.allSettled([
        axios.get(`${CLINIC_NEARBY_API}?user_id=${user.id}`, requestConfig),
        axios.get(`${DOCTOR_NEARBY_API}?user_id=${user.id}`, requestConfig),
      ]);

      clearTimeout(timeoutId);

      const clinicPayload =
        clinicResult.status === "fulfilled" ? clinicResult.value.data : null;
      const doctorPayload =
        doctorResult.status === "fulfilled" ? doctorResult.value.data : null;

      const combined = mergeNearbyDoctors(clinicPayload, doctorPayload);

      if (combined.length) {
        console.log(`✅ Found ${combined.length} doctors (merged)`);
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
      
      // Only log errors that aren't intentional cancellations
      if (error.name !== 'AbortError' && error.name !== 'CanceledError') {
        // ✅ Don't log 404 errors for doctors - they don't have nearby vets
        if (error.response?.status !== 404) {
          console.error("❌ Failed to fetch nearby doctors:", error.message);
        } else {
          console.log("ℹ️ No nearby doctors found (404) - this is normal for doctors");
        }
      } else {
        console.log("🚫 Fetch cancelled");
      }
    } finally {
      isFetchingRef.current = false;
    }
  }, [token, user?.id]);

  // 🟢 Initial fetch and periodic refresh
  useEffect(() => {
    if (!token || !user?.id) return;

    // Initial fetch
    fetchNearbyDoctors();

    // Refresh every 30 seconds
    const interval = setInterval(() => {
      fetchNearbyDoctors();
    }, 30 * 1000);

    return () => clearInterval(interval);
  }, [token, user?.id, fetchNearbyDoctors]);

  // 🟢 Update user info and save to localStorage
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

  // 🟢 Login function
  // const login = async (userData, jwtToken, initialChatToken = null) => {
  //   try {
  //     setUser(userData);
  //     setToken(jwtToken);
  //     localStorage.setItem("token", jwtToken);
  //     localStorage.setItem("user", JSON.stringify(userData));

  //     // if (initialChatToken) {
  //     //   setChatRoomToken(initialChatToken);
  //     //   localStorage.setItem("chat_room_token", initialChatToken);
  //     // }

  //     // Fetch doctors after login
  //     setTimeout(() => {
  //       fetchNearbyDoctors();
  //     }, 1000);
  //   } catch (error) {
  //     console.error("Error during login:", error);
  //   }
  // };

const login = async (userData, jwtToken, initialChatToken = null) => {
  try {
    setUser(userData);
    setToken(jwtToken);
    localStorage.setItem("token", jwtToken);
    localStorage.setItem("user", JSON.stringify(userData));

    // ❌ Backend se aane wale chat token ko ignore karo
    if (initialChatToken) {
      console.log("⚠️ Ignoring backend chat token to avoid auto chat creation.");
    }

    // ✅ Purane chat_room_token ko hamesha clear karo
    localStorage.removeItem("chat_room_token");
    setChatRoomToken(null);

    // ✅ Nearby doctors sirf pet user ke liye fetch karo
    setTimeout(() => {
      if (userData?.business_status !== 1) {
        fetchNearbyDoctors();
      } else {
        console.log("🩺 Doctor account detected, skipping nearby doctors fetch.");
      }
    }, 1200);
  } catch (error) {
    console.error("❌ Error during login:", error);
  }
};


  // 🟢 Update nearby doctors and merge new ones
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
      setNearbyDoctors(uniqueDoctors);
      localStorage.setItem("nearby_doctors", JSON.stringify(uniqueDoctors));
    } catch (error) {
      console.error("Error updating nearby doctors:", error);
    }
  }, []);

  // 🟢 Logout function
  const logout = async () => {
    try {
      // Clear states
      setUser(null);
      setToken(null);
      setChatRoomToken(null);
      setNearbyDoctors([]);
      setLiveDoctors([]);
      setAllActiveDoctors([]);

      // Reset refs
      isFetchingRef.current = false;
      lastFetchTimeRef.current = 0;

      // Remove all auth-related localStorage data
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

      // Disconnect socket if active
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

  // 🧠 Memoized context value
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
      allActiveDoctors, // ✅ Expose all active doctors
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
    <AuthContext.Provider value={authValue}>{children}</AuthContext.Provider>
  );
};

// 🧩 Custom hook for consuming AuthContext
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used within an AuthProvider");
  return context;
};

function normalizeDoctorEntry(entry = {}) {
  if (!entry || typeof entry !== "object") {
    return null;
  }

  const rawDoctor =
    entry.doctor && typeof entry.doctor === "object" ? entry.doctor : {};

  const doctorId = Number(
    entry.id ?? entry.doctor_id ?? rawDoctor.id ?? null
  );
  if (!Number.isFinite(doctorId) || doctorId <= 0) {
    return null;
  }

  const clinicId = Number(
    entry.clinic_id ??
      entry.vet_registeration_id ??
      rawDoctor.clinic_id ??
      rawDoctor.vet_registeration_id ??
      null
  );

  const clinicName =
    entry.clinic_name ??
    entry.business_status ??
    entry.hospital_profile ??
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
    null;

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
    chat_price:
      entry.chat_price !== undefined && entry.chat_price !== null
        ? Number(entry.chat_price)
        : null,
    slug:
      entry.slug ??
      rawDoctor.slug ??
      `doctor-${doctorId}`,
    business_status: entry.business_status ?? rawDoctor.business_status ?? null,
    doctor: {
      id: doctorId,
      name: doctorName,
      email: rawDoctor.email ?? entry.email ?? null,
      mobile: rawDoctor.mobile ?? entry.mobile ?? null,
      license: rawDoctor.license ?? entry.license ?? null,
      image: profileImage,
      clinic_id: Number.isFinite(clinicId) ? clinicId : null,
    },
  };
}

function ensureDoctorsForActiveIds(list, doctorIds) {
  if (!Array.isArray(doctorIds) || doctorIds.length === 0) {
    return list;
  }

  const map = new Map((Array.isArray(list) ? list : []).map((d) => [d.id, d]));
  let changed = false;

  doctorIds.forEach((id) => {
    if (!map.has(id)) {
      map.set(id, createPlaceholderDoctor(id));
      changed = true;
    }
  });

  return changed ? Array.from(map.values()) : list;
}

function createPlaceholderDoctor(id) {
  const name = `Veterinarian ${id}`;
  return {
    id,
    clinic_id: null,
    name,
    clinic_name: "Veterinary Clinic",
    profile_image: null,
    rating: null,
    distance: null,
    chat_price: null,
    slug: `doctor-${id}`,
    business_status: "ONLINE",
    doctor: {
      id,
      name,
      clinic_id: null,
      image: null,
      email: null,
      mobile: null,
      license: null,
    },
  };
}
