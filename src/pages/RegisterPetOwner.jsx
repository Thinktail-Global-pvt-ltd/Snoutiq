import React, { useState, useEffect } from "react";
import { toast } from "react-hot-toast";
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
    mobileNumber: "",
    petDoc1: null,
    petDoc2: null,
  });

  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [breedOptions, setBreedOptions] = useState([]);
  const [filteredBreedOptions, setFilteredBreedOptions] = useState([]);
  const [breedSearchTerm, setBreedSearchTerm] = useState("");
  const [breedImage, setBreedImage] = useState(null);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [isLoadingImage, setIsLoadingImage] = useState(false);

  // Separate dropdown and modal state
  const [showBreedDropdown, setShowBreedDropdown] = useState(false);
  const [showBreedImageModal, setShowBreedImageModal] = useState(false);

  // ---------- VALIDATION ----------
  const validate = () => {
    const newErrors = {};

    if (!formData.petType) newErrors.petType = "Please select pet type";
    if (!formData.petName.trim()) newErrors.petName = "Pet name is required";
    if (!formData.petGender) newErrors.petGender = "Please select gender";
    if (!formData.homeVisit) newErrors.homeVisit = "Please select option";

    const mobileRegex = /^[6-9]\d{9}$/;
    if (!formData.mobileNumber.trim()) {
      newErrors.mobileNumber = "Mobile number is required";
    } else if (!mobileRegex.test(formData.mobileNumber.trim())) {
      newErrors.mobileNumber = "Please enter a valid 10-digit mobile number";
    }

    const years = parseInt(formData.petAgeYears || 0, 10);
    const months = parseInt(formData.petAgeMonths || 0, 10);
    if (isNaN(years) || years < 0) newErrors.petAgeYears = "Enter valid years";
    if (isNaN(months) || months < 0 || months > 11)
      newErrors.petAgeMonths = "Months must be between 0–11";
    if (years === 0 && months === 0) {
      newErrors.petAgeYears = "Enter age in years or months";
      newErrors.petAgeMonths = "Enter age in years or months";
    }

    if (!formData.petBreed) newErrors.petBreed = "Please select pet breed";

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // ---------- FETCH BREEDS ----------
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

            const sorted = breedList.sort();
            setBreedOptions(sorted);
            setFilteredBreedOptions(sorted);
          } else {
            toast.error("Invalid breed data format");
            setBreedOptions([]);
            setFilteredBreedOptions([]);
          }
        } catch (err) {
          toast.error("Failed to load dog breeds");
          setBreedOptions([]);
        } finally {
          setLoadingBreeds(false);
        }
      } else if (formData.petType === "Cat") {
        const catBreeds = [
          "Siamese",
          "Persian",
          "Maine Coon",
          "Bengal",
          "Sphynx",
          "British Shorthair",
          "Ragdoll",
          "Abyssinian",
          "Scottish Fold",
        ];
        setBreedOptions(catBreeds);
        setFilteredBreedOptions(catBreeds);
      } else {
        setBreedOptions([]);
        setFilteredBreedOptions([]);
      }
    };

    if (formData.petType) fetchBreeds();
  }, [formData.petType]);

  // ---------- FILTER BREEDS ----------
  useEffect(() => {
    if (breedSearchTerm.trim() === "") {
      setFilteredBreedOptions(breedOptions);
    } else {
      const filtered = breedOptions.filter((b) =>
        b.toLowerCase().includes(breedSearchTerm.toLowerCase())
      );
      setFilteredBreedOptions(filtered);
    }
  }, [breedSearchTerm, breedOptions]);

  // ---------- HELPERS ----------
  const toTitleCase = (str) =>
    str.replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());

  const handleInputChange = (field, value) => {
    setFormData((p) => ({ ...p, [field]: value }));
    if (errors[field]) setErrors((p) => ({ ...p, [field]: null }));
  };
  const handleBlur = (f) => setTouched((p) => ({ ...p, [f]: true }));

  const handleFileChange = (field, files) => {
    if (files?.length > 0) setFormData((p) => ({ ...p, [field]: files[0] }));
  };

  // ---------- SUBMIT ----------
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
      submitData.append("mobileNumber", formData.mobileNumber.trim());
      submitData.append("role", "pet");

      const years = parseInt(formData.petAgeYears || 0, 10);
      const months = parseInt(formData.petAgeMonths || 0, 10);
      submitData.append("pet_age", years * 12 + months);
      submitData.append("breed", formData.petBreed);

      if (formData.petDoc1) submitData.append("pet_doc1", formData.petDoc1);
      if (formData.petDoc2) submitData.append("pet_doc2", formData.petDoc2);

      const res = await axios.post(
        "https://snoutiq.com/backend/api/auth/register",
        submitData,
        { headers: { "Content-Type": "multipart/form-data" } }
      );
      

      if (res.data.message?.includes("successfully")) {
        toast.success("Pet profile saved successfully!");

        if (res.data.user) {
          updateUser({ ...res.data.user, role: "pet" });
          if (onComplete) onComplete();
        } else {
          const userRes = await axios.get(
            `https://snoutiq.com/backend/api/petparents/${user.id}`,
            { headers: { Authorization: `Bearer ${token}` } }
          );
          const updatedUser = userRes.data?.user || userRes.data;
          updateUser({ ...updatedUser, role: "pet" });
          toast.success("Pet details updated!");
          if (onComplete) onComplete();
        }
      } else {
        toast.error(res.data.message || "Failed to save pet data");
      }
    } catch (err) {
      toast.error(
        err.response?.data?.message ||
          (err.request ? "Network error" : "Something went wrong")
      );
    } finally {
      setIsLoading(false);
    }
  };

  // ---------- UI ----------
  return (
    <div className="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="p-6 border-b border-gray-200">
          <h2 className="text-xl font-bold mb-2">Complete Your Pet Profile</h2>
          <p className="text-gray-600 text-sm">
            Please fill your pet's details to unlock the full Snoutiq experience.
          </p>
        </div>

        {/* Scrollable Content */}
        <div className="overflow-y-auto px-6 py-4 flex-1 grid grid-cols-1 md:grid-cols-2 gap-6">

          {/* Pet Type */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Pet Type *</label>
            <select
              value={formData.petType}
              onChange={(e) => handleInputChange("petType", e.target.value)}
              onBlur={() => handleBlur("petType")}
              className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
                errors.petType && touched.petType ? "border-red-500" : "border-gray-300"
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
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Pet Name *</label>
            <input
              type="text"
              value={formData.petName}
              onChange={(e) => handleInputChange("petName", e.target.value)}
              onBlur={() => handleBlur("petName")}
              className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
                errors.petName && touched.petName ? "border-red-500" : "border-gray-300"
              }`}
              placeholder="Enter your pet's name"
            />
            {errors.petName && touched.petName && (
              <p className="text-red-500 text-xs mt-1">{errors.petName}</p>
            )}
          </div>

          {/* Mobile Number */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Mobile Number *</label>
            <input
              type="tel"
              value={formData.mobileNumber}
              onChange={(e) => handleInputChange("mobileNumber", e.target.value)}
              onBlur={() => handleBlur("mobileNumber")}
              maxLength="10"
              className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
                errors.mobileNumber && touched.mobileNumber ? "border-red-500" : "border-gray-300"
              }`}
              placeholder="Enter your mobile number"
            />
            {errors.mobileNumber && touched.mobileNumber && (
              <p className="text-red-500 text-xs mt-1">{errors.mobileNumber}</p>
            )}
          </div>

          {/* Gender */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Pet Gender *</label>
            <select
              value={formData.petGender}
              onChange={(e) => handleInputChange("petGender", e.target.value)}
              onBlur={() => handleBlur("petGender")}
              className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
                errors.petGender && touched.petGender ? "border-red-500" : "border-gray-300"
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

          {/* Home Visit */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Allow Home Visit *</label>
            <select
              value={formData.homeVisit || ""}
              onChange={(e) => handleInputChange("homeVisit", e.target.value)}
              onBlur={() => handleBlur("homeVisit")}
              className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
                errors.homeVisit && touched.homeVisit ? "border-red-500" : "border-gray-300"
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

          {/* Pet Age */}
          <div className="flex gap-4 col-span-2">
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-2">Pet Age (Years) *</label>
              <input
                type="number"
                min="0"
                value={formData.petAgeYears || ""}
                onChange={(e) => handleInputChange("petAgeYears", e.target.value)}
                onBlur={() => handleBlur("petAgeYears")}
                className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
                  errors.petAgeYears && touched.petAgeYears ? "border-red-500" : "border-gray-300"
                }`}
                placeholder="Years"
              />
              {errors.petAgeYears && touched.petAgeYears && (
                <p className="text-red-500 text-xs mt-1">{errors.petAgeYears}</p>
              )}
            </div>
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-2">Pet Age (Months)</label>
              <input
                type="number"
                min="0"
                max="11"
                value={formData.petAgeMonths || ""}
                onChange={(e) => handleInputChange("petAgeMonths", e.target.value)}
                onBlur={() => handleBlur("petAgeMonths")}
                className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
                  errors.petAgeMonths && touched.petAgeMonths ? "border-red-500" : "border-gray-300"
                }`}
                placeholder="Months"
              />
              {errors.petAgeMonths && touched.petAgeMonths && (
                <p className="text-red-500 text-xs mt-1">{errors.petAgeMonths}</p>
              )}
            </div>
          </div>

          {/* ✅ Searchable Dropdown for Pet Breed */}
          <div className="mb-4 col-span-2 relative">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Pet Breed *
            </label>

            {loadingBreeds ? (
              <div className="flex items-center justify-center py-3">Loading breeds...</div>
            ) : (
              <>
                <div
                  className={`w-full px-4 py-3 border rounded-lg bg-white cursor-pointer ${
                    errors.petBreed && touched.petBreed
                      ? "border-red-500"
                      : "border-gray-300"
                  }`}
                  onClick={() => setShowBreedDropdown((prev) => !prev)}
                >
                  {formData.petBreed || "Select or search breed"}
                </div>

                {showBreedDropdown && (
                  <div className="absolute z-50 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-y-auto shadow-lg">
                    <input
                      type="text"
                      value={breedSearchTerm}
                      onChange={(e) => setBreedSearchTerm(e.target.value)}
                      placeholder="Search breed..."
                      className="w-full px-3 py-2 border-b border-gray-200 focus:outline-none"
                      autoFocus
                    />
                    {filteredBreedOptions.length > 0 ? (
                      filteredBreedOptions.map((breed, index) => (
                        <div
                          key={index}
                          onClick={() => {
                            handleInputChange("petBreed", breed);
                            setShowBreedDropdown(false);
                          }}
                          className={`px-4 py-2 hover:bg-blue-100 cursor-pointer ${
                            formData.petBreed === breed ? "bg-blue-50 font-medium" : ""
                          }`}
                        >
                          {toTitleCase(breed)}
                        </div>
                      ))
                    ) : (
                      <div className="px-4 py-2 text-gray-500 text-sm">No breeds found</div>
                    )}
                  </div>
                )}
              </>
            )}
            {errors.petBreed && touched.petBreed && (
              <p className="text-red-500 text-xs mt-1">{errors.petBreed}</p>
            )}
          </div>

        </div>

        {/* Footer */}
        <div className="p-6 border-t border-gray-200">
          <button
            onClick={handleSubmit}
            disabled={isLoading}
            className="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {isLoading ? "Saving..." : "Save Pet Details"}
          </button>
        </div>

        {/* Breed Image Modal */}
        {showBreedImageModal && breedImage && (
          <div className="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center">
            <div className="bg-white rounded-xl overflow-hidden max-w-sm">
              <img src={breedImage} alt="Breed" className="w-full h-64 object-cover" />
              <div className="p-4 text-center">
                <button
                  className="px-4 py-2 bg-blue-600 text-white rounded-lg"
                  onClick={() => setShowBreedImageModal(false)}
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default PetDetailsModal;
