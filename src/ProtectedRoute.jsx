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


import { useContext } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { AuthContext } from "./auth/AuthContext";

const ProtectedRoute = ({ children }) => {
  const { token, loading } = useContext(AuthContext);
  const location = useLocation();

  if (loading) {
    return <div className="flex justify-center items-center h-screen">Loading...</div>;
  }

  if (!token) {
    return children; 
  }

  // Agar login hai aur user "/" ya "/login" ya "/register" pe aata hai
  if (location.pathname === "/" || location.pathname === "/login" || location.pathname === "/register") {
    return <Navigate to="/dashboard" replace />;
  }

  return children;
};

export default ProtectedRoute;
