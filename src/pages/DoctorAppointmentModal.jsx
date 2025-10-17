import React, { useContext, useEffect, useState } from "react";
import axios from "axios";
import { AuthContext } from "../auth/AuthContext";
import { socket } from "./socket";

const API_BASE_URL = "https://snoutiq.com/backend/api";

const DoctorAppointmentModal = ({ visible, onClose, onBook }) => {
  const [selectedClinic, setSelectedClinic] = useState(null);
  const [clinicDoctors, setClinicDoctors] = useState([]);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [doctorAvailability, setDoctorAvailability] = useState([]);
  const [selectedDate, setSelectedDate] = useState(null);
  const [availableTimes, setAvailableTimes] = useState([]);
  const [selectedTime, setSelectedTime] = useState(null);
  const [selectedServices, setSelectedServices] = useState([]);
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [pets, setPets] = useState([]);
  const [summary, setSummary] = useState("");
  const [selectedPet, setSelectedPet] = useState(null);

  const { nearbyDoctors, user, token } = useContext(AuthContext);

  const availableServices = [
    { id: 1, name: "General Consultation", price: 800, duration: 30 },
    { id: 2, name: "Vaccination", price: 1200, duration: 30 },
    { id: 3, name: "Dental Checkup", price: 1500, duration: 45 },
    { id: 4, name: "Grooming", price: 1000, duration: 60 },
  ];

  const fetchPets = async () => {
    setLoading(true);
    try {
      const response = await axios.get(
        `https://snoutiq.com/backend/api/users/${user.id}/pets`,
        {
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
          timeout: 5000,
        }
      );

      if (
        response.data.status === "success" &&
        Array.isArray(response.data.data)
      ) {
        const transformedPets = response.data.data.map((pet) => ({
          id: pet.id,
          name: pet.name || "Unknown Pet",
          age: pet.pet_age || 0,
          gender: pet.pet_gender || "",
          breed: pet.breed || "Pet",
          avatar: pet.pet_doc1,
          petType: pet.breed?.toLowerCase().includes("cat") ? "cat" : "dog",
          weight: pet.weight || "",
        }));

        setPets(transformedPets);

        if (transformedPets.length > 0 && !selectedPet) {
          setSelectedPet(transformedPets[0]);
        }
      } else {
        setPets([]);
      }
    } catch (error) {
      console.error("Error fetching pets:", error);
      setPets([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPets();
  }, []);

  const fetchAISummary = async () => {
    setLoading(true);
    try {
      const response = await axios.get(
        `https://snoutiq.com/backend/api/ai/summary?user_id=${user.id}`,
        {
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
          timeout: 10000,
        }
      );

      if (response.data?.success) {
        setSummary(response.data.summary || "No summary available");
      } else {
        setSummary("No summary available");
      }
    } catch (error) {
      setSummary("Failed to load summary");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAISummary();
  }, []);

  const processedClinics =
    nearbyDoctors?.map((clinic) => ({
      id: clinic.id,
      name: clinic.vet_name || clinic.name || "Veterinary Clinic",
      rating: parseFloat(clinic.rating) || 4.8,
      address: clinic.vet_address || clinic.formatted_address || clinic.address,
      mobile: clinic.mobile,
      email: clinic.email,
      open_now: clinic.open_now,
      user_ratings_total: clinic.user_ratings_total || 0,
      photos: clinic.photos ? JSON.parse(clinic.photos) : [],
    })) || [];

  useEffect(() => {
    if (!visible) {
      resetForm();
    }
  }, [visible]);

  const resetForm = () => {
    setSelectedClinic(null);
    setClinicDoctors([]);
    setSelectedDoctor(null);
    setDoctorAvailability([]);
    setSelectedDate(null);
    setSelectedTime(null);
    setSelectedServices([]);
    setStep(1);
    setAvailableTimes([]);
  };

  const handleClinicSelect = async (clinic) => {
    setSelectedClinic(clinic);
    setLoading(true);

    await new Promise((resolve) => setTimeout(resolve, 80));

    try {
      const response = await fetch(`${API_BASE_URL}/clinics/${clinic.id}/doctors`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      const rawText = await response.text();
      let cleaned = rawText.trim();
      const firstBrace = Math.min(
        cleaned.indexOf("{") === -1 ? Infinity : cleaned.indexOf("{"),
        cleaned.indexOf("[") === -1 ? Infinity : cleaned.indexOf("[")
      );
      if (firstBrace > 0) cleaned = cleaned.slice(firstBrace);

      let data;
      try {
        data = JSON.parse(cleaned);
      } catch (parseError) {
        alert("Error", "Server returned invalid data");
        return;
      }

      if (response.ok && data.doctors) {
        setClinicDoctors(data.doctors);
        setStep(2);
      } else {
        console.warn("Unexpected API data:", data);
        alert("Error", "Failed to fetch clinic doctors");
      }
    } catch (error) {
      console.error("Error fetching doctors:", error);
      alert("Error", "Failed to load doctors for this clinic");
    } finally {
      setLoading(false);
    }
  };

  const handleDoctorSelect = async (doctor) => {
    setSelectedDoctor(doctor);
    setLoading(true);

    try {
      const response = await fetch(
        `${API_BASE_URL}/clinics/${selectedClinic.id}/availability`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );

      const rawText = await response.text();

      let data;
      try {
        data = JSON.parse(rawText.trim());
      } catch (parseError) {
        console.error("Invalid JSON:", parseError, rawText);
        alert("Error", "Invalid JSON from server");
        return;
      }

      if (response.ok && data.availability) {
        const doctorAvail = data.availability.filter(
          (avail) =>
            avail.doctor_id === doctor.id && avail.service_type === "video"
        );

        setDoctorAvailability(doctorAvail);
        setStep(3);
      } else {
        console.warn("Unexpected data format:", data);
        alert("Error", "Failed to fetch doctor availability");
      }
    } catch (error) {
      console.error("Error fetching doctor availability:", error);
      alert("Error", "Failed to load doctor availability");
    } finally {
      setLoading(false);
    }
  };

  const handleDateSelect = (date) => {
    setSelectedDate(date);
    setStep(4);
  };

  const fetchFreeSlots = async () => {
    if (!selectedDate || !selectedDoctor) return;

    setLoading(true);

    try {
      const response = await fetch(
        `${API_BASE_URL}/doctors/${selectedDoctor.id}/free-slots?date=${selectedDate}&service_type=video`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );

      const rawText = await response.text();

      let data;
      try {
        data = JSON.parse(rawText.trim());
      } catch (e) {
        console.error("Invalid JSON in slot response:", e);
        alert("Error", "Invalid response from server");
        return;
      }

      if (data.free_slots && data.free_slots.length === 0) {
        data.free_slots = ["09:00", "10:30", "12:00"];
      }

      if (response.ok && data.success && data.free_slots) {
        const slots = data.free_slots.map((timeString) => {
          const [hours, minutes] = timeString.split(":");
          const hour = parseInt(hours, 10);
          const displayTime = `${hour % 12 || 12}:${minutes} ${
            hour < 12 ? "AM" : "PM"
          }`;

          return { value: timeString, display: displayTime };
        });

        setAvailableTimes(slots);
      } else {
        alert("No Slots Available", "No free slots for selected date");
      }
    } catch (error) {
      console.error("Error fetching slots:", error);
      alert("Error", "Failed to load available time slots");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (step === 4 && selectedDate && selectedDoctor) {
      fetchFreeSlots();
    }
  }, [step, selectedDate, selectedDoctor]);

  const handleTimeSelect = (time) => {
    setSelectedTime(time);
    setStep(5);
  };

  const handleServiceToggle = (service) => {
    setSelectedServices((prev) => {
      const isSelected = prev.find((s) => s.service_id === service.id);
      if (isSelected) {
        return prev.filter((s) => s.service_id !== service.id);
      } else {
        return [
          ...prev,
          {
            service_id: service.id,
            price: service.price,
          },
        ];
      }
    });
  };

  const calculateTotalAmount = () => {
    if (selectedServices.length === 0) {
      return 80000;
    }

    const total = selectedServices.reduce(
      (sum, service) => sum + service.price,
      0
    );
    return total * 100;
  };

  const calculateDuration = () => {
    if (selectedServices.length === 0) return 20;

    const totalMinutes = selectedServices.reduce((sum, service) => {
      const serviceData = availableServices.find(
        (s) => s.id === service.service_id
      );
      return sum + (serviceData?.duration || 30);
    }, 0);

    return Math.max(20, totalMinutes);
  };

  const getEndTime = () => {
    if (!selectedTime) return null;

    const [hours, minutes, seconds] = selectedTime.value.split(":").map(Number);
    const duration = calculateDuration();

    const startDate = new Date();
    startDate.setHours(hours, minutes, seconds || 0, 0);

    const endDate = new Date(startDate.getTime() + duration * 60000);

    const endHours = endDate.getHours().toString().padStart(2, "0");
    const endMinutes = endDate.getMinutes().toString().padStart(2, "0");
    const endSeconds = endDate.getSeconds().toString().padStart(2, "0");

    return `${endHours}:${endMinutes}:${endSeconds}`;
  };

  const initiateRazorpayPayment = async () => {
    if (!selectedDoctor || !selectedDate || !selectedTime) {
      alert(
        "Missing Information",
        "Please complete all appointment details before proceeding."
      );
      console.warn("❌ Missing doctor/date/time before payment");
      return;
    }

    if (!user) {
      alert(
        "Authentication Required",
        "Please log in to book an appointment."
      );
      console.warn("❌ User not authenticated before payment");
      return;
    }

    if (!selectedPet) {
      alert(
        "Select Pet",
        "Please select a pet before booking an appointment."
      );
      console.warn("❌ Pet not selected");
      return;
    }

    setLoading(true);

    try {
      const bookingData = {
        user_id: user.id,
        clinic_id: selectedClinic?.id,
        doctor_id: selectedDoctor?.id,
        service_type: "video",
        scheduled_date: selectedDate,
        scheduled_time: selectedTime.value,
        pet_id: selectedPet.id,
        urgency: "medium",
        ai_summary: (summary || "Video consultation booking").replace(
          /\n/g,
          " "
        ),
        ai_urgency_score: 0.45,
        symptoms: ["consultation"],
        latitude: 28.4949,
        longitude: 77.0868,
        address: selectedClinic?.address || "Clinic Address",
      };

      const createResponse = await fetch(`${API_BASE_URL}/bookings/create`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(bookingData),
      });

      let rawText = await createResponse.text();
      rawText = rawText.trim().replace(/^\uFEFF/, "");

      const jsonMatch = rawText.match(/({.*}|\[.*\])/);
      if (jsonMatch) {
        rawText = jsonMatch[0];
      } else if (!rawText.startsWith("{") && !rawText.startsWith("[")) {
        console.error("❌ Response is not valid JSON:", rawText);
        throw new Error("Booking API returned non-JSON response");
      }

      let createData;
      try {
        createData = JSON.parse(rawText);
      } catch (err) {
        console.error(
          "❌ Failed to parse booking JSON:",
          err,
          "Raw text:",
          rawText
        );
        throw new Error("Booking API returned invalid JSON");
      }

      if (!createResponse.ok || !createData.success) {
        throw new Error(createData.message || "Failed to create booking");
      }

      const { booking_id, payment } = createData;

      if (!booking_id || !payment || !payment.order_id) {
        throw new Error("Booking created but payment info missing");
      }

      // For web, you would integrate with Razorpay's web SDK here
      console.log("Payment initiated for booking:", booking_id);
      
      // Simulate payment success for demo
      handlePaymentSuccess({}, booking_id);
      
    } catch (error) {
      console.error("❌ Booking creation failed:", error);
      alert("Booking Error", error.message || "Failed to create booking");
      setLoading(false);
    }
  };

  const handlePaymentSuccess = async (paymentData, booking_id) => {
    try {
      setLoading(true);

      // Simulate payment verification for web
      await new Promise(resolve => setTimeout(resolve, 2000));

      alert(
        "🎉 Appointment Confirmed!",
        "Your video consultation has been booked successfully. You will receive a confirmation shortly.",
        [
          {
            text: "Great!",
            onPress: () => {
              onClose?.();
              onBook?.();
            },
          },
        ]
      );

    } catch (error) {
      console.error("❌ Payment Verification Error:", error);
      alert(
        "Payment Verification Issue",
        "Payment was successful but verification failed. Please contact support with your booking ID: " + booking_id,
        [
          {
            text: "OK",
            onPress: () => {
              onClose?.();
              onBook?.();
            },
          },
        ]
      );
    } finally {
      setLoading(false);
    }
  };

  const handlePaymentFailure = (error) => {
    console.error("❌ Payment failed:", error);
    let errorMessage = "The payment was cancelled or failed. Please try again.";
    alert("Payment Failed", errorMessage);
    setLoading(false);
  };

  const renderStepIndicator = () => (
    <div className="flex justify-center items-center px-2 mt-6">
      {[1, 2, 3, 4, 5].map((stepNumber) => (
        <div key={stepNumber} className="flex items-center">
          <div
            className={`
              w-7 h-7 rounded-full flex items-center justify-center transition-colors
              ${step >= stepNumber 
                ? 'bg-blue-500' 
                : 'bg-gray-200'
              }
            `}
          >
            {step > stepNumber ? (
              <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
              </svg>
            ) : (
              <span className={`text-sm font-semibold ${
                step >= stepNumber ? 'text-white' : 'text-gray-500'
              }`}>
                {stepNumber}
              </span>
            )}
          </div>
          {stepNumber < 5 && (
            <div
              className={`
                w-8 h-0.5 mx-1 transition-colors
                ${step > stepNumber ? 'bg-blue-500' : 'bg-gray-200'}
              `}
            />
          )}
        </div>
      ))}
    </div>
  );

  const renderStepLabels = () => (
    <div className="flex justify-between px-4 mt-3 mb-6">
      <span className={`text-xs font-medium ${step >= 1 ? 'text-blue-500' : 'text-gray-400'}`}>Clinic</span>
      <span className={`text-xs font-medium ${step >= 2 ? 'text-blue-500' : 'text-gray-400'}`}>Doctor</span>
      <span className={`text-xs font-medium ${step >= 3 ? 'text-blue-500' : 'text-gray-400'}`}>Date</span>
      <span className={`text-xs font-medium ${step >= 4 ? 'text-blue-500' : 'text-gray-400'}`}>Time</span>
      <span className={`text-xs font-medium ${step >= 5 ? 'text-blue-500' : 'text-gray-400'}`}>Pay</span>
    </div>
  );

  const renderClinicSelection = () => (
    <div className="flex-1 p-6">
      <h2 className="text-2xl font-bold text-gray-900 mb-1">Select Clinic</h2>
      <p className="text-gray-600 mb-6">Choose your preferred clinic</p>

      {processedClinics.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-16">
          <svg className="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <p className="text-lg font-semibold text-gray-500 mb-2">No clinics available</p>
          <p className="text-gray-400">Please try again later</p>
        </div>
      ) : (
        <div className="space-y-4 max-h-96 overflow-y-auto">
          {processedClinics.map((clinic) => (
            <div
              key={clinic.id}
              className={`
                flex items-center p-4 bg-white rounded-xl border-2 transition-all cursor-pointer
                ${selectedClinic?.id === clinic.id 
                  ? 'border-blue-500 bg-blue-50' 
                  : 'border-gray-100 hover:border-blue-200'
                }
                ${loading ? 'opacity-50 cursor-not-allowed' : ''}
              `}
              onClick={() => !loading && handleClinicSelect(clinic)}
            >
              <div className="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-4">
                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>

              <div className="flex-1 min-w-0">
                <h3 className="text-lg font-semibold text-gray-900 mb-1 line-clamp-2">{clinic.name}</h3>
                <div className="flex items-center mb-1">
                  <svg className="w-4 h-4 text-yellow-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <span className="text-sm font-medium text-gray-900 mr-2">{clinic.rating}</span>
                  <span className="text-sm text-gray-500">({clinic.user_ratings_total})</span>
                </div>
                <p className="text-sm text-gray-600 mb-2 line-clamp-2">{clinic.address}</p>
                <div className="flex items-center">
                  <div className={`w-2 h-2 rounded-full mr-2 ${clinic.open_now ? 'bg-green-500' : 'bg-red-500'}`}></div>
                  <span className="text-sm text-gray-500">
                    {clinic.open_now ? "Open Now" : "Currently Closed"}
                  </span>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );

  const renderDoctorSelection = () => (
    <div className="flex-1 p-6">
      <h2 className="text-2xl font-bold text-gray-900 mb-1">Select Doctor</h2>
      <p className="text-gray-600 mb-6">
        Choose your preferred doctor at {selectedClinic?.name}
      </p>

      {loading ? (
        <div className="flex flex-col items-center justify-center py-16">
          <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
          <p className="text-gray-600">Loading doctors...</p>
        </div>
      ) : clinicDoctors.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-16">
          <svg className="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
          </svg>
          <p className="text-lg font-semibold text-gray-500 mb-2">No doctors available</p>
          <p className="text-gray-400">Please try another clinic</p>
        </div>
      ) : (
        <div className="space-y-4 max-h-96 overflow-y-auto">
          {clinicDoctors.map((doctor) => (
            <div
              key={doctor.id}
              className={`
                flex items-center p-4 bg-white rounded-xl border-2 transition-all cursor-pointer
                ${selectedDoctor?.id === doctor.id 
                  ? 'border-blue-500 bg-blue-50' 
                  : 'border-gray-100 hover:border-blue-200'
                }
              `}
              onClick={() => handleDoctorSelect(doctor)}
            >
              <div className="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-4">
                <span className="text-white font-semibold text-sm">
                  {doctor.name
                    .split(" ")
                    .map((n) => n[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2)}
                </span>
              </div>

              <div className="flex-1 min-w-0">
                <h3 className="text-lg font-semibold text-gray-900 mb-1 line-clamp-2">{doctor.name}</h3>
                <p className="text-gray-600 mb-1">Veterinary Doctor</p>
                {doctor.email && (
                  <p className="text-sm text-gray-500 truncate">{doctor.email}</p>
                )}
                {doctor.phone && (
                  <p className="text-sm text-gray-500">{doctor.phone}</p>
                )}
              </div>

              <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </div>
          ))}
        </div>
      )}
    </div>
  );

  const renderDateSelection = () => (
    <div className="flex-1 p-6">
      <h2 className="text-2xl font-bold text-gray-900 mb-1">Select Date</h2>
      <p className="text-gray-600 mb-6">
        Choose your preferred date for video consultation
      </p>

      <div className="bg-blue-50 p-4 rounded-xl mb-6 flex items-center">
        <span className="text-gray-600 font-semibold mr-2">Doctor:</span>
        <span className="text-blue-600 font-semibold flex-1">{selectedDoctor?.name}</span>
      </div>

      <div className="bg-white rounded-xl shadow-lg p-4">
        <div className="flex justify-between items-center mb-4">
          <button className="p-2 hover:bg-gray-100 rounded-lg">
            <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h3 className="text-lg font-semibold text-gray-900">March 2024</h3>
          <button className="p-2 hover:bg-gray-100 rounded-lg">
            <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <div className="grid grid-cols-7 gap-2 mb-2">
          {['S', 'M', 'T', 'W', 'T', 'F', 'S'].map((day) => (
            <div key={day} className="text-center text-sm font-medium text-gray-500 py-2">
              {day}
            </div>
          ))}
        </div>

        <div className="grid grid-cols-7 gap-2">
          {Array.from({ length: 31 }, (_, i) => i + 1).map((day) => (
            <button
              key={day}
              className={`
                py-2 rounded-lg text-center transition-all
                ${selectedDate === `2024-03-${day.toString().padStart(2, '0')}`
                  ? 'bg-blue-500 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
                }
              `}
              onClick={() => handleDateSelect(`2024-03-${day.toString().padStart(2, '0')}`)}
            >
              {day}
            </button>
          ))}
        </div>
      </div>
    </div>
  );

  const renderTimeSelection = () => (
    <div className="flex-1 p-6">
      <h2 className="text-2xl font-bold text-gray-900 mb-1">Select Time</h2>
      <p className="text-gray-600 mb-6">Choose your preferred time slot</p>

      <div className="bg-blue-50 p-4 rounded-xl mb-6">
        <span className="text-gray-600 font-semibold mr-2">Date:</span>
        <span className="text-blue-600 font-semibold">{selectedDate}</span>
      </div>

      {loading ? (
        <div className="flex flex-col items-center justify-center py-16">
          <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
          <p className="text-gray-600">Loading available slots...</p>
        </div>
      ) : availableTimes.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-16">
          <svg className="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p className="text-lg font-semibold text-gray-500 mb-2">No slots available</p>
          <p className="text-gray-400">Please try another date</p>
        </div>
      ) : (
        <div>
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Available Time Slots</h3>
          <div className="grid grid-cols-3 gap-3">
            {availableTimes.map((time) => (
              <button
                key={time.value}
                className={`
                  py-3 px-4 rounded-xl border-2 transition-all text-center
                  ${selectedTime?.value === time.value
                    ? 'border-blue-500 bg-blue-500 text-white'
                    : 'border-gray-200 text-gray-700 hover:border-blue-300'
                  }
                `}
                onClick={() => handleTimeSelect(time)}
              >
                <span className="font-semibold">{time.display}</span>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );

  const renderPayment = () => (
    <div className="flex-1 p-6 overflow-y-auto">
      <h2 className="text-2xl font-bold text-gray-900 mb-1">Confirm Booking</h2>
      <p className="text-gray-600 mb-6">
        Review and complete your video consultation
      </p>

      <div className="bg-gray-50 rounded-xl p-6 mb-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Appointment Details</h3>

        <div className="flex items-center mb-4 pb-4 border-b border-gray-200">
          <svg className="w-6 h-6 text-blue-500 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <div>
            <p className="text-gray-600 text-sm">Clinic</p>
            <p className="font-semibold text-gray-900 line-clamp-2">{selectedClinic?.name}</p>
          </div>
        </div>

        <div className="flex items-center mb-4">
          <div className="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-4">
            <span className="text-white font-semibold text-sm">
              {selectedDoctor?.name
                .split(" ")
                .map((n) => n[0])
                .join("")
                .toUpperCase()
                .slice(0, 2)}
            </span>
          </div>
          <div className="flex-1">
            <p className="font-semibold text-gray-900 line-clamp-2">{selectedDoctor?.name}</p>
            <p className="text-gray-600">Video Consultation</p>
          </div>
        </div>

        <div className="space-y-3">
          <div className="flex items-center">
            <svg className="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span className="text-gray-600 flex-1">Date</span>
            <span className="font-semibold text-gray-900">{selectedDate}</span>
          </div>

          <div className="flex items-center">
            <svg className="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span className="text-gray-600 flex-1">Time</span>
            <span className="font-semibold text-gray-900">{selectedTime?.display}</span>
          </div>

          {selectedServices.length > 0 && (
            <div className="pt-3 border-t border-gray-200">
              <p className="font-semibold text-gray-900 mb-2">Selected Services:</p>
              {selectedServices.map((service, index) => {
                const serviceInfo = availableServices.find(
                  (s) => s.id === service.service_id
                );
                return (
                  <div key={index} className="flex justify-between items-start mb-1">
                    <span className="text-gray-600 flex-1 mr-2">• {serviceInfo?.name}</span>
                    <span className="font-semibold text-green-600">₹{service.price}</span>
                  </div>
                );
              })}
            </div>
          )}

          <div className="flex items-center pt-3 border-t border-gray-200">
            <svg className="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
            <span className="text-gray-600 flex-1">Total Amount</span>
            <span className="text-xl font-bold text-green-600">₹{calculateTotalAmount() / 100}</span>
          </div>
        </div>
      </div>

      <div className="mb-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-3">Payment Method</h3>
        <div className="flex items-center justify-between bg-white rounded-xl p-4 border-2 border-gray-200">
          <div className="flex items-center">
            <div className="bg-blue-500 px-3 py-1 rounded mr-4">
              <span className="text-white font-bold text-sm">Razorpay</span>
            </div>
            <span className="text-gray-600">Credit/Debit Card, UPI, Net Banking</span>
          </div>
          <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
          </svg>
        </div>
      </div>

      <button
        className={`
          w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 
          text-white py-4 rounded-xl font-bold flex items-center justify-center gap-3 
          transition-all shadow-lg shadow-green-500/30 mb-4
          ${loading ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-xl'}
        `}
        onClick={initiateRazorpayPayment}
        disabled={loading}
      >
        {loading ? (
          <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
        ) : (
          <>
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Pay ₹{calculateTotalAmount() / 100}
          </>
        )}
      </button>

      <button
        className="w-full py-4 text-gray-600 font-semibold rounded-xl hover:bg-gray-50 transition-colors"
        onClick={onClose}
        disabled={loading}
      >
        Cancel Booking
      </button>
    </div>
  );

  if (!visible) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-black bg-opacity-50">
      <div className="bg-white rounded-t-3xl w-full max-w-2xl max-h-[95vh] overflow-hidden">
        <div className="flex items-center justify-between p-6 border-b border-gray-100">
          <button
            onClick={step > 1 ? () => setStep(step - 1) : onClose}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <svg className="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={step > 1 ? "M15 19l-7-7 7-7" : "M6 18L18 6M6 6l12 12"} />
            </svg>
          </button>
          <h1 className="text-xl font-bold text-gray-900 text-center flex-1 mx-4">
            Book Video Consultation
          </h1>
          <div className="w-10"></div>
        </div>

        {renderStepIndicator()}
        {renderStepLabels()}

        <div className="flex-1 overflow-hidden">
          {step === 1 && renderClinicSelection()}
          {step === 2 && renderDoctorSelection()}
          {step === 3 && renderDateSelection()}
          {step === 4 && renderTimeSelection()}
          {step === 5 && renderPayment()}
        </div>
      </div>
    </div>
  );
};

export default DoctorAppointmentModal;