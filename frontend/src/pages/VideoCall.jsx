import React, { useState, useEffect } from 'react';
import AgoraUIKit from 'agora-react-uikit';
import { useNavigate, useParams } from 'react-router-dom';

const Videocall = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [canAccessMedia, setCanAccessMedia] = useState(null); 
  const [error, setError] = useState("");

  const callbacks = {
    EndCall: () => {
      navigate('/dashboard');
    },
  };

  const rtcProps = { 
    appId: '88a602d093ed47d6b77a29726aa6c35e', 
    channel: 'booking' + id 
  };

  // Check if camera/mic is accessible
  useEffect(() => {
    const checkMedia = async () => {
      try {
        await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        setCanAccessMedia(true);
      } catch (err) {
        console.error("Cannot access camera/mic:", err);
        setError("Cannot access camera or microphone. Please check permissions or use HTTPS/localhost.");
        setCanAccessMedia(false);
      }
    };
    checkMedia();
  }, []);

  if (canAccessMedia === null) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="flex items-center space-x-3">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <p className="text-lg text-gray-700">Checking camera and microphone...</p>
        </div>
      </div>
    );
  }

  if (canAccessMedia === false) {
    return (
      <div className="flex flex-col items-center justify-center h-screen space-y-4">
        <p className="text-lg text-red-600">{error}</p>
        <button
          onClick={() => window.location.reload()}
          className="px-6 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700"
        >
          Retry
        </button>
      </div>
    );
  }

  return (
    <div className="h-screen w-full bg-gray-100">
      <AgoraUIKit rtcProps={rtcProps} callbacks={callbacks} />
    </div>
  );
};

export default Videocall;
