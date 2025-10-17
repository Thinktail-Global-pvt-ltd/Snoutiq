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
// // };

// import React, { createContext, useState, useEffect } from "react";

// export const AuthContext = createContext();
// export const AuthProvider = ({ children }) => {
//   const [loading, setLoading] = useState(true);
//   const [user, setUser] = useState(null);
//   const [token, setToken] = useState(null);
//   const [chatRoomToken, setChatRoomToken] = useState(null);

//   // 🔹 Load from localStorage on mount
//   useEffect(() => {
//     const savedToken = localStorage.getItem("token");
//     const savedUser = localStorage.getItem("user");
//     const savedChatRoomToken = localStorage.getItem("chat_room_token");

//     if (savedToken) setToken(savedToken);
//     if (savedUser) setUser(JSON.parse(savedUser));
//     if (savedChatRoomToken) setChatRoomToken(savedChatRoomToken);

//     setLoading(false);
//   }, []);

//   // 🔹 Login function
//   const login = (userData, jwtToken, initialChatToken = null) => {
//     setUser(userData);
//     setToken(jwtToken);

//     localStorage.setItem("token", jwtToken);
//     localStorage.setItem("user", JSON.stringify(userData));

//     if (initialChatToken) {
//       setChatRoomToken(initialChatToken);
//       localStorage.setItem("chat_room_token", initialChatToken);
//     }
//   };

//   // 🔹 Logout function - CLEAR EVERYTHING

//   const logout = () => {
//     setUser(null);
//     setToken(null);
//     setChatRoomToken(null);

//     localStorage.removeItem("token");
//     localStorage.removeItem("user");
//     localStorage.removeItem("chat_room_token");
//   };

//   // 🔹 Update chatRoomToken
//   const updateChatRoomToken = (newToken) => {
//     setChatRoomToken(newToken);
//     localStorage.setItem("chat_room_token", newToken);
//   };

//   // AuthContext.js
//   const [nearbyDoctors, setNearbyDoctors] = useState([]);

//   const updateNearbyDoctors = (newDoctors) => {
//     setNearbyDoctors((prev) => {
//       const existingIds = new Set(prev.map((d) => d.id));
//       const merged = [
//         ...prev,
//         ...newDoctors.filter((d) => !existingIds.has(d.id)),
//       ];
//       localStorage.setItem("nearby_doctors", JSON.stringify(merged));
//       return merged;
//     });
//   };
// const updateUser = (newUserData) => {
//   setUser(newUserData);
//   localStorage.setItem("user", JSON.stringify(newUserData));
// };

//   // Load on mount
//   useEffect(() => {
//     const savedDoctors = JSON.parse(
//       localStorage.getItem("nearby_doctors") || "[]"
//     );
//     setNearbyDoctors(savedDoctors);
//   }, []);

//   return (
//     <AuthContext.Provider
//       value={{
//         user,
//         token,
//         chatRoomToken,
//         login,
//         logout,
//         updateChatRoomToken,
//         nearbyDoctors,
//         updateNearbyDoctors,
//         loading,
//         updateUser,
//       }}
//     >
//       {children}
//     </AuthContext.Provider>
//   );
// };

import React, { createContext, useState, useEffect, useCallback, useMemo } from "react";
import axios from "axios";

export const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [chatRoomToken, setChatRoomToken] = useState(null);
  const [nearbyDoctors, setNearbyDoctors] = useState([]);
  const [liveDoctors, setLiveDoctors] = useState([]);

  const memoizedNearbyDoctors = useMemo(() => nearbyDoctors, [nearbyDoctors]);
  const memoizedLiveDoctors = useMemo(() => liveDoctors, [liveDoctors]);

  // Load auth data from localStorage on mount
  useEffect(() => {
    const savedToken = localStorage.getItem("token");
    const savedUser = localStorage.getItem("user");
    const savedChatRoomToken = localStorage.getItem("chat_room_token");
    const savedDoctors = localStorage.getItem("nearby_doctors");

    if (savedToken) setToken(savedToken);
    if (savedUser) setUser(JSON.parse(savedUser));
    if (savedChatRoomToken) setChatRoomToken(savedChatRoomToken);
    if (savedDoctors) setNearbyDoctors(JSON.parse(savedDoctors));

    setLoading(false);
  }, []);

  // Login function
  const login = (userData, jwtToken, initialChatToken = null) => {
    setUser(userData);
    setToken(jwtToken);

    localStorage.setItem("token", jwtToken);
    localStorage.setItem("user", JSON.stringify(userData));

    if (initialChatToken) {
      setChatRoomToken(initialChatToken);
      localStorage.setItem("chat_room_token", initialChatToken);
    }

    fetchNearbyDoctors();
  };

  // Logout function
  const logout = () => {
    setUser(null);
    setToken(null);
    setChatRoomToken(null);
    setNearbyDoctors([]);
    setLiveDoctors([]);

    localStorage.removeItem("token");
    localStorage.removeItem("user");
    localStorage.removeItem("chat_room_token");
    localStorage.removeItem("nearby_doctors");
  };

  // Update chatRoomToken
  const updateChatRoomToken = (newToken) => {
    setChatRoomToken(newToken);
    localStorage.setItem("chat_room_token", newToken);
  };

  // Update nearby doctors
  const updateNearbyDoctors = (newDoctors) => {
    setNearbyDoctors((prev) => {
      const existingIds = new Set(prev.map((d) => d.id));
      const merged = [...prev, ...newDoctors.filter((d) => !existingIds.has(d.id))];
      localStorage.setItem("nearby_doctors", JSON.stringify(merged));
      return merged;
    });
  };

  // Fetch nearby doctors
  const fetchNearbyDoctors = useCallback(async () => {
    if (!token || !user?.id) return;

    try {
      const response = await axios.get(
        `https://snoutiq.com/backend/api/nearby-vets?user_id=${user.id}`,
        { headers: { Authorization: `Bearer ${token}` } }
      );

      if (response.data && Array.isArray(response.data.data)) {
        updateNearbyDoctors(response.data.data);
      }
    } catch (error) {
      console.error("Failed to fetch nearby doctors", error);
    }
  }, [token, user?.id]);

  // Update user data
  const updateUser = (newUserData) => {
    setUser((prevUser) => {
      const updatedUser = { ...prevUser, ...newUserData };
      localStorage.setItem("user", JSON.stringify(updatedUser));
      return updatedUser;
    });
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
      loading,
      isLoggedIn: !!token,
      updateUser,
      nearbyDoctors: memoizedNearbyDoctors,
      liveDoctors: memoizedLiveDoctors,
      updateChatRoomToken,
      setLiveDoctors,
    }),
    [
      user,
      token,
      chatRoomToken,
      loading,
      memoizedNearbyDoctors,
      memoizedLiveDoctors,
      login,
      logout,
      fetchNearbyDoctors,
      updateNearbyDoctors,
      updateUser,
    ]
  );

  return <AuthContext.Provider value={authValue}>{children}</AuthContext.Provider>;
};

// Hook to use AuthContext
export const useAuth = () => {
  const context = React.useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used within an AuthProvider");
  return context;
};
