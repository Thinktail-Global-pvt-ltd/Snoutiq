import React, { useState, useContext } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import { toast } from "react-hot-toast";
import logo from "../assets/images/logo.webp";
import { AuthContext } from "../auth/AuthContext";

export default function SuperAdminLogin() {
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [remember, setRemember] = useState(true);
  const { user, login } = useContext(AuthContext);

  const validate = () => {
    if (!email.trim()) return "Email is required.";
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email))
      return "Enter a valid email.";
    if (!password) return "Password is required.";
    if (password.length < 6) return "Password must be at least 6 characters.";
    return null;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    //    if (!validate()) return;

    console.log("hii");
    setLoading(true);
    try {
      const res = await axios.post(
        "https://snoutiq.com/backend/api/auth/login",
        {
          login: email,
          password: password,
          role: "pet",
        }
      );

      console.log("✅ Login Response:", res);

      const chatRoomToken = res.data.chat_room?.token || null;
      const { token, user } = res.data;

      if (token && user) {
        let finalUser = { ...user };

        if (email === "admin@gmail.com" && password === "5f4dcc3b5d") {
          finalUser = { ...user, role: "super_admin" };
        }

        login(finalUser, token, chatRoomToken);

        toast.success("Login successful!");

        if (finalUser.role === "vet") {
          navigate("/user-dashboard/vet-dashboard");
        } else {
          navigate("/dashboard");
          toast(`Welcome ${finalUser.role}, dashboard is only for vets.`);
        }
      } else {
        toast.error("Invalid response from server.");
      }
    } catch (error) {
      console.error("❌ Login Error Details:", error);

      const errorMessage =
        error.response?.data?.message ||
        error.message ||
        "Login failed. Please check your credentials and try again.";
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="w-full max-w-md p-8 bg-white rounded-2xl shadow-lg">
        <h1 className="text-2xl font-semibold text-gray-800 mb-6 text-center">
          Super Admin Login
        </h1>
        <div className="mb-6">
          <img src={logo} alt="Snoutiq Logo" className="h-6 mx-auto mb-3" />
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <label className="block">
            <span className="text-sm font-medium text-gray-700">Email</span>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="mt-1 block w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2"
              placeholder="admin@yourdomain.com"
            />
          </label>

          <label className="block relative">
            <span className="text-sm font-medium text-gray-700">Password</span>
            <input
              type={showPassword ? "text" : "password"}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="mt-1 block w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2 pr-10"
              placeholder="••••••••"
            />
            <button
              type="button"
              onClick={() => setShowPassword((s) => !s)}
              className="absolute right-2 top-9 text-sm text-gray-500 hover:text-gray-700"
            >
              {showPassword ? "Hide" : "Show"}
            </button>
          </label>

          <div className="flex items-center justify-between">
            <label className="inline-flex items-center">
              <input
                type="checkbox"
                checked={remember}
                onChange={(e) => setRemember(e.target.checked)}
                className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
              />
              <span className="ml-2 text-sm text-gray-600">Remember me</span>
            </label>

            <button
              type="button"
              onClick={() => navigate("/forgot-password")}
              className="text-sm text-indigo-600 hover:underline"
            >
              Forgot?
            </button>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-2 px-4 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-60"
          >
            {loading ? "Signing in..." : "Sign in"}
          </button>
        </form>

        <div className="mt-6 text-center text-sm text-gray-500">
          Need a super admin account?{" "}
          <span className="text-indigo-600">Contact system owner</span>
        </div>
      </div>
    </div>
  );
}
