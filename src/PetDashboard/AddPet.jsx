import React, { useState, useEffect, useRef, useContext } from 'react';
import axios from 'axios';
import { useAuth } from '../auth/AuthContext';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';

const API_BASE_URL = 'https://snoutiq.com/backend/api';
const CACHE_KEYS = {
  DOG_BREEDS: 'dog_breeds_cache',
  PETS_DATA: 'pets_data_cache',
};

// Design System Constants
const DESIGN = {
  COLORS: {
    primary: '#667eea',
    secondary: '#764ba2',
    white: '#FFFFFF',
    black: '#000000',
    gray50: '#F9FAFB',
    gray100: '#F3F4F6',
    gray200: '#E5E7EB',
    gray300: '#D1D5DB',
    gray400: '#9CA3AF',
    gray500: '#6B7280',
    gray600: '#4B5563',
    gray700: '#374151',
    gray800: '#1F2937',
    gray900: '#111827',
    success: '#10B981',
    warning: '#F59E0B',
    error: '#EF4444',
    info: '#3B82F6',
    background: '#F0F4FF',
    surface: '#FFFFFF',
    overlay: 'rgba(0, 0, 0, 0.5)',
  },
  GRADIENTS: {
    primary: ['#667eea', '#764ba2'],
    background: ['#F8F9FA', '#E5E7EB'],
  },
};

const AddPet = () => {
  const { user, token } = useAuth();
  const navigate = useNavigate()

  
  const [formData, setFormData] = useState({
    name: '',
    petType: '',
    petGender: '',
    breed: '',
    age: '',
    weight: '',
    avatar: 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=150&h=150&fit=crop',
  });
  
  const [errors, setErrors] = useState({});
  const [isSaving, setIsSaving] = useState(false);
  const [dogBreeds, setDogBreeds] = useState([]);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [showBreedModal, setShowBreedModal] = useState(false);
  const [filteredBreeds, setFilteredBreeds] = useState([]);
  const [breedSearch, setBreedSearch] = useState('');

  const catBreedOptions = [
    { label: 'American Shorthair', value: 'american_shorthair' },
    { label: 'Domestic Shorthair', value: 'domestic_shorthair' },
    { label: 'Siamese', value: 'siamese' },
    { label: 'Persian', value: 'persian' },
    { label: 'Maine Coon', value: 'maine_coon' },
    { label: 'Bengal', value: 'bengal' },
    { label: 'Ragdoll', value: 'ragdoll' },
    { label: 'Sphynx', value: 'sphynx' },
    { label: 'British Shorthair', value: 'british_shorthair' },
    { label: 'Mixed Breed', value: 'mixed_breed' },
    { label: 'Other', value: 'other' },
  ];

  useEffect(() => {
    fetchDogBreeds();
  }, []);

  const cacheData = async (key, data) => {
    try {
      localStorage.setItem(key, JSON.stringify({ data, timestamp: Date.now() }));
    } catch (error) {
      console.error('Cache save error:', error);
    }
  };

  const getCachedData = async (key) => {
    try {
      const cached = localStorage.getItem(key);
      if (cached) {
        const { data, timestamp } = JSON.parse(cached);
        if (Date.now() - timestamp < 24 * 60 * 60 * 1000) return data;
      }
    } catch (error) {
      console.error('Cache read error:', error);
    }
    return null;
  };

  const fetchDogBreeds = async () => {
    try {
      setLoadingBreeds(true);
      const cachedBreeds = await getCachedData(CACHE_KEYS.DOG_BREEDS);
      if (cachedBreeds) {
        setDogBreeds(cachedBreeds);
        setLoadingBreeds(false);
        return;
      }

      const response = await axios.get(`${API_BASE_URL}/dog-breeds/all`, { timeout: 10000 });
      
      if (response.data.status === 'success' && response.data.breeds) {
        const breeds = [];
        Object.keys(response.data.breeds).forEach(breedKey => {
          const subBreeds = response.data.breeds[breedKey];
          if (subBreeds.length === 0) {
            breeds.push({
              label: formatBreedName(breedKey),
              value: breedKey
            });
          } else {
            subBreeds.forEach(subBreed => {
              breeds.push({
                label: formatBreedName(breedKey, subBreed),
                value: `${breedKey}/${subBreed}`
              });
            });
          }
        });
        
        breeds.sort((a, b) => a.label.localeCompare(b.label));
        breeds.push(
          { label: 'Mixed Breed', value: 'mixed_breed' },
          { label: 'Other', value: 'other' }
        );
        
        setDogBreeds(breeds);
        await cacheData(CACHE_KEYS.DOG_BREEDS, breeds);
      }
    } catch (error) {
      console.error('Error fetching breeds:', error);
      toast.error('Failed to load dog breeds. Using default options.');
      setDogBreeds([
        { label: 'Mixed Breed', value: 'mixed_breed' },
        { label: 'Other', value: 'other' }
      ]);
    } finally {
      setLoadingBreeds(false);
    }
  };

  const formatBreedName = (breedKey, subBreed = null) => {
    let formattedName = breedKey.split(/[-_\s]/).map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
    if (subBreed) {
      const formattedSubBreed = subBreed.split(/[-_\s]/).map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
      formattedName = `${formattedSubBreed} ${formattedName}`;
    }
    return formattedName;
  };

  const getBreedOptions = () => {
    return formData.petType === 'dog' ? dogBreeds : formData.petType === 'cat' ? catBreedOptions : [];
  };

  const handleBreedSearch = (text) => {
    setBreedSearch(text);
    const options = getBreedOptions();
    const filtered = options.filter(breed => breed.label.toLowerCase().includes(text.toLowerCase()));
    setFilteredBreeds(filtered);
  };

  const openBreedModal = () => {
    if (!formData.petType) {
      toast.error('Please select whether it\'s a dog or cat first');
      return;
    }
    const options = getBreedOptions();
    setFilteredBreeds(options);
    setBreedSearch('');
    setShowBreedModal(true);
  };

  const selectBreed = (breed) => {
    setFormData(prev => ({ ...prev, breed: breed.value }));
    setShowBreedModal(false);
    setBreedSearch('');
    toast.success(`Selected breed: ${breed.label}`);
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Pet name is required';
    } else if (formData.name.trim().length < 2) {
      newErrors.name = 'Name must be at least 2 characters';
    }

    if (!formData.petType) newErrors.petType = 'Please select pet type';
    if (!formData.petGender) newErrors.petGender = 'Please select gender';
    if (!formData.breed) newErrors.breed = 'Please select breed';

    if (!formData.age.trim()) {
      newErrors.age = 'Age is required';
    } else if (isNaN(formData.age) || parseFloat(formData.age) < 0 || parseFloat(formData.age) > 30) {
      newErrors.age = 'Enter valid age (0-30)';
    }

    if (formData.weight.trim() && (isNaN(formData.weight) || parseFloat(formData.weight) <= 0)) {
      newErrors.weight = 'Enter valid weight';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleAddPet = async () => {
    if (!validate()) {
      toast.error('Please fix the errors before submitting.');
      return;
    }

    if (!user) {
      toast.error('User ID not available. Please login again.');
      return;
    }

    const savingToast = toast.loading('Adding your pet...');

    try {
      setIsSaving(true);
      
      const petData = {
        name: formData.name.trim(),
        breed: formData.breed,
        pet_age: formData.age ? parseFloat(formData.age) : null,
        pet_gender: formData.petGender,
        weight: formData.weight ? parseFloat(formData.weight) : null,
      };

      const response = await axios.post(`${API_BASE_URL}/users/${user.id}/pets`, petData, {
        timeout: 15000,
        headers: token ? { Authorization: `Bearer ${token}` } : {}
      });
      
      if (response.data.status === 'success') {
        toast.dismiss(savingToast);
        toast.success(
          `🎉 ${formData.name} has been added to your family!`,
          {
            duration: 5000,
            icon: '🐾',
            style: {
              background: '#10B981',
              color: '#FFFFFF',
              fontSize: '16px',
              padding: '16px',
            },
          }
        );
        window.location.reload();
        navigate('/user-dashboard/add-pet');
      } else {
        throw new Error('Failed to add pet');
      }
    } catch (error) {
      console.error('Error adding pet:', error);
      toast.dismiss(savingToast);
      toast.error(
        error.response?.data?.message || 'Failed to add pet. Please try again.',
        {
          duration: 4000,
          style: {
            background: '#EF4444',
            color: '#FFFFFF',
          },
        }
      );
    } finally {
      setIsSaving(false);
    }
  };

  const updateField = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }));
    }
  };

  const renderBreedItem = (item) => (
    <button
      key={item.value}
      className={`flex justify-between items-center w-full p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors ${
        formData.breed === item.value ? 'bg-blue-50' : ''
      }`}
      onClick={() => selectBreed(item)}
    >
      <span className={`font-medium ${
        formData.breed === item.value ? 'text-blue-600' : 'text-gray-900'
      }`}>
        {item.label}
      </span>
      {formData.breed === item.value && (
        <span className="text-blue-600 text-lg">✓</span>
      )}
    </button>
  );

  return (
    <div className="">
      {/* Header */}
      <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-3">
        <div className="max-w-4xl mx-auto">
          <div className="flex items-center justify-between">
            <button 
              onClick={() => window.history.back()}
              className="p-2 bg-white bg-opacity-20 rounded-lg hover:bg-opacity-30 transition-colors"
            >
              <span className="text-white text-lg">←</span>
            </button>
            <h1 className="text-xl font-bold">Add New Pet</h1>
            <div className="w-8"></div> {/* Spacer for balance */}
          </div>
        </div>
      </div>

      {/* Main Form */}
      <div className="max-w-4xl mx-auto p-6 space-y-6 pb-24">
        {/* Avatar Section */}
        <div className="text-center">
          <div className="relative inline-block">
            <img 
              src={formData.avatar} 
              alt="Pet avatar" 
              className="w-24 h-24 rounded-full border-4 border-white shadow-lg"
            />
            <button 
              className="absolute bottom-0 right-0 bg-blue-600 text-white w-10 h-10 rounded-full border-4 border-white shadow-md hover:bg-blue-700 transition-colors"
              onClick={() => toast('📸 Photo upload feature coming soon!', { icon: '🚀' })}
            >
              <span className="text-lg">📷</span>
            </button>
          </div>
          <p className="text-gray-600 text-sm mt-2">Add Photo (Optional)</p>
        </div>

        {/* Pet Name */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-2">
            Pet Name *
          </label>
          <input
            type="text"
            placeholder="Enter your pet's name"
            value={formData.name}
            onChange={(e) => updateField('name', e.target.value)}
            className={`w-full p-4 border-2 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
              errors.name ? 'border-red-500' : 'border-gray-200'
            }`}
          />
          {errors.name && (
            <p className="text-red-500 text-sm mt-1">{errors.name}</p>
          )}
        </div>

        {/* Pet Type */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-2">
            Pet Type *
          </label>
          <div className="grid grid-cols-2 gap-3">
            {[
              { value: 'dog', label: 'Dog', icon: '🐕' },
              { value: 'cat', label: 'Cat', icon: '🐈' },
            ].map((type) => (
              <button
                key={type.value}
                onClick={() => updateField('petType', type.value)}
                className={`p-4 border-2 rounded-xl flex items-center justify-center space-x-2 transition-all ${
                  formData.petType === type.value
                    ? 'border-blue-500 bg-blue-500 text-white shadow-lg'
                    : 'border-gray-200 bg-white text-gray-700 hover:border-blue-300'
                }`}
              >
                <span className="text-xl">{type.icon}</span>
                <span className="font-semibold">{type.label}</span>
              </button>
            ))}
          </div>
          {errors.petType && (
            <p className="text-red-500 text-sm mt-1">{errors.petType}</p>
          )}
        </div>

        {/* Breed */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-2">
            Breed *
          </label>
          <button
            onClick={openBreedModal}
            disabled={!formData.petType || loadingBreeds}
            className={`w-full p-4 border-2 rounded-xl flex justify-between items-center transition-colors ${
              errors.breed 
                ? 'border-red-500' 
                : 'border-gray-200 hover:border-blue-300'
            } ${
              !formData.petType || loadingBreeds ? 'opacity-50 cursor-not-allowed' : ''
            }`}
          >
            <span className={formData.breed ? "text-gray-900" : "text-gray-500"}>
              {formData.breed
                ? getBreedOptions().find(b => b.value === formData.breed)?.label
                : formData.petType
                ? loadingBreeds ? 'Loading...' : `Select ${formData.petType} breed`
                : 'Select pet type first'}
            </span>
            <span className="text-gray-400">▼</span>
          </button>
          {errors.breed && (
            <p className="text-red-500 text-sm mt-1">{errors.breed}</p>
          )}
        </div>

        {/* Age and Weight */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Age (years) *
            </label>
            <input
              type="number"
              placeholder="0"
              value={formData.age}
              onChange={(e) => updateField('age', e.target.value)}
              className={`w-full p-4 border-2 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                errors.age ? 'border-red-500' : 'border-gray-200'
              }`}
            />
            {errors.age && (
              <p className="text-red-500 text-sm mt-1">{errors.age}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Weight (kg)
            </label>
            <input
              type="number"
              placeholder="0.0"
              step="0.1"
              value={formData.weight}
              onChange={(e) => updateField('weight', e.target.value)}
              className={`w-full p-4 border-2 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                errors.weight ? 'border-red-500' : 'border-gray-200'
              }`}
            />
            {errors.weight && (
              <p className="text-red-500 text-sm mt-1">{errors.weight}</p>
            )}
          </div>
        </div>

        {/* Gender */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-2">
            Gender *
          </label>
          <div className="grid grid-cols-2 gap-3">
            {['Male', 'Female'].map((gender) => (
              <button
                key={gender}
                onClick={() => updateField('petGender', gender)}
                className={`p-4 border-2 rounded-xl flex items-center justify-center space-x-2 transition-all ${
                  formData.petGender === gender
                    ? 'border-blue-500 bg-blue-500 text-white shadow-lg'
                    : 'border-gray-200 bg-white text-gray-700 hover:border-blue-300'
                }`}
              >
                <span className="text-lg">
                  {gender === 'Male' ? '♂' : '♀'}
                </span>
                <span className="font-semibold">{gender}</span>
              </button>
            ))}
          </div>
          {errors.petGender && (
            <p className="text-red-500 text-sm mt-1">{errors.petGender}</p>
          )}
        </div>

        {/* Info Card */}
        <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start space-x-3">
          <span className="text-blue-500 text-lg">ℹ️</span>
          <p className="text-blue-700 text-sm flex-1">
            All fields marked with * are required to add your pet.
          </p>
        </div>
      </div>

      {/* Submit Button */}
      <div >
        <div className="max-w-4xl mx-auto">
          <button
            onClick={handleAddPet}
            disabled={isSaving}
            className={`w-full py-4 px-6 bg-gradient-to-r from-purple-600 to-blue-600 text-white font-bold rounded-xl shadow-lg hover:from-purple-700 hover:to-blue-700 transition-all flex items-center justify-center space-x-2 ${
              isSaving ? 'opacity-50 cursor-not-allowed' : ''
            }`}
          >
            {isSaving ? (
              <>
                <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                <span>Adding Pet...</span>
              </>
            ) : (
              <>
                <span className="text-lg">✓</span>
                <span>Add Pet</span>
              </>
            )}
          </button>
        </div>
      </div>

      {/* Breed Selection Modal */}
      {showBreedModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50">
          <div className="bg-white rounded-t-3xl w-full max-w-2xl h-[80vh] overflow-hidden">
            {/* Modal Header */}
            <div className="flex justify-between items-center p-6 border-b border-gray-200">
              <h3 className="text-xl font-bold text-gray-900">
                Select {formData.petType === 'dog' ? 'Dog' : 'Cat'} Breed
              </h3>
              <button
                onClick={() => setShowBreedModal(false)}
                className="text-gray-500 hover:text-gray-700 text-2xl transition-colors"
              >
                ×
              </button>
            </div>

            {/* Search */}
            <div className="p-4 border-b border-gray-200">
              <div className="relative">
                <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                  🔍
                </span>
                <input
                  type="text"
                  placeholder="Search breeds..."
                  value={breedSearch}
                  onChange={(e) => handleBreedSearch(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            {/* Breed List */}
            <div className="h-full overflow-y-auto">
              {filteredBreeds.length > 0 ? (
                filteredBreeds.map(renderBreedItem)
              ) : (
                <div className="text-center py-8 text-gray-500">
                  No breeds found matching your search
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AddPet;