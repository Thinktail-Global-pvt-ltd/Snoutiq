import { useState } from "react";
import { useNavigate, useParams } from "react-router-dom";

export default function PrescriptionPage() {
    const [file, setFile] = useState(null);
    const [text, setText] = useState("");
    const [isSending, setIsSending] = useState(false);
    const [sendSuccess, setSendSuccess] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState("");
    const navigate = useNavigate();

    const handleDrop = (e) => {
        e.preventDefault();
        const droppedFile = e.dataTransfer.files[0];
        if (droppedFile.type.startsWith("image/")) {
            setFile(droppedFile);
        } else {
            alert("Please upload an image file");
        }
    };

    const handleFileSelect = (e) => {
        const selectedFile = e.target.files[0];
        if (selectedFile && selectedFile.type.startsWith("image/")) {
            setFile(selectedFile);
        } else {
            alert("Please upload an image file");
        }
    };

 const { doctorId, patientId} = useParams(); // get from route
 console.log( doctorId, patientId );
 

const handleSubmit = async (paymentType) => {
    if (!text && !file) {
        alert("Please add prescription details or upload an image");
        return;
    }

    setIsSending(true);
    setPaymentMethod(paymentType);

    try {
        const formData = new FormData();
        formData.append("doctor_id", doctorId);
        formData.append("user_id", patientId);
        formData.append("content_html", text);

        if (file) {
            formData.append("image", file, file.name);
        }

        const response = await fetch("https://snoutiq.com/backend/api/prescriptions", {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        if (response.ok) {
            setSendSuccess(true);
            // Navigate 2 pages back in history
            navigate(-2);
        } else {
            console.error("Error submitting prescription:", data);
            alert(data.message || "Failed to submit prescription");
        }
    } catch (error) {
        console.error("Error submitting prescription:", error);
        alert("Something went wrong");
    } finally {
        setIsSending(false);
        setText("");
        setFile(null);
    }
};

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 to-cyan-50 py-8 px-4">
            <div className="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
                {/* Header */}
                <div className="bg-gradient-to-r from-blue-600 to-cyan-600 p-6 text-white">
                    <h1 className="text-2xl font-bold">Digital Prescription</h1>
                    <p className="text-blue-100">Create and send prescriptions to your patients</p>
                </div>

                <div className="p-6">
                    {/* Patient Information */}
                    <div className="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h2 className="text-lg font-semibold text-gray-700 mb-2">Patient Information</h2>
                    </div>

                    {/* Prescription Form */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Prescription Details
                        </label>
                        <textarea
                            value={text}
                            onChange={(e) => setText(e.target.value)}
                            placeholder="Enter prescription details, medications, dosage instructions, etc."
                            className="w-full h-40 p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    {/* File Upload */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Upload Reports or Images
                        </label>
                        <div
                            onDrop={handleDrop}
                            onDragOver={(e) => e.preventDefault()}
                            onClick={() => document.getElementById('file-input').click()}
                            className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-400 transition-colors"
                        >
                            {file ? (
                                <div className="flex flex-col items-center">
                                    <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p className="font-medium text-gray-700">{file.name}</p>
                                    <p className="text-sm text-gray-500">Click or drag to change file</p>
                                </div>
                            ) : (
                                <div className="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org2000/svg" className="h-12 w-12 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p className="font-medium text-gray-700">Drag & Drop files here</p>
                                    <p className="text-sm text-gray-500">or click to browse</p>
                                </div>
                            )}
                            <input
                                id="file-input"
                                type="file"
                                accept="image/*"
                                onChange={handleFileSelect}
                                className="hidden"
                            />
                        </div>
                    </div>


                    {/* Submit Button */}
                    <div className="flex flex-col items-center mt-8">
                        {isSending ? (
                            <div className="w-full max-w-xs bg-blue-100 text-blue-700 p-4 rounded-lg text-center">
                                <div className="flex justify-center items-center">
                                    <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing sending prescription...
                                </div>
                            </div>
                        ) : sendSuccess ? (
                            <div className="w-full max-w-xs bg-green-100 text-green-700 p-4 rounded-lg text-center">
                                <div className="flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                    Prescription sent successfully via {paymentMethod}!
                                </div>
                            </div>
                        ) : (
                            <button
                                onClick={() => handleSubmit(paymentMethod)}
                                className={`px-8 py-3 rounded-lg font-medium text-white transition-all bg-blue-600 hover:bg-blue-700 shadow-md hover:shadow-lg`}
                            >
                                Submit
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}