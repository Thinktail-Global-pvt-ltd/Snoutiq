import React, { useState, useEffect } from 'react';
import { useParams, useSearchParams, useNavigate } from 'react-router-dom';
import { socket } from '../pages/socket';

const DoctorWaitingRoom = () => {
  const { channel } = useParams();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  
  const callId = searchParams.get('callId');
  const patientId = searchParams.get('patientId');
  
  const [paymentStatus, setPaymentStatus] = useState('waiting');
  const [timeWaiting, setTimeWaiting] = useState(0);
  
  useEffect(() => {
    // Listen for payment completion
    socket.on('payment-completed', (data) => {
      if (data.callId === callId) {
        setPaymentStatus('completed');
        setTimeout(() => {
          navigate(`/call-page/${channel}?uid=501&role=host&callId=${callId}`);
        }, 2000);
      }
    });

    // Listen for payment timeout/cancellation
    socket.on('payment-cancelled', (data) => {
      if (data.callId === callId) {
        setPaymentStatus('cancelled');
      }
    });

    // Timer for waiting time
    const timer = setInterval(() => {
      setTimeWaiting(prev => prev + 1);
    }, 1000);

    // Auto-timeout after 5 minutes
    const timeout = setTimeout(() => {
      setPaymentStatus('timeout');
    }, 5 * 60 * 1000);

    return () => {
      socket.off('payment-completed');
      socket.off('payment-cancelled');
      clearInterval(timer);
      clearTimeout(timeout);
    };
  }, [callId, channel, navigate]);

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const handleCancel = () => {
    // Notify patient that doctor cancelled
    socket.emit('doctor-cancelled-call', { callId, patientId });
    navigate('/dashboard');
  };

  if (paymentStatus === 'completed') {
    return (
      <div className="min-h-screen bg-green-50 flex items-center justify-center">
        <div className="bg-white p-8 rounded-lg shadow-lg text-center">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-green-800 mb-2">Payment Successful!</h2>
          <p className="text-green-600 mb-4">Connecting to video call...</p>
          <div className="animate-spin w-8 h-8 border-4 border-green-200 border-t-green-600 rounded-full mx-auto"></div>
        </div>
      </div>
    );
  }

  if (paymentStatus === 'cancelled' || paymentStatus === 'timeout') {
    return (
      <div className="min-h-screen bg-red-50 flex items-center justify-center">
        <div className="bg-white p-8 rounded-lg shadow-lg text-center">
          <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-red-800 mb-2">
            {paymentStatus === 'timeout' ? 'Payment Timeout' : 'Payment Cancelled'}
          </h2>
          <p className="text-red-600 mb-4">
            {paymentStatus === 'timeout' 
              ? 'Patient did not complete payment within 5 minutes.'
              : 'Patient cancelled the payment process.'
            }
          </p>
          <button 
            onClick={() => navigate('/dashboard')}
            className="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700"
          >
            Back to Dashboard
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
      <div className="bg-white rounded-lg shadow-xl p-8 max-w-md w-full text-center">
        <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg className="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        
        <h2 className="text-2xl font-bold text-gray-800 mb-4">Waiting for Payment</h2>
        
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
          <p className="text-yellow-800 mb-2">Patient is being redirected to payment</p>
          <p className="text-sm text-yellow-600">
            The video consultation will start automatically after payment completion
          </p>
        </div>

        <div className="space-y-4 mb-6">
          <div className="flex justify-between text-sm">
            <span className="text-gray-600">Call ID:</span>
            <span className="font-mono text-gray-800">{callId}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-gray-600">Patient ID:</span>
            <span className="font-mono text-gray-800">{patientId}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-gray-600">Channel:</span>
            <span className="font-mono text-gray-800">{channel}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-gray-600">Waiting Time:</span>
            <span className="font-mono text-blue-600">{formatTime(timeWaiting)}</span>
          </div>
        </div>

        <div className="flex flex-col space-y-3">
          <div className="flex items-center justify-center space-x-2">
            <div className="w-2 h-2 bg-blue-600 rounded-full animate-bounce"></div>
            <div className="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style={{animationDelay: '0.1s'}}></div>
            <div className="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style={{animationDelay: '0.2s'}}></div>
          </div>
          <p className="text-sm text-gray-500">Waiting for patient payment...</p>
        </div>

        <div className="mt-6 pt-6 border-t border-gray-200">
          <button 
            onClick={handleCancel}
            className="text-red-600 hover:text-red-800 text-sm font-medium"
          >
            Cancel Call
          </button>
        </div>

        <div className="mt-4 text-xs text-gray-400">
          Call will automatically timeout after 5 minutes
        </div>
      </div>
    </div>
  );
};

export default DoctorWaitingRoom;