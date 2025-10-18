// // import React, { createContext, useState, useEffect } from "react";

// // export const AuthContext = createContext();

// // export const AuthProvider = ({ children }) => {
// //   const [loading, setLoading] = useState(true);
// //   const [user, setUser] = useState(null);
// //   const [token, setToken] = useState(localStorage.getItem("token") || null);
// //   const [chat_room_token , setChatRoomToken] = useState(localStorage.getItem("chat_room_token") || null);

// //   const login = (userData, jwtToken) => {
// //     setUser(userData);
// //     setToken(jwtToken);
// //     localStorage.setItem("token", jwtToken);
// //     localStorage.setItem("user", JSON.stringify(userData));
// //   };

// //   const logout = () => {
// //     setUser(null);
// //     setToken(null);
// //     localStorage.removeItem("token");
// //     localStorage.removeItem("user");

// //   };

// //   useEffect(() => {
// //     const savedUser = localStorage.getItem("user");
// //     if (savedUser) {
// //       setUser(JSON.parse(savedUser));
// //     }
// //     setLoading(false);
// //   }, []);

// //   return React.createElement(
// //     AuthContext.Provider,
// //     { value: { user, token, login, logout } },
// //     children
// //   );

// // };

// // import React, { createContext, useState, useEffect } from "react";

// // export const AuthContext = createContext();

// // export const AuthProvider = ({ children }) => {
// //   const [loading, setLoading] = useState(true);
// //   const [user, setUser] = useState(null);
// //   const [token, setToken] = useState(localStorage.getItem("token") || null);
// //   const [chatRoomToken, setChatRoomToken] = useState(
// //     localStorage.getItem("chat_room_token") || null
// //   );

// //   // 🔹 Login function
// //   const login = (userData, jwtToken, initialChatToken = null) => {
// //     setUser(userData);
// //     setToken(jwtToken);

// //     localStorage.setItem("token", jwtToken);
// //     localStorage.setItem("user", JSON.stringify(userData));

// //     if (initialChatToken) {
// //       setChatRoomToken(initialChatToken);
// //       localStorage.setItem("chat_room_token", initialChatToken);
// //     }
// //   };

// //   // 🔹 Logout function
// //   const logout = () => {
// //     setUser(null);
// //     setToken(null);
// //     setChatRoomToken(null);

// //     localStorage.removeItem("token");
// //     localStorage.removeItem("user");
// //     localStorage.removeItem("chat_room_token");
// //   };

// //   // 🔹 Update chatRoomToken (jab bhi new chat banega)
// //   const updateChatRoomToken = (newToken) => {
// //     setChatRoomToken(newToken);
// //     localStorage.setItem("chat_room_token", newToken);
// //   };

// //   // 🔹 Load user from localStorage
// //   useEffect(() => {
// //     const savedUser = localStorage.getItem("user");
// //     if (savedUser) {
// //       setUser(JSON.parse(savedUser));
// //     }
// //     setLoading(false);
// //   }, []);

// //   return (
// //     <AuthContext.Provider
// //       value={{
// //         user,
// //         token,
// //         chatRoomToken,
// //         login,
// //         logout,
// //         updateChatRoomToken,
// //         loading,
// //       }}
// //     >
// //       {children}
// //     </AuthContext.Provider>
// //   );
// // // };

// // import React, { createContext, useState, useEffect } from "react";

// // export const AuthContext = createContext();
// // export const AuthProvider = ({ children }) => {
// //   const [loading, setLoading] = useState(true);
// //   const [user, setUser] = useState(null);
// //   const [token, setToken] = useState(null);
// //   const [chatRoomToken, setChatRoomToken] = useState(null);

// //   // 🔹 Load from localStorage on mount
// //   useEffect(() => {
// //     const savedToken = localStorage.getItem("token");
// //     const savedUser = localStorage.getItem("user");
// //     const savedChatRoomToken = localStorage.getItem("chat_room_token");

// //     if (savedToken) setToken(savedToken);
// //     if (savedUser) setUser(JSON.parse(savedUser));
// //     if (savedChatRoomToken) setChatRoomToken(savedChatRoomToken);

// //     setLoading(false);
// //   }, []);

// //   // 🔹 Login function
// //   const login = (userData, jwtToken, initialChatToken = null) => {
// //     setUser(userData);
// //     setToken(jwtToken);

// //     localStorage.setItem("token", jwtToken);
// //     localStorage.setItem("user", JSON.stringify(userData));

// //     if (initialChatToken) {
// //       setChatRoomToken(initialChatToken);
// //       localStorage.setItem("chat_room_token", initialChatToken);
// //     }
// //   };

// //   // 🔹 Logout function - CLEAR EVERYTHING

// //   const logout = () => {
// //     setUser(null);
// //     setToken(null);
// //     setChatRoomToken(null);

// //     localStorage.removeItem("token");
// //     localStorage.removeItem("user");
// //     localStorage.removeItem("chat_room_token");
// //   };

// //   // 🔹 Update chatRoomToken
// //   const updateChatRoomToken = (newToken) => {
// //     setChatRoomToken(newToken);
// //     localStorage.setItem("chat_room_token", newToken);
// //   };

// //   // AuthContext.js
// //   const [nearbyDoctors, setNearbyDoctors] = useState([]);

// //   const updateNearbyDoctors = (newDoctors) => {
// //     setNearbyDoctors((prev) => {
// //       const existingIds = new Set(prev.map((d) => d.id));
// //       const merged = [
// //         ...prev,
// //         ...newDoctors.filter((d) => !existingIds.has(d.id)),
// //       ];
// //       localStorage.setItem("nearby_doctors", JSON.stringify(merged));
// //       return merged;
// //     });
// //   };
// // const updateUser = (newUserData) => {
// //   setUser(newUserData);
// //   localStorage.setItem("user", JSON.stringify(newUserData));
// // };

// //   // Load on mount
// //   useEffect(() => {
// //     const savedDoctors = JSON.parse(
// //       localStorage.getItem("nearby_doctors") || "[]"
// //     );
// //     setNearbyDoctors(savedDoctors);
// //   }, []);

// //   return (
// //     <AuthContext.Provider
// //       value={{
// //         user,
// //         token,
// //         chatRoomToken,
// //         login,
// //         logout,
// //         updateChatRoomToken,
// //         nearbyDoctors,
// //         updateNearbyDoctors,
// //         loading,
// //         updateUser,
// //       }}
// //     >
// //       {children}
// //     </AuthContext.Provider>
// //   );
// // };
// import React, {
//   createContext,
//   useState,
//   useEffect,
//   useContext,
//   useCallback,
//   useMemo,
// } from "react";
// import axios from "axios";
// import { socket } from "../pages/socket";

// export const AuthContext = createContext();

// export const AuthProvider = ({ children }) => {
//   const [loading, setLoading] = useState(true);
//   const [user, setUser] = useState(null);
//   const [token, setToken] = useState(null);
//   const [chatRoomToken, setChatRoomToken] = useState(null);
//   const [nearbyDoctors, setNearbyDoctors] = useState([]);
//   const [liveDoctors, setLiveDoctors] = useState([]);

//   const memoizedNearbyDoctors = useMemo(() => nearbyDoctors, [nearbyDoctors]);
//   const memoizedLiveDoctors = useMemo(() => liveDoctors, [liveDoctors]);

//   // 🟢 Load data from localStorage on mount
//   useEffect(() => {
//     try {
//       const savedToken = localStorage.getItem("token");
//       const savedUser = localStorage.getItem("user");
//       const savedChatRoomToken = localStorage.getItem("chat_room_token");
//       const savedDoctors = localStorage.getItem("nearby_doctors");
//       const savedLiveDoctors = localStorage.getItem("live_doctors");

//       if (savedToken) setToken(savedToken);
//       if (savedUser) setUser(JSON.parse(savedUser));
//       if (savedChatRoomToken) setChatRoomToken(savedChatRoomToken);
//       if (savedDoctors) setNearbyDoctors(JSON.parse(savedDoctors));
//       if (savedLiveDoctors) setLiveDoctors(JSON.parse(savedLiveDoctors));
//     } catch (error) {
//       console.error("Error loading auth data:", error);
//     } finally {
//       setLoading(false);
//     }
//   }, []);

//   // 🟢 Socket listener for live active doctors
//   useEffect(() => {
//     if (!socket) return;

//     socket.off("active-doctors");
//     socket.on("active-doctors", (doctorIds) => {
//       console.log("Received active-doctors:", doctorIds);
//       const liveNearbyDoctors = nearbyDoctors.filter((doc) =>
//         doctorIds.includes(doc.id)
//       );
//       setLiveDoctors(liveNearbyDoctors);
//       localStorage.setItem("live_doctors", JSON.stringify(liveNearbyDoctors));
//     });

//     socket.emit("get-active-doctors");

//     const interval = setInterval(() => {
//       socket.emit("get-active-doctors");
//     }, 30000);

//     return () => {
//       socket.off("active-doctors");
//       clearInterval(interval);
//     };
//   }, [nearbyDoctors]);

//   // 🟢 Fetch nearby doctors from API
//   const fetchNearbyDoctors = useCallback(async () => {
//     if (!token || !user?.id) return;

//     try {
//       const response = await axios.get(
//         `https://snoutiq.com/backend/api/nearby-vets?user_id=${user.id}`,
//         { headers: { Authorization: `Bearer ${token}` } }
//       );

//       if (response.data && Array.isArray(response.data.data)) {
//         updateNearbyDoctors(response.data.data);
//       }
//     } catch (error) {
//       console.error("Failed to fetch nearby doctors:", error.message);
//     }
//   }, [token, user?.id]);

//   // 🟢 Update user info and save to localStorage
//   const updateUser = async (newUserData) => {
//     try {
//       setUser((prevUser) => {
//         const updatedUser = { ...prevUser, ...newUserData };
//         localStorage.setItem("user", JSON.stringify(updatedUser));
//         return updatedUser;
//       });
//     } catch (error) {
//       console.error("Error updating user data:", error);
//     }
//   };

//   // 🟢 Login function
//   const login = async (userData, jwtToken, initialChatToken = null) => {
//     try {
//       setUser(userData);
//       setToken(jwtToken);
//       localStorage.setItem("token", jwtToken);
//       localStorage.setItem("user", JSON.stringify(userData));

//       if (initialChatToken) {
//         setChatRoomToken(initialChatToken);
//         localStorage.setItem("chat_room_token", initialChatToken);
//       }

//       fetchNearbyDoctors();
//     } catch (error) {
//       console.error("Error during login:", error);
//     }
//   };

//   // 🟢 Update nearby doctors and merge new ones
//   const updateNearbyDoctors = async (newDoctors) => {
//     try {
//       setNearbyDoctors((prev) => {
//         const existingIds = new Set(prev.map((d) => d.id));
//         const merged = [
//           ...prev,
//           ...newDoctors.filter((d) => !existingIds.has(d.id)),
//         ];
//         localStorage.setItem("nearby_doctors", JSON.stringify(merged));
//         return merged;
//       });
//     } catch (error) {
//       console.error("Error updating nearby doctors:", error);
//     }
//   };

//   // 🟢 Logout function
//   const logout = async () => {
//     try {
//       // Clear states
//       setUser(null);
//       setToken(null);
//       setChatRoomToken(null);
//       setNearbyDoctors([]);
//       setLiveDoctors([]);

//       // Remove all auth-related localStorage data
//       const keysToRemove = [
//         "token",
//         "user",
//         "chat_room_token",
//         "nearby_doctors",
//         "live_doctors",
//         "userEmail",
//         "googleSub",
//         "userId",
//         "userLatitude",
//         "userLongitude",
//       ];
//       keysToRemove.forEach((key) => localStorage.removeItem(key));

//       // Disconnect socket if active
//       if (socket && socket.connected) {
//         socket.disconnect();
//       }
//     } catch (error) {
//       console.error("Error during logout:", error);
//       setUser(null);
//       setToken(null);
//       setLiveDoctors([]);
//     }
//   };

//   // 🧠 Memoized context value
//   const authValue = useMemo(
//     () => ({
//       user,
//       token,
//       chatRoomToken,
//       login,
//       logout,
//       fetchNearbyDoctors,
//       updateNearbyDoctors,
//       updateUser,
//       nearbyDoctors: memoizedNearbyDoctors,
//       liveDoctors: memoizedLiveDoctors,
//       loading,
//       isLoggedIn: !!token,
//     }),
//     [
//       user,
//       token,
//       chatRoomToken,
//       loading,
//       memoizedNearbyDoctors,
//       memoizedLiveDoctors,
//       fetchNearbyDoctors,
//     ]
//   );

//   return (
//     <AuthContext.Provider value={authValue}>{children}</AuthContext.Provider>
//   );
// };

// // 🧩 Custom hook for consuming AuthContext
// export const useAuth = () => {
//   const context = useContext(AuthContext);
//   if (!context) throw new Error("useAuth must be used within an AuthProvider");
//   return context;
// };

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
  
  // ✅ Prevent duplicate API calls
  const isFetchingRef = useRef(false);
  const lastFetchTimeRef = useRef(0);
  const FETCH_COOLDOWN = 5000; // 5 seconds cooldown between fetches

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

  // 🟢 Socket listener for live active doctors
// 🟢 Socket listener for live active doctors
useEffect(() => {
  if (!socket) return;

  const handleActiveDoctors = (doctorIds) => {
    console.log("📡 Received active-doctors:", doctorIds);
    console.log("📋 Current nearbyDoctors:", nearbyDoctors.map(d => d.id));
    
    // ✅ Filter to get only live doctors from nearby list
    const liveNearbyDoctors = nearbyDoctors.filter((doc) => {
      const isLive = doctorIds.includes(doc.id);
      console.log(`Doctor ${doc.id} (${doc.name}): ${isLive ? '🟢 LIVE' : '⚫ OFFLINE'}`);
      return isLive;
    });
    
    console.log(`✅ ${liveNearbyDoctors.length} live doctors found:`, 
      liveNearbyDoctors.map(d => `${d.name} (${d.id})`));
    
    setLiveDoctors(liveNearbyDoctors);
    localStorage.setItem("live_doctors", JSON.stringify(liveNearbyDoctors));
  };

  socket.off("active-doctors");
  socket.on("active-doctors", handleActiveDoctors);

  // Initial request
  socket.emit("get-active-doctors");

  // Periodic polling every 15 seconds (reduced from 30s for better real-time updates)
  const interval = setInterval(() => {
    socket.emit("get-active-doctors");
  }, 15000);

  return () => {
    socket.off("active-doctors");
    clearInterval(interval);
  };
}, [nearbyDoctors]);

  // 🟢 Fetch nearby doctors from API with debouncing
  const fetchNearbyDoctors = useCallback(async () => {
    if (!token || !user?.id) {
      console.warn("No token or user ID available");
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
      } else {
        console.warn("⚠️ No veterinarians data received");
      }
    } catch (error) {
      clearTimeout(timeoutId);
      
      // Only log errors that aren't intentional cancellations
      if (error.name !== 'AbortError' && error.name !== 'CanceledError') {
        console.error("❌ Failed to fetch nearby doctors:", error.message);
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

    // Refresh every 5 minutes (increased from 2 minutes)
    const interval = setInterval(() => {
      fetchNearbyDoctors();
    }, 5 * 60 * 1000);

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