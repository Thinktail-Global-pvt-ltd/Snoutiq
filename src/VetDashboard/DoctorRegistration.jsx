import React, { useState, useEffect } from "react";
import { toast } from "react-hot-toast";
import { useNavigate, Link } from "react-router-dom";
import axios from "axios";
import {
  GoogleMap,
  Marker,
  Autocomplete,
  useLoadScript,
} from "@react-google-maps/api";
import logo from "../assets/images/logo.webp";
import Header from "../components/Header";

const LIBRARIES = ["places"];

const DoctorRegistration = () => {
  const navigate = useNavigate();
  const [isLoading, setIsLoading] = useState(false);
  const [isProfileSaving, setIsProfileSaving] = useState(false);
  const [currentPic1, setCurrentPic1] = useState(
    "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEyMCIgaGVpZ2h0PSIxMjAiIGZpbGw9IiNFMkUyRTIiIHJ4PSI2MCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0iY2VudHJhbCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk5OSIgZm9udC1zaXplPSIxNCI+Q2xpbmljPC90ZXh0Pjwvc3ZnPg=="
  );
  const [clinicPictureFile, setClinicPictureFile] = useState(null);
  const [businessSearchDialogOpen, setBusinessSearchDialogOpen] =
    useState(false);
  const [businessQuery, setBusinessQuery] = useState("");
  const [businessSuggestions, setBusinessSuggestions] = useState([]);
  const [isSearchingBusiness, setIsSearchingBusiness] = useState(false);
  const [businessDetails, setBusinessDetails] = useState(null);
  const [employeeId, setEmployeeId] = useState("");
  const [acceptedTerms, setAcceptedTerms] = useState(false);
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [showTermsModal, setShowTermsModal] = useState(false);
  const [activeTerm, setActiveTerm] = useState("");

  // Individual state variables
  const [name, setName] = useState("");
  const [bio, setBio] = useState("");
  const [chatPrice, setChatPrice] = useState("");
  const [address, setAddress] = useState("");
  const [city, setCity] = useState("");
  const [pinCode, setPinCode] = useState("");
  const [license_no, setLicense] = useState("");
  const [inhome_grooming_services, set_inhome_grooming_services] = useState(0);
  const [coordinates, setCoordinates] = useState({
    lat: null,
    lng: null,
  });
  const [mobileNumber, setMobileNumber] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");

  // State for multiple doctors
  const [doctors, setDoctors] = useState([
    {
      doctor_name: "",
      doctor_email: "",
      doctor_mobile: "",
      doctor_license: "",
      doctor_image: null,
      doctor_image_preview:
        "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEyMCIgaGVpZ2h0PSIxMjAiIGZpbGw9IiNFMkUyRTIiIHJ4PSI2MCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0iY2VudHJhbCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk5OSIgZm9udC1zaXplPSIxNCI+RG9jdG9yPC90ZXh0Pjwvc3ZnPg==",
    },
  ]);

  const { isLoaded, loadError } = useLoadScript({
    googleMapsApiKey: 'AIzaSyDEFWG5jYxYTXBouOr43vjV4Aj6WEOXBps',
    libraries: LIBRARIES,
  });

  // Validation functions
  const validateField = (name, value) => {
    let error = "";

    switch (name) {
      case "name":
        if (!value.trim()) error = "Business name is required";
        break;
      case "mobileNumber":
        if (!/^\d{10}$/.test(value))
          error = "Valid 10-digit mobile number is required";
        break;
      case "email":
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))
          error = "Valid email is required";
        break;
      case "address":
        if (!value.trim()) error = "Address is required";
        break;
      case "city":
        if (!value.trim()) error = "City is required";
        break;
      case "pinCode":
        if (!/^\d{6}$/.test(value))
          error = "Valid 6-digit PIN code is required";
        break;
      case "chatPrice":
        if (!value || value <= 0) error = "Valid consultation fee is required";
        break;
      case "password":
        if (value.length < 6) error = "Password must be at least 6 characters";
        break;
      case "confirmPassword":
        if (value !== password) error = "Passwords do not match";
        break;
      case "doctor_name":
        if (!value.trim()) error = "Doctor name is required";
        break;
      case "doctor_email":
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))
          error = "Valid doctor email is required";
        break;
      case "doctor_mobile":
        if (!/^\d{10}$/.test(value))
          error = "Valid 10-digit doctor mobile number is required";
        break;
      case "doctor_license":
        if (!value.trim()) error = "Doctor license number is required";
        break;
      default:
        break;
    }

    return error;
  };

  const validateForm = () => {
    const newErrors = {};

    // Validate clinic fields
    newErrors.name = validateField("name", name);
    newErrors.mobileNumber = validateField("mobileNumber", mobileNumber);
    newErrors.email = validateField("email", email);
    newErrors.address = validateField("address", address);
    newErrors.city = validateField("city", city);
    newErrors.pinCode = validateField("pinCode", pinCode);
    newErrors.chatPrice = validateField("chatPrice", chatPrice);
    newErrors.password = validateField("password", password);
    newErrors.confirmPassword = validateField(
      "confirmPassword",
      confirmPassword
    );

    // Validate doctors
    doctors.forEach((doctor, index) => {
      newErrors[`doctor_name_${index}`] = validateField(
        "doctor_name",
        doctor.doctor_name
      );
      newErrors[`doctor_email_${index}`] = validateField(
        "doctor_email",
        doctor.doctor_email
      );
      newErrors[`doctor_mobile_${index}`] = validateField(
        "doctor_mobile",
        doctor.doctor_mobile
      );
      newErrors[`doctor_license_${index}`] = validateField(
        "doctor_license",
        doctor.doctor_license
      );
    });

    // Check if any doctor has errors
    const hasDoctorErrors = doctors.some(
      (_, index) =>
        newErrors[`doctor_name_${index}`] ||
        newErrors[`doctor_email_${index}`] ||
        newErrors[`doctor_mobile_${index}`] ||
        newErrors[`doctor_license_${index}`]
    );

    // Check if coordinates are set
    if (!coordinates.lat || !coordinates.lng) {
      newErrors.coordinates = "Please set your location on the map";
    }

    // Check if terms are accepted
    if (!acceptedTerms) {
      newErrors.terms = "You must accept the terms and conditions";
    }

    setErrors(newErrors);

    // Return true if no errors
    return (
      Object.keys(newErrors).every((key) => !newErrors[key]) && !hasDoctorErrors
    );
  };

  const handleBlur = (field) => {
    setTouched({ ...touched, [field]: true });
  };

  const reverseGeocode = async (lat, lng) => {
    try {
      const response = await fetch(
        `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${
          'AIzaSyDEFWG5jYxYTXBouOr43vjV4Aj6WEOXBps'
        }`
      );
      const data = await response.json();

      if (data.results && data.results.length > 0) {
        const addressResult = data.results[0];
        let cityName = "";
        let pinCodeValue = "";
        let fullAddress = addressResult.formatted_address;

        // Extract city and postal code from address components
        for (const component of addressResult.address_components) {
          if (
            component.types.includes("locality") ||
            component.types.includes("administrative_area_level_2")
          ) {
            cityName = component.long_name;
          }
          if (component.types.includes("postal_code")) {
            pinCodeValue = component.long_name;
          }
        }

        return {
          address: fullAddress,
          city: cityName,
          pinCode: pinCodeValue,
          coordinates: { lat, lng },
        };
      }
      return null;
    } catch (error) {
      console.error("Reverse geocoding error:", error);
      throw error;
    }
  };

  const handleBusinessSearch = async (query) => {
    if (!query || query.length < 3) {
      setBusinessSuggestions([]);
      return;
    }

    setIsSearchingBusiness(true);
    try {
      if (!window.google?.maps?.places) {
        toast.error("Maps service not loaded. Please refresh the page.");
        return;
      }

      if (!window.placesService) {
        const mapEl = document.createElement("div");
        window.placesService = new window.google.maps.places.PlacesService(
          mapEl
        );
      }
      const request = {
        query,
        fields: [
          "place_id",
          "business_status",
          "formatted_address",
          "name",
          "geometry",
          "address_components",
          "rating",
          "user_ratings_total",
          "photos",
          "icon",
          "icon_background_color",
          "icon_mask_base_uri",
          "opening_hours",
          "types",
          "website",
          "formatted_phone_number",
        ],
      };

      const results = await new Promise((resolve, reject) => {
        window.placesService.textSearch(request, (results, status) => {
          if (status === window.google.maps.places.PlacesServiceStatus.OK)
            resolve(results);
          else if (
            status ===
            window.google.maps.places.PlacesServiceStatus.ZERO_RESULTS
          )
            resolve([]);
          else reject(new Error(`Places API error: ${status}`));
        });
      });

      setBusinessSuggestions(results);
    } catch (error) {
      console.error(error);
      toast.error("Error searching for businesses: " + error.message);
      setBusinessSuggestions([]);
    } finally {
      setIsSearchingBusiness(false);
    }
  };

  const handleBusinessSelect = async (business) => {
    try {
      if (!window.placesService) {
        const mapEl = document.createElement("div");
        window.placesService = new window.google.maps.places.PlacesService(
          mapEl
        );
      }

      const detailedRequest = {
        placeId: business.place_id,
        fields: [
          "place_id",
          "business_status",
          "formatted_address",
          "name",
          "geometry",
          "address_components",
          "rating",
          "user_ratings_total",
          "photos",
          "icon",
          "icon_background_color",
          "icon_mask_base_uri",
          "opening_hours",
          "types",
          "plus_code",
          "website",
          "formatted_phone_number",
        ],
      };

      const placeDetails = await new Promise((resolve, reject) => {
        window.placesService.getDetails(detailedRequest, (place, status) => {
          if (status === window.google.maps.places.PlacesServiceStatus.OK)
            resolve(place);
          else reject(new Error(`Places details error: ${status}`));
        });
      });

      const extractedData = {
        name: placeDetails.name,
        formatted_address: placeDetails.formatted_address,
        formatted_phone_number: placeDetails.formatted_phone_number,
        website: placeDetails.website,
        place_id: placeDetails.place_id,
        business_status: placeDetails.business_status || "OPERATIONAL",
        geometry: placeDetails.geometry,
        rating: placeDetails.rating || 0,
        user_ratings_total: placeDetails.user_ratings_total || 0,
        photos: placeDetails.photos
          ? placeDetails.photos.map((photo) => ({
              height: photo.height,
              width: photo.width,
              photo_reference: photo.getUrl({ maxWidth: 400 }),
            }))
          : [],
        icon: placeDetails.icon,
        icon_background_color: placeDetails.icon_background_color,
        icon_mask_base_uri: placeDetails.icon_mask_base_uri,
        opening_hours: placeDetails.opening_hours
          ? {
              open_now: placeDetails.opening_hours.open_now,
            }
          : { open_now: true },
        types: placeDetails.types || ["point_of_interest", "establishment"],
        plus_code: placeDetails.plus_code
          ? {
              compound_code: placeDetails.plus_code.compound_code,
              global_code: placeDetails.plus_code.global_code,
            }
          : null,
      };

      setBusinessDetails(extractedData);

      // Update form fields
      setName(extractedData.name);
      setAddress(extractedData.formatted_address);

      let cleanedPhone = placeDetails.formatted_phone_number?.replace(
        /\D/g,
        ""
      );
      if (cleanedPhone && cleanedPhone.length > 10) {
        cleanedPhone = cleanedPhone.slice(-10);
      }

      setMobileNumber(cleanedPhone || "");

      // Extract city and postal code
      let cityName = "";
      let pinCodeValue = "";

      if (placeDetails.address_components) {
        placeDetails.address_components.forEach((comp) => {
          if (
            comp.types.includes("locality") ||
            comp.types.includes("administrative_area_level_2")
          ) {
            cityName = comp.long_name;
          }
          if (comp.types.includes("postal_code")) {
            pinCodeValue = comp.long_name;
          }
        });
      }

      setCity(cityName);
      setPinCode(pinCodeValue);

      // Set coordinates
      if (placeDetails.geometry?.location) {
        setCoordinates({
          lat: placeDetails.geometry.location.lat(),
          lng: placeDetails.geometry.location.lng(),
        });
      }

      setBusinessSearchDialogOpen(false);
      setBusinessSuggestions([]);
      toast.success("Business details filled successfully!");
    } catch (error) {
      console.error("Error getting place details:", error);
      toast.error("Could not get complete business details");
    }
  };

  const handleMarkerDragEnd = async (event) => {
    const newLat = event.latLng.lat();
    const newLng = event.latLng.lng();
    setCoordinates({ lat: newLat, lng: newLng });

    try {
      const addressDetails = await reverseGeocode(newLat, newLng);
      if (addressDetails) {
        setAddress(addressDetails.address);
        setCity(addressDetails.city);
        setPinCode(addressDetails.pinCode);
      }
    } catch (error) {
      toast.error("Error updating address");
    }
  };

  const handleClinicImageChange = async (e) => {
    const file = e.target.files[0];
    if (!file || !file.type.startsWith("image/")) return;

    const compressedFile = await compressImage(file);
    setClinicPictureFile(compressedFile);

    const previewReader = new FileReader();
    previewReader.onload = () => {
      setCurrentPic1(previewReader.result);
    };
    previewReader.readAsDataURL(compressedFile);
  };

  // Helper function for image compression
  const compressImage = (file) => {
    return new Promise((resolve) => {
      const maxWidth = 400;
      const quality = 0.7;

      const reader = new FileReader();
      reader.onload = (event) => {
        const img = new Image();
        img.onload = () => {
          const canvas = document.createElement("canvas");
          const scaleSize = maxWidth / img.width;
          canvas.width = maxWidth;
          canvas.height = img.height * scaleSize;

          const ctx = canvas.getContext("2d");
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

          canvas.toBlob(
            (blob) => {
              if (blob) {
                const compressedFile = new File([blob], file.name, {
                  type: "image/jpeg",
                  lastModified: Date.now(),
                });
                resolve(compressedFile);
              }
            },
            "image/jpeg",
            quality
          );
        };
        img.src = event.target.result;
      };
      reader.readAsDataURL(file);
    });
  };

  // Functions to handle multiple doctors
  const handleAddDoctor = () => {
    setDoctors([
      ...doctors,
      {
        doctor_name: "",
        doctor_email: "",
        doctor_mobile: "",
        doctor_license: "",
        doctor_image: null,
        doctor_image_preview:
          "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEyMCIgaGVpZ2h0PSIxMjAiIGZpbGw9IiNFMkUyRTIiIHJ4PSI2MCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0iY2VudHJhbCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk5OSIgZm9udC1zaXplPSIxNCI+RG9jdG9yPC90ZXh0Pjwvc3ZnPg==",
      },
    ]);
  };

  const handleRemoveDoctor = (index) => {
    if (doctors.length <= 1) {
      toast.error("At least one doctor is required");
      return;
    }
    const updatedDoctors = [...doctors];
    updatedDoctors.splice(index, 1);
    setDoctors(updatedDoctors);

    // Clear errors for removed doctor
    const newErrors = { ...errors };
    delete newErrors[`doctor_name_${index}`];
    delete newErrors[`doctor_email_${index}`];
    delete newErrors[`doctor_mobile_${index}`];
    delete newErrors[`doctor_license_${index}`];
    setErrors(newErrors);
  };

  const handleDoctorChange = (index, field, value) => {
    const updatedDoctors = [...doctors];
    updatedDoctors[index][field] = value;
    setDoctors(updatedDoctors);

    // Validate the field
    if (touched[`${field}_${index}`]) {
      const error = validateField(field, value);
      setErrors({ ...errors, [`${field}_${index}`]: error });
    }
  };

  const handleDoctorImageChange = async (e, index) => {
    const file = e.target.files[0];
    if (!file || !file.type.startsWith("image/")) return;

    const compressedFile = await compressImage(file);
    const updatedDoctors = [...doctors];
    updatedDoctors[index].doctor_image = compressedFile;

    const previewReader = new FileReader();
    previewReader.onload = () => {
      updatedDoctors[index].doctor_image_preview = previewReader.result;
      setDoctors(updatedDoctors);
    };
    previewReader.readAsDataURL(compressedFile);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Mark all fields as touched
    const allTouched = {};
    Object.keys(errors).forEach((key) => {
      allTouched[key] = true;
    });
    setTouched(allTouched);

    // Validate the form
    if (!validateForm()) {
      toast.error("Please fix the errors in the form");
      return;
    }

    if (!acceptedTerms) {
      toast.error("Please accept the terms before submitting!");
      return;
    }

    setIsProfileSaving(true);
    try {
      const formData = new FormData();
      formData.append("clinic_name", name);
      formData.append("city", city);
      formData.append("pincode", pinCode);
      formData.append("mobile", mobileNumber);
      formData.append("email", email);
      formData.append("employee_id", "N/A");
      formData.append("password", password);
      formData.append("confirmPassword", confirmPassword);

      // Send coordinates as an array instead of JSON string
      if (coordinates.lat && coordinates.lng) {
        formData.append("coordinates[]", coordinates.lat);
        formData.append("coordinates[]", coordinates.lng);
      }

      formData.append("address", address);
      formData.append("chat_price", chatPrice);
      formData.append("bio", bio);
      formData.append("inhome_grooming_services", inhome_grooming_services);
      formData.append("acceptedTerms", acceptedTerms);

      // Add all the Google Places data if available
      if (businessDetails) {
        formData.append("place_id", businessDetails.place_id || "");
        formData.append(
          "business_status",
          businessDetails.business_status || "OPERATIONAL"
        );
        formData.append(
          "formatted_address",
          businessDetails.formatted_address || address
        );
        formData.append("lat", coordinates.lat);
        formData.append("lng", coordinates.lng);

        // Viewport data
        if (businessDetails.geometry?.viewport) {
          const viewport = businessDetails.geometry.viewport;
          formData.append("viewport_ne_lat", viewport.getNorthEast().lat());
          formData.append("viewport_ne_lng", viewport.getNorthEast().lng());
          formData.append("viewport_sw_lat", viewport.getSouthWest().lat());
          formData.append("viewport_sw_lng", viewport.getSouthWest().lng());
        }

        formData.append("icon", businessDetails.icon || "");
        formData.append(
          "icon_background_color",
          businessDetails.icon_background_color || ""
        );
        formData.append(
          "icon_mask_base_uri",
          businessDetails.icon_mask_base_uri || ""
        );

        // Send open_now as boolean (not string)
        formData.append(
          "open_now",
          businessDetails.opening_hours?.open_now ? "1" : "0"
        );

        // Send types as individual array items
        if (businessDetails.types && Array.isArray(businessDetails.types)) {
          businessDetails.types.forEach((type, index) => {
            formData.append(`types[${index}]`, type);
          });
        }

        // Send photos as individual array items
        if (businessDetails.photos && Array.isArray(businessDetails.photos)) {
          businessDetails.photos.forEach((photo, index) => {
            formData.append(`photos[${index}][height]`, photo.height);
            formData.append(`photos[${index}][width]`, photo.width);
            formData.append(
              `photos[${index}][photo_reference]`,
              photo.photo_reference
            );
          });
        }

        // Plus code
        if (businessDetails.plus_code) {
          formData.append(
            "compound_code",
            businessDetails.plus_code.compound_code || ""
          );
          formData.append(
            "global_code",
            businessDetails.plus_code.global_code || ""
          );
        }

        formData.append("rating", businessDetails.rating?.toString() || "0");
        formData.append(
          "user_ratings_total",
          businessDetails.user_ratings_total?.toString() || "0"
        );
      }

      if (clinicPictureFile) {
        formData.append("hospital_profile", clinicPictureFile);
      }

      // Add doctors data
      doctors.forEach((doctor, index) => {
        formData.append(`doctors[${index}][doctor_name]`, doctor.doctor_name);
        formData.append(`doctors[${index}][doctor_email]`, doctor.doctor_email);
        formData.append(
          `doctors[${index}][doctor_mobile]`,
          doctor.doctor_mobile
        );
        formData.append(
          `doctors[${index}][doctor_license]`,
          doctor.doctor_license
        );
        if (doctor.doctor_image) {
          formData.append(
            `doctors[${index}][doctor_image]`,
            doctor.doctor_image
          );
        }
      });

      const res = await axios.post(
        "https://snoutiq.com/backend/api/vet-registerations/store",
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );

      if (res.status === 200 || res.status === 201) {
        toast.success(res.data.message || "Profile saved successfully!");
        alert("Form submitted successfully ✅");
        window.location.reload()
        // navigate("/login");
      }
    } catch (error) {
      const errorMessage =
        error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : "Error saving profile";
      toast.error(errorMessage);
      console.log("Error details:", error.response?.data);
    } finally {
      setIsProfileSaving(false);
    }
  };

  const openTermsModal = (term) => {
    setActiveTerm(term);
    setShowTermsModal(true);
  };

  if (loadError) {
    return (
      <div className="flex justify-center items-center h-screen">
        <div>Error loading Google Maps: {loadError.message}</div>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-screen">
        <div className="flex flex-col items-center">
          <div className="w-12 h-12 border-4 border-gray-200 border-t-blue-600 rounded-full animate-spin"></div>
          <div className="mt-2">Loading...</div>
        </div>
      </div>
    );
  }

  return (
    <>
      <Header />
      <div className="w-full min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-4 flex justify-center items-start overflow-auto mt-12">
        <div className="w-full max-w-4xl mx-auto">
          <div className="bg-white rounded-xl shadow-lg p-6 md:p-8 mx-auto my-4 border border-gray-100">
            {/* Terms and Conditions Modal */}
            {showTermsModal && (
              <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div className="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
                  <div className="flex justify-between items-center mb-4">
                    <h2 className="text-xl font-bold text-gray-900">
                      {activeTerm === "privacy" && "Snoutiq — Privacy Policy"}
                      {activeTerm === "terms" &&
                        "Snoutiq — Terms of Service (Users)"}
                      {activeTerm === "community_reviews_policy" &&
                        "Snoutiq — Community & Reviews Policy"}
                      {activeTerm === "cookie_policy" &&
                        "Snoutiq — Cookie Policy"}
                      {activeTerm === "pet_death_disclaimer" &&
                        "Snoutiq — Pet Death — Detailed Disclaimer & Handling"}
                      {activeTerm === "provider_agreement_rvp" &&
                        "Snoutiq — Provider Agreement (Registered Veterinary Practitioners & Service Providers)"}
                      {activeTerm === "refund_cancellation_policy" &&
                        "Snoutiq — Refund & Cancellation Policy"}
                      {activeTerm === "third_party_services_policy" &&
                        "Snoutiq — Third-Party Services Policy"}

                      {activeTerm === "ui_text_snippets" &&
                        "Snoutiq — UI Text Snippets"}
                    </h2>
                    <button
                      onClick={() => setShowTermsModal(false)}
                      className="text-gray-500 hover:text-gray-700"
                    >
                      <svg
                        className="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M6 18L18 6M6 6l12 12"
                        />
                      </svg>
                    </button>
                  </div>

                  <div className="prose max-w-none">
                    {activeTerm === "privacy" && (
                      <div>
                        <h4 className="font-medium mb-2">
                          Effective date: 20 August 2025
                        </h4>
                        <h4 className="font-medium mb-2">1. Scope</h4>
                        <p className="text-gray-700 mb-4">
                          This Privacy Policy explains how Snoutiq collects,
                          uses, stores and discloses personal data and pet data
                          in connection with the Platform. It applies to Users,
                          RVPs, and third parties using Platform features.
                        </p>
                        <h4 className="font-medium mb-2">2. Data We Collect</h4>
                        <ul className="list-disc pl-5 mb-4 text-gray-700">
                          <li>
                            Identity & contact: name, email, phone, address.
                          </li>
                          <li>
                            Authentication: username, hashed password, sign-in
                            metadata.
                          </li>
                          <li>
                            Pet data: name, species, breed, DOB, medical
                            history, photos, vaccination records, lab reports.
                          </li>
                          <li>
                            Clinical records: consultation notes, prescriptions
                            uploaded by RVPs, invoices.
                          </li>
                          <li>
                            Payment data: masked card details, UPI reference,
                            transaction IDs (payments processed via payment
                            gateways—full card data is not stored unless
                            required and then stored securely in
                            tokenized/encrypted form).
                          </li>
                          <li>Technical data: device, IP, logs, cookies.</li>
                        </ul>
                        <h4 className="font-medium mb-2">
                          3. Purposes of Processing
                        </h4>
                        <p className="text-gray-700 mb-4">
                          We process data to: (a) enable teleconsultations; (b)
                          maintain medical records; (c) process payments and
                          refunds; (d) improve Platform features; (e) provide
                          triage/tooling features that are informational only;
                          (f) comply with legal obligations.
                        </p>
                        <h4 className="font-medium mb-2">
                          4. Legal Basis & Consent
                        </h4>
                        <p className="text-gray-700 mb-4">
                          By registering and explicitly checking consent boxes,
                          Users authorize Snoutiq to process their data for the
                          stated purposes. Users can withdraw consent where
                          legally permitted, subject to record retention
                          obligations.
                        </p>
                        <h4 className="font-medium mb-2">5. Data Sharing</h4>
                        <p className="text-gray-700 mb-4">
                          Data will be shared: (i) with RVPs to deliver
                          services; (ii) with payment processors; (iii) with
                          third-party partners (labs, pharmacies, delivery
                          partners) only with user consent; (iv) to comply with
                          law enforcement or regulatory orders.
                        </p>
                        <h4 className="font-medium mb-2"> 6. Data Security</h4>
                        <p className="text-gray-700 mb-4">
                          We implement administrative, technical and physical
                          safeguards including encryption in transit and at
                          rest, access controls, and periodic security audits.
                          RVPs must use secure credentials and multi-factor
                          authentication where available.
                        </p>
                        <h4 className="font-medium mb-2">7. Data Retention</h4>
                        <p className="text-gray-700 mb-4">
                          Medical records, consultation notes and prescriptions
                          uploaded to the Platform will be retained securely for
                          a period of 10 years from the date of the record,
                          unless longer retention is required by law.
                        </p>
                        <h4 className="font-medium mb-2">8. Rights of Users</h4>
                        <ul className="list-disc pl-5 mb-4 text-gray-700">
                          <li>
                            Access and portability: Users may request copies of
                            their data.
                          </li>
                          <li>
                            Correction: Users may request corrections to
                            inaccurate data.
                          </li>
                          <li>
                            Deletion: Requests for deletion will be honored
                            subject to legal retention obligations and except
                            where data is required for safety, fraud prevention,
                            or legal claims.
                          </li>
                          <li>
                            Objection: Users may object to direct marketing
                            processing.
                          </li>
                        </ul>
                        <h4 className="font-medium mb-2">
                          9. International Transfers
                        </h4>
                        <p className="text-gray-700 mb-4">
                          If data is transferred outside India, Snoutiq will
                          ensure adequate safeguards required by applicable law
                          are in place.
                        </p>
                        <h4 className="font-medium mb-2">
                          10. Contact & Grievance
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Data protection officer / contact: info@snoutiq.com.
                          Complaints will be addressed per our grievance
                          mechanism in the Terms of Service.
                        </p>
                      </div>
                    )}
                    {activeTerm === "terms" && (
                      <div>
                        <h4 className="font-medium mb-2">
                          Effective date: 20 August 2025
                        </h4>
                        <h4 className="font-medium mb-2">
                          1. Acceptance & Eligibility
                        </h4>
                        <p className="text-gray-700 mb-4">
                          By using the Platform, you agree to these Terms. You
                          confirm you are at least 18 years old and authorized
                          to consent for the pet(s) in question.
                        </p>
                        <h4 className="font-medium mb-2">
                          2. Platform Role & Liability (Strong Waiver)
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Snoutiq acts solely as a technology intermediary that
                          connects Users with RVPs. Snoutiq does not provide
                          clinical services, medical advice, diagnoses, or
                          prescriptions. All medical advice, diagnoses and
                          prescriptions are provided by independent RVPs. To the
                          maximum extent permitted by applicable law, Snoutiq
                          disclaims all liability for outcomes related to
                          clinical advice, prescriptions, medicine quality,
                          delivery, or RVP conduct. Users agree that RVPs are
                          solely responsible for clinical decisions and for any
                          claims arising from professional negligence or
                          malpractice.
                        </p>
                        <h4 className="font-medium mb-2">
                          3. Medical Disclaimer & Emergency
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Teleconsultations have limitations. Snoutiq is not a
                          substitute for in-person care. In emergencies or
                          life-threatening situations, immediately contact your
                          nearest veterinary emergency clinic. The Platform will
                          present a prominent emergency notice at the point of
                          scheduling.
                        </p>
                        <h4 className="font-medium mb-2">
                          4. Consultations, Prescriptions & Records
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs will create consultation notes and, if
                          applicable, upload prescriptions against the Patient
                          ID. Snoutiq itself will not generate medical
                          prescriptions. Users must provide complete and
                          accurate information. Records will be retained for 10
                          years.
                        </p>
                        <h4 className="font-medium mb-2">
                          5. Payments, Wallet & Refunds
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Payments are processed via authorized payment
                          gateways. Refunds will be credited to the User’s
                          Snoutiq wallet. Wallet funds may be used for future
                          services or withdrawn to the User’s verified bank/UPI
                          account on request and verification. Refunds will be
                          issued only where no consultation occurred (e.g., RVP
                          no-show, technical failure that prevented a scheduled
                          consultation). Refunds will be processed within 7
                          working days. Subjective dissatisfaction with clinical
                          outcomes is handled via feedback and dispute
                          resolution but not by automatic refund.
                        </p>
                        <h4 className="font-medium mb-2">
                          6. Reviews, Ratings & Feedback
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Users may rate and review RVPs. False, defamatory, or
                          misleading reviews are prohibited. Snoutiq reserves
                          the right to moderate, remove or flag reviews
                          inconsistent with the Community Policy.
                        </p>
                        <h4 className="font-medium mb-2">7. Acceptable Use</h4>
                        <p className="text-gray-700 mb-4">
                          Users must not misuse the Platform, harass RVPs,
                          attempt fraud, or solicit RVPs off-platform in ways
                          that circumvent Platform fees or safety controls.
                          Snoutiq may suspend or terminate accounts for
                          violations.
                        </p>
                        <h4 className="font-medium mb-2">
                          8. Intellectual Property
                        </h4>
                        <p className="text-gray-700 mb-4">
                          All Platform content, trademarks and software remain
                          the property of Snoutiq or licensors. Users may not
                          reproduce or commercialize content without permission.
                        </p>

                        <h4 className="font-medium mb-2">
                          9. Complaints & Dispute Resolution
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Complaints should be submitted via in-app support or
                          to info@snoutiq.com. Snoutiq will acknowledge
                          complaints within 48 hours and aim to resolve within
                          15 business days. Unresolved disputes will be subject
                          to binding arbitration in Delhi, India, under Indian
                          law, unless otherwise required by statute.
                        </p>

                        <h4 className="font-medium mb-2">
                          10. Limitations of Liability & Indemnity
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Except for liability that cannot be excluded under
                          Indian law, Snoutiq’s aggregate liability to a User
                          shall be limited to the amount paid by the User for
                          the relevant transaction in the preceding 12 months.
                          Users will indemnify Snoutiq for claims arising from
                          their misuse of the Platform or breach of these Terms.
                        </p>

                        <h4 className="font-medium mb-2">
                          11. Account Termination
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Snoutiq may suspend or terminate accounts for policy
                          violations, fraud, or abuse. Users may terminate their
                          account from settings; data deletion requests are
                          subject to retention obligations.
                        </p>

                        <h4 className="font-medium mb-2">12. Updates</h4>
                        <p className="text-gray-700 mb-4">
                          Snoutiq will notify Users of material changes to the
                          Terms at least 7 days before coming into effect.
                          Continued use constitutes acceptance.
                        </p>
                      </div>
                    )}
                    {activeTerm === "community_reviews_policy" && (
                      <div>
                        <h4 className="font-medium mb-2">
                          Effective date: 20 August 2025
                        </h4>
                        <p className="text-gray-700 mb-4">
                          We encourage honest feedback. Prohibited content
                          includes defamatory statements, private health records
                          in public reviews, hate speech, spam, or content that
                          violates privacy. Snoutiq will moderate reviews and
                          may remove or redact content that violates this
                          policy. RVPs may flag reviews for investigation;
                          unresolved disputes will be handled by Snoutiq’s
                          moderation team and its grievance process.
                        </p>
                      </div>
                    )}
                    {activeTerm === "cookie_policy" && (
                      <div>
                        <h4 className="font-medium mb-2">
                          Effective date: 20 August 2025
                        </h4>
                        <h4 className="font-medium mb-2">
                          Snoutiq uses cookies and similar technologies to
                          provide, secure and improve the Platform. We classify
                          cookies into two categories:
                        </h4>
                        <ul className="list-disc pl-5 mb-4 text-gray-700">
                          <li>
                            Essential cookies: Required for the core operation
                            of the Platform (authentication, session management,
                            security). These are always active.
                          </li>
                          <li>
                            Non-essential cookies: Used for analytics,
                            performance, and marketing. These are used only with
                            User consent and may be disabled in account
                            settings.
                          </li>
                        </ul>
                        <h4 className="font-medium mb-2">
                          Users will be presented with a cookie consent banner
                          on first use and can manage preferences in settings.{" "}
                        </h4>
                      </div>
                    )}

                    {activeTerm === "pet_death_disclaimer" && (
                      <div>
                        <h4 className="font-medium mb-2">
                          Effective date: 20 August 2025
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Pets are sentient beings with unpredictable clinical
                          courses. Telemedicine carries inherent limitations.
                          Snoutiq and RVPs will take reasonable clinical
                          measures consistent with accepted standards of care,
                          but outcomes cannot be guaranteed. In the event of a
                          pet’s death where teleconsultation was a contributing
                          or complicating factor, the following apply:
                        </p>
                        <ul className="list-disc pl-5 mb-4 text-gray-700">
                          <li>
                            Snoutiq’s role is facilitative; RVPs retain
                            professional responsibility for clinical decisions.
                          </li>
                          <li>
                            RVPs must document the full consultation history,
                            advice given, refusals or non-adherence by the
                            Owner, and any referrals to in-person care.
                          </li>
                          <li>
                            Snoutiq will preserve all records related to the
                            incident (including logs, messages, video/audio
                            records where retained) for at least 10 years and
                            provide access to lawful authorities or the User
                            upon request subject to verification.
                          </li>
                          <li>
                            In case of disputes alleging negligence, RVPs shall
                            cooperate with investigations and internal reviews.
                            Snoutiq will facilitate evidence preservation but
                            does not accept liability for professional
                            negligence by RVPs.
                          </li>
                          <li>
                            Users may raise complaints per the Complaints
                            process. Where criminal or civil liability is
                            implicated, matters will be handled under applicable
                            law and may involve external regulators.
                          </li>
                        </ul>
                      </div>
                    )}

                    {activeTerm === "provider_agreement_rvp" && (
                      <div>
                        <h4 className="font-medium mb-2">
                          Effective date: 20 August 2025
                        </h4>
                        <h4 className="font-medium mb-2">
                          1. Engagement & Scope
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs are independent contractors providing
                          professional services to Users via the Platform. This
                          Agreement governs the RVP’s use of the Platform and
                          relationship with Snoutiq. RVPs do not become
                          employees of Snoutiq by using the Platform.
                        </p>
                        <h4 className="font-medium mb-2">
                          2. Eligibility, Credentials & Onboarding
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs must be licensed under the Indian Veterinary
                          Council Act, 1984 or relevant local law. RVPs must
                          submit valid registration certificates, identity
                          proofs and any other credentials requested. Snoutiq
                          will verify credentials during onboarding and
                          periodically thereafter.
                        </p>
                        <h4 className="font-medium mb-2">
                          3. Standards of Care & Clinical Responsibility
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs must provide clinical advice consistent with the
                          accepted standard of care. RVPs retain full
                          professional responsibility for diagnoses, treatment
                          plans, and prescriptions. RVPs must recommend
                          in-person examinations where clinically necessary and
                          document the rationale for telemedicine-only
                          management.
                        </p>
                        <h4 className="font-medium mb-2">
                          4. Prescriptions & Medication
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Only RVPs may issue prescriptions. RVPs must ensure
                          prescriptions conform with applicable laws (including
                          the Drugs and Cosmetics Act, 1940) and professional
                          standards. RVPs shall upload prescriptions to the
                          Platform against the Patient ID. Snoutiq does not
                          issue prescriptions or act as a pharmacy. RVPs are
                          responsible for any prescriptions they issue and for
                          counseling Users on proper use.
                        </p>
                        <h4 className="font-medium mb-2">
                          5. Data, Record-Keeping & Retention
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs must maintain accurate clinical records and use
                          the Platform to document consultations. Snoutiq will
                          store consultation records for 10 years.
                        </p>
                        <h4 className="font-medium mb-2">
                          6. Fees, Payments & Commission
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs set their own consultation pricing. The Platform
                          will collect payments and remit RVP share per the
                          agreed commission structure. RVPs must not solicit
                          Users for off-platform payments that circumvent
                          Platform fees. Penalties for violation are detailed in
                          the No Solicitation section.
                        </p>
                        <h4 className="font-medium mb-2">
                          7. No Solicitation / Non-Circumvention
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs shall not encourage Users to take services off
                          the Platform to avoid fees or quality controls.
                          Examples of prohibited conduct include: (a) requesting
                          direct payments for clinical services, (b) sharing
                          personal contact details to rebook outside the
                          Platform after initial contact, (c) offering discounts
                          conditional on off-platform payment, or (d) re-routing
                          appointments to the RVP’s clinic without disclosing
                          fees and without recording through the Platform.
                          Violations may lead to immediate suspension,
                          withholding of payments, penalties, and termination.
                        </p>
                        <h4 className="font-medium mb-2">
                          8. Professional Indemnity & Liability
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs shall hold and maintain appropriate professional
                          indemnity insurance as required by applicable law and
                          are solely responsible for claims arising from their
                          clinical practice. RVPs shall indemnify Snoutiq for
                          claims arising from their negligence, malpractice, or
                          breach of this Agreement.
                        </p>

                        <h4 className="font-medium mb-2">
                          9. Conduct & Ethics
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs shall adhere to a professional code of conduct:
                          respectful communication, no discriminatory language,
                          no misleading advertising.
                        </p>

                        <h4 className="font-medium mb-2">
                          10. Suspension & Termination
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Grounds for suspension/termination include: fraudulent
                          credentials, repeated user complaints, malpractice
                          findings, breach of non-circumvention, or other
                          material breaches. Snoutiq may suspend access pending
                          investigation. RVPs have an opportunity to respond to
                          allegations per Platform procedures.
                        </p>

                        <h4 className="font-medium mb-2">
                          11. Data Access & Confidentiality
                        </h4>
                        <p className="text-gray-700 mb-4">
                          RVPs may access only the data necessary for care. RVPs
                          must not export or share User data outside the scope
                          of care without consent or legal compulsion. Breaches
                          must be reported immediately.
                        </p>

                        <h4 className="font-medium mb-2">
                          12. Dispute Resolution & Governing Law
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Disputes between RVPs and Snoutiq will be governed by
                          Indian law and adjudicated in courts of Delhi, unless
                          resolved by arbitration on mutual agreement.
                        </p>
                      </div>
                    )}

                    {activeTerm === "refund_cancellation_policy" && (
                      <div>
                        <h4 className="font-medium mb-2">
                          Effective date: 20 August 2025
                        </h4>
                        <h4 className="font-medium mb-2">1. Scope</h4>
                        <p className="text-gray-700 mb-4">
                          This policy applies to paid consultations,
                          subscription charges, and other paid services
                          facilitated by the Platform.
                        </p>
                        <h4 className="font-medium mb-2">2. Refund Triggers</h4>
                        <ul className="list-disc pl-5 mb-4 text-gray-700">
                          <li>
                            Refundable: Instances where a scheduled consultation
                            did not occur due to RVP no-show, technical failure
                            on RVP side, or double-charging errors.
                          </li>
                          <li>
                            Non-refundable: Dissatisfaction with clinical
                            outcome or subjective opinions about service quality
                            (handled via feedback and dispute process).
                          </li>
                        </ul>

                        <h4 className="font-medium mb-2">
                          3. Refund Mechanics
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Approved refunds will be credited to the User’s
                          Snoutiq Wallet. Wallet funds may be applied to future
                          services or withdrawn to the User’s verified bank/UPI
                          account on request. Withdrawal requests are subject to
                          verification. Refunds will be processed within{" "}
                          <b>7 working days</b> of approval.
                        </p>

                        <h4 className="font-medium mb-2">
                          4. Cancellation by User
                        </h4>
                        <p className="text-gray-700 mb-4">
                          Users may cancel scheduled consultations per the
                          in-app scheduling rules. If cancelled before the RVP
                          joins and the Platform classifies it as a
                          no-consultation, refund rules above apply.
                        </p>
                        <h4 className="font-medium mb-2">5. Disputes</h4>
                        <p className="text-gray-700 mb-4">
                          In disputes regarding refunds, Snoutiq will review
                          logs and RVP notes. Decisions will be communicated in
                          writing and are final subject to applicable law.
                        </p>
                      </div>
                    )}

                    {activeTerm === "third_party_services_policy" && (
                      <div>
                        <h4 className="font-medium mb-2">
                          Effective date: 20 August 2025
                        </h4>
                        <h4 className="font-medium mb-2">
                          Snoutiq may integrate labs, pharmacies, delivery
                          partners and other service providers. These third
                          parties are independent and responsible for their own
                          services. Data sharing with partners occurs only with
                          User consent and as necessary to deliver the service.
                          Snoutiq will engage vendors under written agreements
                          that include security and confidentiality obligations.
                          Snoutiq is not liable for the independent actions of
                          third-party service providers beyond ensuring
                          reasonable vendor selection and contractual
                          protections.
                        </h4>
                      </div>
                    )}

                    {activeTerm === "ui_text_snippets" && (
                      <div>
                        <h4 className="font-medium mb-2">
                          Effective date: 20 August 2025
                        </h4>
                        <h4 className="font-medium mb-2">Consent Checkbox</h4>
                        <p className="text-gray-700 mb-4">
                          I have read and agree to Snoutiq’s Terms of Service
                          and Privacy Policy. I understand that
                          teleconsultations have limitations and are not a
                          replacement for in-person emergency care.
                        </p>
                        <h4 className="font-medium mb-2">Emergency Banner</h4>
                        <p className="text-gray-700 mb-4">
                          ⚠️ Emergency? Please visit your nearest veterinary
                          clinic immediately. Snoutiq is not a replacement for
                          emergency care.
                        </p>
                        <h4 className="font-medium mb-2">Triage Disclaimer</h4>
                        <p className="text-gray-700 mb-4">
                          Triage tools are informational only and do not
                          constitute veterinary advice, diagnosis, or treatment.
                          For clinical concerns, book a consultation with a
                          Registered Veterinary Practitioner.
                        </p>
                        <h4 className="font-medium mb-2">
                          Cookie Consent Banner
                        </h4>
                        <p className="text-gray-700 mb-4">
                          We use essential cookies for site operation. With your
                          consent, we also use non-essential cookies for
                          analytics and improvements. [Accept All] [Manage
                          Preferences]
                        </p>
                      </div>
                    )}
                  </div>

                  <div className="mt-6 flex justify-end">
                    <button
                      onClick={() => setShowTermsModal(false)}
                      className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                      Close
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* Business Search Dialog */}
            {businessSearchDialogOpen && (
              <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div className="bg-white rounded-xl p-6 w-full max-w-md mx-4 shadow-xl">
                  <h2 className="text-xl font-bold mb-4 flex items-center gap-2 text-blue-700">
                    <svg
                      className="w-6 h-6 text-blue-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-8 0H3m2 0h4M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                      />
                    </svg>
                    Find your business
                  </h2>
                  <p className="text-gray-600 text-sm mb-4">
                    Search for your business to automatically fill in your
                    details
                  </p>

                  <div className="relative mb-4">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg
                        className="h-5 w-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                      </svg>
                    </div>
                    <input
                      type="text"
                      value={businessQuery}
                      onChange={(e) => {
                        setBusinessQuery(e.target.value);
                        handleBusinessSearch(e.target.value);
                      }}
                      placeholder="Enter your business name"
                      className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    />
                    {isSearchingBusiness && (
                      <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <div className="w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                      </div>
                    )}
                  </div>

                  {businessSuggestions.length > 0 ? (
                    <div className="max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                      <ul className="divide-y divide-gray-200">
                        {businessSuggestions.map((business, index) => (
                          <li key={index}>
                            <button
                              onClick={() => handleBusinessSelect(business)}
                              className="w-full text-left p-3 hover:bg-gray-50 transition-colors"
                            >
                              <p className="font-medium text-gray-900">
                                {business.name}
                              </p>
                              <p className="text-sm text-gray-600 truncate">
                                {business.formatted_address}
                              </p>
                            </button>
                          </li>
                        ))}
                      </ul>
                    </div>
                  ) : businessQuery.length >= 3 && !isSearchingBusiness ? (
                    <p className="text-gray-500 text-sm text-center py-4">
                      No businesses found. Try a different search term.
                    </p>
                  ) : null}

                  <div className="mt-4 flex justify-end">
                    <button
                      onClick={() => setBusinessSearchDialogOpen(false)}
                      className="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* Header Section */}
            <div className="pb-4 border-b border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
              <div className="flex items-center gap-4">
                <div className="w-24 h-24 rounded-lg flex items-center justify-center">
                  <img
                    src={logo}
                    alt="Snoutiq Logo"
                    className="w-full h-full object-contain"
                  />
                </div>
                <div>
                  <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">
                    Doctor Registration
                  </h1>
                  <p className="text-gray-600 mt-1 hidden sm:block">
                    Complete your profile to start your practice
                  </p>
                </div>
              </div>

              <button
                onClick={() => setBusinessSearchDialogOpen(true)}
                className="flex items-center gap-2 px-4 py-2 border border-blue-600 text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition-colors"
              >
                <svg
                  className="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-8 0H3m2 0h4M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                  />
                </svg>
                Find Business
              </button>
            </div>

            {/* Clinic Image Upload */}
            <div className="flex flex-col items-center my-6">
              <div className="relative w-32 h-32 rounded-full border-2 border-dashed border-gray-300 bg-gray-100 overflow-hidden hover:border-blue-500 hover:bg-blue-50 transition-colors">
                <img
                  src={currentPic1}
                  alt="Clinic"
                  className="w-full h-full object-cover"
                />
                <input
                  type="file"
                  accept="image/*"
                  className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                  onChange={handleClinicImageChange}
                />
                <label className="absolute bottom-2 right-2 bg-white border-2 border-blue-600 rounded-full w-8 h-8 flex items-center justify-center cursor-pointer shadow-md hover:bg-gray-50">
                  <svg
                    className="w-4 h-4 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                    />
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                  </svg>
                  <input
                    type="file"
                    accept="image/*"
                    className="hidden"
                    onChange={handleClinicImageChange}
                  />
                </label>
              </div>
              <p className="text-gray-600 text-sm mt-2">
                Drag & drop or click to upload Clinic
              </p>
            </div>

            {/* Form */}
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Business Name */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Business Name *
                  </label>
                  <input
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    onBlur={() => handleBlur("name")}
                    placeholder="Enter your business name"
                    required
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.name && touched.name
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                  />
                  {errors.name && touched.name && (
                    <p className="text-red-500 text-xs mt-1">{errors.name}</p>
                  )}
                </div>

                {/* Mobile Number */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Mobile Number *
                  </label>
                  <input
                    type="tel"
                    value={mobileNumber}
                    onChange={(e) => setMobileNumber(e.target.value)}
                    onBlur={() => handleBlur("mobileNumber")}
                    placeholder="Enter Your Mobile Number"
                    required
                    maxLength={10}
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.mobileNumber && touched.mobileNumber
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                  />
                  {errors.mobileNumber && touched.mobileNumber && (
                    <p className="text-red-500 text-xs mt-1">
                      {errors.mobileNumber}
                    </p>
                  )}
                </div>

                {/* Email */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Email ID *
                  </label>
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    onBlur={() => handleBlur("email")}
                    placeholder="Enter Email Address"
                    required
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.email && touched.email
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                  />
                  {errors.email && touched.email && (
                    <p className="text-red-500 text-xs mt-1">{errors.email}</p>
                  )}
                </div>

                {/* Address */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Address *
                  </label>
                  {isLoaded && (
                    <Autocomplete
                      onLoad={(autocompleteInstance) => {
                        window.autocompleteRef = autocompleteInstance;
                      }}
                      onPlaceChanged={() => {
                        const place = window.autocompleteRef?.getPlace();
                        if (!place?.geometry) {
                          alert("Please select a place from suggestions.");
                          return;
                        }

                        setAddress(place.formatted_address);
                        setCoordinates({
                          lat: place.geometry.location.lat(),
                          lng: place.geometry.location.lng(),
                        });
                        const addressComponents =
                          place.address_components || [];
                        const cityComponent = addressComponents.find(
                          (c) =>
                            c.types.includes("locality") ||
                            c.types.includes("administrative_area_level_2")
                        );
                        const pinCodeComponent = addressComponents.find((c) =>
                          c.types.includes("postal_code")
                        );
                        setCity(cityComponent ? cityComponent.long_name : "");
                        setPinCode(
                          pinCodeComponent ? pinCodeComponent.long_name : ""
                        );
                      }}
                    >
                      <input
                        type="text"
                        value={address}
                        onChange={(e) => setAddress(e.target.value)}
                        onBlur={() => handleBlur("address")}
                        placeholder="Enter your address, city, or village"
                        required
                        className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                          errors.address && touched.address
                            ? "border-red-500"
                            : "border-gray-300"
                        }`}
                      />
                    </Autocomplete>
                  )}
                  {errors.address && touched.address && (
                    <p className="text-red-500 text-xs mt-1">
                      {errors.address}
                    </p>
                  )}
                </div>

                {/* City */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    City *
                  </label>
                  <input
                    type="text"
                    value={city}
                    onChange={(e) => setCity(e.target.value)}
                    onBlur={() => handleBlur("city")}
                    placeholder="Enter city"
                    required
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.city && touched.city
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                  />
                  {errors.city && touched.city && (
                    <p className="text-red-500 text-xs mt-1">{errors.city}</p>
                  )}
                </div>

                {/* PIN Code */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    PIN Code *
                  </label>
                  <input
                    type="text"
                    value={pinCode}
                    onChange={(e) => setPinCode(e.target.value)}
                    onBlur={() => handleBlur("pinCode")}
                    placeholder="Enter PIN code"
                    required
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.pinCode && touched.pinCode
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                  />
                  {errors.pinCode && touched.pinCode && (
                    <p className="text-red-500 text-xs mt-1">
                      {errors.pinCode}
                    </p>
                  )}
                </div>

                {/* Consultation Fee */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Consultation Fee (INR) *
                  </label>
                  <input
                    type="number"
                    value={chatPrice}
                    onChange={(e) => setChatPrice(e.target.value)}
                    onBlur={() => handleBlur("chatPrice")}
                    placeholder="Enter Your Fees"
                    required
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.chatPrice && touched.chatPrice
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                  />
                  {errors.chatPrice && touched.chatPrice && (
                    <p className="text-red-500 text-xs mt-1">
                      {errors.chatPrice}
                    </p>
                  )}
                </div>

                {/* At Home Services */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Do you offer at home services? *
                  </label>
                  <select
                    value={inhome_grooming_services}
                    onChange={(e) =>
                      set_inhome_grooming_services(e.target.value)
                    }
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  >
                    <option value={1}>Yes</option>
                    <option value={0}>No</option>
                  </select>
                </div>
              </div>

              {/* Bio */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Comments (Optional)
                </label>
                <textarea
                  rows={3}
                  value={bio}
                  onChange={(e) => setBio(e.target.value)}
                  placeholder="Write about your medical experience and specialization"
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                />
              </div>

              {/* Doctors Section */}
              <div className="mt-8">
                <div className="flex justify-between items-center mb-6">
                  <h2 className="text-xl font-bold text-gray-900">
                    Doctors Information
                  </h2>
                  <button
                    type="button"
                    onClick={handleAddDoctor}
                    className="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                  >
                    <svg
                      className="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M12 4v16m8-8H4"
                      />
                    </svg>
                    Add Doctor
                  </button>
                </div>

                {doctors.map((doctor, index) => (
                  <div
                    key={index}
                    className="border border-gray-200 rounded-lg p-6 mb-6"
                  >
                    <div className="flex justify-between items-start mb-6">
                      <h3 className="text-lg font-semibold text-gray-900">
                        Doctor {index + 1}
                      </h3>
                      {doctors.length > 1 && (
                        <button
                          type="button"
                          onClick={() => handleRemoveDoctor(index)}
                          className="text-red-600 hover:text-red-800 transition-colors"
                        >
                          <svg
                            className="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                          </svg>
                        </button>
                      )}
                    </div>

                    <div className="flex flex-col items-center mb-6">
                      <div className="relative w-24 h-24 rounded-full border-2 border-dashed border-gray-300 bg-gray-100 overflow-hidden hover:border-blue-500 hover:bg-blue-50 transition-colors">
                        <img
                          src={doctor.doctor_image_preview}
                          alt={`Doctor ${index + 1}`}
                          className="w-full h-full object-cover"
                        />
                        <input
                          type="file"
                          accept="image/*"
                          className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                          onChange={(e) => handleDoctorImageChange(e, index)}
                        />
                        <label className="absolute bottom-1 right-1 bg-white border-2 border-blue-600 rounded-full w-6 h-6 flex items-center justify-center cursor-pointer shadow-sm hover:bg-gray-50">
                          <svg
                            className="w-3 h-3 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                            />
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                          </svg>
                          <input
                            type="file"
                            accept="image/*"
                            className="hidden"
                            onChange={(e) => handleDoctorImageChange(e, index)}
                          />
                        </label>
                      </div>
                      <p className="text-gray-600 text-xs mt-2">Doctor Photo</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      {/* Doctor Name */}
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Doctor Name *
                        </label>
                        <input
                          type="text"
                          value={doctor.doctor_name}
                          onChange={(e) =>
                            handleDoctorChange(
                              index,
                              "doctor_name",
                              e.target.value
                            )
                          }
                          placeholder="Enter doctor name"
                          required
                          className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        />
                      </div>

                      {/* Doctor Email */}
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Doctor Email *
                        </label>
                        <input
                          type="email"
                          value={doctor.doctor_email}
                          onChange={(e) =>
                            handleDoctorChange(
                              index,
                              "doctor_email",
                              e.target.value
                            )
                          }
                          placeholder="Enter doctor email"
                          required
                          className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        />
                      </div>

                      {/* Doctor Mobile */}
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Doctor Mobile *
                        </label>
                        <input
                          type="tel"
                          value={doctor.doctor_mobile}
                          onChange={(e) =>
                            handleDoctorChange(
                              index,
                              "doctor_mobile",
                              e.target.value
                            )
                          }
                          placeholder="Enter doctor mobile"
                          required
                          maxLength={10}
                          className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        />
                      </div>

                      {/* Doctor License */}
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Doctor License No. *
                        </label>
                        <input
                          type="text"
                          value={doctor.doctor_license}
                          onChange={(e) =>
                            handleDoctorChange(
                              index,
                              "doctor_license",
                              e.target.value
                            )
                          }
                          placeholder="Enter doctor license number"
                          required
                          className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        />
                      </div>
                    </div>
                  </div>
                ))}

                {/* Employee ID */}
                {/* <div className="mt-6">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Employee ID *
                  </label>
                  <input
                    type="text"
                    value={employeeId}
                    onChange={(e) => setEmployeeId(e.target.value)}
                    placeholder="Enter Your Employee ID"
                    required
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                </div> */}
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Password *
                  </label>
                  <div className="relative">
                    <input
                      type="password"
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      required
                      placeholder="Enter your password (min. 6 characters)"
                      className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                        errors.password && touched.password
                          ? "border-red-500"
                          : "border-gray-300"
                      }`}
                    />
                    {errors.password && touched.password && (
                      <p className="text-red-500 text-xs mt-1">
                        {errors.password}
                      </p>
                    )}
                  </div>
                </div>

                {/* Confirm Password */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Confirm Password *
                  </label>
                  <div className="relative">
                    <input
                      type="password"
                      value={confirmPassword}
                      onChange={(e) => setConfirmPassword(e.target.value)}
                      required
                      placeholder="Confirm your password"
                      className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                        errors.confirmPassword && touched.confirmPassword
                          ? "border-red-500"
                          : "border-gray-300"
                      }`}
                    />
                    {errors.confirmPassword && touched.confirmPassword && (
                      <p className="text-red-500 text-xs mt-1">
                        {errors.confirmPassword}
                      </p>
                    )}
                  </div>
                </div>
              </div>
              {/* Terms and Conditions */}
              <div className="mt-6">
                <label className="flex items-start">
                  <input
                    type="checkbox"
                    checked={acceptedTerms}
                    onChange={(e) => setAcceptedTerms(e.target.checked)}
                    className="mt-1 mr-2"
                  />
                  <span className="text-sm text-gray-700 leading-relaxed">
                    By continuing, I confirm that I have read and agree to
                    SnoutIQ’s:{" "}
                    <span
                      onClick={() => {
                        setActiveTerm("provider_agreement_rvp");
                        setShowTermsModal(true);
                      }}
                      className="text-blue-600 hover:underline cursor-pointer"
                    >
                      Provider Agreement (RVP)
                    </span>
                    {/* {/* <ul className="list-disc pl-6 mt-2 space-y-1"> */}
                      {/* <li>
                        <span
                          onClick={() => {
                            setActiveTerm("privacy");
                            setShowTermsModal(true);
                          }}
                          className="text-blue-600 hover:underline cursor-pointer"
                        >
                          Privacy Policy
                        </span>
                      </li> */}
                      {/* <li>
                        <span
                          onClick={() => {
                            setActiveTerm("terms");
                            setShowTermsModal(true);
                          }}
                          className="text-blue-600 hover:underline cursor-pointer"
                        >
                          Terms & Conditions
                        </span>
                      </li> */}
                      {/* <li>
                        <span
                          onClick={() => {
                            setActiveTerm("community_reviews_policy");
                            setShowTermsModal(true);
                          }}
                          className="text-blue-600 hover:underline cursor-pointer"
                        >
                          Community Reviews Policy
                        </span>
                      </li> */}
                      {/* <li>
                        <span
                          onClick={() => {
                            setActiveTerm("cookie_policy");
                            setShowTermsModal(true);
                          }}
                          className="text-blue-600 hover:underline cursor-pointer"
                        >
                          Cookie Policy
                        </span>
                      </li> */}
                      {/* <li>
                        <span
                          onClick={() => {
                            setActiveTerm("pet_death_disclaimer");
                            setShowTermsModal(true);
                          }}
                          className="text-blue-600 hover:underline cursor-pointer"
                        >
                          Pet Death Disclaimer
                        </span>
                      </li> */}
                      {/* <li>
                        <span
                          onClick={() => {
                            setActiveTerm("provider_agreement_rvp");
                            setShowTermsModal(true);
                          }}
                          className="text-blue-600 hover:underline cursor-pointer"
                        >
                          Provider Agreement (RVP)
                        </span>
                      </li> */}
                      {/* <li>
                        <span
                          onClick={() => {
                            setActiveTerm("third_party_services_policy");
                            setShowTermsModal(true);
                          }}
                          className="text-blue-600 hover:underline cursor-pointer"
                        >
                          Third-Party Services Policy
                        </span>
                      </li> */}
                      {/* <li>
                        <span
                          onClick={() => {
                            setActiveTerm("refund_cancellation_policy");
                            setShowTermsModal(true);
                          }}
                          className="text-blue-600 hover:underline cursor-pointer"
                        >
                          Refund & Cancellation Policy
                        </span>
                      </li> */}
                      {/* <li>
                        <span
                          onClick={() => {
                            setActiveTerm("ui_text_snippets");
                            setShowTermsModal(true);
                          }}
                          className="text-blue-600 hover:underline cursor-pointer"
                        >
                          UI Text Snippets
                        </span>
                      </li> */}
                    {/* </ul>  */}
                  </span>
                </label>
              </div>

              {/* Google Maps for fine-tuning location */}
              {isLoaded && coordinates.lat && coordinates.lng && (
                <div className="mt-8">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Fine-tune Your Location
                  </label>
                  <GoogleMap
                    mapContainerStyle={{
                      height: "300px",
                      width: "100%",
                      borderRadius: "12px",
                      border: "1px solid #e5e7eb",
                    }}
                    center={coordinates}
                    zoom={15}
                  >
                    <Marker
                      position={coordinates}
                      draggable
                      onDragEnd={handleMarkerDragEnd}
                    />
                  </GoogleMap>
                  <p className="text-sm text-gray-600 mt-2 italic">
                    Drag the marker to precisely set your location
                  </p>
                </div>
              )}

              {/* Submit Button */}
              <div className="flex justify-center mt-8">
                <button
                  type="submit"
                  disabled={isProfileSaving}
                  className="px-8 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed min-w-[220px]"
                >
                  {isProfileSaving ? (
                    <div className="flex items-center justify-center">
                      <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                      Saving...
                    </div>
                  ) : (
                    "Complete Registration"
                  )}
                </button>
              </div>
            </form>
            {/* <div className="mt-6 pt-4 border-t border-gray-200 items-center text-center">
              <p className="text-gray-600 text-sm">
                Already have an account?{" "}
                <Link
                  to="/login"
                  className="text-blue-600 hover:underline font-medium"
                >
                  Login here
                </Link>
              </p>
            </div> */}
            <div className="mt-8 pt-6 border-t border-gray-200">
  <h3 className="text-lg font-semibold text-gray-800 text-center mb-4">
    Contact Us
  </h3>

  <div className="flex flex-col sm:flex-row items-center justify-center gap-6 text-gray-700">
    {/* Email */}
    <div className="flex items-center gap-2 group">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        className="h-5 w-5 text-blue-600 group-hover:text-blue-800 transition-colors"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth="2"
          d="M16 12H8m8-4H8m8 8H8m12-12H4a2 2 0 00-2 2v12a2
          2 0 002 2h16a2 2 0 002-2V6a2
          2 0 00-2-2z"
        />
      </svg>
      <a
        href="mailto:info@snoutiq.com"
        className="font-medium hover:text-blue-600 transition-colors"
      >
        info@snoutiq.com
      </a>
    </div>

    {/* Divider */}
    <div className="hidden sm:block h-6 w-px bg-gray-300"></div>

    {/* Phone */}
    <div className="flex items-center gap-2 group">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        className="h-5 w-5 text-green-600 group-hover:text-green-800 transition-colors"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth="2"
          d="M3 5a2 2 0 012-2h2.586a1 1 0 01.707.293l2.414
          2.414a1 1 0 01.293.707V9a1 1 0
          01-1 1H8a1 1 0 00-1 1v2c0
          5.523 4.477 10 10 10h2a1 1 0
          001-1v-1.586a1 1 0
          00-.293-.707l-2.414-2.414a1 1 0
          00-.707-.293H15a1 1 0
          01-1-1v-2a1 1 0 011-1h3.586a1
          1 0 01.707.293l2.414 2.414a1
          1 0 01.293.707V21a2 2 0
          01-2 2h-2C10.477 23 5 17.523
          5 11V9a2 2 0 012-2h2a1 1 0
          011-1V5a2 2 0 00-2-2H5a2 2 0
          00-2 2z"
        />
      </svg>
      <a
        href="tel:+918588007466"
        className="font-medium hover:text-green-600 transition-colors"
      >
        +91 8588007466
      </a>
    </div>
  </div>
</div>

          </div>
        </div>
      </div>
    </>
  );
};

export default DoctorRegistration;
