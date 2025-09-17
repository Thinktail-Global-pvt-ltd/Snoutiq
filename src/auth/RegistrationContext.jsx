import React, { createContext, useContext, useState, useCallback } from 'react';

const RegistrationContext = createContext();

export const useRegistration = () => {
  const context = useContext(RegistrationContext);
  if (!context) {
    throw new Error('useRegistration must be used within a RegistrationProvider');
  }
  return context;
};

export const RegistrationProvider = ({ children }) => {
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

  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [coords, setCoords] = useState({ lat: null, lng: null });
  const [locationAllowed, setLocationAllowed] = useState(null);

  // Update form data
  const updateFormData = useCallback((field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    
    // Clear error when field is updated
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: "" }));
    }
  }, [errors]);

  // Update multiple fields at once
  const updateMultipleFields = useCallback((data) => {
    setFormData(prev => ({ ...prev, ...data }));
  }, []);

  // Set field as touched
  const setFieldTouched = useCallback((field) => {
    setTouched(prev => ({ ...prev, [field]: true }));
  }, []);

  // Validation functions
  const validateBasicDetails = useCallback(() => {
    const newErrors = {};
    
    if (!formData.fullName.trim()) {
      newErrors.fullName = "Full name is required";
    }
    
    if (!formData.email.trim()) {
      newErrors.email = "Email is required";
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = "Please enter a valid email address";
    }

    setErrors(prev => ({ ...prev, ...newErrors }));
    return Object.keys(newErrors).length === 0;
  }, [formData]);

  const validatePetDetails = useCallback(() => {
    const newErrors = {};
    
    if (!formData.petType) newErrors.petType = "Pet type is required";
    if (!formData.petName.trim()) newErrors.petName = "Pet name is required";
    if (!formData.petGender) newErrors.petGender = "Pet gender is required";
    if (!formData.petAge || formData.petAge <= 0) {
      newErrors.petAge = "Valid pet age is required";
    }
    if (!formData.petBreed) newErrors.petBreed = "Pet breed is required";

    setErrors(prev => ({ ...prev, ...newErrors }));
    return Object.keys(newErrors).length === 0;
  }, [formData]);

  const validatePasswordDetails = useCallback(() => {
    const newErrors = {};
    
    if (!/^\d{10}$/.test(formData.mobileNumber)) {
      newErrors.mobileNumber = "Please enter a valid 10-digit mobile number";
    }
    
    if (formData.password.length < 6) {
      newErrors.password = "Password must be at least 6 characters long";
    }
    
    if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = "Passwords do not match";
    }

    setErrors(prev => ({ ...prev, ...newErrors }));
    return Object.keys(newErrors).length === 0;
  }, [formData]);

  // Clear all data (for logout or reset)
  const clearRegistrationData = useCallback(() => {
    setFormData({
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
    setErrors({});
    setTouched({});
    setCoords({ lat: null, lng: null });
    setLocationAllowed(null);
  }, []);

  const value = {
    // State
    formData,
    errors,
    touched,
    coords,
    locationAllowed,
    
    // Actions
    updateFormData,
    updateMultipleFields,
    setFieldTouched,
    setErrors,
    setCoords,
    setLocationAllowed,
    clearRegistrationData,
    
    // Validations
    validateBasicDetails,
    validatePetDetails,
    validatePasswordDetails,
  };

  return (
    <RegistrationContext.Provider value={value}>
      {children}
    </RegistrationContext.Provider>
  );
};