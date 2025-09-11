import React, { useState, useEffect, useRef, useCallback, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import axiosClient from '../axios';

import ringtone from './../assets/ringtone.mp3';
import { AuthContext } from '../auth/AuthContext';

// Modal component to show the emergency request
const EmergencyCallModal = ({ request, onAccept, onDecline, isAccepting }) => {
  if (!request) return null;

  return (
    // Positioned in the top-right corner
    <div className="fixed top-5 right-5 z-50">
      <div className="p-6 text-center bg-white rounded-2xl shadow-2xl max-w-sm w-full transform transition-all animate-fade-in-right">
        <div className="flex justify-center items-center w-16 h-16 mx-auto bg-red-100 rounded-full border-4 border-red-500 animate-pulse">
          <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        </div>
        <h2 className="mt-4 text-2xl font-bold text-gray-800">Incoming Call!</h2>
        <p className="mt-1 text-sm text-gray-600">
          {/* An emergency request requires your attention. */}
<br/>
Reason: {request.reason}

        </p>
        <div className="flex gap-3 mt-6">
           <button
            onClick={onDecline}
            disabled={isAccepting}
            className="w-full px-4 py-3 text-md font-semibold text-gray-700 bg-gray-200 rounded-lg shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-all"
          >
            Decline
          </button>
          <button
            onClick={onAccept}
            disabled={isAccepting}
            className="w-full px-4 py-3 text-md font-semibold text-white bg-green-600 rounded-lg shadow-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:bg-gray-400 transition-transform transform hover:scale-105"
          >
            {isAccepting ? 'Accepting...' : 'Accept'}
          </button>
        </div>
      </div>
    </div>
  );
};


const DoctorEmergencySearch = () => {
  const navigate = useNavigate();
  const [pendingRequest, setPendingRequest] = useState(null);
  const [isAccepting, setIsAccepting] = useState(false);
  const [isPollingActive, setIsPollingActive] = useState(true); // Controls the search
  const audioRef = useRef(null);
const {token} = useContext(AuthContext)
  // Function to search for new requests
  const searchForRequest = useCallback(async () => {
    // Stop if polling is deactivated or a request is already showing
    if (!isPollingActive || pendingRequest) return;

    try {
      const response = await axiosClient.post('https://snoutiq.com/backend/user/emergency/searchForRequest',{}, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (response.data && response.data.pending) {
        setPendingRequest(response.data.pending);
      }
    } catch (error) {
        if (error.response && error.response.status !== 404) {
             console.error('Error searching for emergency requests:', error);
        }
    }
  }, [pendingRequest, isPollingActive]);

  // Effect for polling
  useEffect(() => {
    if (!isPollingActive) {
        return; // Stop polling if deactivated
    }
    searchForRequest();
    const intervalId = setInterval(searchForRequest, 8000);
    return () => clearInterval(intervalId);
  }, [searchForRequest, isPollingActive]);

  // Effect for handling audio playback
  useEffect(() => {
    if (pendingRequest && audioRef.current) {
      audioRef.current.play().catch(error => {
        console.warn("Ringtone autoplay was blocked by the browser.");
        toast.error("Incoming call detected! Enable audio for ringtone.", { duration: 6000 });
      });
    } else if (!pendingRequest && audioRef.current) {
      audioRef.current.pause();
      audioRef.current.currentTime = 0;
    }
  }, [pendingRequest]);

  // Handler for accepting the call
  const handleAccept = async () => {
    if (!pendingRequest) return;
    setIsAccepting(true);
    toast.loading('Accepting call...');
    try {
      await axiosClient.post('https://snoutiq.com/backend/user/emergency/acceptEmergancy', {
        token: pendingRequest.token,
      }, {
        headers: { Authorization: `Bearer ${token}` },
      });

      toast.dismiss();
      toast.success('Call accepted! Waiting for user payment.');
      
      if (audioRef.current) audioRef.current.pause();
      const acceptedToken = pendingRequest.token;
      setPendingRequest(null);
      navigate(`/dashboard/waitTillPayment/${acceptedToken}`);

    } catch (error) {
      toast.dismiss();
      const message = error.response?.data?.message || 'Failed to accept the call.';
      toast.error(message);
      setIsAccepting(false);
    }
  };

  // Handler for declining the call
  const handleDecline = () => {
    if (audioRef.current) {
        audioRef.current.pause();
        audioRef.current.currentTime = 0;
    }
    setPendingRequest(null);
    setIsPollingActive(false); // This stops the search
    toast.error('Call declined. Search for new calls has been paused.');
  };

  return (
    <>
      <audio ref={audioRef} src={ringtone} loop preload="auto" />
      
      <EmergencyCallModal 
        request={pendingRequest} 
        onAccept={handleAccept}
        onDecline={handleDecline}
        isAccepting={isAccepting}
      />

      {/* UI to re-enable searching if it was paused */}
      {!isPollingActive && (
        <div className="fixed bottom-5 right-5 z-40">
            <div className="p-4 bg-yellow-100 border border-yellow-300 rounded-lg shadow-lg flex items-center gap-4">
                <p className="text-sm text-yellow-800">Searching is paused.</p>
                <button 
                    onClick={() => {
                        setIsPollingActive(true);
                        toast.success('Resumed searching for emergency calls.');
                    }}
                    className="px-3 py-1.5 text-sm font-semibold text-white bg-blue-500 rounded-md hover:bg-blue-600"
                >
                    Resume
                </button>
            </div>
        </div>
      )}
    </>
  );
};

export default DoctorEmergencySearch;
