import React, { useState, useEffect } from "react";
import { useParams } from "react-router-dom";
import axios from "axios";
import {
  CreditCardIcon,
  LockClosedIcon,
  ShieldCheckIcon,
  ClockIcon,
  CheckCircleIcon,
  XCircleIcon
} from "@heroicons/react/24/outline";

const RAZORPAY_KEY_ID = "rzp_test_1nhE9190sR3rkP";
const API_BASE = "https://snoutiq.com/backend";

const PaymentPage = () => {
  const { sessionId } = useParams();
  const [loading, setLoading] = useState(false);
  const [paymentStatus, setPaymentStatus] = useState(null);
  const [doctorInfo, setDoctorInfo] = useState(null);
  const [selectedMethod, setSelectedMethod] = useState("card");

  useEffect(() => {
    // Fetch doctor information for the session
    const fetchDoctorInfo = async () => {
      try {
        const response = await axios.get(`${API_BASE}/api/call/${sessionId}`);
        setDoctorInfo(response.data.doctor);
      } catch (error) {
        console.error("Error fetching doctor info:", error);
      }
    };

    fetchDoctorInfo();
  }, [sessionId]);

  const handlePayment = async () => {
    setLoading(true);
    setPaymentStatus(null);

    try {
      const orderRes = await axios.post(`${API_BASE}/api/create-order`);
      const options = {
        key: RAZORPAY_KEY_ID,
        amount: orderRes.data.amount,
        currency: orderRes.data.currency,
        order_id: orderRes.data.id,
        name: "Snoutiq Veterinary Consultation",
        description: "Video consultation with certified veterinarian",
        image: "https://snoutiq.com/logo.webp",
        theme: {
          color: "#4F46E5"
        },
        handler: async (response) => {
          await axios.post(`${API_BASE}/api/call/${sessionId}/payment-success`, {
            payment_id: response.razorpay_payment_id,
            order_id: response.razorpay_order_id,
            signature: response.razorpay_signature,
          });
          setPaymentStatus("success");
          setTimeout(() => {
            window.location.href = `/video-call/${sessionId}`;
          }, 2000);
        },
        modal: {
          ondismiss: function() {
            setLoading(false);
            setPaymentStatus("cancelled");
          }
        }
      };
      
      const rzp = new window.Razorpay(options);
      rzp.open();
    } catch (error) {
      console.error("Payment error:", error);
      setPaymentStatus("error");
      setLoading(false);
    }
  };

  const paymentMethods = [
    { id: "card", name: "Credit/Debit Card", icon: "💳", description: "Pay securely with your card" },
    { id: "upi", name: "UPI", icon: "📱", description: "Pay using UPI apps" },
    { id: "netbanking", name: "Net Banking", icon: "🏦", description: "Pay using net banking" },
    { id: "wallet", name: "Wallet", icon: "💰", description: "Pay using wallet" }
  ];

  const packageDetails = {
    duration: "30 minutes",
    price: "₹499",
    features: [
      "One-on-one video consultation",
      "Professional veterinary advice",
      "Post-consultation summary",
      "Prescription if needed"
    ]
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
      <div className="max-w-4xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        {/* Header */}
        <div className="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white text-center">
          <div className="flex items-center justify-center mb-3">
            <ShieldCheckIcon className="w-8 h-8 mr-2" />
            <h1 className="text-2xl font-bold">Secure Payment</h1>
          </div>
          <p className="opacity-90">Complete your payment to start the video consultation</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6">
          {/* Left Column - Consultation Details */}
          <div className="space-y-6">
            <div className="bg-gray-50 rounded-xl p-5 border border-gray-200">
              <h2 className="text-lg font-semibold text-gray-800 mb-4">Consultation Details</h2>
              
              {doctorInfo && (
                <div className="flex items-center mb-4">
                  <div className="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                    <span className="text-indigo-600 font-semibold">
                      {doctorInfo.name.split(" ").map(n => n[0]).join("")}
                    </span>
                  </div>
                  <div>
                    <h3 className="font-medium text-gray-900">{doctorInfo.name}</h3>
                    <p className="text-sm text-gray-600">{doctorInfo.specialty}</p>
                  </div>
                </div>
              )}

              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-600">Duration:</span>
                  <span className="font-medium">{packageDetails.duration}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Session ID:</span>
                  <span className="font-mono text-sm">{sessionId}</span>
                </div>
                <div className="flex justify-between text-lg font-semibold mt-4 pt-4 border-t border-gray-200">
                  <span>Total Amount:</span>
                  <span className="text-indigo-600">{packageDetails.price}</span>
                </div>
              </div>
            </div>

            <div className="bg-blue-50 rounded-xl p-5 border border-blue-200">
              <h3 className="font-semibold text-blue-800 mb-3">What's Included</h3>
              <ul className="space-y-2">
                {packageDetails.features.map((feature, index) => (
                  <li key={index} className="flex items-center text-sm text-blue-700">
                    <CheckCircleIcon className="w-4 h-4 mr-2 text-green-500" />
                    {feature}
                  </li>
                ))}
              </ul>
            </div>
          </div>

          {/* Right Column - Payment Options */}
          <div className="space-y-6">
            <div className="bg-white rounded-xl p-5 border border-gray-200">
              <h2 className="text-lg font-semibold text-gray-800 mb-4">Select Payment Method</h2>
              
              <div className="grid grid-cols-2 gap-3 mb-6">
                {paymentMethods.map((method) => (
                  <div
                    key={method.id}
                    onClick={() => setSelectedMethod(method.id)}
                    className={`p-4 border rounded-lg cursor-pointer transition-all duration-200 ${
                      selectedMethod === method.id
                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100'
                        : 'border-gray-300 hover:border-indigo-300'
                    }`}
                  >
                    <div className="flex items-center">
                      <span className="text-2xl mr-2">{method.icon}</span>
                      <div>
                        <p className="font-medium text-gray-900 text-sm">{method.name}</p>
                        <p className="text-xs text-gray-500">{method.description}</p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {/* Security Badge */}
              <div className="flex items-center justify-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                <LockClosedIcon className="w-5 h-5 text-green-600 mr-2" />
                <span className="text-sm text-gray-600">Secure & encrypted payment processing</span>
              </div>
            </div>

            {/* Payment Button */}
            <button
              onClick={handlePayment}
              disabled={loading}
              className="w-full py-4 px-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
            >
              {loading ? (
                <>
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                  Processing...
                </>
              ) : (
                <>
                  <CreditCardIcon className="w-5 h-5 mr-2" />
                  Pay {packageDetails.price} Now
                </>
              )}
            </button>

            {/* Payment Status */}
            {paymentStatus === "success" && (
              <div className="p-4 bg-green-50 border border-green-200 rounded-xl flex items-center">
                <CheckCircleIcon className="w-6 h-6 text-green-600 mr-2" />
                <span className="text-green-800">Payment successful! Redirecting to consultation...</span>
              </div>
            )}

            {paymentStatus === "error" && (
              <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center">
                <XCircleIcon className="w-6 h-6 text-red-600 mr-2" />
                <span className="text-red-800">Payment failed. Please try again.</span>
              </div>
            )}

            {paymentStatus === "cancelled" && (
              <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-xl flex items-center">
                <ClockIcon className="w-6 h-6 text-yellow-600 mr-2" />
                <span className="text-yellow-800">Payment cancelled. You can try again.</span>
              </div>
            )}

            {/* Security Features */}
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
                  <span className="text-xs text-gray-600">PCI DSS Compliant</span>
                </div>
              </div>
              <p className="text-xs text-gray-500">
                Your payment information is secure and encrypted
              </p>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="bg-gray-50 p-4 border-t border-gray-200 text-center">
          <p className="text-sm text-gray-600">
            Need help? Contact support at <span className="text-indigo-600">support@snoutiq.com</span>
          </p>
        </div>
      </div>
    </div>
  );
};

export default PaymentPage;