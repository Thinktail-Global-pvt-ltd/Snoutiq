import React, { useState, useEffect, useCallback, useContext } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import axiosClient from '../axios';
import { AuthContext } from '../auth/AuthContext';

// Loading spinner component
const Spinner = () => (
  <div className="flex flex-col items-center justify-center space-y-4">
    <div className="w-16 h-16 border-4 border-blue-100 rounded-full animate-spin border-t-blue-500"></div>
    <p className="text-lg font-medium text-gray-600">Please wait...</p>
  </div>
);

// Main component
const SearchingDoctor = () => {
  const { token } = useContext(AuthContext);
  const navigate = useNavigate();
  

  const [status, setStatus] = useState('searching');
  const [requestDetails, setRequestDetails] = useState(null);
  const [groomerDetails, setGroomerDetails] = useState(null);
  const [errorMessage, setErrorMessage] = useState('');
  const [countdown, setCountdown] = useState(5);
  const [searchTime, setSearchTime] = useState(0);
  const [progressValue, setProgressValue] = useState(0);

  // Timer for search duration
  useEffect(() => {
    let timer;
    if (status === 'searching') {
      timer = setInterval(() => {
        setSearchTime(prev => prev + 1);
        setProgressValue(prev => Math.min(prev + 0.5, 100));
      }, 1000);
    }
    return () => clearInterval(timer);
  }, [status]);

  // Load Razorpay script
  const loadRazorpayScript = () => {
    return new Promise((resolve) => {
      if (document.getElementById('razorpay-checkout-js')) {
        resolve(true);
        return;
      }
      const script = document.createElement('script');
      script.id = 'razorpay-checkout-js';
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.onload = () => resolve(true);
      script.onerror = () => resolve(false);
      document.body.appendChild(script);
    });
  };

  // Verify payment
  const verifyPayment = async (paymentData) => {
    try {
      const authToken = localStorage.getItem('token');
      const payload = {
        token,
        razorpay_payment_id: paymentData.razorpay_payment_id,
        razorpay_order_id: paymentData.razorpay_order_id,
        razorpay_signature: paymentData.razorpay_signature,
      };

      await axiosClient.post('/user/emergency/amtPaid', payload, {
        headers: { Authorization: `Bearer ${authToken}` },
      });

      toast.success('Payment Successful! Redirecting to call...');
      
      // Start countdown before redirecting
      let count = 3;
      const countdownInterval = setInterval(() => {
        if (count <= 0) {
          clearInterval(countdownInterval);
          navigate(`/video-call`);
        } else {
          setCountdown(count);
          count--;
        }
      }, 1000);

    } catch (error) {
      const message = error.response?.data?.message || 'Payment verification failed. Please contact support.';
      toast.error(message);
      setErrorMessage(message);
      setStatus('error');
    }
  };

  // Handle payment
  const handlePayment = async () => {
    setStatus('paying');
    toast.loading('Initializing payment...');

    const scriptLoaded = await loadRazorpayScript();
    if (!scriptLoaded) {
      toast.error('Could not load payment gateway. Please check your connection.');
      setStatus('error');
      setErrorMessage('Failed to load payment script.');
      return;
    }

    try {
      const authToken = localStorage.getItem('token');
      const orderResponse = await axiosClient.post('/user/razorpay/create-order', {
        amount: requestDetails.amount_tobe_paid,
      }, {
        headers: { Authorization: `Bearer ${authToken}` },
      });

      const { amount, order_id, razorpay_key } = orderResponse.data;
      toast.dismiss();

      const options = {
        key: razorpay_key,
        amount: amount,
        currency: 'INR',
        name: 'Vet Emergency Call',
        description: `Payment for consultation with ${groomerDetails?.name || `Servicer ID: ${requestDetails.servicer_id}`}`,
        order_id: order_id,
        handler: (response) => verifyPayment(response),
        prefill: {},
        notes: { emergency_token: token },
        theme: { color: '#3B82F6' },
        modal: {
          ondismiss: () => {
            toast.error('Payment was cancelled.');
            setStatus('accepted');
          },
        },
      };

      const paymentObject = new window.Razorpay(options);
      paymentObject.open();

    } catch (error) {
      toast.dismiss();
      const message = error.response?.data?.message || 'An error occurred during payment initiation.';
      toast.error(message);
      setErrorMessage(message);
      setStatus('error');
    }
  };

  // Check request status
  const checkRequestStatus = useCallback(async () => {
    try {
      const authToken = localStorage.getItem('token');
      const res = await axiosClient.post('/user/emergency/isAccepted', { token }, {
        headers: { Authorization: `Bearer ${authToken}` },
      });
      const data = res.data.data;

      if (data.servicer_id !== 0 && data.is_paid === 0) {
        setRequestDetails(data);

        try {
          const groomerRes = await axiosClient.get(`/public/single_groomer/${data.servicer_id}`);
          setGroomerDetails(groomerRes.data.data);
          setStatus('accepted');
        } catch (groomerError) {
          console.error("Failed to fetch doctor details:", groomerError);
          setErrorMessage('Found a doctor, but could not fetch their details. Please try again.');
          setStatus('error');
        }
        
      } else if (data.is_paid === 1) {
        toast.success('Payment already verified. Redirecting...');
        navigate(`/dashboard/videocall/${token}`);
      }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to get request status.';
      setErrorMessage(message);
      setStatus('error');
    }
  }, [token, navigate]);

  // Poll for doctor acceptance
  useEffect(() => {
    if (status !== 'searching') return;
    
    checkRequestStatus();
    const intervalId = setInterval(checkRequestStatus, 5000);

    return () => clearInterval(intervalId);
  }, [status, checkRequestStatus]);

  // Render content based on status
  const renderContent = () => {
    switch (status) {
      case 'searching':
        return (
          <div className="text-center">
            <div className="relative mb-8">
              <div className="w-32 h-32 mx-auto mb-6 bg-blue-50 rounded-full flex items-center justify-center relative">
                <div className="absolute inset-0 rounded-full border-4 border-blue-100 animate-ping"></div>
                <div className="absolute inset-0 rounded-full border-4 border-blue-200 animate-pulse"></div>
                <svg xmlns="http://www.w3.org/2000/svg" className="w-16 h-16 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
            </div>
            
            <h1 className="text-3xl font-bold text-gray-800 mb-3">Connecting You With A Veterinarian</h1>
            <p className="text-lg text-gray-600 mb-6">We're finding the best available specialist for your emergency</p>
            
            {/* Animated progress indicator */}
            <div className="w-full bg-gray-200 rounded-full h-2.5 mb-6 mx-auto max-w-xs">
              <div 
                className="bg-blue-600 h-2.5 rounded-full transition-all duration-1000 ease-out" 
                style={{ width: `${progressValue}%` }}
              ></div>
            </div>
            
            {/* Real-time status updates */}
            <div className="space-y-4 mb-8">
              <div className="flex items-center justify-center space-x-3 text-left">
                <div className="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                  <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </div>
                <p className="text-gray-700">Emergency request received</p>
              </div>
              
              <div className="flex items-center justify-center space-x-3 text-left">
                <div className="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                  <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </div>
                <p className="text-gray-700">Searching available veterinarians</p>
              </div>
              
              <div className="flex items-center justify-center space-x-3 text-left">
                <div className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                  {searchTime > 10 ? (
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                  ) : (
                    <div className="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
                  )}
                </div>
                <p className="text-gray-700">Connecting to veterinarian</p>
              </div>
            </div>
            
            {/* Estimated time and counter */}
            <div className="inline-flex items-center px-4 py-2 bg-blue-50 rounded-full">
              <svg className="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span className="text-blue-700 font-medium">Search time: {searchTime}s</span>
            </div>
            
            {/* Reassuring message */}
            <div className="mt-8 bg-blue-50 p-4 rounded-xl border border-blue-100">
              <p className="text-sm text-blue-700">
                <span className="font-semibold">Please stay online.</span> Your pet's health is our priority. 
                All our veterinarians are certified and experienced in emergency care.
              </p>
            </div>
          </div>
        );
        
      case 'accepted':
        return (
          <div className="text-center animate-fade-in">
            <div className="relative mb-8">
              <div className="w-32 h-32 mx-auto mb-6 bg-green-50 rounded-full flex items-center justify-center relative">
                <div className="absolute inset-0 rounded-full border-4 border-green-100 animate-ping"></div>
                <svg xmlns="http://www.w3.org/2000/svg" className="w-16 h-16 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            
            <h1 className="text-3xl font-bold text-gray-800 mb-3">Veterinarian Found!</h1>
            <p className="text-lg text-gray-600 mb-6">Complete your payment to start your consultation immediately</p>
            
            {/* Doctor/Groomer Details */}
            {groomerDetails && (
              <div className="bg-gray-50 p-5 rounded-xl border border-gray-200 mb-6 text-left">
                <div className="flex items-center space-x-4">
                  <div className="relative flex-shrink-0">
                    <img
                      src={`https://app.snoutiq.com/public/${groomerDetails.profile_picture}`}
                      alt={groomerDetails.name}
                      className="w-16 h-16 rounded-full object-cover ring-2 ring-white shadow-sm"
                      onError={(e) => {
                        e.target.src = 'https://via.placeholder.com/64?text=Vet';
                      }}
                    />
                    <div className="absolute bottom-0 right-0 w-5 h-5 bg-green-400 rounded-full border-2 border-white"></div>
                  </div>
                  <div className="flex-1 min-w-0">
                    <h3 className="font-bold text-gray-900 truncate">Dr. {groomerDetails.name}</h3>
                    <p className="text-sm text-gray-600 mt-1 line-clamp-2">{groomerDetails.bio || "Certified veterinarian specializing in emergency care"}</p>
                    <div className="flex items-center mt-2">
                      <svg className="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                      <span className="text-xs text-gray-600">4.8 (126 consultations)</span>
                    </div>
                  </div>
                </div>
              </div>
            )}
            
            <div className="bg-blue-50 p-5 rounded-xl border border-blue-100 mb-6">
              <p className="text-sm text-blue-700 font-medium mb-2">Emergency Consultation Fee</p>
              <p className="text-3xl font-bold text-blue-800">₹{requestDetails?.amount_tobe_paid || '0'}</p>
              <p className="text-xs text-blue-600 mt-1">One-time video consultation fee</p>
            </div>

            <button
              onClick={handlePayment}
              disabled={!requestDetails}
              className="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02] active:scale-100 disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg flex items-center justify-center"
            >
              <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
              </svg>
              Proceed to Secure Payment
            </button>
            
            <p className="text-xs text-gray-500 mt-4 flex items-center justify-center">
              <svg className="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
              </svg>
              Secure payment processed by Razorpay
            </p>
          </div>
        );
        
      case 'paying':
        return (
          <div className="text-center">
            <div className="w-24 h-24 mx-auto mb-6 bg-blue-50 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" className="w-12 h-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            
            <h1 className="text-2xl font-bold text-gray-800 mb-2">Processing Your Payment</h1>
            <p className="text-gray-600 mb-6">Please wait while we securely process your transaction.</p>
            
            <Spinner />
            
            <div className="bg-blue-50 p-4 rounded-lg border border-blue-100 mt-6">
              <p className="text-sm text-blue-700">Do not close this window until the process is complete.</p>
            </div>
          </div>
        );
        
      case 'error':
        return (
          <div className="text-center">
            <div className="w-24 h-24 mx-auto mb-6 bg-red-50 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" className="w-12 h-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            
            <h1 className="text-2xl font-bold text-gray-800 mb-2">We Encountered an Issue</h1>
            <p className="text-gray-600 mb-6">{errorMessage}</p>
            
            <div className="bg-red-50 p-4 rounded-lg border border-red-100 mb-6">
              <p className="text-sm text-red-700">Please try again or contact support if the problem persists.</p>
            </div>
            
            <div className="flex space-x-4">
              <button 
                onClick={() => navigate('/dashboard')} 
                className="flex-1 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors"
              >
                Dashboard
              </button>
              <button 
                onClick={() => window.location.reload()} 
                className="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
              >
                Try Again
              </button>
            </div>
          </div>
        );
        
      default:
        return null;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center p-4">
      <div className="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
        <div className="p-8">
          {renderContent()}
        </div>
        
        {/* Footer */}
        <div className="bg-gray-50 px-8 py-4 border-t border-gray-200">
          <p className="text-xs text-gray-500 text-center">
            © {new Date().getFullYear()} SnoutIQ. All rights reserved. 
            <a href="#" className="text-blue-600 hover:text-blue-800 ml-2">Privacy Policy</a>
          </p>
        </div>
      </div>
    </div>
  );
};

export default SearchingDoctor;