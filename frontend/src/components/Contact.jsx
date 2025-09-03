import React, { useState } from "react";
import axios from "axios";
import toast from "react-hot-toast";
import Header from "./Header";
import Footer from "./Footer";
import contact from '../assets/images/contact.jpg';

export default function Contact() {
    const [formData, setFormData] = useState({
        business_name: "",
        full_name: "",
        whatsapp_number: "",
        best_time_to_connect: "",
        consent: false,
    });

    const [formErrors, setFormErrors] = useState({});
    const [formSubmitted, setFormSubmitted] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData({
            ...formData,
            [name]: value
        });
    };

    const validateForm = () => {
        const errors = {};

        // Business Name
        if (!formData.business_name.trim()) {
            errors.business_name = "Business name is required";
        }

        // Full Name
        if (!formData.full_name.trim()) {
            errors.full_name = "Full name is required";
        }

        // Whatsapp Number
        if (!formData.whatsapp_number.trim()) {
            errors.whatsapp_number = "Whatsapp number is required";
        } else if (!/^\d{10}$/.test(formData.whatsapp_number)) {
            errors.whatsapp_number = "Whatsapp number must be exactly 10 digits";
        }

        // Best Time to Connect
        if (!formData.best_time_to_connect) {
            errors.best_time_to_connect = "Please select a time to connect";
        }

        // Consent
        if (!formData.consent) {
            errors.consent = "You must agree before submitting";
        }

        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };


    const handleSubmit = async (e) => {
        e.preventDefault();

        if (validateForm()) {
            try {
                setLoading(true);

                const payload = {
                    business_name: "",
                    full_name: formData.full_name,
                    whatsapp_number: formData.whatsapp_number,
                    best_time_to_connect: formData.best_time_to_connect
                };

                const res = await axios.post("https://snoutiqai.com/public/api/contact-request", payload);

                if (res.status === 200 || res.status === 201) {
                    toast.success("Message sent successfully!");
                    setFormSubmitted(true);
                    setFormData({
                        business_name: "",
                        full_name: "",
                        whatsapp_number: "",
                        best_time_to_connect: "",
                    });
                }
            } catch (error) {
                console.error("Error sending contact request:", error);
                toast.error(error.response?.data?.message || "Something went wrong. Please try again.");
            } finally {
                setLoading(false);
            }
        }
    };

    return (
        <>
            <Header />
            <div className="min-h-screen bg-gray-50 mt-10">
                {/* Hero Section with Image */}
                <div className="relative bg-gradient-to-r from-blue-600 to-purple-700 h-[300px] md:h-[400px] lg:h-[350px]">
                    {/* Overlay */}
                    <div className="absolute inset-0 bg-black opacity-40"></div>

                    {/* Background Image */}
                    <div
                        className="absolute inset-0 bg-cover bg-center"
                        style={{ backgroundImage: `url(${contact})` }}
                    ></div>

                    {/* Content */}
                    <div className="relative container mx-auto px-4 text-center flex flex-col justify-center items-center h-full">
                        <h1 className="text-4xl md:text-5xl font-bold text-white mb-4">Contact Us</h1>
                        <p className="text-xl text-white max-w-2xl mx-auto">
                            We'd love to hear from you. Get in touch with our team for any questions or inquiries.
                        </p>
                    </div>
                </div>


                {/* Contact Form Section */}
                <section className="py-16 bg-gray-50">
                    <div className="container mx-auto px-4 max-w-4xl">
                        <div className="flex flex-col md:flex-row gap-8">
                            {/* Contact Information */}
                            <div className="md:w-2/5 bg-white p-8 rounded-xl shadow-md">
                                <h2 className="text-2xl font-bold text-gray-800 mb-6">Get in Touch</h2>

                                <div className="space-y-6">
                                    <div className="flex items-start">
                                        <div className="bg-blue-100 p-3 rounded-full mr-4">
                                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-800">Address</h3>
                                            <p className="text-gray-600">337, 3rd Floor, Udyog Vihar, Phase 2, Gurgaon, Haryana, 122016</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start">
                                        <div className="bg-blue-100 p-3 rounded-full mr-4">
                                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-800">Phone</h3>
                                            <p className="text-gray-600">+91 85880 07466</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start">
                                        <div className="bg-blue-100 p-3 rounded-full mr-4">
                                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-800">Email</h3>
                                            <p className="text-gray-600">info@snoutiq.com</p>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            {/* Contact Form */}
                            <div className="md:w-3/5 bg-white p-8 rounded-xl shadow-md">
                                {formSubmitted ? (
                                    <div className="text-center py-10">
                                        <div className="text-5xl text-green-500 mb-4">✅</div>
                                        <h3 className="text-2xl font-bold text-green-700 mb-4">Message Sent Successfully!</h3>
                                        <p className="text-gray-600 mb-6">
                                            Thank you for contacting us. We'll get back to you within 24 hours.
                                        </p>
                                        <button
                                            onClick={() => setFormSubmitted(false)}
                                            className="bg-blue-600 text-white font-medium px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                                        >
                                            Send Another Message
                                        </button>
                                    </div>
                                ) : (
                                    <>
                                        <h2 className="text-2xl font-bold text-gray-800 mb-6">Send us a Message</h2>
                                        <form onSubmit={handleSubmit} className="space-y-6">
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                {/* Business Name */}
                                                <div>
                                                    <label htmlFor="business_name" className="block text-sm font-medium text-gray-700 mb-2">
                                                        Business Name *
                                                    </label>
                                                    <input
                                                        type="text"
                                                        id="business_name"
                                                        name="business_name"
                                                        value={formData.business_name}
                                                        onChange={handleInputChange}
                                                        className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${formErrors.business_name ? "border-red-500" : "border-gray-300"
                                                            }`}
                                                        placeholder="Your business name"
                                                    />
                                                    {formErrors.business_name && (
                                                        <p className="mt-1 text-sm text-red-600">{formErrors.business_name}</p>
                                                    )}
                                                </div>

                                                {/* Full Name */}
                                                <div>
                                                    <label htmlFor="full_name" className="block text-sm font-medium text-gray-700 mb-2">
                                                        Full Name *
                                                    </label>
                                                    <input
                                                        type="text"
                                                        id="full_name"
                                                        name="full_name"
                                                        value={formData.full_name}
                                                        onChange={handleInputChange}
                                                        className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${formErrors.full_name ? "border-red-500" : "border-gray-300"
                                                            }`}
                                                        placeholder="Your full name"
                                                    />
                                                    {formErrors.full_name && (
                                                        <p className="mt-1 text-sm text-red-600">{formErrors.full_name}</p>
                                                    )}
                                                </div>

                                                {/* Whatsapp Number */}
                                                <div>
                                                    <label htmlFor="whatsapp_number" className="block text-sm font-medium text-gray-700 mb-2">
                                                        Whatsapp Number *
                                                    </label>
                                                    <input
                                                        type="tel"
                                                        id="whatsapp_number"
                                                        name="whatsapp_number"
                                                        value={formData.whatsapp_number}
                                                        onChange={handleInputChange}
                                                        maxLength={10} // ✅ prevent typing more than 10 digits
                                                        className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${formErrors.whatsapp_number ? "border-red-500" : "border-gray-300"
                                                            }`}
                                                        placeholder="Enter your 10-digit Whatsapp number"
                                                    />
                                                    {formErrors.whatsapp_number && (
                                                        <p className="mt-1 text-sm text-red-600">{formErrors.whatsapp_number}</p>
                                                    )}
                                                </div>
                                                <div>
                                                    <label htmlFor="best_time_to_connect" className="block text-sm font-medium text-gray-700 mb-2">
                                                        Best Time to Connect *
                                                    </label>
                                                    <select
                                                        id="best_time_to_connect"
                                                        name="best_time_to_connect"
                                                        value={formData.best_time_to_connect}
                                                        onChange={handleInputChange}
                                                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    >
                                                        <option value="">Select a time slot</option>
                                                        <option value="9AM - 12PM">9 AM - 12 PM</option>
                                                        <option value="12PM - 3PM">12 PM - 3 PM</option>
                                                        <option value="3PM - 6PM">3 PM - 6 PM</option>
                                                        <option value="6PM - 9PM">6 PM - 9 PM</option>
                                                    </select>
                                                </div>

                                            </div>

                                            <div className="flex items-start">
                                                <div className="flex items-center h-5">
                                                    <input
                                                        id="consent"
                                                        name="consent"
                                                        type="checkbox"
                                                        checked={formData.consent || false}
                                                        onChange={(e) =>
                                                            setFormData({ ...formData, consent: e.target.checked })
                                                        }
                                                        className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                    />
                                                </div>
                                                <div className="ml-3 text-sm">
                                                    <label htmlFor="consent" className="font-medium text-gray-700">
                                                        I agree to be contacted by Snoutiq regarding the Founding Partner
                                                        Program and understand my data will be handled as per the Privacy
                                                        Policy. *
                                                    </label>
                                                    {formErrors.consent && (
                                                        <p className="mt-1 text-sm text-red-600">{formErrors.consent}</p>
                                                    )}
                                                </div>
                                            </div>


                                            <button
                                                type="submit"
                                                disabled={loading}
                                                className="w-full bg-blue-600 text-white font-bold px-6 py-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center cursor-pointer"
                                            >
                                                {loading ? "Sending..." : "Send Message"}
                                            </button>
                                        </form>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

            </div>
            <Footer />
        </>
    );
}