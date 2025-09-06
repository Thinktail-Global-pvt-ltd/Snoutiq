import React, { useState } from 'react';
import { FaPaw, FaPlusCircle, FaCamera, FaTrash, FaArrowLeft } from 'react-icons/fa';
import { useNavigate } from 'react-router-dom';
import axios from './../axios';
import toast from 'react-hot-toast';
import Header from '../components/Header';
import Footer from '../components/Footer';

const AddPetForm = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    name: '',
    type: '',
    breed: '',
    dob: '',
    gender: '',
    petPicture: 'https://placehold.co/200x200?text=Upload+Image',
  });
  const [petPictureFile, setPetPictureFile] = useState(null);
  const [isPetPictureUpdated, setIsPetPictureUpdated] = useState(false);
  const [medicalEntries, setMedicalEntries] = useState([]);
  const [vaccinationEntries, setVaccinationEntries] = useState([]);
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const today = new Date().toISOString().split('T')[0];

  const validateForm = () => {
    const newErrors = {};

    if (isPetPictureUpdated && petPictureFile) {
      const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
      if (!allowedTypes.includes(petPictureFile.type)) {
        newErrors.petPicture = 'Only JPEG, PNG, or GIF images are allowed';
      } else if (petPictureFile.size > 5 * 1024 * 1024) {
        newErrors.petPicture = 'Image size must be less than 5MB';
      }
    }
    medicalEntries.forEach((entry, index) => {
      if (!entry.condition.trim()) {
        newErrors[`medicalCondition${index}`] = 'Condition is required';
      }
      if (!entry.date) {
        newErrors[`medicalDate${index}`] = 'Date is required';
      } else if (new Date(entry.date) > new Date()) {
        newErrors[`medicalDate${index}`] = 'Date cannot be in the future';
      }
    });
    vaccinationEntries.forEach((entry, index) => {
      if (!entry.vaccineName.trim()) {
        newErrors[`vaccineName${index}`] = 'Vaccine Name is required';
      }
      if (!entry.date) {
        newErrors[`vaccineDate${index}`] = 'Date is required';
      } else if (new Date(entry.date) > new Date()) {
        newErrors[`vaccineDate${index}`] = 'Date cannot be in the future';
      }
    });
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    setErrors((prev) => ({ ...prev, [name]: '' }));
  };

  const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
      setPetPictureFile(file);
      setIsPetPictureUpdated(true);
      const reader = new FileReader();
      reader.onload = () => {
        setFormData((prev) => ({ ...prev, petPicture: reader.result }));
      };
      reader.readAsDataURL(file);
    }
  };

  const handleMedicalEntryChange = (index, e) => {
    const { name, value, type, checked } = e.target;
    setMedicalEntries((prev) =>
      prev.map((entry, i) =>
        i === index
          ? { ...entry, [name]: type === 'checkbox' ? checked : value }
          : entry,
      ),
    );
    setErrors((prev) => ({ ...prev, [`${name}${index}`]: '' }));
  };

  const addMedicalEntry = () => {
    setMedicalEntries((prev) => [
      ...prev,
      { condition: '', date: '', isRecovered: false },
    ]);
  };

  const removeMedicalEntry = (index) => {
    setMedicalEntries((prev) => prev.filter((_, i) => i !== index));
    setErrors((prev) => {
      const newErrors = { ...prev };
      delete newErrors[`medicalCondition${index}`];
      delete newErrors[`medicalDate${index}`];
      return newErrors;
    });
  };

  const handleVaccinationChange = (index, e) => {
    const { name, value } = e.target;
    setVaccinationEntries((prev) =>
      prev.map((entry, i) => (i === index ? { ...entry, [name]: value } : entry)),
    );
    setErrors((prev) => ({ ...prev, [`${name}${index}`]: '' }));
  };

  const addVaccination = () => {
    setVaccinationEntries((prev) => [...prev, { vaccineName: '', date: '' }]);
  };

  const removeVaccinationEntry = (index) => {
    setVaccinationEntries((prev) => prev.filter((_, i) => i !== index));
    setErrors((prev) => {
      const newErrors = { ...prev };
      delete newErrors[`vaccineName${index}`];
      delete newErrors[`vaccineDate${index}`];
      return newErrors;
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (validateForm()) {
      setIsLoading(true);
      try {
        const formDataPayload = new FormData();
        formDataPayload.append('name', formData.name);
        formDataPayload.append('type', formData.type);
        formDataPayload.append('breed', formData.breed);
        formDataPayload.append('dob', formData.dob);
        formDataPayload.append('gender', formData.gender);
        formDataPayload.append('medical_history', JSON.stringify(medicalEntries));
        formDataPayload.append('vaccination_log', JSON.stringify(vaccinationEntries));
        if (isPetPictureUpdated && petPictureFile) {
          formDataPayload.append('pet_pic', petPictureFile);
        }

        const res = await axios.post('user/add_pet', formDataPayload, {
          headers: {
            'Content-Type': 'multipart/form-data',
            Authorization: `Bearer ${localStorage.getItem('token')}`,
          },
        });
        console.log('Pet saved:', res.data);
        toast.success('Pet registered successfully!');
        navigate('/dashboard');
      } catch (error) {
        const errorMessage =
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : 'Error getting profile';
        toast.error(errorMessage);
        setErrors({ submit: 'Failed to register pet. Please try again.' });
      } finally {
        setIsLoading(false);
      }
    }
  };

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-8 px-4 md:px-8 mt-[70px]">
        <div className="max-w-4xl mx-auto">
          {/* Header with Back Button */}
          <div className="flex items-center mb-6">
            <button
              onClick={() => navigate('/user/pets')}
              className="flex items-center text-blue-600 hover:text-blue-800 transition-colors mr-4 p-2 rounded-lg hover:bg-blue-50"
            >
              <FaArrowLeft className="mr-2" />
              Back to Pets
            </button>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-900">Register New Pet</h1>
          </div>

          <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
            {/* Form Header */}
            <div className="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-4 text-white">
              <h2 className="text-xl font-semibold">Pet Information</h2>
              <p className="text-blue-100 text-sm">Add your furry friend to the family</p>
            </div>

            <form onSubmit={handleSubmit} className="p-6 md:p-8 space-y-8">
              {/* Basic Information Section */}
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Basic Information
                </h3>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {/* Pet Name */}
                  <div>
                    <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                      Pet Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      id="name"
                      name="name"
                      type="text"
                      value={formData.name}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                      placeholder="e.g., Luna"
                      required
                    />
                  </div>

                  {/* Pet Type */}
                  <div>
                    <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-2">
                      Pet Type <span className="text-red-500">*</span>
                    </label>
                    <select
                      id="type"
                      name="type"
                      value={formData.type}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                      required
                    >
                      <option value="">Select Pet Type</option>
                      <option value="dog">Dog</option>
                      <option value="cat">Cat</option>
                      <option value="bird">Bird</option>
                      <option value="reptile">Reptile</option>
                      <option value="small mammal">Small Mammal</option>
                      <option value="other">Other</option>
                    </select>
                  </div>

                  {/* Breed */}
                  <div>
                    <label htmlFor="breed" className="block text-sm font-medium text-gray-700 mb-2">
                      Breed <span className="text-red-500">*</span>
                    </label>
                    <input
                      id="breed"
                      name="breed"
                      type="text"
                      value={formData.breed}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                      placeholder="e.g., Golden Retriever"
                      required
                    />
                  </div>

                  {/* Date of Birth */}
                  <div>
                    <label htmlFor="dob" className="block text-sm font-medium text-gray-700 mb-2">
                      Date of Birth <span className="text-red-500">*</span>
                    </label>
                    <input
                      id="dob"
                      name="dob"
                      type="date"
                      value={formData.dob}
                      onChange={handleInputChange}
                      max={today}
                      required
                      className="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                    />
                  </div>

                  {/* Gender */}
                  <div>
                    <label htmlFor="gender" className="block text-sm font-medium text-gray-700 mb-2">
                      Gender <span className="text-red-500">*</span>
                    </label>
                    <select
                      id="gender"
                      name="gender"
                      value={formData.gender}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                      required
                    >
                      <option value="">Select Gender</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                    </select>
                  </div>
                </div>
              </div>

              {/* Pet Picture Section */}
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Pet Photo
                </h3>

                <div className="flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-6">
                  <div className="flex-shrink-0">
                    <img
                      src={formData.petPicture}
                      alt="Pet Preview"
                      className="h-32 w-32 rounded-xl object-cover border-2 border-gray-300 shadow-sm"
                    />
                  </div>

                  <div className="flex-grow">
                    <label
                      htmlFor="petPicture"
                      className="inline-flex items-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer shadow-md"
                    >
                      <FaCamera className="mr-2" />
                      <span>Choose Photo</span>
                      <input
                        id="petPicture"
                        type="file"
                        accept="image/*"
                        className="hidden"
                        onChange={handleImageUpload}
                      />
                    </label>

                    <p className="text-sm text-gray-500 mt-2">
                      JPEG, PNG or GIF (Max 5MB). This helps us personalize your experience.
                    </p>

                    {errors.petPicture && (
                      <p className="text-sm text-red-600 mt-1">{errors.petPicture}</p>
                    )}
                  </div>
                </div>
              </div>

              {/* Medical History Section */}
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Medical History
                </h3>

                <div className="space-y-4">
                  {medicalEntries.map((entry, index) => (
                    <div key={index} className="bg-blue-50 p-4 rounded-lg border border-blue-100">
                      <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                        <div className="md:col-span-5">
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            Condition <span className="text-red-500">*</span>
                          </label>
                          <input
                            type="text"
                            name="condition"
                            value={entry.condition}
                            onChange={(e) => handleMedicalEntryChange(index, e)}
                            placeholder="e.g., Allergies, Surgery, etc."
                            className="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                          />
                          {errors[`medicalCondition${index}`] && (
                            <p className="text-xs text-red-600 mt-1">
                              {errors[`medicalCondition${index}`]}
                            </p>
                          )}
                        </div>

                        <div className="md:col-span-4">
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            Date <span className="text-red-500">*</span>
                          </label>
                          <input
                            type="date"
                            name="date"
                            value={entry.date}
                            onChange={(e) => handleMedicalEntryChange(index, e)}
                            max={today}
                            className="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                          />
                          {errors[`medicalDate${index}`] && (
                            <p className="text-xs text-red-600 mt-1">
                              {errors[`medicalDate${index}`]}
                            </p>
                          )}
                        </div>

                        <div className="md:col-span-2 flex items-center h-full pt-5">
                          <label className="flex items-center space-x-2">
                            <input
                              type="checkbox"
                              name="isRecovered"
                              checked={entry.isRecovered}
                              onChange={(e) => handleMedicalEntryChange(index, e)}
                              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <span className="text-sm text-gray-700">Recovered</span>
                          </label>
                        </div>

                        <div className="md:col-span-1 flex items-center h-full pt-5 justify-center">
                          <button
                            type="button"
                            onClick={() => removeMedicalEntry(index)}
                            className="text-red-500 hover:text-red-700 transition-colors p-1 rounded-full hover:bg-red-50"
                            disabled={isLoading}
                          >
                            <FaTrash size={16} />
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}

                  <button
                    type="button"
                    onClick={addMedicalEntry}
                    disabled={isLoading}
                    className="flex items-center justify-center w-full py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors border border-dashed border-blue-300"
                  >
                    <FaPlusCircle className="mr-2" />
                    Add Medical Entry
                  </button>
                </div>
              </div>

              {/* Vaccination Log Section */}
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Vaccination Records
                </h3>

                <div className="space-y-4">
                  {vaccinationEntries.map((entry, index) => (
                    <div key={index} className="bg-green-50 p-4 rounded-lg border border-green-100">
                      <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                        <div className="md:col-span-5">
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            Vaccine Name <span className="text-red-500">*</span>
                          </label>
                          <input
                            type="text"
                            name="vaccineName"
                            value={entry.vaccineName}
                            onChange={(e) => handleVaccinationChange(index, e)}
                            placeholder="e.g., Rabies, Distemper, etc."
                            className="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                          />
                          {errors[`vaccineName${index}`] && (
                            <p className="text-xs text-red-600 mt-1">
                              {errors[`vaccineName${index}`]}
                            </p>
                          )}
                        </div>

                        <div className="md:col-span-4">
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            Date Administered <span className="text-red-500">*</span>
                          </label>
                          <input
                            type="date"
                            name="date"
                            value={entry.date}
                            onChange={(e) => handleVaccinationChange(index, e)}
                            max={today}
                            className="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                          />
                          {errors[`vaccineDate${index}`] && (
                            <p className="text-xs text-red-600 mt-1">
                              {errors[`vaccineDate${index}`]}
                            </p>
                          )}
                        </div>

                        <div className="md:col-span-3 flex items-center h-full pt-5 justify-center">
                          <button
                            type="button"
                            onClick={() => removeVaccinationEntry(index)}
                            className="text-red-500 hover:text-red-700 transition-colors p-1 rounded-full hover:bg-red-50"
                            disabled={isLoading}
                          >
                            <FaTrash size={16} />
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}

                  <button
                    type="button"
                    onClick={addVaccination}
                    disabled={isLoading}
                    className="flex items-center justify-center w-full py-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors border border-dashed border-green-300"
                  >
                    <FaPlusCircle className="mr-2" />
                    Add Vaccination Record
                  </button>
                </div>
              </div>

              {/* Form Actions */}
              <div className="flex flex-col-reverse md:flex-row justify-end space-y-4 space-y-reverse md:space-y-0 md:space-x-4 pt-6 border-t border-gray-200">
                <button
                  type="button"
                  onClick={() => navigate('/user/pets')}
                  disabled={isLoading}
                  className="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors shadow-sm disabled:bg-gray-200 disabled:cursor-not-allowed"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isLoading}
                  className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-md disabled:bg-blue-400 disabled:cursor-not-allowed flex items-center justify-center"
                >
                  {isLoading ? (
                    <>
                      <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Processing...
                    </>
                  ) : (
                    <>
                      <FaPaw className="mr-2" />
                      Register Pet
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <Footer/>
    </>
  );
};

const Add = () => {
  return <AddPetForm />;
};

export default Add;