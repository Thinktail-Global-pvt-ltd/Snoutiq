import React, { useState, useEffect, useContext } from "react";
import { toast } from "react-hot-toast";
import { AuthContext } from "../auth/AuthContext";
import axios from "../axios";

const PetDetailsModal = ({ onComplete, updateUser, token, user }) => {
  const [formData, setFormData] = useState({
    petType: "",
    petName: "",
    petGender: "",
    homeVisit: "",
    petAgeYears: "",
    petAgeMonths: "",
    petBreed: "",
    petDoc1: null,
    petDoc2: null,
  });

  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [breedOptions, setBreedOptions] = useState([]);
  const [breedImage, setBreedImage] = useState(null);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [isLoadingImage, setIsLoadingImage] = useState(false);
  const [showBreedModal, setShowBreedModal] = useState(false);

  const validate = () => {
    const newErrors = {};

    if (!formData.petType) newErrors.petType = "Please select pet type";
    if (!formData.petName.trim()) newErrors.petName = "Pet name is required";
    if (!formData.petGender) newErrors.petGender = "Please select gender";
    if (!formData.homeVisit) newErrors.homeVisit = "Please select option";

    // ✅ Pet Age Validation
    const years = parseInt(formData.petAgeYears || 0, 10);
    const months = parseInt(formData.petAgeMonths || 0, 10);

    if (isNaN(years) || years < 0) {
      newErrors.petAgeYears = "Enter valid years";
    }
    if (isNaN(months) || months < 0 || months > 11) {
      newErrors.petAgeMonths = "Months must be between 0–11";
    }
    if (years === 0 && months === 0) {
      newErrors.petAgeYears = "Enter age in years or months";
      newErrors.petAgeMonths = "Enter age in years or months";
    }

    if (!formData.petBreed) newErrors.petBreed = "Please select pet breed";

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  useEffect(() => {
    const fetchBreeds = async () => {
      if (formData.petType === "Dog") {
        setLoadingBreeds(true);
        try {
          const res = await axios.get(
            "https://snoutiq.com/backend/api/dog-breeds/all"
          );

          if (res.data && res.data.breeds) {
            const breedsData = res.data.breeds;
            const breedList = [];

            Object.entries(breedsData).forEach(([breed, subBreeds]) => {
              if (Array.isArray(subBreeds) && subBreeds.length > 0) {
                subBreeds.forEach((sub) => {
                  breedList.push(`${sub} ${breed}`);
                });
              } else {
                breedList.push(breed);
              }
            });

            setBreedOptions(breedList.sort());
          } else {
            console.error("Invalid response format for breeds");
            setBreedOptions([]);
          }
        } catch (err) {
          console.error("Failed to fetch breeds", err);
          toast.error("Failed to load dog breeds");
          setBreedOptions([]);
        } finally {
          setLoadingBreeds(false);
        }
      } else if (formData.petType === "Cat") {
        setBreedOptions([
          "Siamese",
          "Persian",
          "Maine Coon",
          "Bengal",
          "Sphynx",
          "British Shorthair",
          "Ragdoll",
          "Abyssinian",
          "Scottish Fold",
        ]);
      } else {
        setBreedOptions([]);
      }
    };

    if (formData.petType) {
      fetchBreeds();
    }
  }, [formData.petType]);

  const toTitleCase = (str) => {
    return str.replace(/\w\S*/g, (txt) => {
      return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
    });
  };

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) setErrors((prev) => ({ ...prev, [field]: null }));
  };

  const handleBlur = (field) => {
    setTouched((prev) => ({ ...prev, [field]: true }));
  };

  const handleFileChange = (field, files) => {
    if (files && files.length > 0) {
      setFormData((prev) => ({ ...prev, [field]: files[0] }));
    }
  };

// const handleSubmit = async () => {
//   if (!validate()) {
//     setTouched(Object.fromEntries(Object.keys(formData).map((k) => [k, true])));
//     toast.error("Please fix the errors");
//     return;
//   }

//   setIsLoading(true);
//   try {
//     const submitData = new FormData();
//     submitData.append("user_id", user?.id);
//     submitData.append("pet_type", formData.petType);
//     submitData.append("pet_name", formData.petName.trim());
//     submitData.append("pet_gender", formData.petGender);
//     submitData.append("home_visit", formData.homeVisit);

//     // Convert years+months → total months
//     const years = parseInt(formData.petAgeYears || 0, 10);
//     const months = parseInt(formData.petAgeMonths || 0, 10);
//     const totalMonths = years * 12 + months;
//     submitData.append("pet_age", totalMonths);

//     submitData.append("breed", formData.petBreed);
//     if (formData.petDoc1) submitData.append("pet_doc1", formData.petDoc1);
//     if (formData.petDoc2) submitData.append("pet_doc2", formData.petDoc2);

//     const res = await axios.post(
//       "https://snoutiq.com/backend/api/auth/register",
//       submitData,
//       { headers: { "Content-Type": "multipart/form-data" } }
//     );

//     if (res.data.message && res.data.message.includes("successfully")) {
//       toast.success("Pet profile saved successfully!");

//       if (res.data.user) {
//         updateUser(res.data.user);
//         console.log("Updated user from registration response:", res.data.user);
        
//         if (onComplete) onComplete();
//         return;
//       }
//       try {
//         const userRes = await axios.get(
//           `https://snoutiq.com/backend/api/petparents/${user.id}`,
//           { headers: { Authorization: `Bearer ${token}` } }
//         );

//         console.log("Fetch user response:", userRes.data);

//         let updatedUser = null;
        
//         if (userRes.data.status === "success" && userRes.data.user) {
//           updatedUser = userRes.data.user;
//         } else if (userRes.data.user) {
//           updatedUser = userRes.data.user;
//         } else if (userRes.data && !userRes.data.status) {
//           updatedUser = userRes.data;
//         }

//         if (updatedUser) {
//           updateUser(updatedUser);
//           toast.success("Pet details updated!");
//           console.log("Updated user:", updatedUser);
          
//           if (onComplete) onComplete();
//         } else {
//           console.error("No user data found in response:", userRes.data);
//           toast.error("Failed to update user data - no user found");
          
//           if (onComplete) onComplete();
//         }
//       } catch (fetchError) {
//         console.error("Error fetching updated user:", fetchError);
//         toast.error("Registration successful, but failed to fetch updated data");
        
//         if (onComplete) onComplete();
//       }
//     } else {
//       console.error("Registration failed:", res.data);
//       toast.error(res.data.message || "Failed to save pet data");
//     }
//   } catch (error) {
//     console.error("Registration error:", error);
    
//     if (error.response) {
//       toast.error(`Server error: ${error.response.data?.message || 'Registration failed'}`);
//     } else if (error.request) {
//       toast.error("Network error: Please check your connection");
//     } else {
//       toast.error("Something went wrong!");
//     }
//   } finally {
//     setIsLoading(false);
//   }
  
// };


const handleSubmit = async () => {
  if (!validate()) {
    setTouched(Object.fromEntries(Object.keys(formData).map((k) => [k, true])));
    toast.error("Please fix the errors");
    return;
  }

  setIsLoading(true);
  try {
    const submitData = new FormData();
    submitData.append("user_id", user?.id);
    submitData.append("pet_type", formData.petType);
    submitData.append("pet_name", formData.petName.trim());
    submitData.append("pet_gender", formData.petGender);
    submitData.append("home_visit", formData.homeVisit);

    // ✅ Role ko force send karo
    submitData.append("role", "pet");

    // Convert years+months → total months
    const years = parseInt(formData.petAgeYears || 0, 10);
    const months = parseInt(formData.petAgeMonths || 0, 10);
    const totalMonths = years * 12 + months;
    submitData.append("pet_age", totalMonths);

    submitData.append("breed", formData.petBreed);
    if (formData.petDoc1) submitData.append("pet_doc1", formData.petDoc1);
    if (formData.petDoc2) submitData.append("pet_doc2", formData.petDoc2);

    const res = await axios.post(
      "https://snoutiq.com/backend/api/auth/register",
      submitData,
      { headers: { "Content-Type": "multipart/form-data" } }
    );

    if (res.data.message && res.data.message.includes("successfully")) {
      toast.success("Pet profile saved successfully!");

      if (res.data.user) {
        // ✅ role ko force set karna for safety
        updateUser({ ...res.data.user, role: "pet" });

        console.log("Updated user from registration response:", res.data.user);
        if (onComplete) onComplete();
        return;
      }

      // agar direct user return nahi hua toh fetch karo
      try {
        const userRes = await axios.get(
          `https://snoutiq.com/backend/api/petparents/${user.id}`,
          { headers: { Authorization: `Bearer ${token}` } }
        );

        let updatedUser = userRes.data?.user || userRes.data;
        if (updatedUser) {
          updateUser({ ...updatedUser, role: "pet" }); // ✅ ensure role
          toast.success("Pet details updated!");
          if (onComplete) onComplete();
        }
      } catch (fetchError) {
        console.error("Error fetching updated user:", fetchError);
        toast.error("Registration successful, but failed to fetch updated data");
        if (onComplete) onComplete();
      }
    } else {
      console.error("Registration failed:", res.data);
      toast.error(res.data.message || "Failed to save pet data");
    }
  } catch (error) {
    console.error("Registration error:", error);
    if (error.response) {
      toast.error(`Server error: ${error.response.data?.message || 'Registration failed'}`);
    } else if (error.request) {
      toast.error("Network error: Please check your connection");
    } else {
      toast.error("Something went wrong!");
    }
  } finally {
    setIsLoading(false);
  }
};


  return (
    <div className="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="p-6 border-b border-gray-200">
          <h2 className="text-xl font-bold mb-2">Complete Your Pet Profile</h2>
          <p className="text-gray-600 text-sm">
            Please fill your pet's details to unlock the full Snoutiq
            experience. You cannot access the dashboard until this form is
            complete.
          </p>
        </div>

        {/* Scrollable Content */}
        <div className="overflow-y-auto px-6 py-4 flex-1 grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Pet Type */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Pet Type *
            </label>
            <select
              value={formData.petType}
              onChange={(e) => handleInputChange("petType", e.target.value)}
              onBlur={() => handleBlur("petType")}
              className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                errors.petType && touched.petType
                  ? "border-red-500"
                  : "border-gray-300"
              }`}
            >
              <option value="">Select Pet Type</option>
              <option value="Dog">Dog</option>
              <option value="Cat">Cat</option>
              <option value="Other">Other</option>
            </select>
            {errors.petType && touched.petType && (
              <p className="text-red-500 text-xs mt-1">{errors.petType}</p>
            )}
          </div>

          {/* Pet Name */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Pet Name *
            </label>
            <input
              type="text"
              value={formData.petName}
              onChange={(e) => handleInputChange("petName", e.target.value)}
              onBlur={() => handleBlur("petName")}
              className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                errors.petName && touched.petName
                  ? "border-red-500"
                  : "border-gray-300"
              }`}
              placeholder="Enter your pet's name"
            />
            {errors.petName && touched.petName && (
              <p className="text-red-500 text-xs mt-1">{errors.petName}</p>
            )}
          </div>

          {/* Pet Gender */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Pet Gender *
            </label>
            <select
              value={formData.petGender}
              onChange={(e) => handleInputChange("petGender", e.target.value)}
              onBlur={() => handleBlur("petGender")}
              className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                errors.petGender && touched.petGender
                  ? "border-red-500"
                  : "border-gray-300"
              }`}
            >
              <option value="">Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
            {errors.petGender && touched.petGender && (
              <p className="text-red-500 text-xs mt-1">{errors.petGender}</p>
            )}
          </div>

          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Allow Home Visit *
            </label>
            <select
              value={formData.homeVisit || ""}
              onChange={(e) => handleInputChange("homeVisit", e.target.value)}
              onBlur={() => handleBlur("homeVisit")}
              className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                errors.homeVisit && touched.homeVisit
                  ? "border-red-500"
                  : "border-gray-300"
              }`}
            >
              <option value="">Select Option</option>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select>
            {errors.homeVisit && touched.homeVisit && (
              <p className="text-red-500 text-xs mt-1">{errors.homeVisit}</p>
            )}
          </div>

          {/* Pet Age (Years & Months) */}
          <div className="mb-4 flex gap-4 col-span-2">
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Pet Age (Years) *
              </label>
              <input
                type="number"
                min="0"
                value={formData.petAgeYears || ""}
                onChange={(e) =>
                  handleInputChange("petAgeYears", e.target.value)
                }
                onBlur={() => handleBlur("petAgeYears")}
                className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                  errors.petAgeYears && touched.petAgeYears
                    ? "border-red-500"
                    : "border-gray-300"
                }`}
                placeholder="Years"
              />
              {errors.petAgeYears && touched.petAgeYears && (
                <p className="text-red-500 text-xs mt-1">
                  {errors.petAgeYears}
                </p>
              )}
            </div>

            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Pet Age (Months)
              </label>
              <input
                type="number"
                min="0"
                max="11"
                value={formData.petAgeMonths || ""}
                onChange={(e) =>
                  handleInputChange("petAgeMonths", e.target.value)
                }
                onBlur={() => handleBlur("petAgeMonths")}
                className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                  errors.petAgeMonths && touched.petAgeMonths
                    ? "border-red-500"
                    : "border-gray-300"
                }`}
                placeholder="Months"
              />
              {errors.petAgeMonths && touched.petAgeMonths && (
                <p className="text-red-500 text-xs mt-1">
                  {errors.petAgeMonths}
                </p>
              )}
            </div>
          </div>
          {/* Pet Breed */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Pet Breed *
            </label>
            {loadingBreeds ? (
              <div className="flex items-center justify-center py-3">
                <svg
                  className="animate-spin h-5 w-5 mr-2 text-blue-600"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    className="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    strokeWidth="4"
                  ></circle>
                  <path
                    className="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
                <span>Loading breeds...</span>
              </div>
            ) : (
              <select
                value={formData.petBreed}
                onChange={(e) => handleInputChange("petBreed", e.target.value)}
                onBlur={() => handleBlur("petBreed")}
                className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                  errors.petBreed && touched.petBreed
                    ? "border-red-500"
                    : "border-gray-300"
                }`}
                disabled={!formData.petType || breedOptions.length === 0}
              >
                <option value="">Select Breed</option>
                {breedOptions.map((breed, index) => (
                  <option key={index} value={breed}>
                    {toTitleCase(breed)}
                  </option>
                ))}
              </select>
            )}
            {errors.petBreed && touched.petBreed && (
              <p className="text-red-500 text-xs mt-1">{errors.petBreed}</p>
            )}
          </div>

          {/* Breed Image for Dogs */}
          {formData.petType === "Dog" && formData.petBreed && (
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Breed Image
              </label>
              {isLoadingImage ? (
                <div className="flex items-center justify-center py-3">
                  <svg
                    className="animate-spin h-5 w-5 mr-2 text-blue-600"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      className="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      strokeWidth="4"
                    ></circle>
                    <path
                      className="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  <span>Loading image...</span>
                </div>
              ) : breedImage ? (
                <div
                  className="cursor-pointer"
                  onClick={() => setShowBreedModal(true)}
                >
                  <img
                    src={breedImage}
                    alt={formData.petBreed}
                    className="w-24 h-24 object-cover rounded-lg shadow-md"
                  />
                  <p className="text-xs text-gray-500 mt-1">
                    Click to view larger image
                  </p>
                </div>
              ) : (
                <p className="text-sm text-gray-500">
                  No image available for this breed
                </p>
              )}
            </div>
          )}

          {/* Document Uploads */}
          <div className="mb-4 col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Vaccination Record History (Optional)
            </label>
            <div className="flex items-center justify-center w-full">
              <label
                className={`flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer ${
                  formData.petDoc1
                    ? "border-blue-500 bg-blue-50"
                    : "border-gray-300 hover:border-gray-400"
                }`}
              >
                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                  <svg
                    className="w-8 h-8 mb-4 text-gray-500"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 20 16"
                  >
                    <path
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth="2"
                      d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                    />
                  </svg>
                  <p className="mb-2 text-sm text-gray-500">
                    {formData.petDoc1 ? (
                      formData.petDoc1.name
                    ) : (
                      <span>Click to upload or drag and drop</span>
                    )}
                  </p>
                </div>
                <input
                  type="file"
                  className="hidden"
                  onChange={(e) => handleFileChange("petDoc1", e.target.files)}
                  multiple
                />
              </label>
            </div>
          </div>

          <div className="mb-4 col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Medical History (Optional)
            </label>
            <div className="flex items-center justify-center w-full">
              <label
                className={`flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer ${
                  formData.petDoc2
                    ? "border-blue-500 bg-blue-50"
                    : "border-gray-300 hover:border-gray-400"
                }`}
              >
                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                  <svg
                    className="w-8 h-8 mb-4 text-gray-500"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 20 16"
                  >
                    <path
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth="2"
                      d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                    />
                  </svg>
                  <p className="mb-2 text-sm text-gray-500">
                    {formData.petDoc2 ? (
                      formData.petDoc2.name
                    ) : (
                      <span>Click to upload or drag and drop</span>
                    )}
                  </p>
                </div>
                <input
                  type="file"
                  className="hidden"
                  onChange={(e) => handleFileChange("petDoc2", e.target.files)}
                  multiple
                />
              </label>
            </div>
          </div>
        </div>

        {/* Footer with Submit Button */}
        <div className="p-6 border-t border-gray-200">
          <button
            onClick={handleSubmit}
            disabled={isLoading}
            className="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? "Saving..." : "Save Pet Details"}
          </button>
        </div>

        {/* Breed Image Modal */}
        {showBreedModal && breedImage && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg p-6 max-w-md w-full">
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-semibold">
                  {toTitleCase(formData.petBreed)}
                </h3>
                <button
                  onClick={() => setShowBreedModal(false)}
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
                      strokeWidth="2"
                      d="M6 18L18 6M6 6l12 12"
                    ></path>
                  </svg>
                </button>
              </div>
              <img
                src={breedImage}
                alt={formData.petBreed}
                className="w-full h-64 object-cover rounded-lg"
              />
              <div className="mt-4">
                <h4 className="font-medium text-gray-700">
                  Breed Information:
                </h4>
                <p className="text-sm text-gray-600 mt-2">
                  This is a {formData.petBreed} breed. For more detailed
                  information about this breed's characteristics, temperament,
                  and care requirements, please consult a breed database or
                  veterinarian.
                </p>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default PetDetailsModal;
