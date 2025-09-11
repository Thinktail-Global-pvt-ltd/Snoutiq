import React, { useState, useEffect, useCallback, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import axiosClient from '../axios';
import { AuthContext } from '../auth/AuthContext';

// Spinner component
const Spinner = () => (
  <div className="flex flex-col items-center justify-center space-y-4">
    <div className="w-16 h-16 border-4 border-blue-100 rounded-full animate-spin border-t-blue-500"></div>
    <p className="text-lg font-medium text-gray-600">Please wait...</p>
  </div>
);

const SearchingDoctor = () => {
  const { token } = useContext(AuthContext);
  const navigate = useNavigate();

  const [status, setStatus] = useState('searching'); // searching | accepted | paying | error
  const [requestDetails, setRequestDetails] = useState(null);
  const [doctorDetails, setDoctorDetails] = useState(null);
  const [errorMessage, setErrorMessage] = useState('');
  const [searchTime, setSearchTime] = useState(0);
  const [progressValue, setProgressValue] = useState(0);

  // Timer for search duration & progress
  useEffect(() => {
    if (status !== 'searching') return;
    const timer = setInterval(() => {
      setSearchTime(prev => prev + 1);
      setProgressValue(prev => Math.min(prev + 0.5, 100));
    }, 1000);
    return () => clearInterval(timer);
  }, [status]);

  // Razorpay script loader
  const loadRazorpayScript = () => new Promise((resolve) => {
    if (document.getElementById('razorpay-checkout-js')) return resolve(true);
    const script = document.createElement('script');
    script.id = 'razorpay-checkout-js';
    script.src = 'https://checkout.razorpay.com/v1/checkout.js';
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });

  // Verify payment
  const verifyPayment = async (paymentData) => {
    try {
      await axiosClient.post('https://snoutiq.com/backend/user/emergency/amtPaid', {
        token,
        razorpay_payment_id: paymentData.razorpay_payment_id,
        razorpay_order_id: paymentData.razorpay_order_id,
        razorpay_signature: paymentData.razorpay_signature,
      }, { headers: { Authorization: `Bearer ${token}` } });

      toast.success('Payment Successful! Redirecting to call...');
      setTimeout(() => navigate(`/dashboard/videocall/${token}`), 3000);
    } catch (error) {
      toast.error('Payment verification failed.');
      setStatus('error');
      setErrorMessage('Payment verification failed. Please try again.');
    }
  };

  // Handle payment
  const handlePayment = async () => {
    setStatus('paying');
    toast.loading('Initializing payment...');

    const scriptLoaded = await loadRazorpayScript();
    if (!scriptLoaded) {
      toast.error('Could not load payment gateway.');
      setStatus('error');
      return;
    }

    try {
      const orderRes = await axiosClient.post('https://snoutiq.com/backend/user/razorpay/create-order', {
        amount: requestDetails.amount_tobe_paid,
      }, { headers: { Authorization: `Bearer ${token}` } });

      const { amount, order_id, razorpay_key } = orderRes.data;

      toast.dismiss();

      const options = {
        key: razorpay_key,
        amount,
        currency: 'INR',
        name: 'Vet Emergency Call',
        description: `Payment for consultation with Dr. ${doctorDetails?.name || ''}`,
        order_id,
        handler: verifyPayment,
        theme: { color: '#3B82F6' },
      };

      const rzp = new window.Razorpay(options);
      rzp.open();

    } catch (err) {
      toast.error('Payment initiation failed.');
      setStatus('error');
      setErrorMessage('Payment initiation failed. Try again.');
    }
  };

  // Polling to check doctor acceptance
  const checkRequestStatus = useCallback(async () => {
    try {
      const res = await axiosClient.post('https://snoutiq.com/backend/user/emergency/isAccepted', { token }, {
        headers: { Authorization: `Bearer ${token}` },
      });

      const data = res.data.data;
      if (data.servicer_id && data.is_paid === 0) {
        setRequestDetails(data);

        const doctorRes = await axiosClient.get(`https://snoutiq.com/backend/public/single_groomer/${data.servicer_id}`);
        setDoctorDetails(doctorRes.data.data);
        setStatus('accepted');
      } else if (data.is_paid === 1) {
        navigate(`/dashboard/videocall/${token}`);
      }
    } catch (err) {
      console.error(err);
      setErrorMessage('Failed to fetch request status.');
      setStatus('error');
    }
  }, [token, navigate]);

  useEffect(() => {
    if (status !== 'searching') return;
    checkRequestStatus();
    const interval = setInterval(checkRequestStatus, 5000);
    return () => clearInterval(interval);
  }, [status, checkRequestStatus]);

  // Render UI based on status
  const renderContent = () => {
    switch (status) {
      case 'searching':
        return (
          <div className="text-center">
            <h1 className="text-2xl font-bold mb-3">Connecting You With A Veterinarian</h1>
            <p className="text-gray-600 mb-4">Searching for the best available specialist...</p>
          </div>
        );
      case 'accepted':
        return (
          <div className="text-center">
            <h1 className="text-2xl font-bold mb-3">Veterinarian Found!</h1>
            <p className="text-gray-600 mb-4">Complete payment to start consultation</p>
            <div className="bg-gray-50 p-5 rounded-xl mb-4">
              <p className="font-medium">Dr. {doctorDetails?.name}</p>
              <p className="text-sm text-gray-600">{doctorDetails?.bio}</p>
              <p className="text-xl font-bold mt-2">₹{requestDetails?.amount_tobe_paid}</p>
            </div>
            <button onClick={handlePayment} className="w-full py-3 bg-blue-600 text-white rounded-lg">Pay Now</button>
          </div>
        );
      case 'paying':
        return <Spinner />;
      case 'error':
        return (
          <div className="text-center">
            <h1 className="text-2xl font-bold text-red-600 mb-2">Error</h1>
            <p className="text-gray-600 mb-4">{errorMessage}</p>
            <button onClick={() => window.location.reload()} className="bg-blue-600 text-white py-2 px-4 rounded-lg">Retry</button>
          </div>
        );
      default:
        return null;
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
      <div className="w-full max-w-md bg-white rounded-2xl shadow-xl p-6">
        {renderContent()}
      </div>
    </div>
  );
};

export default SearchingDoctor;
