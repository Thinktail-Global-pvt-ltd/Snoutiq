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
import { socket } from "../pages/socket";

export const AuthContext = createContext();

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
      if (savedDoctors) setNearbyDoctors(JSON.parse(savedDoctors));
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
      console.log("📋 Current nearbyDoctors:", nearbyDoctors.map(d => `${d.id} (${d.name})`));
      
      // ✅ Store all active doctor IDs
      setAllActiveDoctors(doctorIds);
      
      // ✅ Filter nearby doctors to find which ones are currently online
      const liveNearbyDoctors = nearbyDoctors.filter((doctor) => 
        doctorIds.includes(doctor.id)
      );
      
      console.log(`✅ ${liveNearbyDoctors.length} live doctors found from ${nearbyDoctors.length} nearby:`, 
        liveNearbyDoctors.map(d => `${d.name} (${d.id})`));
      
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

    // Periodic polling every 20 seconds
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
  }, [nearbyDoctors]); // ✅ Only depend on nearbyDoctors

  // 🟢 Update live doctors whenever nearbyDoctors OR allActiveDoctors changes
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
      console.log("🔍 Fetching nearby veterinarians...");
      
      const response = await axios.get(
        `https://snoutiq.com/backend/api/nearby-vets?user_id=${user.id}`,
        { 
          headers: { 
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          signal: controller.signal
        }
      );

      clearTimeout(timeoutId);

      if (response.data && Array.isArray(response.data.data)) {
        console.log(`✅ Found ${response.data.data.length} veterinarians`);
        updateNearbyDoctors(response.data.data);
        
        // ✅ Request updated active doctors after fetching nearby doctors
        if (socket) {
          setTimeout(() => {
            socket.emit("get-active-doctors");
          }, 1000);
        }
      } else {
        console.warn("⚠️ No veterinarians data received");
      }
    } catch (error) {
      clearTimeout(timeoutId);
      
      // Only log errors that aren't intentional cancellations
      if (error.name !== 'AbortError' && error.name !== 'CanceledError') {
        // ✅ Don't log 404 errors for doctors - they don't have nearby vets
        if (error.response?.status !== 404) {
          console.error("❌ Failed to fetch nearby doctors:", error.message);
        } else {
          console.log("ℹ️ No nearby veterinarians found (404) - this is normal for doctors");
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
  const login = async (userData, jwtToken, initialChatToken = null) => {
    try {
      setUser(userData);
      setToken(jwtToken);
      localStorage.setItem("token", jwtToken);
      localStorage.setItem("user", JSON.stringify(userData));

      if (initialChatToken) {
        setChatRoomToken(initialChatToken);
        localStorage.setItem("chat_room_token", initialChatToken);
      }

      // Fetch doctors after login
      setTimeout(() => {
        fetchNearbyDoctors();
      }, 1000);
    } catch (error) {
      console.error("Error during login:", error);
    }
  };

  // 🟢 Update nearby doctors and merge new ones
  const updateNearbyDoctors = useCallback((newDoctors) => {
    try {
      setNearbyDoctors((prev) => {
        const existingIds = new Set(prev.map((d) => d.id));
        const merged = [
          ...prev,
          ...newDoctors.filter((d) => !existingIds.has(d.id)),
        ];
        localStorage.setItem("nearby_doctors", JSON.stringify(merged));
        return merged;
      });
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