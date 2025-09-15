// import { useContext } from "react";
// import { Navigate, useLocation } from "react-router-dom";
// import { AuthContext } from "./auth/AuthContext";

// const ProtectedRoute = ({ children }) => {
//   const { token, loading } = useContext(AuthContext);
//   const location = useLocation();

//   if (loading) {
//     return <div className="flex justify-center items-center h-screen">Loading...</div>;
//   }

//   if (!token) {
//     if (location.pathname === "/") {
//       return children;
//     }
//     return <Navigate to="/login" replace />;
//   }

//   return children;
// };

// export default ProtectedRoute;


import React,{ useContext } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { AuthContext } from "./auth/AuthContext";

// 🔹 Role to Path Mapping
const roleRoutes = {
  vet: "/user-dashboard/bookings",
  pet: "/dashboard",
  super_admin: "/user-dashboard",
};

const ProtectedRoute = ({ children }) => {
  const { token, loading, user } = useContext(AuthContext);
  const location = useLocation();

  if (loading) {
    return (
      <div className="flex justify-center items-center h-screen">
        Loading...
      </div>
    );
  }

  // 🔹 Agar login nahi hai → children show karo (jaise login/register page)
  if (!token) {
    return children;
  }

  // 🔹 Agar login hai aur restricted paths pe hai
  if (
    location.pathname === "/" ||
    location.pathname === "/login" ||
    location.pathname === "/register"
  ) {
    const redirectPath = roleRoutes[user?.role] || "/dashboard"; // fallback
    return <Navigate to={redirectPath} replace />;
  }

  // 🔹 Otherwise normal page access allowed
  return children;
};

export default ProtectedRoute;
