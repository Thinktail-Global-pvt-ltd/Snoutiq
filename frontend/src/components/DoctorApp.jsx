import { useState } from 'react';
import background from '../assets/images/screen_screenshot.png';

export default function DoctorApp() {
    const [currentScreen, setCurrentScreen] = useState('call');
    const [callAccepted, setCallAccepted] = useState(false);
    const [notes, setNotes] = useState('');
    const [medications, setMedications] = useState([
        { name: '', quantity: '', timing: '', price: '' }
    ]);
    const [isPopupVisible, setIsPopupVisible] = useState(true);

    const handleAcceptCall = () => {
        setCallAccepted(true);
        setIsPopupVisible(false);
        setTimeout(() => setCurrentScreen('video'), 500);
    };

    const handleRejectCall = () => {
        setIsPopupVisible(false);
    };

    const handleEndCall = () => {
        setCurrentScreen('bill');
    };

    const addMedication = () => {
        setMedications([...medications, { name: '', quantity: '', timing: '', price: '' }]);
    };

    const updateMedication = (index, field, value) => {
        const updatedMeds = [...medications];
        updatedMeds[index][field] = value;
        setMedications(updatedMeds);
    };

    return (
        <div className="min-h-screen bg-gray-100 p-4 md:p-8">
            {/* Incoming Call Popup */}
            {isPopupVisible && currentScreen === 'call' && (
                <div className="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4" 
                  style={{ backgroundImage: `url(${background})`, backgroundSize: 'cover', backgroundPosition: 'center' }}>
                    <div className="bg-white rounded-2xl overflow-hidden w-full max-w-md shadow-2xl">
                        {/* Header with call status */}
                        <div className="bg-gradient-to-r from-blue-500 to-indigo-600 p-4 text-white text-center">
                            <div className="flex items-center justify-center space-x-2 mb-2">
                                <div className="w-3 h-3 bg-white rounded-full animate-pulse"></div>
                                <h2 className="text-xl font-bold">INCOMING CALL</h2>
                            </div>
                            <p className="text-blue-100">Pet Consultation Request</p>
                        </div>

                        {/* Main content */}
                        <div className="p-6">
                            {/* Caller info */}
                            <div className="flex items-center justify-between mb-2">
                                <div className="flex items-center">
                                    <div className="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mr-4 border-4 border-indigo-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-lg text-gray-800">Robert Johnson</h3>
                                        <p className="text-gray-500 text-sm">Pet Owner</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className="text-xs text-gray-500">Call duration</p>
                                    <p className="font-mono text-gray-700">00:00</p>
                                </div>
                            </div>

                            {/* Pet summary section */}
                            <div className="bg-gray-50 rounded-xl p-4 mb-2">
                                <div className="flex items-center justify-between mb-3">
                                    <h4 className="font-semibold text-gray-800 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Pet Information
                                    </h4>
                                    <span className="bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full">Regular Patient</span>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-xs text-gray-500">Pet Name & Type</p>
                                        <p className="font-medium text-gray-800">Buddy • Golden Retriever</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500">Age & Weight</p>
                                        <p className="font-medium text-gray-800">5 years • 2 kg</p>
                                    </div>
                                </div>
                            </div>

                            {/* Medical summary */}
                            <div className="bg-amber-50 rounded-xl p-4 mb-6 border-l-4 border-amber-400">
                                <h4 className="font-semibold text-gray-800 flex items-center mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Reported Symptoms
                                </h4>
                                <ul className="list-disc list-inside text-sm text-gray-700 pl-2">
                                    <li>Loss of appetite for 2 days</li>
                                    <li>Occasional vomiting</li>
                                    <li>Lethargy and reduced activity</li>
                                    <li>Whining when touching abdomen</li>
                                </ul>
                            </div>

                            {/* Call buttons */}
                            <div className="flex justify-between space-x-4">
                                <button
                                    onClick={handleRejectCall}
                                    className="flex-1 bg-white border border-red-300 text-red-600 hover:bg-red-50 px-6 py-3 rounded-xl font-medium flex items-center justify-center transition-all duration-200 shadow-sm hover:shadow-md"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Reject
                                </button>
                                <button
                                    onClick={handleAcceptCall}
                                    className="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-medium flex items-center justify-center transition-all duration-200 shadow-md hover:shadow-lg"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                    Accept Call
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            )}

            {/* Video Call Screen */}
            {currentScreen === 'video' && (
                <div className="bg-black text-white rounded-lg overflow-hidden h-screen">
                    <div className="grid grid-cols-1 md:grid-cols-3 h-full">
                        {/* Patient Video (Main View) */}
                        <div className="col-span-2 relative">
                            <div className="absolute inset-0 bg-gray-900 flex items-center justify-center">
                                <div className="text-center">
                                    <div className="w-24 h-24 mx-auto mb-4 bg-blue-900 rounded-full flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <p className="text-xl">Patient Video Feed</p>
                                </div>
                            </div>

                            {/* Pet Image Overlay */}
                            <div className="absolute bottom-4 right-4 w-48 h-32 bg-gray-800 rounded-lg overflow-hidden border-2 border-white">
                                <div className="h-full flex items-center justify-center bg-green-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        {/* Notepad Sidebar */}
                        <div className="bg-gray-800 p-4 flex flex-col">
                            <h3 className="text-lg font-semibold mb-4">Consultation Notes</h3>
                            <textarea
                                className="flex-grow bg-gray-700 text-white p-3 rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter patient symptoms, diagnosis, and notes here..."
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                            />

                            <div className="flex space-x-2">
                                <button
                                    onClick={() => setCurrentScreen('bill')}
                                    className="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition"
                                >
                                    Create Prescription
                                </button>
                                <button
                                    onClick={handleEndCall}
                                    className="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Billing Screen */}
            {currentScreen === 'bill' && (
                <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8 px-4">
                    <div className="bg-white rounded-2xl shadow-xl p-6 max-w-5xl mx-auto">
                        {/* Header */}
                        <div className="flex justify-between items-center mb-8 pb-4 border-b border-gray-200">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-800">Medication & Billing</h1>
                                <p className="text-gray-600 mt-1">Prescription details for John Doe</p>
                            </div>
                            <div className="flex items-center">
                                <div className="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-sm font-medium">
                                    Consultation ID: #CT2023-0876
                                </div>
                            </div>
                        </div>

                        {/* Patient Info */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div className="bg-blue-50 p-4 rounded-lg">
                                <h3 className="text-sm font-semibold text-blue-800 uppercase mb-1">Patient</h3>
                                <p className="text-gray-800 font-medium">Robert Johnson</p>
                                <p className="text-gray-600 text-sm">Age: 5 years • Male</p>
                            </div>
                            <div className="bg-green-50 p-4 rounded-lg">
                                <h3 className="text-sm font-semibold text-green-800 uppercase mb-1">Date</h3>
                                <p className="text-gray-800 font-medium">{new Date().toLocaleDateString()}</p>
                                <p className="text-gray-600 text-sm">Time: {new Date().toLocaleTimeString()}</p>
                            </div>
                            <div className="bg-purple-50 p-4 rounded-lg">
                                <h3 className="text-sm font-semibold text-purple-800 uppercase mb-1">Doctor</h3>
                                <p className="text-gray-800 font-medium">Dr. </p>
                                <p className="text-gray-600 text-sm">Cardiologist</p>
                            </div>
                        </div>

                        {/* Medication Form */}
                        <div className="mb-8">
                            <div className="flex justify-between items-center mb-6">
                                <h2 className="text-xl font-semibold text-gray-800">Medication Prescription</h2>
                                <button
                                    onClick={addMedication}
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg flex items-center transition duration-200 shadow-md hover:shadow-lg"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Medication
                                </button>
                            </div>

                            {/* Table Headers */}
                            <div className="hidden md:grid grid-cols-12 gap-7 mb-3 px-4 py-2 bg-gray-100 rounded-lg">
                                <div className="col-span-4 text-sm font-medium text-gray-700">Medication Name</div>
                                <div className="col-span-1 text-sm font-medium text-gray-700 text-center">Quantity</div>
                                <div className="col-span-3 text-sm font-medium text-gray-700">Timing</div>
                                <div className="col-span-3 text-sm font-medium text-gray-700">Action</div>
                            </div>

                            {/* Medication Items */}
                            <div className="space-y-4">
                                {medications.map((med, index) => (
                                    <div key={index} className="grid grid-cols-1 md:grid-cols-12 gap-7 items-center p-4 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200">
                                        <div className="md:col-span-4">
                                            <label className="text-xs text-gray-500 block md:hidden">Medication Name</label>
                                            <input
                                                type="text"
                                                placeholder="e.g., Amoxicillin 500mg"
                                                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                                value={med.name}
                                                onChange={(e) => updateMedication(index, 'name', e.target.value)}
                                            />
                                        </div>
                                        <div className="md:col-span-1">
                                            <label className="text-xs text-gray-500 block md:hidden">Quantity</label>
                                            <input
                                                type="number"
                                                min="1"
                                                placeholder="Qty"
                                                className="w-full p-3 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                                value={med.quantity}
                                                onChange={(e) => updateMedication(index, 'quantity', e.target.value)}
                                            />
                                        </div>
                                        <div className="md:col-span-3">
                                            <label className="text-xs text-gray-500 block md:hidden">Timing Instructions</label>
                                            <input
                                                type="text"
                                                placeholder="e.g., After meals, twice daily"
                                                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                                value={med.timing}
                                                onChange={(e) => updateMedication(index, 'timing', e.target.value)}
                                            />
                                        </div>
                                        <div className="md:col-span-2 flex justify-center">
                                            {medications.length > 1 && (
                                                <button
                                                    onClick={() => setMedications(medications.filter((_, i) => i !== index))}
                                                    className="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50 transition"
                                                    title="Remove medication"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Totals and Actions */}
                        <div className="border-t border-gray-200 pt-6 mt-8">
                            <div className="flex flex-col md:flex-row justify-end items-start md:items-center">

                                <div className="bg-gray-50 p-5 rounded-xl w-full md:w-72">
                                    <div className="flex justify-between items-center mb-3">
                                        <span className="text-gray-600">Consultation Fee:</span>
                                        <span className="text-gray-800 font-medium">₹ ***.00</span>
                                    </div>

                                    <div className="flex flex-col space-y-3 mt-5">
                                        <button
                                            onClick={() => {
                                                // Show success message
                                                alert('Prescription submitted successfully!');

                                                // Reload the page after a brief delay
                                                setTimeout(() => {
                                                    window.location.reload();
                                                }, 1000);
                                            }}
                                            className="bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-6 rounded-lg font-medium transition duration-200 shadow-md hover:shadow-lg flex items-center justify-center"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Submit Prescription
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}