// import React, { createContext, useState, useEffect } from "react";

// export const AuthContext = createContext();

// export const AuthProvider = ({ children }) => {
//   const [loading, setLoading] = useState(true);
//   const [user, setUser] = useState(null);
//   const [token, setToken] = useState(localStorage.getItem("token") || null);
//   const [chat_room_token , setChatRoomToken] = useState(localStorage.getItem("chat_room_token") || null);


//   const login = (userData, jwtToken) => {
//     setUser(userData);
//     setToken(jwtToken);
//     localStorage.setItem("token", jwtToken);
//     localStorage.setItem("user", JSON.stringify(userData));
//   };


//   const logout = () => {
//     setUser(null);
//     setToken(null);
//     localStorage.removeItem("token");
//     localStorage.removeItem("user");
    
//   };


//   useEffect(() => {
//     const savedUser = localStorage.getItem("user");
//     if (savedUser) {
//       setUser(JSON.parse(savedUser));
//     }
//     setLoading(false);
//   }, []);

//   return React.createElement(
//     AuthContext.Provider,
//     { value: { user, token, login, logout } },
//     children
//   );

// };

// import React, { createContext, useState, useEffect } from "react";

// export const AuthContext = createContext();

// export const AuthProvider = ({ children }) => {
//   const [loading, setLoading] = useState(true);
//   const [user, setUser] = useState(null);
//   const [token, setToken] = useState(localStorage.getItem("token") || null);
//   const [chatRoomToken, setChatRoomToken] = useState(
//     localStorage.getItem("chat_room_token") || null
//   );

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

//   // 🔹 Logout function
//   const logout = () => {
//     setUser(null);
//     setToken(null);
//     setChatRoomToken(null);

//     localStorage.removeItem("token");
//     localStorage.removeItem("user");
//     localStorage.removeItem("chat_room_token");
//   };

//   // 🔹 Update chatRoomToken (jab bhi new chat banega)
//   const updateChatRoomToken = (newToken) => {
//     setChatRoomToken(newToken);
//     localStorage.setItem("chat_room_token", newToken);
//   };

//   // 🔹 Load user from localStorage
//   useEffect(() => {
//     const savedUser = localStorage.getItem("user");
//     if (savedUser) {
//       setUser(JSON.parse(savedUser));
//     }
//     setLoading(false);
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
//         loading,
//       }}
//     >
//       {children}
//     </AuthContext.Provider>
//   );
// };

import React, { createContext, useState, useEffect } from "react";

export const AuthContext = createContext();
export const AuthProvider = ({ children }) => {
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [chatRoomToken, setChatRoomToken] = useState(null);

  // 🔹 Load from localStorage on mount
  useEffect(() => {
    const savedToken = localStorage.getItem("token");
    const savedUser = localStorage.getItem("user");
    const savedChatRoomToken = localStorage.getItem("chat_room_token");

    if (savedToken) setToken(savedToken);
    if (savedUser) setUser(JSON.parse(savedUser));
    if (savedChatRoomToken) setChatRoomToken(savedChatRoomToken);
    
    setLoading(false);
  }, []);

  // 🔹 Login function
  const login = (userData, jwtToken, initialChatToken = null) => {
    setUser(userData);
    setToken(jwtToken);

    localStorage.setItem("token", jwtToken);
    localStorage.setItem("user", JSON.stringify(userData));

    if (initialChatToken) {
      setChatRoomToken(initialChatToken);
      localStorage.setItem("chat_room_token", initialChatToken);
    }
  };

  // 🔹 Logout function - CLEAR EVERYTHING

  const logout = () => {
    setUser(null);
    setToken(null);
    setChatRoomToken(null);

    localStorage.removeItem("token");
    localStorage.removeItem("user");
    localStorage.removeItem("chat_room_token");
  };

  // 🔹 Update chatRoomToken
  const updateChatRoomToken = (newToken) => {
    setChatRoomToken(newToken);
    localStorage.setItem("chat_room_token", newToken);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        chatRoomToken,
        login,
        logout,
        updateChatRoomToken,
        loading,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};