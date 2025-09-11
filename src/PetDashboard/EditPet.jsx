import React, { useState,useEffect  } from 'react';
import { FaPaw, FaPlusCircle, FaCamera, FaTrash } from 'react-icons/fa';
import { useNavigate,useParams } from 'react-router-dom';
import axios from '../axios';
import toast from 'react-hot-toast';

const Edit = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const [formData, setFormData] = useState({
    name: '',
    type: '',
    breed: '',
    dob: '',
    gender: '',
    petPicture: 'https://placehold.co/50x50?text=Pet',
  });
  const [petPictureFile, setPetPictureFile] = useState(null);
  const [isPetPictureUpdated, setIsPetPictureUpdated] = useState(false);
  const [medicalEntries, setMedicalEntries] = useState([]);
  const [vaccinationEntries, setVaccinationEntries] = useState([]);
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const today = new Date().toISOString().split('T')[0];
  const [isLoadingMain , setIsLoadingMain] = useState(false);
  const get_data = async ()=>{
    setIsLoadingMain(true);
    try{
      const token = localStorage.getItem('token');
      const res = await axios.get('user/pet/'+id, {
        headers: {
          'Content-Type': 'multipart/form-data',
          Authorization: `Bearer ${token}`,
        },
      });
      setFormData({
    name: res.data.pet.name,
    type: res.data.pet.type,
    breed: res.data.pet.breed,
    dob: res.data.pet.dob,
    gender: res.data.pet.gender,
    petPicture: res.data.pet.pic_link,
  });
  setMedicalEntries(res.data.pet.medical_history || []);
  setVaccinationEntries(res.data.pet.vaccination_log || []);
    }catch (error) {
      const errorMessage =
        error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Error getting pet data. Please try again later.';
      toast.error(errorMessage);
      console.log(error);
  }finally{
    setIsLoadingMain(false);
  }
}
useEffect(()=>{
get_data();
},[])
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

        const res = await axios.post('user/pet/'+id, formDataPayload, {
          headers: { 'Content-Type': 'multipart/form-data' ,
            Authorization: `Bearer ${localStorage.getItem('token')}`,
          },
        });
        console.log('Pet saved:', res.data);
        toast.success('Pet registered successfully!');
        navigate('/user/pets');
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
     if (isLoadingMain) {
    return (
      <div className="flex justify-center items-center h-screen">
        <div className="border-t-4 border-blue-500 border-solid w-16 h-16 rounded-full animate-spin"></div>
      </div>
    );
  }
  return (
    <div className="flex flex-col  p-6 mt-[70px]">
      {/* <Toaster /> */}
      <div className="max-w-4xl mx-auto w-full bg-white rounded-2xl shadow-lg p-8">
        <h2 className="text-3xl font-bold text-blue-900 mb-8">Pet Profile</h2>
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Two-Column Fields */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            {/* Pet Name */}
            <div>
              <label
                htmlFor="name"
                className="block text-sm font-medium text-blue-700 mb-2"
              >
                Pet Name
              </label>
              <input
                id="name"
                name="name"
                type="text"
                value={formData.name}
                onChange={handleInputChange}
                className={`w-full px-4 py-3 rounded-lg border  border-blue-300 bg-blue-50/20 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors`}
                placeholder="e.g., Luna" required
              />
            
            </div>


    {/* Pet Type */}
            <div>
              <label
                htmlFor="name"
                className="block text-sm font-medium text-blue-700 mb-2"
              >
                Pet Type
              </label>
              <select
                id="type"
                name="type"
                
                value={formData.type}
                onChange={handleInputChange}
                className={`w-full px-4 py-3 rounded-lg border  border-blue-300 bg-blue-50/20 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors`}
                required
              >
            <option value="dog">Dog</option>
            <option value="cat">Cat</option>
            <option value="bird">Bird</option>
            <option value="snake">Snake</option>
            </select>
            </div>

            {/* Breed */}
            <div>
              <label
                htmlFor="breed"
                className="block text-sm font-medium text-blue-700 mb-2"
              >
                Breed
              </label>
              <input
                id="breed"
                name="breed"
                type="text"
                value={formData.breed}
                onChange={handleInputChange}
                className={`w-full px-4 py-3 rounded-lg border  border-blue-300  bg-blue-50/20 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors`}
                placeholder="e.g., Golden Retriever" required
              />
             
            </div>

            {/* Date of Birth */}
            <div>
              <label
                htmlFor="dob"
                className="block text-sm font-medium text-blue-700 mb-2"
              >
                Date of Birth
              </label>
              <input
                id="dob"
                name="dob"
                type="date"
                value={formData.dob}
                onChange={handleInputChange}
                max={today} required
                className={`w-full px-4 py-3 rounded-lg border  bg-blue-50/20 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors`}
              />
              
            </div>

            {/* Gender */}
            <div>
              <label
                htmlFor="gender"
                className="block text-sm font-medium text-blue-700 mb-2"
              >
                Gender
              </label>
              <select
                id="gender"
                name="gender"
                value={formData.gender}
                onChange={handleInputChange}
                className={`w-full px-4 py-3 rounded-lg border  border-blue-300 bg-blue-50/20 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors`}
             required  >
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
              
            </div>
          </div>

          {/* Pet Picture */}
          <div>
            <label
              htmlFor="petPicture"
              className="block text-sm font-medium text-blue-700 mb-2"
            >
              Pet Picture
            </label>
            <div className="flex items-center space-x-4">
              <img
                src={formData.petPicture}
                alt="Pet Preview"
                className="h-20 w-20 rounded-full border-2 border-blue-300 object-cover"
              />
              <label
                htmlFor="petPicture"
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2 cursor-pointer"
              >
                <FaCamera size={16} />
                <span>Upload Picture</span>
                <input
                  id="petPicture"
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={handleImageUpload}
                />
              </label>
            </div>
            {errors.petPicture && (
              <p className="text-xs text-coral-500 mt-1">{errors.petPicture}</p>
            )}
          </div>

          {/* Medical History */}
          <div className="pt-4">
            <label className="block text-sm font-medium text-blue-700 mb-2">
              Medical History
            </label>
            <div className="space-y-4 bg-blue-50/10 p-4 rounded-lg">
              {medicalEntries.map((entry, index) => (
                <div
                  key={index}
                  className="grid grid-cols-1 sm:grid-cols-6 gap-4 items-center"
                >
                  <div className="sm:col-span-2">
                    <input
                      type="text"
                      name="condition"
                      value={entry.condition}
                      onChange={(e) => handleMedicalEntryChange(index, e)}
                      placeholder="Condition"
                      className={`w-full px-4 py-3 rounded-lg border ${
                        errors[`medicalCondition${index}`]
                          ? 'border-coral-500'
                          : 'border-blue-300'
                      } bg-blue-50/20 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500`}
                      required
                    />
                    {errors[`medicalCondition${index}`] && (
                      <p className="text-xs text-coral-500 mt-1">
                        {errors[`medicalCondition${index}`]}
                      </p>
                    )}
                  </div>
                  <div className="sm:col-span-2">
                    <input
                      type="date"
                      name="date"
                      value={entry.date}
                      onChange={(e) => handleMedicalEntryChange(index, e)}
                      max={today}
                      className={`w-full px-4 py-3 rounded-lg border ${
                        errors[`medicalDate${index}`] ? 'border-coral-500' : 'border-blue-300'
                      } bg-blue-50/20 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500`}
                      required
                    />
                    {errors[`medicalDate${index}`] && (
                      <p className="text-xs text-coral-500 mt-1">
                        {errors[`medicalDate${index}`]}
                      </p>
                    )}
                  </div>
                  <label className="flex items-center space-x-2 sm:col-span-1">
                    <input
                      type="checkbox"
                      name="isRecovered"
                      checked={entry.isRecovered}
                      onChange={(e) => handleMedicalEntryChange(index, e)}
                      className="h-4 w-4 text-lime-600 focus:ring-lime-500 border-blue-300 rounded"
                       
                    />
                    <span className="text-sm text-gray-700">Recovered</span>
                  </label>
                  <button
                    type="button"
                    onClick={() => removeMedicalEntry(index)}
                    className="sm:col-span-1 text-coral-500 hover:text-coral-700 transition-colors"
                    disabled={isLoading}
                  >
                    <FaTrash size={16} />
                  </button>
                </div>
              ))}
              <button
                type="button"
                onClick={addMedicalEntry}
                disabled={isLoading}
                className="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2 disabled:bg-blue-400 disabled:cursor-not-allowed"
              >
                <FaPlusCircle size={16} />
                <span>Add Medical Entry</span>
              </button>
            </div>
          </div>

          {/* Vaccination Log */}
          <div className="pt-4">
            <label className="block text-sm font-medium text-blue-700 mb-2">
              Vaccination Log
            </label>
            <div className="space-y-4 bg-blue-50/10 p-4 rounded-lg">
              {vaccinationEntries.map((entry, index) => (
                <div
                  key={index}
                  className="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center"
                >
                  <div>
                    <input
                      type="text"
                      name="vaccineName"
                      value={entry.vaccineName}
                      onChange={(e) => handleVaccinationChange(index, e)}
                      placeholder="Vaccine Name"
                      className={`w-full px-4 py-3 rounded-lg border ${
                        errors[`vaccineName${index}`] ? 'border-coral-500' : 'border-blue-300'
                      } bg-blue-50/20 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500`}
                      required
                    />
                    {errors[`vaccineName${index}`] && (
                      <p className="text-xs text-coral-500 mt-1">
                        {errors[`vaccineName${index}`]}
                      </p>
                    )}
                  </div>
                  <div>
                    <input
                      type="date"
                      name="date"
                      value={entry.date}
                      onChange={(e) => handleVaccinationChange(index, e)}
                      max={today}
                      className={`w-full px-4 py-3 rounded-lg border ${
                        errors[`vaccineDate${index}`] ? 'border-coral-500' : 'border-blue-300'
                      } bg-blue-50/20 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500`}
                      required
                    />
                    {errors[`vaccineDate${index}`] && (
                      <p className="text-xs text-coral-500 mt-1">
                        {errors[`vaccineDate${index}`]}
                      </p>
                    )}
                  </div>
                  <button
                    type="button"
                    onClick={() => removeVaccinationEntry(index)}
                    className="text-coral-500 hover:text-coral-700 transition-colors"
                    disabled={isLoading}
                  >
                    <FaTrash size={16} />
                  </button>
                </div>
              ))}
              <button
                type="button"
                onClick={addVaccination}
                disabled={isLoading}
                className="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2 disabled:bg-blue-400 disabled:cursor-not-allowed"
              >
                <FaPlusCircle size={16} />
                <span>Add Vaccination</span>
              </button>
            </div>
          </div>

          {/* Form Actions */}
          <div className="flex justify-end space-x-4 pt-6">
            <button
              type="button"
              onClick={() => navigate('/user/pets')}
              disabled={isLoading}
              className="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors shadow-sm disabled:bg-gray-300 disabled:cursor-not-allowed"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isLoading}
              className={`px-6 py-3 rounded-lg font-medium text-white transition-colors flex items-center space-x-2 ${
                isLoading ? 'bg-blue-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'
              } shadow-md`}
            >
              {isLoading ? (
                <svg className="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                  <circle
                    className="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    strokeWidth="4"
                    fill="none"
                  />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                </svg>
              ) : (
                <FaPaw size={16} />
              )}
              <span>Save Pet</span>
            </button>
          </div>
          
        </form>
      </div>

    
    </div>
  );
};

 
export default Edit;