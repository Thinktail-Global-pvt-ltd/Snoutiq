import React, { useState, useEffect, useContext } from 'react';
import { AuthContext } from '../auth/AuthContext';
import axiosClient from '../axios';
import { useNavigate } from 'react-router-dom';

const VetOwnerProfile = () => {
  const [activeTab, setActiveTab] = useState(0);
  const [isEditing, setIsEditing] = useState(false);
  const [profileData, setProfileData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', type: 'success' });
  const [formData, setFormData] = useState({});
  const [profileImage, setProfileImage] = useState(null);
  const [profileImagePreview, setProfileImagePreview] = useState('');
  
  const { user } = useContext(AuthContext);
  const navigate = useNavigate();

  useEffect(() => {
    fetchProfileData();
  }, []);

  const fetchProfileData = async () => {
    try {
      setLoading(true);
      const response = await axiosClient.get('/vet/profile');
      setProfileData(response.data);
      setFormData(response.data);
      setProfileImagePreview(response.data.profile_image || '');
      setLoading(false);
    } catch (error) {
      console.error('Error fetching profile data:', error);
      setSnackbar({ open: true, message: 'Failed to load profile data', type: 'error' });
      setLoading(false);
    }
  };

  const handleTabChange = (newValue) => {
    setActiveTab(newValue);
  };

  const handleEditToggle = () => {
    if (isEditing) {
      setFormData(profileData);
    }
    setIsEditing(!isEditing);
  };

  const handleInputChange = (field, value) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setProfileImage(file);
      const reader = new FileReader();
      reader.onload = (e) => {
        setProfileImagePreview(e.target.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSave = async () => {
    try {
      setSaving(true);
      const data = new FormData();
      
      Object.keys(formData).forEach(key => {
        if (formData[key] !== null && formData[key] !== undefined) {
          data.append(key, formData[key]);
        }
      });
      
      if (profileImage) {
        data.append('profile_image', profileImage);
      }
      
      const response = await axiosClient.post('/vet/profile/update', data, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
      
      setProfileData(response.data);
      setIsEditing(false);
      setSnackbar({ open: true, message: 'Profile updated successfully', type: 'success' });
    } catch (error) {
      console.error('Error updating profile:', error);
      setSnackbar({ open: true, message: 'Failed to update profile', type: 'error' });
    } finally {
      setSaving(false);
    }
  };

  const handleSnackbarClose = () => {
    setSnackbar({ ...snackbar, open: false });
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-60">
        <p>Loading profile...</p>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto p-6">
      {/* Header */}
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-3xl font-bold text-gray-900">
          My Profile
        </h1>
        <div>
          {isEditing ? (
            <>
              <button
                className="mr-4 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                onClick={handleEditToggle}
                disabled={saving}
              >
                Cancel
              </button>
              <button
                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center"
                onClick={handleSave}
                disabled={saving}
              >
                {saving ? 'Saving...' : 'Save Changes'}
              </button>
            </>
          ) : (
            <button
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center"
              onClick={handleEditToggle}
            >
              Edit Profile
            </button>
          )}
        </div>
      </div>

      {/* Main Content */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        {/* Left Sidebar - Profile Summary */}
        <div className="md:col-span-1">
          <div className="bg-white p-6 rounded-lg shadow-sm text-center">
            <div className="relative inline-block">
              <img
                src={profileImagePreview || '/default-avatar.png'}
                alt="Profile"
                className="w-32 h-32 rounded-full mx-auto mb-4 object-cover"
              />
              {isEditing && (
                <label className="absolute bottom-2 right-2 bg-white p-2 rounded-full shadow-md cursor-pointer">
                  <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <input
                    type="file"
                    className="hidden"
                    accept="image/*"
                    onChange={handleImageChange}
                  />
                </label>
              )}
            </div>
            
            <h2 className="text-xl font-semibold mb-2">
              {formData.clinic_name || 'Your Clinic Name'}
            </h2>
            <p className="text-gray-600 mb-6">
              Veterinary Clinic
            </p>
            
            <div className="text-left space-y-3">
              <div className="flex items-center">
                <svg className="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p className="text-sm text-gray-700">
                  {formData.address || 'No address provided'}
                </p>
              </div>
              
              <div className="flex items-center">
                <svg className="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <p className="text-sm text-gray-700">
                  {formData.mobile || 'No phone number provided'}
                </p>
              </div>
              
              <div className="flex items-center">
                <svg className="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <p className="text-sm text-gray-700">
                  {formData.email || 'No email provided'}
                </p>
              </div>
              
              <div className="flex items-center">
                <svg className="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                </svg>
                <p className="text-sm text-gray-700">
                  {formData.employee_id ? `ID: ${formData.employee_id}` : 'No employee ID'}
                </p>
              </div>
            </div>
            
            <hr className="my-6" />
            
            <div>
              <p className="text-sm font-medium mb-2">Account Status</p>
              <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${
                profileData?.status === 'active' 
                  ? 'bg-green-100 text-green-800' 
                  : 'bg-gray-100 text-gray-800'
              }`}>
                {profileData?.status === 'active' ? 'Active' : 'Inactive'}
              </span>
            </div>
          </div>
        </div>

        {/* Right Content - Detailed Information */}
        <div className="md:col-span-3">
          <div className="bg-white rounded-lg shadow-sm">
            <div className="border-b">
              <div className="flex">
                <button
                  className={`px-4 py-3 font-medium text-sm ${activeTab === 0 ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'}`}
                  onClick={() => handleTabChange(0)}
                >
                  Basic Information
                </button>
                <button
                  className={`px-4 py-3 font-medium text-sm ${activeTab === 1 ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'}`}
                  onClick={() => handleTabChange(1)}
                >
                  Clinic Details
                </button>
                <button
                  className={`px-4 py-3 font-medium text-sm ${activeTab === 2 ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'}`}
                  onClick={() => handleTabChange(2)}
                >
                  Services & Pricing
                </button>
              </div>
            </div>

            {/* Basic Information Tab */}
            {activeTab === 0 && (
              <div className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Clinic Name</label>
                    <input
                      type="text"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={formData.clinic_name || ''}
                      onChange={(e) => handleInputChange('clinic_name', e.target.value)}
                      disabled={!isEditing}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input
                      type="text"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={formData.contact_person || ''}
                      onChange={(e) => handleInputChange('contact_person', e.target.value)}
                      disabled={!isEditing}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input
                      type="email"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={formData.email || ''}
                      onChange={(e) => handleInputChange('email', e.target.value)}
                      disabled={!isEditing}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input
                      type="text"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={formData.mobile || ''}
                      onChange={(e) => handleInputChange('mobile', e.target.value)}
                      disabled={!isEditing}
                    />
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Bio/Description</label>
                    <textarea
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      rows={4}
                      value={formData.bio || ''}
                      onChange={(e) => handleInputChange('bio', e.target.value)}
                      disabled={!isEditing}
                      placeholder="Tell us about your clinic and expertise"
                    />
                  </div>
                </div>
              </div>
            )}

            {/* Clinic Details Tab */}
            {activeTab === 1 && (
              <div className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Clinic Address</label>
                    <input
                      type="text"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={formData.address || ''}
                      onChange={(e) => handleInputChange('address', e.target.value)}
                      disabled={!isEditing}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input
                      type="text"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={formData.city || ''}
                      onChange={(e) => handleInputChange('city', e.target.value)}
                      disabled={!isEditing}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">PIN Code</label>
                    <input
                      type="text"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={formData.pincode || ''}
                      onChange={(e) => handleInputChange('pincode', e.target.value)}
                      disabled={!isEditing}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                    <input
                      type="text"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={formData.employee_id || ''}
                      onChange={(e) => handleInputChange('employee_id', e.target.value)}
                      disabled={!isEditing}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">License Number</label>
                    <input
                      type="text"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={formData.license_no || ''}
                      onChange={(e) => handleInputChange('license_no', e.target.value)}
                      disabled={!isEditing}
                    />
                  </div>
                  <div className="md:col-span-2">
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2"
                        checked={formData.inhome_grooming_services === 1}
                        onChange={(e) => handleInputChange('inhome_grooming_services', e.target.checked ? 1 : 0)}
                        disabled={!isEditing}
                      />
                      <span className="text-sm text-gray-700">Offer at-home grooming services</span>
                    </label>
                  </div>
                </div>
              </div>
            )}

            {/* Services & Pricing Tab */}
            {activeTab === 2 && (
              <div className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Consultation Fee (₹)</label>
                    <div className="relative">
                      <span className="absolute left-3 top-2 text-gray-500">₹</span>
                      <input
                        type="number"
                        className="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value={formData.chat_price || ''}
                        onChange={(e) => handleInputChange('chat_price', e.target.value)}
                        disabled={!isEditing}
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Video Consultation Fee (₹)</label>
                    <div className="relative">
                      <span className="absolute left-3 top-2 text-gray-500">₹</span>
                      <input
                        type="number"
                        className="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value={formData.video_consultation_price || ''}
                        onChange={(e) => handleInputChange('video_consultation_price', e.target.value)}
                        disabled={!isEditing}
                      />
                    </div>
                  </div>
                </div>
                
                <div className="mt-6">
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Business Hours</h3>
                  <div className="space-y-3">
                    {['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'].map(day => (
                      <div key={day} className="flex items-center">
                        <span className="w-24 text-sm text-gray-700">{day}</span>
                        <input
                          type="time"
                          className="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm mr-2"
                          disabled={!isEditing}
                        />
                        <span className="mx-2 text-gray-500">to</span>
                        <input
                          type="time"
                          className="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm mr-4"
                          disabled={!isEditing}
                        />
                        <label className="flex items-center">
                          <input
                            type="checkbox"
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2"
                            disabled={!isEditing}
                          />
                          <span className="text-sm text-gray-700">Closed</span>
                        </label>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Doctors List Card */}
          {profileData?.doctors && profileData.doctors.length > 0 && (
            <div className="bg-white p-6 rounded-lg shadow-sm mt-6">
              <h3 className="text-lg font-medium text-gray-900 mb-4">Associated Doctors</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {profileData.doctors.map((doctor, index) => (
                  <div key={index} className="bg-gray-50 p-4 rounded-lg flex items-center">
                    <img
                      src={doctor.doctor_image || '/default-avatar.png'}
                      alt={doctor.doctor_name}
                      className="w-12 h-12 rounded-full mr-4 object-cover"
                    />
                    <div>
                      <p className="font-medium text-gray-900">{doctor.doctor_name}</p>
                      <p className="text-sm text-gray-600">{doctor.doctor_license}</p>
                      <p className="text-sm text-gray-600">{doctor.doctor_email}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Snackbar Notification */}
      {snackbar.open && (
        <div className={`fixed bottom-4 right-4 px-4 py-2 rounded-md shadow-md ${
          snackbar.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`}>
          {snackbar.message}
          <button
            className="ml-4 text-sm font-medium"
            onClick={handleSnackbarClose}
          >
            Close
          </button>
        </div>
      )}
    </div>
  );
};

export default VetOwnerProfile;