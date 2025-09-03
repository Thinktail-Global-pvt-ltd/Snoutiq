// Create a separate memoized component for messages to prevent unnecessary re-renders
import React, { memo } from 'react';


const ActionButton = ({ tag }) => {
    switch (tag) {
        case "VIDEO_CONSULT_SUGGESTED":
            return (
                <Button
                    onClick={() => window.location.href = "/book-video-consultation"}
                    className="mt-3 w-full bg-blue-600 hover:bg-blue-700 text-white"
                >
                    📹 Schedule Video Consultation
                </Button>
            );
        case "CLINIC_VISIT_NEEDED":
            return (
                <Button
                    onClick={() => window.location.href = "/book-clinic-visit"}
                    className="mt-3 w-full bg-green-600 hover:bg-green-700 text-white"
                >
                    🏥 Book Clinic Appointment
                </Button>
            );
        case "EMERGENCY":
            return (
                <Button
                    onClick={() => window.location.href = "/emergency-vets"}
                    className="mt-3 w-full bg-red-600 hover:bg-red-700 text-white"
                >
                    🚨 Find Emergency Vet Now
                </Button>
            );
        default:
            return null;
    }
};


const MessageBubble = memo(({ msg, index, onFeedback }) => {
    console.log(msg, 'msg');

    if (msg.type === "loading") {
        return (
            <div key={`loader-${index}`} className="flex justify-start">
                <div className="bg-white border border-gray-200 rounded-2xl px-4 py-3 shadow-sm flex items-center space-x-2">
                    <span className="text-sm text-gray-500">Thinking</span>
                    <div className="flex space-x-1">
                        <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></span>
                        <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-150"></span>
                        <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-300"></span>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div
            key={msg.id || `msg-${index}`}
            className={`flex ${msg.sender === "user" ? "justify-end" : "justify-start"}`}
        >
            <div
                className={`w-fit max-w-full lg:max-w-[70%] break-words rounded-2xl px-4 py-3
                    ${msg.sender === "user"
                        ? "bg-blue-600 text-white"
                        : msg.isError
                            ? "bg-red-100 border border-red-200"
                            : "bg-white shadow-sm border border-gray-200"
                    }`}
            >
                {/* Headers for AI and Error messages */}
                {msg.sender === "ai" && !msg.isError && (
                    <div className="flex items-center mb-2">
                        <div className="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mr-2">
                            <svg
                                className="w-4 h-4 text-green-600"
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
                        <span className="text-sm font-medium text-gray-600">
                            Pet Health Assistant
                        </span>
                    </div>
                )}

                {msg.isError && (
                    <div className="flex items-center mb-2">
                        <div className="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center mr-2">
                            <svg
                                className="w-4 h-4 text-red-600"
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
                        <span className="text-sm font-medium text-red-600">
                            Connection Error
                        </span>
                    </div>
                )}

                {/* Message Text */}
                <div className="whitespace-pre-line leading-relaxed text-sm lg:text-base">
                    {msg.displayedText !== undefined ? msg.displayedText : msg.text}
                </div>
                
                {msg.sender === "ai" && msg.classificationTag && (
                    <ActionButton tag={msg.classificationTag} />
                )}
                {/* Timestamp */}
                {/* <div
                    className={`text-xs mt-2 ${msg.sender === "user"
                        ? "text-blue-200"
                        : msg.isError
                            ? "text-red-500"
                            : "text-gray-500"
                        }`}
                >
                    {msg.timestamp.toLocaleTimeString([], {
                        hour: "2-digit",
                        minute: "2-digit",
                    })}
                </div> */}
                <div
                    className={`text-xs mt-2 ${msg.sender === "user"
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

                {/* Feedback buttons */}
                {msg.sender === "ai" && !msg.isError && (
                    <div className="mt-2 flex gap-2">
                        <button
                            className="text-green-600 hover:text-green-800 text-lg"
                            onClick={() => onFeedback(1, msg.timestamp)}
                        >
                            👍
                        </button>
                        <button
                            className="text-red-600 hover:text-red-800 text-lg"
                            onClick={() => onFeedback(-1, msg.timestamp)}
                        >
                            👎
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}, (prevProps, nextProps) => {
    // Custom comparison for better performance
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