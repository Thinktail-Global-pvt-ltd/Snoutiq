import React, { useState, useEffect, useContext, useMemo, useRef } from "react";
import {
  useParams,
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

const RAZORPAY_KEY_ID = "rzp_test_1nhE9190sR3rkP";
const API_BASE = "https://snoutiq.com/backend";

const Payment = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { nearbyDoctors, liveDoctors, user } = useContext(AuthContext);
  
console.log(nearbyDoctors,"ankit");


  const [loading, setLoading] = useState(false);
  const [paymentStatus, setPaymentStatus] = useState(null);
  // Holds the selected doctor's resolved details, including chat_price
  const [doctorInfo, setDoctorInfo] = useState(null);
  const [selectedMethod, setSelectedMethod] = useState("card");
  const [timeLeft, setTimeLeft] = useState(5 * 60);
  const [razorpayLoaded, setRazorpayLoaded] = useState(false);
  
  const location = useLocation();
  const callIdFromParams = searchParams.get("callId");
  const {
    doctor,
    channel,
    patientId,
    callId: callIdFromState,
  } = location.state || {};
  
  const callId = callIdFromState || callIdFromParams;
  const doctorId = doctor?.id || searchParams.get("doctorId");

  const hasCleanedUp = useRef(false);
  const countdownInterval = useRef(null);
  const paymentWindowActive = useRef(false);

  const channelFromParams = searchParams.get("channel");
  const patientIdFromParams = searchParams.get("patientId");
  const channelValue = channel || channelFromParams || "";
  const patientIdValue = patientId || patientIdFromParams || user?.id || "";

  // ✅ FIXED: Proper doctor finding logic
  const doctorFromContext = useMemo(() => {
    if (!doctorId) return null;
    const idStr = String(doctorId);
    const all = [...(nearbyDoctors || []), ...(liveDoctors || [])];
    return all.find((d) => String(d.id) === idStr) || null;
  }, [doctorId, nearbyDoctors, liveDoctors]);

  const performCleanup = (reason = "unknown") => {
    if (hasCleanedUp.current) {
      console.log("⚠️ Cleanup already performed, skipping...");
      return;
    }

    console.log(`🧹 Performing cleanup - Reason: ${reason}`);
    hasCleanedUp.current = true;

    if (countdownInterval.current) {
      clearInterval(countdownInterval.current);
      countdownInterval.current = null;
    }

    if (paymentStatus !== "success" && socket) {
      socket.emit("payment-cancelled", {
        callId: callId,
        patientId: patientIdValue,
        doctorId: doctorInfo?.id || doctorFromContext?.id || doctorId,
        reason: reason,
        timestamp: new Date().toISOString(),
      });
      console.log(`📤 Emitted payment-cancelled: ${reason}`);
    }

    paymentWindowActive.current = false;
  };

  useEffect(() => {
    const loadRazorpayScript = () => {
      return new Promise((resolve) => {
        if (window.Razorpay) {
          setRazorpayLoaded(true);
          resolve(true);
          return;
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
    };

    loadRazorpayScript();
  }, []);

  // Resolve the selected doctor from navigation state or context
  useEffect(() => {
    const src = doctor || doctorFromContext;
console.log(src,"ankit12");

    if (src) {
      const actualDoctor = src.doctor || src;
      // Prefer chat_price from either nested doctor or top-level normalized object
      const priceValueRaw =
        actualDoctor?.chat_price ?? src?.chat_price ?? null;
      const priceValue = Number(priceValueRaw);

      console.log(
        `💰 Selected Doctor Price: ₹${Number.isFinite(priceValue) ? priceValue : 'N/A'} for Dr. ${
          actualDoctor.name || src.name
        }`
      );

      setDoctorInfo({
        id: actualDoctor.id || src.id,
        name: actualDoctor.name || src.name || src.email?.split("@")[0] || `Doctor ${src.id}`,
        specialty: actualDoctor.specialty || src.specialty || "Veterinarian",
        experience: actualDoctor.experience || src.experience || "5+ years",
        rating: actualDoctor.rating || src.rating || 4.5,
        price: Number.isFinite(priceValue) ? `₹${priceValue}` : undefined,
        chat_price: Number.isFinite(priceValue) ? priceValue : undefined,
        clinic_name: actualDoctor.clinic_name || src.clinic_name || "Veterinary Clinic",
        business_status: actualDoctor.business_status || src.business_status,
        distance: actualDoctor.distance || src.distance,
        address: actualDoctor.formatted_address || src.formatted_address || "Not available",
        photo: actualDoctor.profile_image || src.profile_image || actualDoctor.image || src.image || null,
      });
    } else {
      setDoctorInfo(null);
    }
  }, [doctor, doctorFromContext]);
// console.log(doctorInfo,"ankit123");

  // If doctorId is not provided in URL/state, fetch call-session to infer doctor and price
  useEffect(() => {
    if (!callId) return;
    // If we already have a valid price, skip
    if (doctorInfo?.chat_price !== undefined && doctorInfo?.chat_price !== null) return;

    let cancelled = false;
    (async () => {
      try {
        const res = await axios.get(`${API_BASE}/api/call/${callId}`);
        const s = res?.data?.session ?? res?.data ?? {};

        const resolvedDoctorId =
          s.doctorId ?? s.doctor_id ?? s.assigned_doctor_id ?? s.accepted_doctor_id ?? s.vet_doctor_id ?? s?.doctor?.id ?? null;
        const resolvedPriceRaw = s.chat_price ?? s.price ?? s.amount ?? null;
        const resolvedPrice = Number(resolvedPriceRaw);

        // Try to enrich with context list
        const candidate =
          [...(nearbyDoctors || []), ...(liveDoctors || [])].find(
            (d) => (resolvedDoctorId ? String(d.id) === String(resolvedDoctorId) : false)
          ) || doctorFromContext || doctor || null;

        if (cancelled) return;
        setDoctorInfo((prev) => {
          const priceToUse = Number.isFinite(resolvedPrice)
            ? resolvedPrice
            : Number(candidate?.chat_price);

          return {
            ...(prev || {}),
            id: candidate?.id || resolvedDoctorId || prev?.id,
            name: candidate?.name || prev?.name || s?.doctor?.name || 'Doctor',
            clinic_name: candidate?.clinic_name || prev?.clinic_name || s?.clinic_name,
            rating: candidate?.rating || prev?.rating,
            chat_price: Number.isFinite(priceToUse) ? priceToUse : undefined,
            price: Number.isFinite(priceToUse) ? `₹${priceToUse}` : prev?.price,
            distance: candidate?.distance ?? prev?.distance,
            photo: candidate?.profile_image || candidate?.image || prev?.photo || null,
          };
        });
      } catch (e) {
        // ignore fetch errors, UI will fallback
        console.warn('Call session lookup failed, using fallback price.');
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [callId, doctorInfo?.chat_price, nearbyDoctors, liveDoctors, doctorFromContext, doctor]);

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
  }, []);

  useEffect(() => {
    if (!socket) return;

    const handleDoctorCancelled = (data) => {
      console.log("📞 Doctor cancelled the call:", data);
      if (data.callId === callId) {
        setPaymentStatus("doctor-cancelled");
        performCleanup("doctor-cancelled");
      }
    };

    const handleCallRejected = (data) => {
      console.log("❌ Call rejected:", data);
      if (data.callId === callId) {
        setPaymentStatus("doctor-cancelled");
        performCleanup("call-rejected");
      }
    };

    const handleCallTimeout = (data) => {
      console.log("⏰ Call timeout:", data);
      if (data.callId === callId) {
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

  useEffect(() => {
    return () => {
      if (paymentStatus !== "success") {
        performCleanup("component-unmount");
      }
    };
  }, [paymentStatus]);

  useEffect(() => {
    const handleBeforeUnload = (e) => {
      if (paymentStatus !== "success" && paymentWindowActive.current) {
        performCleanup("browser-close");
        e.preventDefault();
        e.returnValue = "Payment in progress. Are you sure you want to leave?";
      }
    };

    window.addEventListener("beforeunload", handleBeforeUnload);

    return () => {
      window.removeEventListener("beforeunload", handleBeforeUnload);
    };
  }, [paymentStatus]);

  const handlePayment = async () => {
    if (!window.Razorpay) {
      console.error("Razorpay SDK not loaded");
      setPaymentStatus("error");
      performCleanup("razorpay-not-loaded");
      return;
    }

    setLoading(true);
    setPaymentStatus(null);
    paymentWindowActive.current = true;

    try {
      // ✅ FIXED: Use the correct doctor's price
      let amount = Number(doctorInfo?.chat_price);
      if (!Number.isFinite(amount) || amount < 1) amount = 500;
      console.log(`💳 Initiating payment for ₹${amount}`);

      const orderRes = await axios.post(`${API_BASE}/api/create-order`, {
        amount: Number(amount),
        callId: callId,
        doctorId: doctorInfo?.id || doctorFromContext?.id || doctorId,
        patientId: patientIdValue,
        channel: channelValue,
      });

      console.log("Order API Response:", orderRes.data);

      if (
        !orderRes.data?.success ||
        !orderRes.data?.order_id ||
        !orderRes.data?.key
      ) {
        throw new Error("Invalid order response");
      }

      const { order_id, key } = orderRes.data;

      const options = {
        key,
        order_id,
        name: "Snoutiq Veterinary Consultation",
        description: `Video consultation with ${doctorInfo?.name || "Doctor"}`,
        image: "https://snoutiq.com/logo.webp",
        theme: { color: "#4F46E5" },
        handler: async (response) => {
          console.log("✅ Payment successful:", response.razorpay_payment_id);
          paymentWindowActive.current = false;

          try {
            const verifyRes = await axios.post(`${API_BASE}/api/rzp/verify`, {
              callId: callId,
              doctorId: doctorInfo?.id || doctorFromContext?.id || doctorId,
              patientId: patientIdValue,
              channel: channelValue,
              razorpay_order_id: response.razorpay_order_id,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_signature: response.razorpay_signature,
            });

            console.log("Verify API Response:", verifyRes.data);

            socket.emit("payment-completed", {
              callId: callId,
              patientId: patientIdValue,
              doctorId: doctorInfo?.id || doctorFromContext?.id || doctorId,
              channel: channelValue,
              paymentId: response.razorpay_payment_id,
            });

            setPaymentStatus("success");
            setLoading(false);

            setTimeout(() => {
              const query = new URLSearchParams({
                uid: String(patientIdValue || ""),
                role: "audience",
                callId: String(callId || ""),
                doctorId: String(
                  doctorInfo?.id || doctorFromContext?.id || doctorId || ""
                ),
                patientId: String(patientIdValue || ""),
              }).toString();

              navigate(`/call-page/${channelValue}?${query}`);
            }, 1500);
          } catch (error) {
            console.error("Payment verification error:", error);
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
      
      rzp.on('payment.failed', function (response) {
        console.error("💳 Payment failed:", response.error);
        setPaymentStatus("error");
        setLoading(false);
        paymentWindowActive.current = false;
        performCleanup("payment-failed");
      });

      rzp.open();
    } catch (error) {
      console.error("Payment initiation error:", error);
      setPaymentStatus("error");
      setLoading(false);
      paymentWindowActive.current = false;
      performCleanup("payment-initiation-failed");
    }
  };

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, "0")}`;
  };

  // Always use doctorInfo.chat_price for accurate price display
  const getDisplayPrice = () => {
    const p = Number(doctorInfo?.chat_price);
    return Number.isFinite(p) ? `₹${p}` : "₹500";
  };

  const paymentMethods = [
    {
      id: "card",
      name: "Credit/Debit Card",
      icon: "💳",
      description: "Pay securely with your card",
    },
    { id: "upi", name: "UPI", icon: "📱", description: "Pay using UPI apps" },
    {
      id: "netbanking",
      name: "Net Banking",
      icon: "🏦",
      description: "Pay using net banking",
    },
    {
      id: "wallet",
      name: "Wallet",
      icon: "💰",
      description: "Pay using wallet",
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

  if (!razorpayLoaded) {
    return (
      <div className="min-h-screen bg-blue-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <div className="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <h2 className="text-xl font-semibold text-gray-800 mb-2">
            Loading Payment Gateway...
          </h2>
          <p className="text-gray-600">
            Please wait while we set up secure payment processing
          </p>
        </div>
      </div>
    );
  }

  if (paymentStatus === "timeout") {
    return (
      <div className="min-h-screen bg-red-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <XCircleIcon className="w-16 h-16 text-red-500 mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-red-800 mb-4">
            Payment Timeout
          </h2>
          <p className="text-red-600 mb-6">
            The payment window has expired. The doctor has been notified.
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

  if (paymentStatus === "doctor-cancelled") {
    return (
      <div className="min-h-screen bg-yellow-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <XCircleIcon className="w-16 h-16 text-yellow-500 mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-yellow-800 mb-4">
            Call Cancelled
          </h2>
          <p className="text-yellow-600 mb-6">
            The doctor has cancelled the call. No payment has been charged.
          </p>
          <button
            onClick={() => navigate("/dashboard")}
            className="w-full py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
          >
            Back to Dashboard
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
      <div className="max-w-4xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
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

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6">
          <div className="space-y-6">
            <div className="bg-green-50 border border-green-200 rounded-xl p-5">
              <div className="flex items-center mb-4">
                <CheckCircleIcon className="w-6 h-6 text-green-600 mr-2" />
                <h2 className="text-lg font-semibold text-green-800">
                  Call Accepted
                </h2>
              </div>
              <p className="text-sm text-green-700">
                The doctor has accepted your call request and is waiting for you
                to complete the payment.
              </p>
            </div>

            <div className="bg-gray-50 rounded-xl p-5 border border-gray-200">
              <h2 className="text-lg font-semibold text-gray-800 mb-4">
                Call Details
              </h2>

              {doctorInfo && (
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
                        {doctorInfo.name.split(" ").map(n => n[0]).join("")}
                      </span>
                    </div>
                  )}
                  <div>
                    <h3 className="font-medium text-gray-900">{doctorInfo.name}</h3>
                    <p className="text-sm text-gray-600">{doctorInfo.clinic_name}</p>
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
              )}

              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-600">Duration:</span>
                  <span className="font-medium">{packageDetails.duration}</span>
                </div>
                {doctorInfo?.distance && (
                  <div className="flex justify-between">
                    <span className="text-gray-600">Distance:</span>
                    <span className="font-medium">{doctorInfo.distance.toFixed(1)} km away</span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-gray-600">Call ID:</span>
                  <span className="font-mono text-sm">{callId}</span>
                </div>
                <div className="flex justify-between text-lg font-semibold mt-4 pt-4 border-t border-gray-200">
                  <span>Total Amount:</span>
                  <span className="text-indigo-600">{getDisplayPrice()}</span>
                </div>
              </div>
            </div>

            <div className="bg-blue-50 rounded-xl p-5 border border-blue-200">
              <h3 className="font-semibold text-blue-800 mb-3">
                What's Included
              </h3>
              <ul className="space-y-2">
                {packageDetails.features.map((feature, index) => (
                  <li
                    key={index}
                    className="flex items-center text-sm text-blue-700"
                  >
                    <CheckCircleIcon className="w-4 h-4 mr-2 text-green-500" />
                    {feature}
                  </li>
                ))}
              </ul>
            </div>
          </div>

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

            <div className="bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center justify-between">
              <div className="flex items-center gap-2">
                <CheckCircleIcon className="w-5 h-5 text-green-600" />
                <p className="text-green-800 text-sm font-medium">
                  ₹100 OFF coupon applied successfully
                </p>
              </div>
              <span className="text-[11px] font-semibold text-green-800 bg-white border border-green-200 px-2 py-0.5 rounded-md">
                SPECIAL100
              </span>
            </div>

            <button
              onClick={handlePayment}
              disabled={loading || timeLeft <= 0 || !razorpayLoaded}
              className="w-full py-4 px-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
            >
              {loading ? (
                <>
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                  Processing...
                </>
              ) : !razorpayLoaded ? (
                <>
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                  Loading Gateway...
                </>
              ) : (
                <>
                  <CreditCardIcon className="w-5 h-5 mr-2" />
                  Pay {getDisplayPrice()} & Join Call
                </>
              )}
            </button>

            {paymentStatus === "success" && (
              <div className="p-4 bg-green-50 border border-green-200 rounded-xl flex items-center">
                <CheckCircleIcon className="w-6 h-6 text-green-600 mr-2" />
                <span className="text-green-800">
                  Payment successful! Joining call...
                </span>
              </div>
            )}

            {paymentStatus === "error" && (
              <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center">
                <XCircleIcon className="w-6 h-6 text-red-600 mr-2" />
                <span className="text-red-800">
                  Payment failed. Please try again or refresh the page.
                </span>
              </div>
            )}

            {paymentStatus === "cancelled" && (
              <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-xl flex items-center">
                <ClockIcon className="w-6 h-6 text-yellow-600 mr-2" />
                <span className="text-yellow-800">
                  Payment cancelled. Doctor is still waiting.
                </span>
              </div>
            )}

            {paymentStatus === "verification-failed" && (
              <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center">
                <XCircleIcon className="w-6 h-6 text-red-600 mr-2" />
                <span className="text-red-800">
                  Payment verification failed. Please contact support.
                </span>
              </div>
            )}

            <div className="text-center">
              <div className="flex items-center justify-center space-x-6 mb-2">
                <div className="flex items-center">
                  <ShieldCheckIcon className="w-4 h-4 text-green-600 mr-1" />
                  <span className="text-xs text-gray-600">SSL Secure</span>
                </div>
                <div className="flex items-center">
                  <LockClosedIcon className="w-4 h-4 text-green-600 mr-1" />
                  <span className="text-xs text-gray-600">Encrypted</span>
                </div>
                <div className="flex items-center">
                  <CreditCardIcon className="w-4 h-4 text-green-600 mr-1" />
                  <span className="text-xs text-gray-600">
                    PCI DSS Compliant
                  </span>
                </div>
              </div>
              <p className="text-xs text-gray-500">
                Your payment information is secure and encrypted
              </p>
            </div>

            {timeLeft < 60 && (
              <div className="p-3 bg-red-50 border border-red-200 rounded-lg">
                <div className="flex items-center">
                  <ClockIcon className="w-5 h-5 text-red-600 mr-2" />
                  <span className="text-sm text-red-800 font-medium">
                    Hurry! Payment window expires in {formatTime(timeLeft)}
                  </span>
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="bg-gray-50 p-4 border-t border-gray-200 text-center">
          <p className="text-sm text-gray-600">
            Need help? Contact support at{" "}
            <span className="text-indigo-600">support@snoutiq.com</span>
          </p>
        </div>
      </div>
    </div>
  );
};

export default Payment;
