import React, { memo } from 'react';

const EmergencyStatusBox = ({ emergencyStatus }) => {
    if (!emergencyStatus) return null;

    if (emergencyStatus.includes("URGENT")) {
        return (
            <div className="mt-3 p-3 bg-red-50 border border-red-100 rounded-lg">
                <div className="flex items-center mb-2">
                    <div className="flex-shrink-0 w-5 h-5 bg-red-100 rounded-full flex items-center justify-center mr-2">
                        <svg className="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <span className="text-xs font-semibold text-red-800 uppercase tracking-wide">Emergency Care Required</span>
                </div>
                <p className="text-red-700 text-xs mb-3">Immediate veterinary attention needed</p>
                <button
                    onClick={() => window.location.href = "/book-clinic-visit"}
                    className="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-3 rounded-md transition-colors flex items-center justify-center space-x-1.5"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span>Book Emergency Visit</span>
                </button>
            </div>
        );
    } else if (emergencyStatus.includes("Routine")) {
        return (
            <div className="mt-3 p-3 bg-blue-50 border border-blue-100 rounded-lg">
                <div className="flex items-center mb-2">
                    <div className="flex-shrink-0 w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                        <svg className="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span className="text-xs font-semibold text-blue-800 uppercase tracking-wide">Routine Consultation</span>
                </div>
                <p className="text-blue-700 text-xs mb-3">Schedule a convenient video consultation</p>
                <button
                    onClick={() => window.location.href = "/video-call"}
                    className="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-3 rounded-md transition-colors flex items-center justify-center space-x-1.5"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    <span>Video Consultation</span>
                </button>
            </div>
        );
    } else {
        return (
            <div className="mt-3 p-3 bg-gray-50 border border-gray-100 rounded-lg">
                <div className="flex items-center mb-2">
                    <div className="flex-shrink-0 w-5 h-5 bg-gray-100 rounded-full flex items-center justify-center mr-2">
                        <svg className="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <span className="text-xs font-semibold text-gray-800 uppercase tracking-wide">General Inquiry</span>
                </div>
                <p className="text-gray-700 text-xs mb-3">Choose your preferred consultation method</p>
                <div className="space-y-2">
                    <button
                        onClick={() => window.location.href = "/book-video-consultation"}
                        className="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-3 rounded-md transition-colors flex items-center justify-center space-x-1.5"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span>Video Call</span>
                    </button>
                    <button
                        onClick={() => window.location.href = "/book-clinic-visit"}
                        className="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 px-3 rounded-md transition-colors flex items-center justify-center space-x-1.5"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <span>Clinic Visit</span>
                    </button>
                </div>
            </div>
        );
    }
};

const MessageBubble = memo(({ msg, index, onFeedback }) => {
    if (msg.type === "loading") {
        return (
            <div key={`loader-${index}`} className="flex justify-start">
                <div className="bg-white border border-gray-200 rounded-2xl px-3 py-2 shadow-sm flex items-center space-x-2">
                    <span className="text-sm text-gray-500">Thinking</span>
                    <div className="flex space-x-1">
                        <span className="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></span>
                        <span className="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce delay-150"></span>
                        <span className="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce delay-300"></span>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div
            key={msg.id || `msg-${index}`}
            className={`flex ${msg.sender === "user" ? "justify-end" : "justify-start"} mb-3`}
        >
            <div
                className={`w-fit max-w-full lg:max-w-[75%] break-words rounded-xl px-3 py-2
                    ${msg.sender === "user"
                        ? "bg-blue-600 text-white"
                        : msg.isError
                            ? "bg-red-100 border border-red-200"
                            : "bg-white shadow-sm border border-gray-200"
                    }`}
            >
                {/* Headers for AI and Error messages */}
                {msg.sender === "ai" && !msg.isError && (
                    <div className="flex items-center mb-1">
                        <div className="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center mr-2">
                            <svg
                                className="w-3 h-3 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </div>
                        <span className="text-xs font-medium text-gray-600">
                            Pet Health Assistant
                        </span>
                    </div>
                )}

                {msg.isError && (
                    <div className="flex items-center mb-1">
                        <div className="w-5 h-5 bg-red-100 rounded-full flex items-center justify-center mr-2">
                            <svg
                                className="w-3 h-3 text-red-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <span className="text-xs font-medium text-red-600">
                            Connection Error
                        </span>
                    </div>
                )}

                {/* Message Text */}
                <div className="whitespace-pre-line leading-relaxed text-sm">
                    {msg.displayedText !== undefined ? msg.displayedText : msg.text}
                </div>

                {/* Emergency Status Box */}
                {msg.sender === "ai" && msg.emergency_status && (
                    <EmergencyStatusBox emergencyStatus={msg.emergency_status.trim()} />
                )}

                <div
                    className={`text-xs mt-1 ${msg.sender === "user"
                        ? "text-blue-200"
                        : msg.isError
                            ? "text-red-500"
                            : "text-gray-500"
                        }`}
                >
                    {msg.timestamp
                        ? new Date(msg.timestamp).toLocaleTimeString([], {
                            hour: "2-digit",
                            minute: "2-digit",
                        })
                        : ""}
                </div>

                {msg.sender === "ai" && !msg.isError && (
                    <div className="mt-1 flex gap-1.5">
                        <button
                            className="text-green-600 hover:text-green-800 text-md transition-colors"
                            onClick={() => onFeedback(1, msg.timestamp)}
                            aria-label="Helpful response"
                        >
                            👍
                        </button>
                        <button
                            className="text-red-600 hover:text-red-800 text-md transition-colors"
                            onClick={() => onFeedback(-1, msg.timestamp)}
                            aria-label="Not helpful response"
                        >
                            👎
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}, (prevProps, nextProps) => {
    const prevMsg = prevProps.msg;
    const nextMsg = nextProps.msg;

    return (
        prevMsg.id === nextMsg.id &&
        prevMsg.displayedText === nextMsg.displayedText &&
        prevMsg.text === nextMsg.text &&
        prevMsg.type === nextMsg.type
    );
});

export default MessageBubble;