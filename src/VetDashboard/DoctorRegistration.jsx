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
import logo from "../assets/images/logo.png";
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

  // State for multiple doctors
  const [doctors, setDoctors] = useState([
    {
      doctor_name: "",
      doctor_email: "",
      doctor_mobile: "",
      doctor_license: "",
      doctor_image: null,
      doctor_image_preview:
        "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5z=",
    },
  ]);

  const { isLoaded, loadError } = useLoadScript({
    googleMapsApiKey: "AIzaSyDEFWG5jYxYTXBouOr43vjV4Aj6WEOXBps",
    libraries: LIBRARIES,
  });

  const reverseGeocode = async (lat, lng) => {
    try {
      const response = await fetch(
        `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=AIzaSyDEFWG5jYxYTXBouOr43vjV4Aj6WEOXBps`
      );
      const data = await response.json();
      console.log(data, "data");

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
    if (doctors.length <= 1) return;
    const updatedDoctors = [...doctors];
    updatedDoctors.splice(index, 1);
    setDoctors(updatedDoctors);
  };

  const handleDoctorChange = (index, field, value) => {
    const updatedDoctors = [...doctors];
    updatedDoctors[index][field] = value;
    setDoctors(updatedDoctors);
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
    if (!acceptedTerms) {
      alert("Please accept the terms before submitting!");
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
        navigate("/login");
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
      <div className="w-full min-h-screen bg-gray-50 p-4 flex justify-center items-start overflow-auto mt-12">
        <div className="w-full max-w-4xl mx-auto">
          <div className="bg-white rounded-xl shadow-md p-6 md:p-8 mx-auto my-4">
            {/* Business Search Dialog */}
            {businessSearchDialogOpen && (
              <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div className="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                  <h2 className="text-xl font-bold mb-4 flex items-center gap-2">
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
                      className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
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
                    placeholder="Enter your business name"
                    required
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
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
                    placeholder="Enter Your Mobile Number"
                    required
                    maxLength={10}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
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
                    placeholder="Enter Email Address"
                    required
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
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
                        const place = window.autocompleteRef.getPlace();
                        if (!place.geometry) {
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
                        placeholder="Enter your address, city, or village"
                        required
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                      />
                    </Autocomplete>
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
                    placeholder="Enter city"
                    required
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
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
                    placeholder="Enter PIN code"
                    required
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
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
                    placeholder="Enter Your Fees"
                    required
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
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

                {/* Terms and Conditions */}
                <div className="mt-6">
                  <label className="flex items-start">
                    <input
                      type="checkbox"
                      checked={acceptedTerms}
                      onChange={(e) => setAcceptedTerms(e.target.checked)}
                      className="mt-1 mr-2"
                    />
                    <span className="text-sm text-gray-700">
                      I agree to the{" "}
                      <span className="text-blue-600 hover:underline cursor-pointer">
                        Privacy Policy
                      </span>
                      ,{" "}
                      <span className="text-blue-600 hover:underline cursor-pointer">
                        Document
                      </span>{" "}
                      and{" "}
                      <span className="text-blue-600 hover:underline cursor-pointer">
                        Terms & Conditions
                      </span>
                    </span>
                  </label>
                </div>
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
            <div className="mt-6 pt-4 border-t border-gray-200 items-center text-center">
              <p className="text-gray-600 text-sm">
                Already have an account?{" "}
                <Link
                  to="/login"
                  className="text-blue-600 hover:underline font-medium"
                >
                  Login here
                </Link>
              </p>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default DoctorRegistration;
