import React, { useState, useEffect, useContext } from 'react';
import { FaPaw, FaPlusCircle, FaEdit, FaChevronDown, FaChevronUp, FaDog, FaCat, FaVenus, FaMars, FaNotesMedical, FaSyringe, FaUser, FaBirthdayCake, FaUpload, FaFile } from 'react-icons/fa';
import { useNavigate } from 'react-router-dom';
import toast, { Toaster } from 'react-hot-toast';
import axios from './../axios';
import { AuthContext } from '../auth/AuthContext';
import Header from '../components/Header';

const Pets = () => {
  const navigate = useNavigate();
  const [isLoading, setIsLoading] = useState(false);
  const [pets, setPets] = useState([]);
  const [expandedCards, setExpandedCards] = useState({});
  const [editingPet, setEditingPet] = useState(null);
  const [editFormData, setEditFormData] = useState({});
  const [uploadedDocs, setUploadedDocs] = useState([]);
  const { user } = useContext(AuthContext);

  // Convert user data to pet format if needed
  useEffect(() => {
    if (user && user.pet_name) {
      // If user has pet data, convert it to the pets array format
      const userPet = {
        id: user.id,
        name: user.pet_name,
        age: user.pet_age,
        gender: user.pet_gender,
        documents: []
      };

      // Add documents if they exist
      if (user.pet_doc1) {
        userPet.documents.push({
          name: "Document 1",
          url: user.pet_doc1
        });
      }

      if (user.pet_doc2) {
        userPet.documents.push({
          name: "Document 2",
          url: user.pet_doc2
        });
      }

      setPets([userPet]);
    } else {
      // If no pet data in user, fetch pets from API
      fetchPets();
    }
  }, [user]);

  const toggleCardExpansion = (id) => {
    setExpandedCards((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  const fetchPets = async () => {
    try {
      setIsLoading(true);
      const token = localStorage.getItem('token');
      if (!token) {
        navigate('/login');
        return;
      }
      const response = await axios.get('user/my_pets', {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      setPets(response.data.data);
      if (response.data.data.length === 0) {
        toast.info('No pets registered yet. Add your first pet!');
      }
    } catch (error) {
      const errorMessage =
        error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Error getting pets. Please try again later.';
      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  const getPetIcon = (type) => {
    if (!type) return <FaPaw className="text-blue-500" />;

    const lowerType = type.toLowerCase();
    if (lowerType.includes('dog') || lowerType === 'canine') {
      return <FaDog className="text-blue-500" />;
    } else if (lowerType.includes('cat') || lowerType === 'feline') {
      return <FaCat className="text-blue-500" />;
    } else {
      return <FaPaw className="text-blue-500" />;
    }
  };

  const getGenderIcon = (gender) => {
    if (!gender) return null;

    const lowerGender = gender.toLowerCase();
    if (lowerGender.includes('female') || lowerGender === 'f') {
      return <FaVenus className="text-pink-500 ml-1" />;
    } else if (lowerGender.includes('male') || lowerGender === 'm') {
      return <FaMars className="text-blue-500 ml-1" />;
    }
    return null;
  };

  const calculateAge = (dateOfBirth) => {
    if (!dateOfBirth) return 'Unknown';

    const birthDate = new Date(dateOfBirth);
    const today = new Date();
    let years = today.getFullYear() - birthDate.getFullYear();
    let months = today.getMonth() - birthDate.getMonth();

    if (months < 0) {
      years--;
      months += 12;
    }

    if (years === 0) {
      return `${months} month${months !== 1 ? 's' : ''}`;
    } else {
      return `${years} year${years !== 1 ? 's' : ''} ${months} month${months !== 1 ? 's' : ''}`;
    }
  };

  const handleEditClick = (pet) => {
    setEditingPet(pet.id);
    setEditFormData({
      name: pet.name || '',
      age: pet.age || '',
      gender: pet.gender || '',
    });
  };

  const handleEditFormChange = (field, value) => {
    setEditFormData(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const handleFileUpload = (e) => {
    const files = Array.from(e.target.files);
    setUploadedDocs(prev => [...prev, ...files]);
  };

  const removeUploadedDoc = (index) => {
    setUploadedDocs(prev => prev.filter((_, i) => i !== index));
  };

  const handleSaveEdit = async (petId) => {
    try {
      setIsLoading(true);
      const token = localStorage.getItem('token');

      const formData = new FormData();
      formData.append('pet_name', editFormData.name);
      formData.append('pet_age', editFormData.age);
      formData.append('pet_gender', editFormData.gender);

      // Add uploaded documents
      uploadedDocs.forEach((file, index) => {
        formData.append(`pet_doc${index + 1}`, file);
      });

      const response = await axios.put(`user/update-pet`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'multipart/form-data'
        }
      });

      if (response.data.success) {
        toast.success('Pet updated successfully!');
        setEditingPet(null);
        setUploadedDocs([]);
        // Refresh the page or update state
        window.location.reload();
      }
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Error updating pet. Please try again.';
      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  const handleCancelEdit = () => {
    setEditingPet(null);
    setUploadedDocs([]);
  };

  if (isLoading && !editingPet) {
    return (
      <div className="flex justify-center items-center h-screen bg-gray-50">
        <div className="text-center">
          <div className="border-t-4 border-blue-500 border-solid w-16 h-16 rounded-full animate-spin mb-4"></div>
          <p className="text-gray-600">Loading your pets...</p>
        </div>
      </div>
    );
  }

  return (
    <>
      <Header />

      <div className="min-h-screen bg-gradient-to-b from-blue-50 to-gray-50 py-8 px-4 md:px-8 mt-[70px]">
        <Toaster position="top-right" />

        <div className="max-w-7xl mx-auto">
          {/* Header Section */}
          <div className="flex flex-col md:flex-row justify-between items-center mb-8">
            <div>
              <h1 className="text-3xl md:text-4xl font-bold text-gray-900">Your Pet Family</h1>
              <p className="text-gray-600 mt-2">Manage all your furry friends in one place</p>
            </div>
            <button
              onClick={() => navigate('/user/pets/add')}
              className="mt-4 md:mt-0 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all duration-300 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-1"
            >
              <FaPlusCircle size={18} />
              <span>Add New Pet</span>
            </button>
          </div>

          {/* Stats Summary */}
          {pets.length > 0 && (
            <div className="bg-white rounded-2xl shadow-sm p-6 mb-8 grid grid-cols-1 md:grid-cols-4 gap-6">
              <div className="text-center">
                <div className="text-3xl font-bold text-blue-600">{pets.length}</div>
                <div className="text-gray-600">Total Pets</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-blue-600">
                  {pets.filter(pet => pet.documents && pet.documents.length > 0).length}
                </div>
                <div className="text-gray-600">With Documents</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-blue-600">
                  {pets.filter(pet => pet.age).length}
                </div>
                <div className="text-gray-600">With Age Info</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-blue-600">
                  {pets.reduce((total, pet) => total + (pet.documents ? pet.documents.length : 0), 0)}
                </div>
                <div className="text-gray-600">Total Documents</div>
              </div>
            </div>
          )}

          {/* Pets Grid */}
          {pets.length === 0 ? (
            <div className="bg-white rounded-2xl shadow-sm p-8 text-center">
              <div className="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <FaPaw className="text-blue-500 text-4xl" />
              </div>
              <h3 className="text-xl font-semibold text-gray-800 mb-2">No pets yet</h3>
              <p className="text-gray-600 mb-6">Add your first pet to get started with personalized care</p>
              <button
                onClick={() => navigate('/user/pets/add')}
                className="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors flex items-center space-x-2 mx-auto"
              >
                <FaPlusCircle size={18} />
                <span>Add Your First Pet</span>
              </button>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
              {pets.map((pet) => (
                <div
                  key={pet.id}
                  className="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 border border-gray-100"
                >
                  {editingPet === pet.id ? (
                    /* Edit Mode */
                    <div className="p-6">
                      <h3 className="text-xl font-bold text-blue-600 mb-4">Edit Pet Details</h3>

                      <div className="space-y-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Pet Name</label>
                          <input
                            type="text"
                            value={editFormData.name}
                            onChange={(e) => handleEditFormChange('name', e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Age</label>
                            <input
                              type="number"
                              value={editFormData.age}
                              onChange={(e) => handleEditFormChange('age', e.target.value)}
                              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              min="0"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select
                              value={editFormData.gender}
                              onChange={(e) => handleEditFormChange('gender', e.target.value)}
                              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                              <option value="male">Male</option>
                              <option value="female">Female</option>
                            </select>
                          </div>
                        </div>

                        {/* Document Upload */}
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">Upload Documents (Optional)</label>
                          <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                            <input
                              type="file"
                              multiple
                              onChange={handleFileUpload}
                              className="hidden"
                              id="file-upload"
                            />
                            <label htmlFor="file-upload" className="cursor-pointer text-blue-600 hover:text-blue-800 flex items-center justify-center">
                              <FaUpload className="mr-2" /> Select files to upload
                            </label>
                            <p className="text-xs text-gray-500 mt-1">PDF, JPG, PNG up to 5MB each</p>
                          </div>

                          {uploadedDocs.length > 0 && (
                            <div className="mt-2">
                              <p className="text-sm font-medium text-gray-700">Files to be uploaded:</p>
                              {uploadedDocs.map((file, index) => (
                                <div key={index} className="flex items-center justify-between text-sm bg-gray-100 p-2 rounded mt-1">
                                  <span>{file.name}</span>
                                  <button
                                    type="button"
                                    onClick={() => removeUploadedDoc(index)}
                                    className="text-red-500 hover:text-red-700"
                                  >
                                    ×
                                  </button>
                                </div>
                              ))}
                            </div>
                          )}
                        </div>

                        {/* Action Buttons */}
                        <div className="flex justify-end space-x-3 pt-4">
                          <button
                            type="button"
                            onClick={handleCancelEdit}
                            className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                          >
                            Cancel
                          </button>
                          <button
                            type="button"
                            onClick={() => handleSaveEdit(pet.id)}
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                          >
                            Save Changes
                          </button>
                        </div>
                      </div>
                    </div>
                  ) : (
                    /* View Mode */
                    <>
                      {/* Pet Image Header */}
                      <div className="h-48 bg-gradient-to-r from-blue-400 to-purple-500 relative overflow-hidden">
                        <div className="w-full h-full bg-gray-200 flex items-center justify-center">
                          <FaPaw className="text-white text-6xl" />
                        </div>
                        <div className="absolute top-4 right-4 bg-white rounded-full p-2 shadow-md">
                          <FaPaw className="text-blue-500" />
                        </div>
                      </div>

                      {/* Pet Info */}
                      <div className="p-6">
                        <div className="flex items-center justify-between mb-4">
                          <h3 className="text-xl font-bold text-gray-900">{pet.name}</h3>
                          <span className="flex items-center text-gray-600">
                            {pet.gender && getGenderIcon(pet.gender)}
                          </span>
                        </div>

                        <div className="space-y-3 text-gray-700 mb-6">
                          <div className="flex items-center">
                            <span className="font-medium text-gray-600 w-28">Age:</span>
                            <span>{pet.age ? `${pet.age} years` : 'Unknown'}</span>
                          </div>
                          <div className="flex items-center">
                            <span className="font-medium text-gray-600 w-28">Gender:</span>
                            <span>{pet.gender || 'Unknown'}</span>
                          </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex justify-between items-center border-t border-gray-100 pt-4">
                          <button
                            onClick={() => toggleCardExpansion(pet.id)}
                            className="flex items-center text-blue-600 hover:text-blue-800 transition-colors text-sm font-medium"
                          >
                            {expandedCards[pet.id] ? (
                              <>
                                <FaChevronUp className="mr-2" /> Hide Details
                              </>
                            ) : (
                              <>
                                <FaChevronDown className="mr-2" /> View Details
                              </>
                            )}
                          </button>
                          <button
                            onClick={() => handleEditClick(pet)}
                            className="text-blue-600 hover:text-blue-800 transition-colors p-2 rounded-lg hover:bg-blue-50"
                            title="Edit Pet"
                          >
                            <FaEdit size={18} />
                          </button>
                        </div>

                        {/* Expanded Details */}
                        {expandedCards[pet.id] && (
                          <div className="mt-6 space-y-6 border-t border-gray-100 pt-6">
                            {/* Documents */}
                            {pet.documents && pet.documents.length > 0 && (
                              <div>
                                <div className="flex items-center mb-3 text-gray-800 font-medium">
                                  <FaFile className="text-blue-500 mr-2" />
                                  Documents
                                </div>
                                <ul className="space-y-2">
                                  {pet.documents.map((doc, index) => (
                                    <li key={index} className="bg-gray-50 rounded-lg p-3 text-sm">
                                      <div className="font-medium">{doc.name}</div>
                                      <div className="text-gray-600 mt-1">
                                        <a
                                          href={doc.url}
                                          target="_blank"
                                          rel="noopener noreferrer"
                                          className="text-blue-600 hover:underline flex items-center"
                                        >
                                          <FaFile className="mr-1" /> View Document
                                        </a>
                                      </div>
                                    </li>
                                  ))}
                                </ul>
                              </div>
                            )}

                            {(!pet.documents || pet.documents.length === 0) && (
                              <p className="text-gray-500 text-sm bg-gray-50 rounded-lg p-3">No documents uploaded yet.</p>
                            )}
                          </div>
                        )}
                      </div>
                    </>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </>
  );
};

export default Pets;