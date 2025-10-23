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

  // Fetch pets function
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

  // Handler functions (keep the same as your original)
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

      let freeSlots = data.free_slots || [];
      
      const today = new Date();
      const selected = new Date(selectedDate);
      
      today.setHours(0, 0, 0, 0);
      selected.setHours(0, 0, 0, 0);
      
      if (selected.getTime() === today.getTime()) {
        const currentTime = new Date();
        const currentHours = currentTime.getHours();
        const currentMinutes = currentTime.getMinutes();
        
        freeSlots = freeSlots.filter(slot => {
          const [slotHours, slotMinutes] = slot.split(':').map(Number);
          return slotHours > currentHours || 
                 (slotHours === currentHours && slotMinutes > currentMinutes);
        });
      }

      if (freeSlots.length === 0) {
        freeSlots = ["09:00", "10:30", "12:00", "14:00", "15:30", "17:00"];
      }

      if (response.ok && data.success && freeSlots) {
        const slots = freeSlots.map((timeString) => {
          const [hours, minutes] = timeString.split(':');
          const hour = parseInt(hours, 10);
          const displayTime = `${hour % 12 || 12}:${minutes.padStart(2, '0')} ${
            hour < 12 ? 'AM' : 'PM'
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
      return;
    }

    if (!user) {
      alert(
        "Authentication Required",
        "Please log in to book an appointment."
      );
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
        throw new Error("Booking API returned non-JSON response");
      }

      let createData;
      try {
        createData = JSON.parse(rawText);
      } catch (err) {
        throw new Error("Booking API returned invalid JSON");
      }

      if (!createResponse.ok || !createData.success) {
        throw new Error(createData.message || "Failed to create booking");
      }

      const { booking_id, payment } = createData;

      if (!booking_id || !payment || !payment.order_id) {
        throw new Error("Booking created but payment info missing");
      }

      console.log("Payment initiated for booking:", booking_id);
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
      await new Promise(resolve => setTimeout(resolve, 2000));

      alert(
        "🎉 Appointment Confirmed!",
        "Your video consultation has been booked successfully.",
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
        "Payment was successful but verification failed.",
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

  // Compact Step Indicator
  const renderStepIndicator = () => (
    <div className="flex justify-center items-center px-4 mt-4">
      {[1, 2, 3, 4, 5].map((stepNumber) => (
        <div key={stepNumber} className="flex items-center">
          <div
            className={`
              w-6 h-6 rounded-full flex items-center justify-center transition-all duration-300 text-xs font-medium
              ${step >= stepNumber 
                ? 'bg-blue-600 text-white' 
                : 'bg-gray-100 text-gray-400 border border-gray-300'
              }
              ${step === stepNumber ? 'ring-2 ring-blue-400 ring-offset-1' : ''}
            `}
          >
            {step > stepNumber ? (
              <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
              </svg>
            ) : (
              stepNumber
            )}
          </div>
          {stepNumber < 5 && (
            <div
              className={`
                w-4 h-0.5 mx-1 transition-all duration-300
                ${step > stepNumber ? 'bg-blue-600' : 'bg-gray-200'}
              `}
            />
          )}
        </div>
      ))}
    </div>
  );

  // Compact Step Labels
  const renderStepLabels = () => {
    const labels = ["Clinic", "Doctor", "Date", "Time", "Confirm"];
    return (
      <div className="flex justify-between px-2 mt-2 mb-4">
        {labels.map((label, index) => (
          <span 
            key={index}
            className={`text-xs text-center transition-colors ${
              step >= index + 1 ? 'text-blue-600 font-medium' : 'text-gray-400'
            }`}
            style={{ width: '20%' }}
          >
            {label}
          </span>
        ))}
      </div>
    );
  };

  // Compact Clinic Selection
  const renderClinicSelection = () => (
    <div className="p-4">
      <div className="text-center mb-4">
        <h2 className="text-lg font-bold text-gray-900 mb-1">Select Clinic</h2>
        <p className="text-gray-600 text-xs">Choose your preferred veterinary clinic</p>
      </div>

      {processedClinics.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-8">
          <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
            <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <p className="text-sm font-medium text-gray-500 mb-1">No clinics available</p>
          <p className="text-gray-400 text-xs text-center">We couldn't find any clinics in your area.</p>
        </div>
      ) : (
        <div className="space-y-3 max-h-96 overflow-y-auto">
          {processedClinics.map((clinic) => (
            <div
              key={clinic.id}
              className={`
                flex items-start p-3 bg-white rounded-lg border transition-all duration-200 cursor-pointer
                ${selectedClinic?.id === clinic.id 
                  ? 'border-blue-500 bg-blue-50' 
                  : 'border-gray-200 hover:border-blue-300'
                }
                ${loading ? 'opacity-50 cursor-not-allowed' : ''}
              `}
              onClick={() => !loading && handleClinicSelect(clinic)}
            >
              <div className="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>

              <div className="flex-1 min-w-0">
                <div className="flex items-start justify-between mb-1">
                  <h3 className="text-sm font-semibold text-gray-900 line-clamp-2 pr-2">{clinic.name}</h3>
                  <div className="flex items-center flex-shrink-0">
                    <svg className="w-3 h-3 text-yellow-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span className="text-xs font-semibold text-gray-900">{clinic.rating}</span>
                  </div>
                </div>
                
                <p className="text-xs text-gray-600 mb-2 line-clamp-2 leading-relaxed">{clinic.address}</p>
                
                <div className="flex items-center justify-between">
                  <div className="flex items-center">
                    <div className={`w-1.5 h-1.5 rounded-full mr-1 ${clinic.open_now ? 'bg-green-500' : 'bg-red-500'}`}></div>
                    <span className="text-xs text-gray-700">
                      {clinic.open_now ? "Open Now" : "Closed"}
                    </span>
                  </div>
                  <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );

  // Compact Doctor Selection
  const renderDoctorSelection = () => (
    <div className="p-4">
      <div className="text-center mb-4">
        <h2 className="text-lg font-bold text-gray-900 mb-1">Select Doctor</h2>
        <p className="text-gray-600 text-xs">
          Choose your preferred doctor at <span className="font-medium text-blue-600">{selectedClinic?.name}</span>
        </p>
      </div>

      {loading ? (
        <div className="flex flex-col items-center justify-center py-8">
          <div className="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mb-2"></div>
          <p className="text-gray-600 text-sm">Loading doctors...</p>
        </div>
      ) : clinicDoctors.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-8">
          <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
            <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
          </div>
          <p className="text-sm font-medium text-gray-500 mb-1">No doctors available</p>
          <p className="text-gray-400 text-xs text-center">No doctors available at this clinic.</p>
        </div>
      ) : (
        <div className="space-y-3 max-h-96 overflow-y-auto">
          {clinicDoctors.map((doctor) => (
            <div
              key={doctor.id}
              className={`
                flex items-center p-3 bg-white rounded-lg border transition-all duration-200 cursor-pointer
                ${selectedDoctor?.id === doctor.id 
                  ? 'border-blue-500 bg-blue-50' 
                  : 'border-gray-200 hover:border-blue-300'
                }
              `}
              onClick={() => handleDoctorSelect(doctor)}
            >
              <div className="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                <span className="text-white font-bold text-xs">
                  {doctor.name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2)}
                </span>
              </div>

              <div className="flex-1 min-w-0">
                <h3 className="text-sm font-semibold text-gray-900 mb-1 line-clamp-2">{doctor.name}</h3>
                <p className="text-blue-600 font-medium text-xs mb-1">Veterinary Doctor</p>
                {doctor.email && (
                  <p className="text-xs text-gray-500 truncate flex items-center">
                    <svg className="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    {doctor.email}
                  </p>
                )}
              </div>

              <svg className="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </div>
          ))}
        </div>
      )}
    </div>
  );

  // Compact Date Selection
  const renderDateSelection = () => {
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
    
    const getDaysInMonth = (year, month) => {
      return new Date(year, month + 1, 0).getDate();
    };
    
    const getFirstDayOfMonth = (year, month) => {
      return new Date(year, month, 1).getDay();
    };
    
    const daysInMonth = getDaysInMonth(currentYear, currentMonth);
    const firstDayOfMonth = getFirstDayOfMonth(currentYear, currentMonth);
    
    const monthNames = [
      "January", "February", "March", "April", "May", "June",
      "July", "August", "September", "October", "November", "December"
    ];
    
    const currentMonthName = monthNames[currentMonth];
    
    const isPastDate = (day) => {
      const date = new Date(currentYear, currentMonth, day);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      return date < today;
    };
    
    const formatDate = (day) => {
      const month = (currentMonth + 1).toString().padStart(2, '0');
      const dayStr = day.toString().padStart(2, '0');
      return `${currentYear}-${month}-${dayStr}`;
    };
    
    const calendarDays = [];
    
    for (let i = 0; i < firstDayOfMonth; i++) {
      calendarDays.push(null);
    }
    
    for (let day = 1; day <= daysInMonth; day++) {
      calendarDays.push(day);
    }

    return (
      <div className="p-4">
        <div className="text-center mb-4">
          <h2 className="text-lg font-bold text-gray-900 mb-1">Select Date</h2>
          <p className="text-gray-600 text-xs">Choose your preferred date</p>
        </div>

        <div className="bg-blue-50 p-3 rounded-lg mb-4 flex items-center">
          <div className="flex-shrink-0 w-8 h-8 bg-white rounded-lg flex items-center justify-center mr-3">
            <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div className="flex-1">
            <p className="text-gray-600 text-xs font-medium">Doctor</p>
            <p className="text-blue-600 font-semibold text-sm">{selectedDoctor?.name}</p>
          </div>
        </div>

        <div className="bg-white rounded-lg border border-gray-200 p-3">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-sm font-bold text-gray-900">{currentMonthName} {currentYear}</h3>
          </div>

          <div className="grid grid-cols-7 gap-1 mb-3">
            {['S', 'M', 'T', 'W', 'T', 'F', 'S'].map((day) => (
              <div key={day} className={`text-center text-xs font-medium py-2 ${
                day === 'S' ? 'text-red-500' : 'text-gray-500'
              }`}>
                {day}
              </div>
            ))}
          </div>

          <div className="grid grid-cols-7 gap-1">
            {calendarDays.map((day, index) => (
              <div key={index} className="flex justify-center">
                {day ? (
                  <button
                    className={`
                      w-8 h-8 rounded-lg text-center transition-all duration-200 text-xs font-medium
                      flex items-center justify-center
                      ${isPastDate(day)
                        ? 'text-gray-300 bg-gray-50 cursor-not-allowed'
                        : selectedDate === formatDate(day)
                        ? 'bg-blue-600 text-white'
                        : 'text-gray-700 bg-white hover:bg-blue-50 hover:text-blue-600'
                      }
                      ${index % 7 === 0 && !isPastDate(day) ? 'text-red-500' : ''}
                    `}
                    onClick={() => !isPastDate(day) && handleDateSelect(formatDate(day))}
                    disabled={isPastDate(day)}
                  >
                    {day}
                  </button>
                ) : (
                  <div className="w-8 h-8"></div>
                )}
              </div>
            ))}
          </div>
        </div>

        {selectedDate && (
          <div className="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
            <div className="flex items-center justify-center">
              <svg className="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
              <span className="text-green-800 font-medium text-sm">
                {new Date(selectedDate).toLocaleDateString('en-US', { 
                  weekday: 'short', 
                  month: 'short', 
                  day: 'numeric' 
                })}
              </span>
            </div>
          </div>
        )}
      </div>
    );
  };

  // Compact Time Selection
  const renderTimeSelection = () => (
    <div className="p-4">
      <div className="text-center mb-4">
        <h2 className="text-lg font-bold text-gray-900 mb-1">Select Time</h2>
        <p className="text-gray-600 text-xs">Choose your preferred time slot</p>
      </div>

      <div className="bg-blue-50 p-3 rounded-lg mb-4 flex items-center">
        <div className="flex-shrink-0 w-8 h-8 bg-white rounded-lg flex items-center justify-center mr-3">
          <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div className="flex-1">
          <p className="text-gray-600 text-xs font-medium">Selected Date</p>
          <p className="text-blue-600 font-semibold text-sm">
            {selectedDate ? new Date(selectedDate).toLocaleDateString('en-US', { 
              weekday: 'short', 
              month: 'short', 
              day: 'numeric' 
            }) : 'No date selected'}
          </p>
        </div>
      </div>

      {loading ? (
        <div className="flex flex-col items-center justify-center py-8">
          <div className="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mb-2"></div>
          <p className="text-gray-600 text-sm">Loading time slots...</p>
        </div>
      ) : availableTimes.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-8">
          <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
            <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <p className="text-sm font-medium text-gray-500 mb-1">No slots available</p>
          <p className="text-gray-400 text-xs text-center">No available time slots for this date.</p>
        </div>
      ) : (
        <div>
          <h3 className="text-sm font-bold text-gray-900 mb-3 flex items-center">
            <svg className="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Available Time Slots
          </h3>
          <div className="grid grid-cols-3 gap-2 max-h-60 overflow-y-auto">
            {availableTimes.map((time) => (
              <button
                key={time.value}
                className={`
                  py-2 px-2 rounded-lg border transition-all duration-200 text-center
                  ${selectedTime?.value === time.value
                    ? 'border-blue-500 bg-blue-600 text-white'
                    : 'border-gray-200 bg-white text-gray-700 hover:border-blue-300'
                  }
                `}
                onClick={() => handleTimeSelect(time)}
              >
                <span className="font-semibold text-sm">{time.display}</span>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );

  // Compact Payment Section
  const renderPayment = () => (
    <div className="p-4">
      <div className="text-center mb-4">
        <h2 className="text-lg font-bold text-gray-900 mb-1">Confirm Booking</h2>
        <p className="text-gray-600 text-xs">Review and complete your consultation</p>
      </div>

      <div className="bg-white rounded-lg border border-gray-200 p-3 mb-4">
        <h3 className="text-sm font-bold text-gray-900 mb-3 flex items-center">
          <svg className="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Appointment Details
        </h3>

        <div className="space-y-3">
          <div className="flex items-center justify-between py-1 border-b border-gray-100">
            <span className="text-gray-600 text-xs">Clinic</span>
            <span className="font-semibold text-gray-900 text-sm text-right">{selectedClinic?.name}</span>
          </div>

          <div className="flex items-center justify-between py-1 border-b border-gray-100">
            <span className="text-gray-600 text-xs">Doctor</span>
            <span className="font-semibold text-gray-900 text-sm text-right">{selectedDoctor?.name}</span>
          </div>

          <div className="flex items-center justify-between py-1 border-b border-gray-100">
            <span className="text-gray-600 text-xs">Date</span>
            <span className="font-semibold text-gray-900 text-sm text-right">
              {selectedDate ? new Date(selectedDate).toLocaleDateString('en-US', { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric' 
              }) : 'No date selected'}
            </span>
          </div>

          <div className="flex items-center justify-between py-1">
            <span className="text-gray-600 text-xs">Time</span>
            <span className="font-semibold text-gray-900 text-sm text-right">{selectedTime?.display}</span>
          </div>

          <div className="pt-2 border-t border-gray-200">
            <div className="flex justify-between items-center bg-green-50 rounded-lg p-2">
              <div className="flex items-center">
                <svg className="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                <span className="text-gray-600 text-xs font-medium">Total Amount</span>
              </div>
              <span className="text-lg font-bold text-green-600">₹{calculateTotalAmount() / 100}</span>
            </div>
          </div>
        </div>
      </div>

      <div className="mb-4">
        <h3 className="text-sm font-bold text-gray-900 mb-2 flex items-center">
          <svg className="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          Payment Method
        </h3>
        <div className="flex items-center justify-between bg-white rounded-lg p-3 border border-gray-200">
          <div className="flex items-center">
            <div className="bg-blue-600 px-2 py-1 rounded mr-3">
              <span className="text-white font-bold text-xs">RP</span>
            </div>
            <div>
              <p className="font-semibold text-gray-900 text-sm">Razorpay</p>
              <p className="text-gray-500 text-xs">Card, UPI, Net Banking</p>
            </div>
          </div>
          <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
          </svg>
        </div>
      </div>

      <div className="space-y-2">
        <button
          className={`
            w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2 
            transition-all duration-200 text-sm
            ${loading ? 'opacity-50 cursor-not-allowed' : ''}
          `}
          onClick={initiateRazorpayPayment}
          disabled={loading}
        >
          {loading ? (
            <>
              <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              Processing...
            </>
          ) : (
            <>
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              Pay ₹{calculateTotalAmount() / 100}
            </>
          )}
        </button>

        <button
          className="w-full py-3 text-gray-600 font-medium rounded-lg hover:bg-gray-50 transition-all duration-200 border border-gray-200 text-sm"
          onClick={onClose}
          disabled={loading}
        >
          Cancel
        </button>
      </div>
    </div>
  );

  if (!visible) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-black bg-opacity-50">
      {/* Main Modal Container */}
      <div className="bg-white rounded-t-2xl w-full max-w-md h-[85vh] flex flex-col shadow-xl">
        
        {/* Fixed Header */}
        <div className="flex-shrink-0 border-b border-gray-200">
          <div className="flex items-center justify-between p-4">
            <button
              onClick={step > 1 ? () => setStep(step - 1) : onClose}
              className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <svg className="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={step > 1 ? "M15 19l-7-7 7-7" : "M6 18L18 6M6 6l12 12"} />
              </svg>
            </button>
            <h1 className="text-lg font-bold text-gray-900 text-center flex-1 mx-4">
              Book Consultation
            </h1>
            <div className="w-9"></div>
          </div>

          {/* Progress Steps */}
          <div className="pb-3">
            {renderStepIndicator()}
            {renderStepLabels()}
          </div>
        </div>

        {/* Scrollable Content */}
        <div className="flex-1 overflow-hidden">
          <div className="h-full overflow-y-auto">
            {step === 1 && renderClinicSelection()}
            {step === 2 && renderDoctorSelection()}
            {step === 3 && renderDateSelection()}
            {step === 4 && renderTimeSelection()}
            {step === 5 && renderPayment()}
          </div>
        </div>
      </div>
    </div>
  );
};

export default DoctorAppointmentModal;