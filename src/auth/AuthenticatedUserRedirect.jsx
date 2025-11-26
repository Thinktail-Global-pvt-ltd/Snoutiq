import React, { useContext, lazy, Suspense } from "react";
import { Navigate } from "react-router-dom";
import { AuthContext } from "./AuthContext";

// 🔹 Role to Path Mapping
const roleRoutes = {
  vet: "/user-dashboard/vet-dashboard",
  pet: "/dashboard",
  super_admin: "/user-dashboard",
};

// Lazy load Home
const Home = lazy(() => import("../pages/Home"));

const AuthenticatedUserRedirect = () => {
  const { token, user } = useContext(AuthContext);

  if (token) {
    const redirectPath = roleRoutes[user?.role] || "/dashboard";
    return <Navigate to={redirectPath} replace />;
  }

  // Guest users ko home page dikhao
  return (
    <Suspense fallback={<div>Loading...</div>}>
      <Home />
    </Suspense>
  );
};

export default AuthenticatedUserRedirect;
