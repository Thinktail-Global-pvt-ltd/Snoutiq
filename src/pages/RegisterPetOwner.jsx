import React, { useState, useEffect } from "react";
import Card from "../components/Card";
import logo from "../assets/images/logo.webp";
const RegisterPetOwner = () => {
  const [formData, setFormData] = useState({
    fullName: "",
    email: "",
    mobileNumber: "",
    petType: "",
    petName: "",
    petGender: "",
    petAge: "",
    petBreed: "",
    petDoc1: null,
    petDoc2: null,
    password: "",
    confirmPassword: "",
    google_token: "",
  });
  const [breedOptions, setBreedOptions] = useState([]);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [breedImage, setBreedImage] = useState(null);
  const [showBreedModal, setShowBreedModal] = useState(false);
  const [errors, setErrors] = useState({});
    const [isLoading, setIsLoading] = useState({
      email: false,
      register: false,
      breedImage: false,
    });

    const handleBack = () => {

  };

  const compressImage = (file, maxSizeKB = 50, maxWidthOrHeight = 800) => {
    addDebugLog("Compressing image", { file: file.name, size: file.size });

    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);

      reader.onload = (event) => {
        const img = new Image();
        img.src = event.target.result;

        img.onload = () => {
          const canvas = document.createElement("canvas");
          const ctx = canvas.getContext("2d");

          let width = img.width;
          let height = img.height;
          if (width > height && width > maxWidthOrHeight) {
            height = Math.round((height *= maxWidthOrHeight / width));
            width = maxWidthOrHeight;
          } else if (height > maxWidthOrHeight) {
            width = Math.round((width *= maxWidthOrHeight / height));
            height = maxWidthOrHeight;
          }

          canvas.width = width;
          canvas.height = height;
          ctx.drawImage(img, 0, 0, width, height);

          let quality = 0.7;
          const tryCompress = () => {
            canvas.toBlob(
              (blob) => {
                addDebugLog("Image compression iteration", {
                  quality,
                  size: blob.size / 1024,
                  target: maxSizeKB,
                });

                if (blob.size / 1024 > maxSizeKB && quality > 0.1) {
                  quality -= 0.1;
                  tryCompress();
                } else {
                  const compressedFile = new File([blob], file.name, {
                    type: "image/jpeg",
                  });
                  addDebugLog("Image compression complete", {
                    original: file.size / 1024,
                    compressed: compressedFile.size / 1024,
                  });
                  resolve(compressedFile);
                }
              },
              "image/jpeg",
              quality
            );
          };
          tryCompress();
        };
      };
    });
  };

  const toTitleCase = (str) =>
    str
      .split(" ")
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");

  useEffect(() => {
    const fetchBreeds = async () => {
      if (formData.petType === "Dog") {
        setLoadingBreeds(true);
        addDebugLog("Fetching dog breeds");

        try {
          const res = await axios.get(
            "https://snoutiq.com/backend/api/dog-breeds/all"
          );

          addDebugLog("Dog breeds API response", res.data);

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
            addDebugLog("Processed breed options", breedList);
          } else {
            const msg = "Invalid response format for breeds";
            addDebugLog(msg, res.data);
            console.error(msg);
            setBreedOptions([]);
          }
        } catch (err) {
          addDebugLog("Failed to fetch breeds", err);
          console.error("Failed to fetch breeds", err);
          toast.error("Failed to load dog breeds");
          setBreedOptions([]);
        } finally {
          setLoadingBreeds(false);
        }
      } else if (formData.petType === "Cat") {
        addDebugLog("Setting cat breeds");
        // Static cat breeds
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

  const handleFileChange = async (field, files) => {
    addDebugLog("File selected", { field, file: files[0]?.name });

    if (files && files.length > 0) {
      const file = files[0];

      if (!file.type.startsWith("image/")) {
        handleInputChange(field, file);
        return;
      }

      const compressedFile = await compressImage(file, 50);
      handleInputChange(field, compressedFile);
    }
  };
  return (
    <div className="min-h-screen bg-white bg-gradient-to-br from-blue-50 to-indigo-100 mt-12 flex items-center justify-center px-4 py-8">
      <div className="w-full max-w-sm sm:max-w-md">
        <Card className="text-center shadow-xl rounded-xl p-6 sm:p-8">
               <div className="mb-6">
                          <img
                            src={logo}
                            alt="Snoutiq Logo"
                            className="h-6 mx-auto mb-3 debug-logo cursor-pointer"
                          />
                        </div>
          {/* Pet Type */}
          <div className="text-left">
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

          <div className="text-left">
            <label className="block text-sm font-medium text-gray-700 mb极狐">
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

          <div className="text-left">
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
          <div className="text-left">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Pet Age *
            </label>
            <input
              type="number"
              min="0"
              value={formData.petAge}
              onChange={(e) => handleInputChange("petAge", e.target.value)}
              onBlur={() => handleBlur("petAge")}
              className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                errors.petAge && touched.petAge
                  ? "border-red-500"
                  : "border-gray-300"
              }`}
              placeholder="Enter your pet's age"
            />
            {errors.petAge && touched.petAge && (
              <p className="text-red-500 text-xs mt-1">{errors.petAge}</p>
            )}
          </div>

          <div className="text-left">
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
                className={`w-full px-极狐 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
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

          {formData.petType === "Dog" && formData.petBreed && (
            <div className="text-left">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Breed Image
              </label>
              {isLoading.breedImage ? (
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
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 极狐 7.938l3-2.647z"
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

          <div className="text-left">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Prescription Document (Optional)
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
                />
              </label>
            </div>
          </div>

          <div className="text-left">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Medical Document (Optional)
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
                />
              </label>
            </div>
          </div>
          <div className="flex justify-between gap-3 mt-6">
                <button
                  onClick={handleBack}
                  className="flex-1 bg-gray-100 text-gray-800 font-medium py-3 px-6 rounded-lg hover:bg-gray-200 transition-colors"
                >
                  Back
                </button>

                <button
                //   onClick={handleSubmit}
                  // disabled={isLoading.register}
                //   disabled={isLoading.register || !coords.lat || !coords.lng}
                  className="flex-1 bg-blue-600 text-white font-medium py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-400 disabled:cursor-not-allowed"
                >
                  {isLoading.register ? (
                    <span className="flex items-center justify-center">
                      <svg
                        className="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                      Registering...
                    </span>
                  ) : (
                    "Register"
                  )}
                </button>
 
            </div>
        </Card>
      </div>
       

    </div>
  );
};

export default RegisterPetOwner;
