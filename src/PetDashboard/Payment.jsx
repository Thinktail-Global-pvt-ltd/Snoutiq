import React, {
  useState,
  useEffect,
  useContext,
  useMemo,
  useRef,
} from "react";
import {
  useSearchParams,
  useNavigate,
  useLocation,
} from "react-router-dom";
import axios from "axios";
import { socket } from "../pages/socket";
import { AuthContext } from "../auth/AuthContext";

import {
  CreditCardIcon,
  LockClosedIcon,
  ShieldCheckIcon,
  ClockIcon,
  CheckCircleIcon,
  XCircleIcon,
  VideoCameraIcon,
} from "@heroicons/react/24/outline";

const API_BASE = "https://snoutiq.com/backend";

const Payment = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const location = useLocation();

  console.log(Object.fromEntries(searchParams.entries()), "✅ All params");

  const { nearbyDoctors, liveDoctors, user } = useContext(AuthContext);

  const [loading, setLoading] = useState(false);
  const [paymentStatus, setPaymentStatus] = useState(null);
  const [doctorInfo, setDoctorInfo] = useState(null);
  const [selectedMethod, setSelectedMethod] = useState("card");
  const [timeLeft, setTimeLeft] = useState(5 * 60);
  const [razorpayLoaded, setRazorpayLoaded] = useState(false);

  const originalDoctorRef = useRef(null);
  const hasCleanedUp = useRef(false);
  const countdownInterval = useRef(null);
  const paymentWindowActive = useRef(false);

  // ---- URL & state params ----
  const callIdFromParams = searchParams.get("callId");
  const {
    doctor: doctorFromState,
    channel,
    patientId,
    callId: callIdFromState,
  } = location.state || {};

  const channelFromParams = searchParams.get("channel");
  const patientIdFromParams = searchParams.get("patientId");
  const doctorIdFromParams = searchParams.get("doctorId");

  const callId = callIdFromState || callIdFromParams || "";
  const channelValue = channel || channelFromParams || "";
  const patientIdValue = patientId || patientIdFromParams || user?.id || "";

  // ✅ Preserve original doctor from state (most trusted)
  if (!originalDoctorRef.current && doctorFromState) {
    originalDoctorRef.current = doctorFromState;
    console.log("💾 Stored doctor from navigation state:", doctorFromState);
  }

  // Pick best doctor from context if needed
  const doctorFromContext = useMemo(() => {
    const baseId =
      originalDoctorRef.current?.doctor?.id ||
      originalDoctorRef.current?.id ||
      doctorIdFromParams;

    if (!baseId) return null;

    const all = [...(nearbyDoctors || []), ...(liveDoctors || [])];
    const found = all.find((d) => String(d.id) === String(baseId));

    return found || null;
  }, [nearbyDoctors, liveDoctors, doctorIdFromParams]);

  // ✅ CRITICAL FIX: Enhanced doctor resolution with multiple fallbacks
  useEffect(() => {
    const resolveDoctorInfo = () => {
      let sourceDoctor = null;
      let source = "unknown";

      // Priority 1: Navigation state (most reliable)
      if (originalDoctorRef.current) {
        sourceDoctor = originalDoctorRef.current;
        source = "navigation-state";
      }

      // Priority 2: SessionStorage fallback (for page reloads)
      if (!sourceDoctor) {
        try {
          const stored = sessionStorage.getItem("payment_doctor");
          if (stored) {
            sourceDoctor = JSON.parse(stored);
            source = "sessionStorage";
            sessionStorage.removeItem("payment_doctor"); // Clean up
            console.log("🔄 Retrieved doctor from sessionStorage:", sourceDoctor);
          }
        } catch (err) {
          console.error("Failed to parse stored doctor:", err);
        }
      }

      // Priority 3: Context lookup as last resort
      if (!sourceDoctor && doctorIdFromParams) {
        sourceDoctor = doctorFromContext;
        source = "context-lookup";
      }

      if (!sourceDoctor) {
        console.error("❌ No doctor data available from any source");
        setDoctorInfo(null);
        return;
      }

      console.log(`📦 Doctor source: ${source}`, sourceDoctor);

      // Normalize doctor object
      const base =
        sourceDoctor.doctor && typeof sourceDoctor.doctor === "object"
          ? sourceDoctor.doctor
          : sourceDoctor;

      const amount = resolvePrice(sourceDoctor, base);
      const id = base.id || sourceDoctor.id || doctorIdFromParams;

      if (!id || !amount) {
        console.error("❌ Invalid doctor data:", {
          id,
          amount,
          sourceDoctor,
        });
        setDoctorInfo(null);
        return;
      }

      const info = {
        id: Number(id),
        name:
          base.name ||
          sourceDoctor.name ||
          base.email?.split("@")[0] ||
          `Doctor ${id}`,
        clinic_name:
          base.clinic_name ||
          sourceDoctor.clinic_name ||
          "Veterinary Clinic",
        rating: base.rating || sourceDoctor.rating || 4.5,
        distance: base.distance || sourceDoctor.distance || null,
        photo:
          base.profile_image ||
          sourceDoctor.profile_image ||
          base.image ||
          sourceDoctor.image ||
          null,
        amount,
        chat_price: amount,
      };

      console.log(
        `💰 Resolved doctor: id=${info.id}, name=${info.name}, amount=₹${info.amount}`
      );

      setDoctorInfo(info);
    };

    resolveDoctorInfo();
  }, [doctorFromContext, doctorIdFromParams]);

  console.log("🧭 callIdFromParams:", callIdFromParams);
  console.log("🧭 doctorIdFromParams:", doctorIdFromParams);
  console.log("🧭 doctorInfo:", doctorInfo);
  console.log("🧭 callId state:", callId);

  // ---- cleanup helper ----
  const performCleanup = (reason = "unknown") => {
    if (hasCleanedUp.current) return;
    hasCleanedUp.current = true;

    console.log(`🧹 Performing cleanup - Reason: ${reason}`);

    if (countdownInterval.current) {
      clearInterval(countdownInterval.current);
      countdownInterval.current = null;
    }

    if (paymentStatus !== "success" && socket && callId) {
      socket.emit("payment-cancelled", {
        callId,
        patientId: patientIdValue,
        doctorId: doctorInfo?.id,
        reason,
        timestamp: new Date().toISOString(),
      });
      console.log("📤 Emitted payment-cancelled");
    }

    paymentWindowActive.current = false;
  };

  // ---- load Razorpay script ----
  useEffect(() => {
    const loadRazorpayScript = () =>
      new Promise((resolve) => {
        if (window.Razorpay) {
          setRazorpayLoaded(true);
          return resolve(true);
        }
        const script = document.createElement("script");
        script.src = "https://checkout.razorpay.com/v1/checkout.js";
        script.onload = () => {
          setRazorpayLoaded(true);
          resolve(true);
        };
        script.onerror = () => {
          console.error("Failed to load Razorpay script");
          setPaymentStatus("error");
          performCleanup("razorpay-load-failed");
          resolve(false);
        };
        document.body.appendChild(script);
      });

    loadRazorpayScript();
  }, []);

  // ---- countdown timer ----
  useEffect(() => {
    countdownInterval.current = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev <= 1) {
          clearInterval(countdownInterval.current);
          setPaymentStatus("timeout");
          performCleanup("5-minute-timeout");
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => {
      if (countdownInterval.current) {
        clearInterval(countdownInterval.current);
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // ---- socket listeners for call status ----
  useEffect(() => {
    if (!socket || !callId) return;

    const handleDoctorCancelled = (data) => {
      if (data.callId === callId) {
        console.log("📞 Doctor cancelled the call:", data);
        setPaymentStatus("doctor-cancelled");
        performCleanup("doctor-cancelled");
      }
    };

    const handleCallRejected = (data) => {
      if (data.callId === callId) {
        console.log("❌ Call rejected:", data);
        setPaymentStatus("doctor-cancelled");
        performCleanup("call-rejected");
      }
    };

    const handleCallTimeout = (data) => {
      if (data.callId === callId) {
        console.log("⏰ Call timeout:", data);
        setPaymentStatus("timeout");
        performCleanup("call-timeout");
      }
    };

    socket.on("call-rejected", handleCallRejected);
    socket.on("doctor-cancelled", handleDoctorCancelled);
    socket.on("call-timeout", handleCallTimeout);

    return () => {
      socket.off("call-rejected", handleCallRejected);
      socket.off("doctor-cancelled", handleDoctorCancelled);
      socket.off("call-timeout", handleCallTimeout);
    };
  }, [callId]);

  // ---- cleanup on unmount ----
  useEffect(() => {
    return () => {
      if (paymentStatus !== "success") {
        performCleanup("component-unmount");
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [paymentStatus]);

  // ---- before unload ----
  useEffect(() => {
    const handleBeforeUnload = (e) => {
      if (paymentStatus !== "success" && paymentWindowActive.current) {
        performCleanup("browser-close");
        e.preventDefault();
        e.returnValue =
          "Payment in progress. Are you sure you want to leave?";
      }
    };

    window.addEventListener("beforeunload", handleBeforeUnload);
    return () =>
      window.removeEventListener("beforeunload", handleBeforeUnload);
  }, [paymentStatus]);

  // ---- handle payment ----
  const handlePayment = async () => {
    if (!window.Razorpay || !razorpayLoaded) {
      console.error("Razorpay SDK not loaded");
      setPaymentStatus("error");
      performCleanup("razorpay-not-loaded");
      return;
    }

    if (!doctorInfo || !doctorInfo.amount || !callId || !patientIdValue) {
      console.error("❌ Missing data for payment:", {
        doctorInfo,
        callId,
        patientIdValue,
      });
      setPaymentStatus("error");
      return;
    }

    const amount = Number(doctorInfo.amount);
    if (!Number.isFinite(amount) || amount <= 0) {
      console.error("❌ Invalid amount for payment:", amount);
      setPaymentStatus("error");
      return;
    }

    setLoading(true);
    setPaymentStatus(null);
    paymentWindowActive.current = true;

    try {
      console.log(`💳 Creating order for ₹${amount} | callId=${callId}`);

      const orderRes = await axios.post(`${API_BASE}/api/create-order`, {
        amount,
        callId,
        doctorId: doctorInfo.id,
        patientId: patientIdValue,
        channel: channelValue,
      });

      if (
        !orderRes.data?.success ||
        !orderRes.data?.order_id ||
        !orderRes.data?.key
      ) {
        console.error("❌ Invalid order response", orderRes.data);
        throw new Error("Invalid order response");
      }

      const { order_id, key } = orderRes.data;

      const options = {
        key,
        order_id,
        name: "SnoutIQ Veterinary Consultation",
        description: `Video consultation with ${doctorInfo.name}`,
        image: "https://snoutiq.com/logo.webp",
        theme: { color: "#4F46E5" },
        handler: async (response) => {
          paymentWindowActive.current = false;
          console.log("✅ Payment success:", response);

          try {
            const verifyRes = await axios.post(`${API_BASE}/api/rzp/verify`, {
              callId,
              doctorId: doctorInfo.id,
              patientId: patientIdValue,
              channel: channelValue,
              razorpay_order_id: response.razorpay_order_id,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_signature: response.razorpay_signature,
            });

            console.log("Verify API Response:", verifyRes.data);

            socket?.emit("payment-completed", {
              callId,
              patientId: patientIdValue,
              doctorId: doctorInfo.id,
              channel: channelValue,
              paymentId: response.razorpay_payment_id,
            });

            setPaymentStatus("success");
            setLoading(false);

            const query = new URLSearchParams({
              uid: String(patientIdValue || ""),
              role: "audience",
              callId: String(callId || ""),
              doctorId: String(doctorInfo.id || ""),
              patientId: String(patientIdValue || ""),
            }).toString();

            setTimeout(() => {
              navigate(`/call-page/${channelValue}?${query}`);
            }, 1200);
          } catch (error) {
            console.error(
              "Payment verification error:",
              error.response?.data || error.message
            );
            setPaymentStatus("verification-failed");
            setLoading(false);
            performCleanup("verification-failed");
          }
        },
        modal: {
          ondismiss: () => {
            console.warn("💳 Payment popup closed by user");
            setLoading(false);
            setPaymentStatus("cancelled");
            paymentWindowActive.current = false;
            performCleanup("user-cancelled");
          },
          escape: false,
        },
      };

      const rzp = new window.Razorpay(options);

      rzp.on("payment.failed", (response) => {
        console.error("💳 Payment failed:", response.error);
        setPaymentStatus("error");
        setLoading(false);
        paymentWindowActive.current = false;
        performCleanup("payment-failed");
      });

      rzp.open();
    } catch (error) {
      console.error(
        "Payment initiation error:",
        error.response?.data || error.message
      );
      setPaymentStatus("error");
      setLoading(false);
      paymentWindowActive.current = false;
      performCleanup("payment-initiation-failed");
    }
  };

  // ---- helpers ----
  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, "0")}`;
  };

  const getDisplayPrice = () => {
    if (doctorInfo?.amount) {
      return `₹${doctorInfo.amount}`;
    }
    return "₹500";
  };

  const paymentMethods = [
    {
      id: "card",
      name: "Credit/Debit Card",
      icon: "💳",
      description: "Pay securely with your card",
    },
    { id: "upi", name: "UPI", icon: "📱", description: "UPI apps" },
    {
      id: "netbanking",
      name: "Net Banking",
      icon: "🏦",
      description: "Internet banking",
    },
    {
      id: "wallet",
      name: "Wallet",
      icon: "💰",
      description: "Supported wallets",
    },
  ];

  const packageDetails = {
    duration: "30 minutes",
    features: [
      "One-on-one video consultation",
      "Professional veterinary advice",
      "Post-consultation summary",
      "Prescription if needed",
    ],
  };

  // ---- UI states (timeout / cancelled) ----
  if (!razorpayLoaded) {
    return (
      <div className="min-h-screen bg-blue-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <div className="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
          <h2 className="text-xl font-semibold text-gray-800 mb-2">
            Loading Payment Gateway...
          </h2>
          <p className="text-gray-600">
            Please wait while we set up secure payment processing.
          </p>
        </div>
      </div>
    );
  }

  if (!doctorInfo || !callId) {
    return (
      <div className="min-h-screen bg-red-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <XCircleIcon className="w-16 h-16 text-red-500 mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-red-800 mb-4">
            Unable to start payment
          </h2>
          <p className="text-red-600 mb-6">
            Missing consultation details. Please go back and try again.
          </p>
          <button
            onClick={() => navigate("/dashboard")}
            className="w-full py-3 bg-red-600 text-white rounded-lg hover:bg-red-700"
          >
            Back to Dashboard
          </button>
        </div>
      </div>
    );
  }

  if (paymentStatus === "timeout") {
    return (
      <TimeoutScreen
        navigate={navigate}
        icon={XCircleIcon}
        title="Payment Timeout"
        message="The payment window has expired. The doctor has been notified."
      />
    );
  }

  if (paymentStatus === "doctor-cancelled") {
    return (
      <TimeoutScreen
        navigate={navigate}
        icon={XCircleIcon}
        title="Call Cancelled"
        message="The doctor has cancelled the call. No payment has been charged."
        color="yellow"
      />
    );
  }

  // ---- main payment UI ----
  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
      <div className="max-w-4xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        {/* Header */}
        <div className="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <VideoCameraIcon className="w-8 h-8 mr-2" />
              <div>
                <h1 className="text-2xl font-bold">Video Call Payment</h1>
                <p className="opacity-90">
                  Complete payment to join consultation
                </p>
              </div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold">{formatTime(timeLeft)}</div>
              <div className="text-sm opacity-80">Time left</div>
            </div>
          </div>
        </div>

        {/* Body */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6">
          {/* Left: Call details */}
          <div className="space-y-6">
            <div className="bg-green-50 border border-green-200 rounded-xl p-5">
              <div className="flex items-center mb-2">
                <CheckCircleIcon className="w-6 h-6 text-green-600 mr-2" />
                <h2 className="text-lg font-semibold text-green-800">
                  Call Accepted
                </h2>
              </div>
              <p className="text-sm text-green-700">
                The doctor has accepted your call request and is waiting for
                you to complete the payment.
              </p>
            </div>

            <div className="bg-gray-50 rounded-xl p-5 border border-gray-200">
              <h2 className="text-lg font-semibold text-gray-800 mb-4">
                Call Details
              </h2>

              <div className="flex items-center mb-4">
                {doctorInfo.photo ? (
                  <img
                    src={doctorInfo.photo}
                    alt={doctorInfo.name}
                    className="w-12 h-12 rounded-full object-cover mr-3 border-2 border-indigo-100"
                  />
                ) : (
                  <div className="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                    <span className="text-indigo-600 font-semibold">
                      {doctorInfo.name
                        .split(" ")
                        .map((n) => n[0])
                        .join("")
                        .slice(0, 2)}
                    </span>
                  </div>
                )}
                <div>
                  <h3 className="font-medium text-gray-900">
                    {doctorInfo.name}
                  </h3>
                  <p className="text-sm text-gray-600">
                    {doctorInfo.clinic_name}
                  </p>
                  {doctorInfo.rating && (
                    <div className="flex items-center mt-1">
                      <span className="text-yellow-400 mr-1">⭐</span>
                      <span className="text-sm text-gray-600">
                        {doctorInfo.rating}/5
                      </span>
                    </div>
                  )}
                </div>
              </div>

              <div className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600">Duration:</span>
                  <span className="font-medium">
                    {packageDetails.duration}
                  </span>
                </div>
                {doctorInfo.distance && (
                  <div className="flex justify-between">
                    <span className="text-gray-600">Distance:</span>
                    <span className="font-medium">
                      {doctorInfo.distance.toFixed(1)} km away
                    </span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-gray-600">Call ID:</span>
                  <span className="font-mono text-xs">{callId}</span>
                </div>
                <div className="flex justify-between text-lg font-semibold mt-4 pt-4 border-t border-gray-200">
                  <span>Total Amount:</span>
                  <span className="text-indigo-600">
                    {getDisplayPrice()}
                  </span>
                </div>
              </div>
            </div>

            <div className="bg-blue-50 rounded-xl p-5 border border-blue-200">
              <h3 className="font-semibold text-blue-800 mb-3">
                What&apos;s Included
              </h3>
              <ul className="space-y-2 text-sm text-blue-700">
                {packageDetails.features.map((feature) => (
                  <li key={feature} className="flex items-center">
                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                    {feature}
                  </li>
                ))}
              </ul>
            </div>
          </div>

          {/* Right: Payment options */}
          <div className="space-y-6">
            <div className="bg-white rounded-xl p-5 border border-gray-200">
              <h2 className="text-lg font-semibold text-gray-800 mb-4">
                Select Payment Method
              </h2>

              <div className="grid grid-cols-2 gap-3 mb-6">
                {paymentMethods.map((method) => (
                  <div
                    key={method.id}
                    onClick={() => setSelectedMethod(method.id)}
                    className={`p-4 border rounded-lg cursor-pointer transition-all duration-200 ${
                      selectedMethod === method.id
                        ? "border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100"
                        : "border-gray-300 hover:border-indigo-300"
                    }`}
                  >
                    <div className="flex items-center">
                      <span className="text-2xl mr-2">{method.icon}</span>
                      <div>
                        <p className="font-medium text-gray-900 text-sm">
                          {method.name}
                        </p>
                        <p className="text-xs text-gray-500">
                          {method.description}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              <div className="flex items-center justify-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                <LockClosedIcon className="w-5 h-5 text-green-600 mr-2" />
                <span className="text-sm text-gray-600">
                  Secure & encrypted payment processing
                </span>
              </div>
            </div>

            <button
              onClick={handlePayment}
              disabled={loading || timeLeft <= 0 || !razorpayLoaded}
              className="w-full py-4 px-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
            >
              {loading ? (
                <>
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                  Processing...
                </>
              ) : (
                <>
                  <CreditCardIcon className="w-5 h-5 mr-2" />
                  Pay {getDisplayPrice()} &amp; Join Call
                </>
              )}
            </button>

            {/* status banners */}
            {paymentStatus === "success" && (
              <StatusBanner
                icon={CheckCircleIcon}
                color="green"
                text="Payment successful! Joining call..."
              />
            )}
            {paymentStatus === "error" && (
              <StatusBanner
                icon={XCircleIcon}
                color="red"
                text="Payment failed. Please try again."
              />
            )}
            {paymentStatus === "cancelled" && (
              <StatusBanner
                icon={ClockIcon}
                color="yellow"
                text="Payment cancelled. Doctor is still waiting."
              />
            )}
            {paymentStatus === "verification-failed" && (
              <StatusBanner
                icon={XCircleIcon}
                color="red"
                text="Payment verification failed. Please contact support."
              />
            )}

            <div className="text-center">
              <div className="flex items-center justify-center space-x-6 mb-2">
                <TrustBadge Icon={ShieldCheckIcon} label="SSL Secure" />
                <TrustBadge Icon={LockClosedIcon} label="Encrypted" />
                <TrustBadge Icon={CreditCardIcon} label="PCI DSS" />
              </div>
              <p className="text-xs text-gray-500">
                Your payment information is secure and encrypted.
              </p>
            </div>

            {timeLeft < 60 && (
              <div className="p-3 bg-red-50 border border-red-200 rounded-lg">
                <div className="flex items-center">
                  <ClockIcon className="w-5 h-5 text-red-600 mr-2" />
                  <span className="text-sm text-red-800 font-medium">
                    Hurry! Payment window expires in {formatTime(timeLeft)}.
                  </span>
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="bg-gray-50 p-4 border-t border-gray-200 text-center">
          <p className="text-sm text-gray-600">
            Need help? Contact{" "}
            <span className="text-indigo-600">support@snoutiq.com</span>
          </p>
        </div>
      </div>
    </div>
  );
};

// ---- small components & helpers ----

const TimeoutScreen = ({
  navigate,
  icon: Icon,
  title,
  message,
  color = "red",
}) => (
  <div
    className={`min-h-screen bg-${color}-50 flex items-center justify-center p-4`}
  >
    <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
      <Icon className={`w-16 h-16 text-${color}-500 mx-auto mb-4`} />
      <h2 className={`text-2xl font-bold text-${color}-800 mb-4`}>{title}</h2>
      <p className={`text-${color}-600 mb-6`}>{message}</p>
      <button
        onClick={() => navigate("/dashboard")}
        // className={`w-full py-3 bg-${color}-600 text-white rounded-lg hover:bg
        className={`w-full py-3 bg-${color}-600 text-white rounded-lg hover:bg-${color}-700`}
      >
        Back to Dashboard
      </button>
    </div>
  </div>
);

const StatusBanner = ({ icon: Icon, color, text }) => (
  <div
    className={`p-4 bg-${color}-50 border border-${color}-200 rounded-xl flex items-center`}
  >
    <Icon
      className={`w-6 h-6 text-${color}-600 mr-2`}
    />
    <span className={`text-${color}-800 text-sm`}>{text}</span>
  </div>
);

const TrustBadge = ({ Icon, label }) => (
  <div className="flex items-center">
    <Icon className="w-4 h-4 text-green-600 mr-1" />
    <span className="text-xs text-gray-600">{label}</span>
  </div>
);

function resolvePrice(src, base) {
  const candidates = [
    base.chat_price,
    src.chat_price,
    base.price,
    src.price,
  ].filter((v) => v !== undefined && v !== null);

  if (!candidates.length) return 500;

  const asNumber = Number(candidates[0]);
  return Number.isFinite(asNumber) && asNumber > 0
    ? asNumber
    : 500;
}

export default Payment;
