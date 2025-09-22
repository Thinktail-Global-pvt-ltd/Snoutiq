// import React, { useState, useEffect, useContext } from 'react';
// import AgoraUIKit from 'agora-react-uikit';
// import { useNavigate, useParams } from 'react-router-dom';
// import { AuthContext } from '../auth/AuthContext';

// const Videocall = () => {
//   const { id } = useParams();
//   const navigate = useNavigate();
//   const [canAccessMedia, setCanAccessMedia] = useState(null);
//   const [error, setError] = useState("");
//   const { token } = useContext(AuthContext);

//   const callbacks = {
//     EndCall: () => {
//       navigate('/dashboard');
//     },
//   };

//   // Check if camera/mic is accessible
//   useEffect(() => {
//     const checkMedia = async () => {
//       try {
//         await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
//         setCanAccessMedia(true);
//       } catch (err) {
//         console.error("Cannot access camera/mic:", err);
//         setError("Cannot access camera or microphone. Please check permissions or use HTTPS/localhost.");
//         setCanAccessMedia(false);
//       }
//     };
//     checkMedia();
//   }, []);

//   if (canAccessMedia === null) {
//     return (
//       <div className="flex items-center justify-center h-screen">
//         <div className="flex items-center space-x-3">
//           <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
//           <p className="text-lg text-gray-700">Checking camera and microphone...</p>
//         </div>
//       </div>
//     );
//   }

//   if (canAccessMedia === false) {
//     return (
//       <div className="flex flex-col items-center justify-center h-screen space-y-4">
//         <p className="text-lg text-red-600">{error}</p>
//         <button
//           onClick={() => window.location.reload()}
//           className="px-6 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700"
//         >
//           Retry
//         </button>
//       </div>
//     );
//   }

//   const [agoraToken, setAgoraToken] = useState(null);
// const [channelName, setChannelName] = useState(null);

// useEffect(() => {
//   const fetchAgoraToken = async () => {
//     try {
//       const res = await axios.post(`/api/agora-token`, {
//         channel: "booking_" + id
//       });
//       setAgoraToken(res.data.token);
//       setChannelName(res.data.channel);
//     } catch (error) {
//       console.error("Error fetching Agora token:", error);
//     }
//   };
//   fetchAgoraToken();
// }, [id]);

// const rtcProps = {
//   appId: "88a602d093ed47d6b77a29726aa6c35e",
//   channel: channelName,
//   token: agoraToken
// };


//   return (
//     <div className="h-screen w-full bg-gray-100">
//       <AgoraUIKit rtcProps={rtcProps} callbacks={callbacks} />
//     </div>
//   );
// };

// export default Videocall;

// import React, { useState, useEffect, useContext } from 'react';
// import AgoraUIKit from 'agora-react-uikit';
// import { useNavigate, useParams } from 'react-router-dom';
// import { AuthContext } from '../auth/AuthContext';
// import axios from 'axios';

// const Videocall = () => {
//   const { id } = useParams();
//   const navigate = useNavigate();
//   const [canAccessMedia, setCanAccessMedia] = useState(null);
//   const [error, setError] = useState("");
//   const [showPrescriptionModal, setShowPrescriptionModal] = useState(false);
//   const [prescription, setPrescription] = useState("");
//   const [isSending, setIsSending] = useState(false);
//   const [callEnded, setCallEnded] = useState(false);
//   const [callDuration, setCallDuration] = useState(0);
//   const { token, user } = useContext(AuthContext);

//   // Timer for call duration
//   useEffect(() => {
//     let timer;
//     if (canAccessMedia === true && !callEnded) {
//       timer = setInterval(() => {
//         setCallDuration(prev => prev + 1);
//       }, 1000);
//     }
//     return () => clearInterval(timer);
//   }, [canAccessMedia, callEnded]);

//   const formatTime = (seconds) => {
//     const mins = Math.floor(seconds / 60);
//     const secs = seconds % 60;
//     return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
//   };

//   const callbacks = {
//     EndCall: () => {
//       // If user is a doctor, show prescription modal
//       if (user?.role === 'doctor') {
//         setShowPrescriptionModal(true);
//       } else {
//         // If user is pet owner, navigate to dashboard
//         navigate('/dashboard');
//       }
//       setCallEnded(true);
//     },
//   };

//   const rtcProps = {
//     appId: '88a602d093ed47d6b77a29726aa6c35e',
//     channel: 'booking_' + id + '_' + token
//   };

//   const handleSendPrescription = async () => {
//     if (!prescription.trim()) {
//       alert('Please enter prescription details');
//       return;
//     }

//     setIsSending(true);
//     try {
//       // Send prescription to backend
//       await axios.post('/api/prescriptions', {
//         consultation_id: id,
//         doctor_id: user.id,
//         prescription_text: prescription,
//         call_duration: callDuration
//       }, {
//         headers: { Authorization: `Bearer ${token}` }
//       });

//       alert('Prescription sent successfully!');
//       setShowPrescriptionModal(false);
//       navigate('/doctor/dashboard');
//     } catch (error) {
//       console.error('Error sending prescription:', error);
//       alert('Failed to send prescription. Please try again.');
//     } finally {
//       setIsSending(false);
//     }
//   };

//   // Check if camera/mic is accessible
//   useEffect(() => {
//     const checkMedia = async () => {
//       try {
//         await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
//         setCanAccessMedia(true);
//       } catch (err) {
//         console.error("Cannot access camera/mic:", err);
//         setError("Cannot access camera or microphone. Please check permissions or use HTTPS/localhost.");
//         setCanAccessMedia(false);
//       }
//     };
//     checkMedia();
//   }, []);

//   if (canAccessMedia === null) {
//     return (
//       <div className="flex flex-col items-center justify-center min-h-screen bg-gray-50">
//         <div className="bg-white rounded-xl shadow-lg p-8 max-w-md w-full mx-4">
//           <div className="text-center">
//             <div className="w-20 h-20 mx-auto mb-6 bg-blue-50 rounded-full flex items-center justify-center relative">
//               <div className="absolute inset-0 rounded-full border-4 border-blue-100 animate-ping"></div>
//               <svg xmlns="http://www.w3.org/2000/svg" className="w-10 h-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
//                 <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
//               </svg>
//             </div>
            
//             <h1 className="text-2xl font-bold text-gray-800 mb-3">Preparing Your Video Consultation</h1>
//             <p className="text-gray-600 mb-6">Checking your camera and microphone permissions</p>
            
//             <div className="flex items-center justify-center space-x-3">
//               <div className="w-3 h-3 bg-blue-500 rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></div>
//               <div className="w-3 h-3 bg-blue-500 rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></div>
//               <div className="w-3 h-3 bg-blue-500 rounded-full animate-bounce" style={{ animationDelay: '300ms' }}></div>
//             </div>
//           </div>
//         </div>
//       </div>
//     );
//   }

//   if (canAccessMedia === false) {
//     return (
//       <div className="flex flex-col items-center justify-center min-h-screen bg-gray-50 p-4">
//         <div className="bg-white rounded-xl shadow-lg p-8 max-w-md w-full">
//           <div className="text-center">
//             <div className="w-20 h-20 mx-auto mb-6 bg-red-50 rounded-full flex items-center justify-center">
//               <svg xmlns="http://www.w3.org/2000/svg" className="w-10 h-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
//                 <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
//               </svg>
//             </div>
            
//             <h1 className="text-2xl font-bold text-gray-800 mb-3">Permission Required</h1>
//             <p className="text-red-600 mb-6">{error}</p>
            
//             <div className="bg-red-50 p-4 rounded-xl border border-red-100 mb-6">
//               <p className="text-sm text-red-700">
//                 Your browser is blocking access to your camera or microphone. This is required for your video consultation.
//               </p>
//             </div>
            
//             <div className="space-y-3">
//               <button
//                 onClick={() => window.location.reload()}
//                 className="w-full py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition-colors"
//               >
//                 Retry
//               </button>
//               <button
//                 onClick={() => navigate('/dashboard')}
//                 className="w-full py-3 bg-gray-500 text-white rounded-lg shadow hover:bg-gray-600 transition-colors"
//               >
//                 Return to Dashboard
//               </button>
//             </div>
//           </div>
//         </div>
//       </div>
//     );
//   }

//   return (
//     <div className="h-screen w-full bg-gray-900 relative">
//       {/* Custom header overlay */}
//       <div className="absolute top-0 left-0 right-0 z-10 bg-gradient-to-b from-black/70 to-transparent p-4">
//         <div className="flex justify-between items-center">
//           <div className="flex items-center space-x-3">
//             <div className="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
//               <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
//                 <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
//               </svg>
//             </div>
//             <div>
//               <h2 className="text-white font-semibold">Veterinary Consultation</h2>
//               <p className="text-blue-200 text-sm">Call duration: {formatTime(callDuration)}</p>
//             </div>
//           </div>
//         </div>
//       </div>
      
//       {/* Main video container */}
//       <div className="h-full w-full">
//         <AgoraUIKit rtcProps={rtcProps} callbacks={callbacks} />
//       </div>

//       {/* Prescription Modal for Doctors */}
//       {showPrescriptionModal && (
//         <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
//           <div className="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
//             <h2 className="text-2xl font-bold text-gray-800 mb-4">Consultation Summary</h2>
//             <p className="text-gray-600 mb-2">Call Duration: {formatTime(callDuration)}</p>
            
//             <div className="mb-4">
//               <label className="block text-gray-700 text-sm font-medium mb-2">
//                 Prescription & Recommendations
//               </label>
//               <textarea
//                 value={prescription}
//                 onChange={(e) => setPrescription(e.target.value)}
//                 placeholder="Enter prescription details, recommendations, and follow-up instructions..."
//                 className="w-full h-40 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
//               />
//             </div>
            
//             <div className="flex space-x-3">
//               <button
//                 onClick={() => {
//                   setShowPrescriptionModal(false);
//                   navigate('/doctor/dashboard');
//                 }}
//                 className="flex-1 py-3 bg-gray-500 text-white rounded-lg font-medium hover:bg-gray-600 transition-colors"
//                 disabled={isSending}
//               >
//                 Skip
//               </button>
//               <button
//                 onClick={handleSendPrescription}
//                 className="flex-1 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center justify-center"
//                 disabled={isSending}
//               >
//                 {isSending ? (
//                   <>
//                     <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
//                     Sending...
//                   </>
//                 ) : (
//                   'Send Prescription'
//                 )}
//               </button>
//             </div>
//           </div>
//         </div>
//       )}
//     </div>
//   );
// };

// export default Videocall;


{/*import React, { useState, useEffect, useContext } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { AuthContext } from '../auth/AuthContext';
import axios from 'axios';

const VideocallFlow = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { token, user } = useContext(AuthContext);
  const [currentStep, setCurrentStep] = useState('searching'); // searching, accepted, payment, connecting, in-call
  const [doctorDetails, setDoctorDetails] = useState(null);
  const [searchTime, setSearchTime] = useState(0);
  const [paymentStatus, setPaymentStatus] = useState('pending'); // pending, processing, completed, failed

  // Timer for search duration
  useEffect(() => {
    let timer;
    if (currentStep === 'searching') {
      timer = setInterval(() => {
        setSearchTime(prev => prev + 1);
      }, 1000);
    }
    return () => clearInterval(timer);
  }, [currentStep]);

  // Simulate doctor search and acceptance
  useEffect(() => {
    if (currentStep === 'searching') {
      // In a real app, this would be an API call to check if a doctor has accepted
      const searchTimer = setTimeout(() => {
        // Simulate doctor acceptance after 5-10 seconds
        setDoctorDetails({
          id: 'doc_123',
          name: 'Dr. Sarah Johnson',
          specialty: 'Veterinary Surgeon',
          experience: '8 years',
          rating: 4.9,
          image: 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1000&q=80',
          fee: 49.99
        });
        setCurrentStep('accepted');
      }, 7000);

      return () => clearTimeout(searchTimer);
    }
  }, [currentStep]);

  const handlePayment = () => {
    setPaymentStatus('processing');
    
    // Simulate payment processing
    setTimeout(() => {
      setPaymentStatus('completed');
      
      // Move to connecting state
      setTimeout(() => {
        setCurrentStep('connecting');
        
        // Simulate connection establishment
        setTimeout(() => {
          setCurrentStep('in-call');
        }, 3000);
      }, 2000);
    }, 3000);
  };

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  // Render different UI based on current step
  const renderStep = () => {
    switch (currentStep) {
      case 'searching':
        return (
          <div className="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-6">
            <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full">
              <div className="text-center">
                <div className="relative mb-6">
                  <div className="w-24 h-24 mx-auto bg-blue-100 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" className="w-12 h-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </div>
                  <div className="absolute -top-2 -right-2">
                    <div className="relative">
                      <div className="w-16 h-16 bg-blue-100 rounded-full animate-ping absolute inset-0"></div>
                      <div className="w-16 h-16 bg-blue-200 rounded-full opacity-50"></div>
                    </div>
                  </div>
                </div>
                
                <h1 className="text-2xl font-bold text-gray-800 mb-3">Finding the Best Veterinarian for You</h1>
                <p className="text-gray-600 mb-6">We're connecting you with an available specialist in your area</p>
                
                <div className="bg-blue-50 rounded-xl p-4 mb-6">
                  <div className="flex justify-between items-center mb-2">
                    <span className="text-sm font-medium text-blue-700">Searching time</span>
                    <span className="text-sm font-bold text-blue-800">{formatTime(searchTime)}</span>
                  </div>
                  <div className="w-full bg-blue-200 rounded-full h-2">
                    <div 
                      className="bg-blue-600 h-2 rounded-full transition-all duration-1000" 
                      style={{ width: `${Math.min(searchTime * 5, 100)}%` }}
                    ></div>
                  </div>
                </div>
                
                <div className="flex items-center justify-center space-x-2">
                  <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></div>
                  <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></div>
                  <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style={{ animationDelay: '300ms' }}></div>
                </div>
                
                <div className="mt-8 bg-gray-50 rounded-xl p-4 border border-gray-200">
                  <h3 className="text-sm font-semibold text-gray-700 mb-2">Why choose our video consultation?</h3>
                  <ul className="text-xs text-gray-600 space-y-1">
                    <li className="flex items-start">
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-green-500 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                      <span>Licensed and verified veterinarians</span>
                    </li>
                    <li className="flex items-start">
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-green-500 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                      <span>Secure and encrypted connection</span>
                    </li>
                    <li className="flex items-start">
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-green-500 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                      <span>Prescription available after consultation</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        );
      
      case 'accepted':
        return (
          <div className="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-6">
            <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full">
              <div className="text-center">
                <div className="w-24 h-24 mx-auto mb-6 rounded-full overflow-hidden border-4 border-blue-100 shadow-md">
                  <img 
                    src={doctorDetails.image} 
                    alt={doctorDetails.name}
                    className="w-full h-full object-cover"
                  />
                </div>
                
                <div className="mb-2">
                  <span className="inline-flex items-center bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Verified Veterinarian
                  </span>
                </div>
                
                <h1 className="text-2xl font-bold text-gray-800 mb-1">{doctorDetails.name}</h1>
                <p className="text-gray-600 mb-3">{doctorDetails.specialty} • {doctorDetails.experience} experience</p>
                
                <div className="flex items-center justify-center mb-6">
                  <div className="flex items-center">
                    {[1, 2, 3, 4, 5].map((star) => (
                      <svg 
                        key={star} 
                        xmlns="http://www.w3.org/2000/svg" 
                        className={`h-5 w-5 ${star <= Math.floor(doctorDetails.rating) ? 'text-yellow-400' : 'text-gray-300'}`} 
                        viewBox="0 0 20 20" 
                        fill="currentColor"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    ))}
                    <span className="ml-2 text-gray-600 text-sm">({doctorDetails.rating})</span>
                  </div>
                </div>
                
                <div className="bg-blue-50 rounded-xl p-5 mb-6">
                  <div className="flex justify-between items-center mb-2">
                    <span className="text-gray-700 font-medium">Consultation Fee</span>
                    <span className="text-2xl font-bold text-blue-800">${doctorDetails.fee}</span>
                  </div>
                  <p className="text-xs text-gray-600">Payment is secure and encrypted. You'll only be charged after the consultation.</p>
                </div>
                
                <button
                  onClick={handlePayment}
                  className="w-full py-4 bg-blue-600 text-white rounded-xl font-semibold shadow-md hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                  Proceed to Payment
                </button>
                
                <button
                  onClick={() => navigate('/dashboard')}
                  className="mt-4 text-sm text-gray-600 hover:text-gray-800 transition-colors"
                >
                  Cancel and return to dashboard
                </button>
              </div>
            </div>
          </div>
        );
      
      case 'payment':
      case 'connecting':
        return (
          <div className="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-6">
            <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full">
              <div className="text-center">
                <div className="w-24 h-24 mx-auto mb-6 bg-blue-100 rounded-full flex items-center justify-center">
                  {paymentStatus === 'processing' ? (
                    <div className="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                  ) : (
                    <svg xmlns="http://www.w3.org/2000/svg" className="w-12 h-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  )}
                </div>
                
                <h1 className="text-2xl font-bold text-gray-800 mb-3">
                  {paymentStatus === 'processing' ? 'Processing Payment' : 'Payment Successful!'}
                </h1>
                
                <p className="text-gray-600 mb-6">
                  {paymentStatus === 'processing' 
                    ? 'Please wait while we process your payment securely.' 
                    : 'Your payment has been processed successfully. Connecting you to the call...'
                  }
                </p>
                
                {paymentStatus === 'processing' && (
                  <div className="bg-blue-50 rounded-xl p-4 mb-6">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-sm font-medium text-blue-700">Payment Status</span>
                      <span className="text-sm font-bold text-blue-800">Processing...</span>
                    </div>
                    <div className="w-full bg-blue-200 rounded-full h-2">
                      <div className="bg-blue-600 h-2 rounded-full animate-pulse"></div>
                    </div>
                  </div>
                )}
                
                <div className="mt-8 bg-gray-50 rounded-xl p-4 border border-gray-200">
                  <h3 className="text-sm font-semibold text-gray-700 mb-2">Your consultation includes:</h3>
                  <ul className="text-xs text-gray-600 space-y-1">
                    <li className="flex items-start">
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-green-500 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                      <span>20-minute video consultation with {doctorDetails.name}</span>
                    </li>
                    <li className="flex items-start">
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-green-500 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                      <span>Digital prescription if needed</span>
                    </li>
                    <li className="flex items-start">
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-green-500 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                      <span>Follow-up care instructions</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        );
      
      case 'in-call':
        // This would be your actual video call component
        return (
          <div className="flex flex-col items-center justify-center min-h-screen bg-gray-900 p-6">
            <div className="text-center text-white mb-8">
              <h1 className="text-2xl font-bold mb-2">Connected to {doctorDetails.name}</h1>
              <p className="text-blue-200">Your consultation is in progress</p>
            </div>
            
            <div className="bg-black rounded-xl overflow-hidden w-full max-w-2xl h-96 mb-8 relative">
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="w-32 h-32 bg-blue-600 rounded-full flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-16 w-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              
              <div className="absolute bottom-4 left-0 right-0 flex justify-center">
                <div className="bg-gray-800 bg-opacity-70 rounded-full px-6 py-3 flex items-center space-x-6">
                  <button className="p-3 bg-red-600 rounded-full hover:bg-red-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                  
                  <button className="p-3 bg-gray-600 rounded-full hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                  </button>
                  
                  <button className="p-3 bg-blue-600 rounded-full hover:bg-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15.536a5 5 0 001.414 1.414m0-9.9l-2.828 2.829a9 9 0 000 12.728" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            
            <div className="text-center text-gray-400">
              <p>For the best experience, make sure you're in a well-lit area with a stable internet connection.</p>
            </div>
          </div>
        );
      
      default:
        return null;
    }
  };

  return renderStep();
};

export default VideocallFlow; */}
import React, { useState, useEffect, useContext } from 'react';
import AgoraUIKit from 'agora-react-uikit';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { AuthContext } from '../auth/AuthContext';
import axios from 'axios';
import { socket } from './socket';

const Videocall = () => {
  const { channel } = useParams(); // Get channel from URL params
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { user } = useContext(AuthContext);
  
  const [canAccessMedia, setCanAccessMedia] = useState(null);
  const [error, setError] = useState("");
  const [agoraToken, setAgoraToken] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  // Get params from URL
  const uid = searchParams.get('uid');
  const role = searchParams.get('role'); // 'host' for doctor, 'audience' for patient
  const callId = searchParams.get('callId');

  // Generate UID if not provided
  const userId = uid || Math.floor(Math.random() * 10000);
  
  const callbacks = {
    EndCall: () => {
      // Notify other party that call ended
      socket.emit('call-ended', { 
        callId, 
        channel, 
        userId: user?.id || userId,
        role 
      });
      navigate('/dashboard');
    },
  };

  // Check if camera/mic is accessible
  useEffect(() => {
    const checkMedia = async () => {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
          video: true, 
          audio: true 
        });
        
        // Stop the test stream
        stream.getTracks().forEach(track => track.stop());
        
        setCanAccessMedia(true);
      } catch (err) {
        console.error("Cannot access camera/mic:", err);
        setError("Cannot access camera or microphone. Please check permissions and ensure you're using HTTPS or localhost.");
        setCanAccessMedia(false);
      }
    };
    checkMedia();
  }, []);

  // Fetch Agora token
  useEffect(() => {
    const fetchAgoraToken = async () => {
      if (!channel || !canAccessMedia) return;
      
      try {
        setIsLoading(true);
        
        // Call your backend to generate Agora token
        const response = await axios.post('https://snoutiq.com/backend/api/agora-token', {
          channel: channel,
          uid: userId,
          role: role === 'host' ? 'publisher' : 'subscriber', // Agora role
          expireTime: 3600 // 1 hour
        });
        
        if (response.data.token) {
          setAgoraToken(response.data.token);
        } else {
          throw new Error('No token received from server');
        }
      } catch (error) {
        console.error("Error fetching Agora token:", error);
        setError("Failed to get video call token. Please refresh and try again.");
        setCanAccessMedia(false);
      } finally {
        setIsLoading(false);
      }
    };

    fetchAgoraToken();
  }, [channel, userId, role, canAccessMedia]);

  // Socket listeners for call events
  useEffect(() => {
    const handleCallEnded = (data) => {
      if (data.channel === channel && data.userId !== (user?.id || userId)) {
        // Other party ended the call
        alert('The other party has ended the call');
        navigate('/dashboard');
      }
    };

    const handleCallError = (error) => {
      console.error('Call error:', error);
      setError('Call connection error: ' + error.message);
    };

    socket.on('call-ended', handleCallEnded);
    socket.on('call-error', handleCallError);

    return () => {
      socket.off('call-ended', handleCallEnded);
      socket.off('call-error', handleCallError);
    };
  }, [channel, user, userId, navigate]);

  // Loading state
  if (canAccessMedia === null || isLoading) {
    return (
      <div className="flex items-center justify-center h-screen bg-gray-900">
        <div className="flex flex-col items-center space-y-4 text-white">
          <div className="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <p className="text-lg">
            {canAccessMedia === null 
              ? "Checking camera and microphone..." 
              : "Connecting to video call..."}
          </p>
          <div className="text-sm text-gray-300">
            <p>Channel: {channel}</p>
            <p>Role: {role}</p>
            <p>User ID: {userId}</p>
          </div>
        </div>
      </div>
    );
  }

  // Error state
  if (canAccessMedia === false || error) {
    return (
      <div className="flex flex-col items-center justify-center h-screen space-y-6 bg-gray-900 text-white">
        <div className="bg-red-600 p-4 rounded-lg max-w-md text-center">
          <h2 className="text-xl font-bold mb-2">Connection Error</h2>
          <p className="text-red-100">{error}</p>
        </div>
        
        <div className="space-y-2">
          <button
            onClick={() => window.location.reload()}
            className="px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition-colors"
          >
            Retry Connection
          </button>
          <button
            onClick={() => navigate('/dashboard')}
            className="block px-6 py-2 text-gray-300 hover:text-white transition-colors"
          >
            Back to Dashboard
          </button>
        </div>

        <div className="text-xs text-gray-400 text-center">
          <p>Make sure you:</p>
          <ul className="list-disc list-inside mt-2 space-y-1">
            <li>Allow camera and microphone permissions</li>
            <li>Use HTTPS or localhost</li>
            <li>Have a stable internet connection</li>
          </ul>
        </div>
      </div>
    );
  }

  // Main video call interface
  const rtcProps = {
    appId: "88a602d093ed47d6b77a29726aa6c35e", // Your Agora App ID
    channel: channel,
    token: agoraToken,
    uid: userId,
    layout: role === 'host' ? 0 : 1, // Different layouts for doctor vs patient
  };

  const styleProps = {
    localBtnContainer: {
      backgroundColor: '#1f2937',
      bottom: '20px',
    },
    localBtnStyles: {
      muteLocalAudio: {
        backgroundColor: '#ef4444',
        borderRadius: '8px',
      },
      muteLocalVideo: {
        backgroundColor: '#ef4444', 
        borderRadius: '8px',
      },
      endCall: {
        backgroundColor: '#dc2626',
        borderRadius: '8px',
      },
    },
  };

  return (
    <div className="h-screen w-full bg-gray-900 relative">
      {/* Call Info Header */}
      <div className="absolute top-4 left-4 z-10 bg-black bg-opacity-50 text-white px-4 py-2 rounded-lg">
        <div className="text-sm">
          <p>Call ID: {callId}</p>
          <p>Role: {role === 'host' ? 'Doctor' : 'Patient'}</p>
          <p>Channel: {channel}</p>
        </div>
      </div>

      {/* Agora Video Component */}
      <AgoraUIKit 
        rtcProps={rtcProps} 
        callbacks={callbacks} 
        styleProps={styleProps}
      />

      {/* Connection Status */}
      <div className="absolute top-4 right-4 z-10">
        <div className="flex items-center bg-green-600 text-white px-3 py-1 rounded-full text-sm">
          <div className="w-2 h-2 bg-white rounded-full mr-2 animate-pulse"></div>
          Connected
        </div>
      </div>
    </div>
  );
};

export default Videocall;