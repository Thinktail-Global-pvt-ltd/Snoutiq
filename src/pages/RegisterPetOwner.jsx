import React, { useState, useEffect } from "react";
import { Search, X, Upload, Info, CheckCircle2 } from "lucide-react";

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
  const [filteredBreeds, setFilteredBreeds] = useState([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [showBreedDropdown, setShowBreedDropdown] = useState(false);
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
          const res = await fetch("https://dog.ceo/api/breeds/list/all");
          const data = await res.json();

          if (data.status === "success" && data.message) {
            const breedsData = data.message;
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
            setFilteredBreeds(breedList.sort());
          }
        } catch (err) {
          console.error("Failed to fetch breeds", err);
          setBreedOptions([]);
          setFilteredBreeds([]);
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
          "Birman",
          "Russian Blue",
          "Norwegian Forest",
        ].sort();
        setBreedOptions(catBreeds);
        setFilteredBreeds(catBreeds);
      } else {
        setBreedOptions([]);
        setFilteredBreeds([]);
      }
    };

    if (formData.petType) {
      fetchBreeds();
      setSearchQuery("");
      setFormData(prev => ({ ...prev, petBreed: "" }));
    }
  }, [formData.petType]);

  useEffect(() => {
    if (searchQuery) {
      const filtered = breedOptions.filter(breed =>
        breed.toLowerCase().includes(searchQuery.toLowerCase())
      );
      setFilteredBreeds(filtered);
    } else {
      setFilteredBreeds(breedOptions);
    }
  }, [searchQuery, breedOptions]);

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

  const handleBreedSelect = (breed) => {
    setFormData(prev => ({ ...prev, petBreed: breed }));
    setSearchQuery(breed);
    setShowBreedDropdown(false);
    if (errors.petBreed) setErrors(prev => ({ ...prev, petBreed: null }));
  };

  const handleSubmit = async () => {
    if (!validate()) {
      setTouched(Object.fromEntries(Object.keys(formData).map((k) => [k, true])));
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
      submitData.append("role", "pet");

      const years = parseInt(formData.petAgeYears || 0, 10);
      const months = parseInt(formData.petAgeMonths || 0, 10);
      const totalMonths = years * 12 + months;
      submitData.append("pet_age", totalMonths);

      submitData.append("breed", formData.petBreed);
      if (formData.petDoc1) submitData.append("pet_doc1", formData.petDoc1);
      if (formData.petDoc2) submitData.append("pet_doc2", formData.petDoc2);

      // Simulating API call
      console.log("Form submitted successfully", Object.fromEntries(submitData));
      
      setTimeout(() => {
        setIsLoading(false);
        if (onComplete) onComplete();
      }, 1500);
    } catch (error) {
      console.error("Registration error:", error);
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 bg-gradient-to-br from-gray-900/95 to-gray-800/95 backdrop-blur-sm flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
        {/* Header */}
        <div className="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white">
          <h2 className="text-2xl font-bold mb-2">Complete Your Pet Profile</h2>
          <p className="text-blue-100 text-sm">
            Please fill in your pet's details to unlock the full Snoutiq experience
          </p>
        </div>

        {/* Scrollable Content */}
        <div className="overflow-y-auto px-6 py-6 flex-1">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Pet Type */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Pet Type <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.petType}
                onChange={(e) => handleInputChange("petType", e.target.value)}
                onBlur={() => handleBlur("petType")}
                className={`w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                  errors.petType && touched.petType
                    ? "border-red-400 bg-red-50"
                    : "border-gray-200 hover:border-gray-300"
                }`}
              >
                <option value="">Select Pet Type</option>
                <option value="Dog">🐕 Dog</option>
                <option value="Cat">🐈 Cat</option>
                <option value="Other">🐾 Other</option>
              </select>
              {errors.petType && touched.petType && (
                <p className="text-red-500 text-xs mt-1 flex items-center gap-1">
                  <Info className="w-3 h-3" /> {errors.petType}
                </p>
              )}
            </div>

            {/* Pet Name */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Pet Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.petName}
                onChange={(e) => handleInputChange("petName", e.target.value)}
                onBlur={() => handleBlur("petName")}
                className={`w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                  errors.petName && touched.petName
                    ? "border-red-400 bg-red-50"
                    : "border-gray-200 hover:border-gray-300"
                }`}
                placeholder="Enter your pet's name"
              />
              {errors.petName && touched.petName && (
                <p className="text-red-500 text-xs mt-1 flex items-center gap-1">
                  <Info className="w-3 h-3" /> {errors.petName}
                </p>
              )}
            </div>

            {/* Pet Gender */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Pet Gender <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.petGender}
                onChange={(e) => handleInputChange("petGender", e.target.value)}
                onBlur={() => handleBlur("petGender")}
                className={`w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                  errors.petGender && touched.petGender
                    ? "border-red-400 bg-red-50"
                    : "border-gray-200 hover:border-gray-300"
                }`}
              >
                <option value="">Select Gender</option>
                <option value="Male">♂️ Male</option>
                <option value="Female">♀️ Female</option>
              </select>
              {errors.petGender && touched.petGender && (
                <p className="text-red-500 text-xs mt-1 flex items-center gap-1">
                  <Info className="w-3 h-3" /> {errors.petGender}
                </p>
              )}
            </div>

            {/* Home Visit */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Allow Home Visit <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.homeVisit || ""}
                onChange={(e) => handleInputChange("homeVisit", e.target.value)}
                onBlur={() => handleBlur("homeVisit")}
                className={`w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                  errors.homeVisit && touched.homeVisit
                    ? "border-red-400 bg-red-50"
                    : "border-gray-200 hover:border-gray-300"
                }`}
              >
                <option value="">Select Option</option>
                <option value="Yes">✓ Yes</option>
                <option value="No">✗ No</option>
              </select>
              {errors.homeVisit && touched.homeVisit && (
                <p className="text-red-500 text-xs mt-1 flex items-center gap-1">
                  <Info className="w-3 h-3" /> {errors.homeVisit}
                </p>
              )}
            </div>

            {/* Pet Age */}
            <div className="md:col-span-2">
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Pet Age <span className="text-red-500">*</span>
              </label>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <input
                    type="number"
                    min="0"
                    value={formData.petAgeYears || ""}
                    onChange={(e) => handleInputChange("petAgeYears", e.target.value)}
                    onBlur={() => handleBlur("petAgeYears")}
                    className={`w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                      errors.petAgeYears && touched.petAgeYears
                        ? "border-red-400 bg-red-50"
                        : "border-gray-200 hover:border-gray-300"
                    }`}
                    placeholder="Years"
                  />
                  {errors.petAgeYears && touched.petAgeYears && (
                    <p className="text-red-500 text-xs mt-1 flex items-center gap-1">
                      <Info className="w-3 h-3" /> {errors.petAgeYears}
                    </p>
                  )}
                </div>
                <div>
                  <input
                    type="number"
                    min="0"
                    max="11"
                    value={formData.petAgeMonths || ""}
                    onChange={(e) => handleInputChange("petAgeMonths", e.target.value)}
                    onBlur={() => handleBlur("petAgeMonths")}
                    className={`w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                      errors.petAgeMonths && touched.petAgeMonths
                        ? "border-red-400 bg-red-50"
                        : "border-gray-200 hover:border-gray-300"
                    }`}
                    placeholder="Months (0-11)"
                  />
                  {errors.petAgeMonths && touched.petAgeMonths && (
                    <p className="text-red-500 text-xs mt-1 flex items-center gap-1">
                      <Info className="w-3 h-3" /> {errors.petAgeMonths}
                    </p>
                  )}
                </div>
              </div>
            </div>

            {/* Pet Breed with Search */}
            <div className="md:col-span-2">
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Pet Breed <span className="text-red-500">*</span>
              </label>
              {loadingBreeds ? (
                <div className="flex items-center justify-center py-8 bg-gray-50 rounded-xl border-2 border-gray-200">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                  <span className="ml-3 text-gray-600">Loading breeds...</span>
                </div>
              ) : (
                <div className="relative">
                  <div className="relative">
                    <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                    <input
                      type="text"
                      value={searchQuery}
                      onChange={(e) => {
                        setSearchQuery(e.target.value);
                        setShowBreedDropdown(true);
                      }}
                      onFocus={() => setShowBreedDropdown(true)}
                      onBlur={() => handleBlur("petBreed")}
                      className={`w-full pl-12 pr-10 py-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                        errors.petBreed && touched.petBreed
                          ? "border-red-400 bg-red-50"
                          : "border-gray-200 hover:border-gray-300"
                      }`}
                      placeholder={formData.petType ? `Search ${formData.petType.toLowerCase()} breeds...` : "Select pet type first"}
                      disabled={!formData.petType || breedOptions.length === 0}
                    />
                    {searchQuery && (
                      <button
                        onClick={() => {
                          setSearchQuery("");
                          setFormData(prev => ({ ...prev, petBreed: "" }));
                        }}
                        className="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      >
                        <X className="w-5 h-5" />
                      </button>
                    )}
                  </div>
                  
                  {showBreedDropdown && filteredBreeds.length > 0 && (
                    <div className="absolute z-10 w-full mt-2 bg-white border-2 border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                      {filteredBreeds.map((breed, index) => (
                        <div
                          key={index}
                          onClick={() => handleBreedSelect(breed)}
                          onMouseDown={(e) => e.preventDefault()}
                          className="px-4 py-3 hover:bg-blue-50 cursor-pointer transition-colors flex items-center justify-between group"
                        >
                          <span className="text-gray-700 group-hover:text-blue-600">
                            {toTitleCase(breed)}
                          </span>
                          {formData.petBreed === breed && (
                            <CheckCircle2 className="w-5 h-5 text-blue-600" />
                          )}
                        </div>
                      ))}
                    </div>
                  )}
                  
                  {showBreedDropdown && filteredBreeds.length === 0 && searchQuery && (
                    <div className="absolute z-10 w-full mt-2 bg-white border-2 border-gray-200 rounded-xl shadow-lg p-4 text-center text-gray-500">
                      No breeds found matching "{searchQuery}"
                    </div>
                  )}
                </div>
              )}
              {errors.petBreed && touched.petBreed && (
                <p className="text-red-500 text-xs mt-1 flex items-center gap-1">
                  <Info className="w-3 h-3" /> {errors.petBreed}
                </p>
              )}
            </div>

            {/* Document Uploads */}
            <div className="md:col-span-2">
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Vaccination Record History <span className="text-gray-400 text-xs">(Optional)</span>
              </label>
              <label className={`flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-xl cursor-pointer transition-all ${
                formData.petDoc1
                  ? "border-blue-500 bg-blue-50"
                  : "border-gray-300 hover:border-blue-400 hover:bg-gray-50"
              }`}>
                <div className="flex flex-col items-center justify-center">
                  <Upload className={`w-10 h-10 mb-2 ${formData.petDoc1 ? "text-blue-600" : "text-gray-400"}`} />
                  <p className="text-sm text-gray-600">
                    {formData.petDoc1 ? (
                      <span className="font-medium text-blue-600">{formData.petDoc1.name}</span>
                    ) : (
                      <span>Click to upload or drag and drop</span>
                    )}
                  </p>
                  <p className="text-xs text-gray-400 mt-1">PDF, JPG, PNG (Max 5MB)</p>
                </div>
                <input
                  type="file"
                  className="hidden"
                  onChange={(e) => handleFileChange("petDoc1", e.target.files)}
                  accept=".pdf,.jpg,.jpeg,.png"
                />
              </label>
            </div>

            <div className="md:col-span-2">
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Medical History <span className="text-gray-400 text-xs">(Optional)</span>
              </label>
              <label className={`flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-xl cursor-pointer transition-all ${
                formData.petDoc2
                  ? "border-blue-500 bg-blue-50"
                  : "border-gray-300 hover:border-blue-400 hover:bg-gray-50"
              }`}>
                <div className="flex flex-col items-center justify-center">
                  <Upload className={`w-10 h-10 mb-2 ${formData.petDoc2 ? "text-blue-600" : "text-gray-400"}`} />
                  <p className="text-sm text-gray-600">
                    {formData.petDoc2 ? (
                      <span className="font-medium text-blue-600">{formData.petDoc2.name}</span>
                    ) : (
                      <span>Click to upload or drag and drop</span>
                    )}
                  </p>
                  <p className="text-xs text-gray-400 mt-1">PDF, JPG, PNG (Max 5MB)</p>
                </div>
                <input
                  type="file"
                  className="hidden"
                  onChange={(e) => handleFileChange("petDoc2", e.target.files)}
                  accept=".pdf,.jpg,.jpeg,.png"
                />
              </label>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="p-6 border-t border-gray-200 bg-gray-50">
          <button
            onClick={handleSubmit}
            disabled={isLoading}
            className="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
          >
            {isLoading ? (
              <span className="flex items-center justify-center">
                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                Saving Pet Profile...
              </span>
            ) : (
              "Save Pet Details"
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

export default PetDetailsModal;