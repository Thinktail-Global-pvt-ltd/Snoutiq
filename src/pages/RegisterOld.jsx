import React, { useState, useEffect } from 'react';
import { toast } from 'react-hot-toast';
import { Link, Navigate, useNavigate } from 'react-router-dom';
// import axios from '../axios';
import Card from '../components/Card';
import logo from '../assets/images/logo.webp';
import axios from '../axios';
 

const Register = ({ checkIfLoggedIn,isLoggedIn }) => {
  const [formData, setFormData] = useState({
    fullName: '',
    email: '',
    mobileNumber: '',
    emailOtp: '',
    mobileOtp: '',
    password: '',
    confirmPassword: '',
  });
  const [errors, setErrors] = useState({});
  const [otpSent, setOtpSent] = useState({ email: false, mobile: false });
  const [isLoading, setIsLoading] = useState({ email: false, mobile: false, register: false });
  const [verifiedFields, setVerifiedFields] = useState({ email: '', mobile: '' });
  const [emailOtpCooldown, setEmailOtpCooldown] = useState(0);
  const [mobileOtpCooldown, setMobileOtpCooldown] = useState(0);
  const [emailOtpToken, setEmailOtpToken] = useState('');
  const [mobileOtpToken, setMobileOtpToken] = useState('');
  const [activeStep, setActiveStep] = useState(0);
  const navigate = useNavigate();

  const steps = ['Basic Details', 'Verification & Password'];

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }));
    if (field === 'email' && value !== verifiedFields.email && otpSent.email) {
      setOtpSent({ ...otpSent, email: false });
      setFormData((prev) => ({ ...prev, email: value, emailOtp: '' }));
      setErrors({ ...errors, email: '', emailOtp: '' });
      setEmailOtpCooldown(0);
      setEmailOtpToken('');
    }
    if (field === 'mobileNumber' && value !== verifiedFields.mobile && otpSent.mobile) {
      setOtpSent({ ...otpSent, mobile: false });
      setFormData((prev) => ({ ...prev, mobileNumber: value, mobileOtp: '' }));
      setErrors({ ...errors, mobileNumber: '', mobileOtp: '' });
      setMobileOtpCooldown(0);
      setMobileOtpToken('');
    }
  };

  const handleSendEmailOtp = async () => {
    if (!formData.email.includes('@')) {
      setErrors({ ...errors, email: 'Valid email is required' });
      toast.error('Valid email is required');
      return;
    }
    setIsLoading({ ...isLoading, email: true });
    try {
      const res = await axios.post('send-otp', {
        type: 'email',
        value: formData.email,
        unique: 'yes',
      });
      if (res.data.message === 'OTP sent successfully') {
        setEmailOtpToken(res.data.token);
        setOtpSent({ ...otpSent, email: true });
        setVerifiedFields({ ...verifiedFields, email: formData.email });
        setErrors({ ...errors, email: '' });
        setEmailOtpCooldown(15);
        toast.success(`Email OTP sent successfully`, { duration: 5000 });
      } else {
        toast.error(res.data.message);
        setFormData({ ...formData, emailOtp: '' });
      }
    } catch (error) {
      setErrors({ ...errors, email: 'Failed to send OTP' });
      const errorMessage =
        error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Failed to send email OTP';
      toast.error(errorMessage);
    } finally {
      setIsLoading({ ...isLoading, email: false });
    }
  };

  const handleSendMobileOtp = async () => {
    if (!formData.mobileNumber.match(/^\d{10}$/)) {
      setErrors({ ...errors, mobileNumber: 'Valid 10-digit mobile number is required' });
      toast.error('Valid 10-digit mobile number is required');
      return;
    }
    setIsLoading({ ...isLoading, mobile: true });
    try {
      const res = await axios.post('send-otp', {
        type: 'phone',
        value: formData.mobileNumber,
        unique: 'yes',
      });
      if (res.data.message === 'OTP sent successfully') {
        setMobileOtpToken(res.data.token);
        setOtpSent({ ...otpSent, mobile: true });
        setVerifiedFields({ ...verifiedFields, mobile: formData.mobileNumber });
        setErrors({ ...errors, mobileNumber: '' });
        setMobileOtpCooldown(15);
    //      setFormData((prev) => ({
    //   ...prev,
    //   mobileOtp: res.data.otp,
    // }));
        // toast.success(`Mobile OTP sent successfully`, { duration: 5000 });
      } else {
        toast.error(res.data.message);
        setFormData({ ...formData, mobileOtp: '' });
      }
    } catch (error) {
      setErrors({ ...errors, mobileNumber: 'Failed to send OTP' });
      const errorMessage =
        error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Failed to send mobile OTP';
      toast.error(errorMessage);
    } finally {
      setIsLoading({ ...isLoading, mobile: false });
    }
  };

  useEffect(() => {
     let emailTimer, mobileTimer;
    if (emailOtpCooldown > 0) {
      emailTimer = setInterval(() => {
        setEmailOtpCooldown((prev) => prev - 1);
      }, 1000);
    }
    if (mobileOtpCooldown > 0) {
      mobileTimer = setInterval(() => {
        setMobileOtpCooldown((prev) => prev - 1);
      }, 1000);
    }
    return () => {
      clearInterval(emailTimer);
      clearInterval(mobileTimer);
    };
  }, [emailOtpCooldown, mobileOtpCooldown]);

  const validateStep = () => {
    const newErrors = {};
    if (activeStep === 0) {
      if (!formData.fullName.trim()) newErrors.fullName = 'Name is required';
      if (!formData.email.includes('@')) newErrors.email = 'Valid email is required';
      if (!formData.mobileNumber.match(/^\d{10}$/)) newErrors.mobileNumber = 'Valid 10-digit mobile number is required';
    } else if (activeStep === 1) {
      if (!otpSent.email) newErrors.email = 'Please send email OTP';
      if (otpSent.email && !formData.emailOtp) newErrors.emailOtp = 'Enter email OTP';
      if (!otpSent.mobile) newErrors.mobileNumber = 'Please send mobile OTP';
      if (otpSent.mobile && !formData.mobileOtp) newErrors.mobileOtp = 'Enter mobile OTP';
      if (formData.password.length < 6) newErrors.password = 'Password must be at least 6 characters';
      if (formData.password !== formData.confirmPassword) newErrors.confirmPassword = 'Passwords do not match';
    }
    setErrors(newErrors);
    if (Object.keys(newErrors).length > 0) {
      Object.values(newErrors).forEach((error) => toast.error(error));
    }
    return Object.keys(newErrors).length === 0;
  };

  const handleNext = async() => {
    if (validateStep()) {
    
      setActiveStep((prev) => prev + 1);
      await  handleSendMobileOtp();
    await  handleSendEmailOtp();
    }
  };

  const handleBack = () => {
    setActiveStep((prev) => prev - 1);
  };

  const handleSubmit = async () => {
    if (!validateStep()) return;
    setIsLoading({ ...isLoading, register: true });
    try {
      await axios.post('register', {
        name: formData.fullName,
        email: formData.email,
        phone: formData.mobileNumber,
        role: 'Pet Owner',
        password: formData.password,
        email_otp_token: emailOtpToken,
        phone_otp_token: mobileOtpToken,
      });
      toast.success('Registration successful!');
      try {
        const res = await axios.post('login', {
          login: formData.email,
          password: formData.password,
        });
        localStorage.setItem('token', res.data.token);
        if (checkIfLoggedIn) checkIfLoggedIn();
        await checkIfLoggedIn();
        setFormData({ fullName: '', email: '', mobileNumber: '', emailOtp: '', mobileOtp: '', password: '', confirmPassword: '' });
        navigate('/pet-info');
        toast.success(res.data.message);
      } catch (error) {
        const errorMessage =
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : 'Error logging in';
        toast.error(errorMessage);
      }
    } catch (error) {
      const errorMessage =
        error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Registration failed. Please try again.';
      toast.error(errorMessage);
    } finally {
      setIsLoading({ ...isLoading, register: false });
    }
  };
if(isLoggedIn){
  return <Navigate to="/dashboard" replace={true}/>
}
  return (
    <div className="min-h-screen bg-white mt-12 flex items-center justify-center px-4 py-8">
      <div className="w-full max-w-sm sm:max-w-md">
        <Card className="text-center shadow-lg p-6 sm:p-8">
          {/* Logo */}
          <div className="mb-6">
            <img src={logo} alt="Snoutiq Logo" className="h-10 sm:h-12 mx-auto mb-2" />
          </div>

          {/* Welcome Message */}
          <div className="mb-6 sm:mb-8">
            <h1 className="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Welcome to Snoutiq!</h1>
            <p className="text-sm sm:text-base text-gray-600">Let's start by getting to know you</p>
          </div>

          {/* Stepper */}
          <div className="mb-6">
            <div className="flex justify-between">
              {steps.map((label, index) => (
                <div key={label} className="flex-1 text-center">
                  <div className={`w-6 h-6 sm:w-8 sm:h-8 mx-auto rounded-full flex items-center justify-center text-xs sm:text-sm ${activeStep >= index ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'}`}>
                    {index + 1}
                  </div>
                  <p className="text-xs sm:text-sm mt-2">{label}</p>
                </div>
              ))}
            </div>
          </div>

          {/* Form Fields */}
          <div className="space-y-4 mb-6">
            {activeStep === 0 && (
              <>
                {/* Full Name */}
                <div className="text-left">
                  <label className="block text-sm font-semibold text-gray-800 mb-2">Full Name</label>
                  <div className="relative">
                    <div className="absolute left-3 top-1/2 transform -translate-y-1/2">
                      <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </div>
                    <input
                      type="text"
                      value={formData.fullName}
                      onChange={(e) => handleInputChange('fullName', e.target.value)}
                      className="w-full pl-10 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Enter your full name"
                    />
                    {errors.fullName && <p className="text-red-500 text-xs mt-1">{errors.fullName}</p>}
                  </div>
                </div>

                {/* Email */}
                <div className="text-left">
                  <label className="block text-sm font-semibold text-gray-800 mb-2">Email</label>
                  <div className="relative">
                    <div className="absolute left-3 top-1/2 transform -translate-y-1/2">
                      <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <input
                      type="email"
                      value={formData.email}
                      onChange={(e) => handleInputChange('email', e.target.value)}
                      className="w-full pl-10 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Enter your email"
                    />
                    {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
                  </div>
                </div>

                {/* Mobile Number */}
                <div className="text-left">
                  <label className="block text-sm font-semibold text-gray-800 mb-2">Mobile Number</label>
                  <div className="relative">
                    <div className="absolute left-3 top-1/2 transform -translate-y-1/2">
                      <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                      </svg>
                    </div>
                    <input
                      type="tel"
                      value={formData.mobileNumber}
                      onChange={(e) => handleInputChange('mobileNumber', e.target.value)}
                      className="w-full pl-10 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Enter your mobile number"
                    />
                    {errors.mobileNumber && <p className="text-red-500 text-xs mt-1">{errors.mobileNumber}</p>}
                  </div>
                </div>
              </>
            )}

            {activeStep === 1 && (
              <>
                {/* Email OTP */}
                <div className="text-left">
                  <label className="block text-sm font-semibold text-gray-800 mb-2">Email OTP</label>
                  <div className="relative">
                    <input
                      type="text"
                      value={formData.emailOtp}
                      onChange={(e) => handleInputChange('emailOtp', e.target.value)}
                      className="w-full pl-4 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Enter email OTP"
                    />
                    <button
                      onClick={handleSendEmailOtp}
                      disabled={isLoading.email || emailOtpCooldown > 0}
                      className={`absolute right-2 top-1/2 transform -translate-y-1/2 text-xs sm:text-sm ${isLoading.email || emailOtpCooldown > 0 ? 'text-gray-400' : 'text-blue-600 hover:text-blue-700'}`}
                    >
                      {isLoading.email ? 'Sending...' : emailOtpCooldown > 0 ? `Resend in ${emailOtpCooldown}s` : 'Send OTP'}
                    </button>
                    {errors.emailOtp && <p className="text-red-500 text-xs mt-1">{errors.emailOtp}</p>}
                  </div>
                </div>

                {/* Mobile OTP */}
               <div className="text-left">
                  <label className="block text-sm font-semibold text-gray-800 mb-2">Mobile OTP</label>
                  <div className="relative">
                    <input
                      type="text"
                      value={formData.mobileOtp}
                      onChange={(e) => handleInputChange('mobileOtp', e.target.value)}
                      className="w-full pl-4 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Enter mobile OTP"
                    />
                    <button
                      onClick={handleSendMobileOtp}
                      disabled={isLoading.mobile || mobileOtpCooldown > 0}
                      className={`absolute right-2 top-1/2 transform -translate-y-1/2 text-xs sm:text-sm ${isLoading.mobile || mobileOtpCooldown > 0 ? 'text-gray-400' : 'text-blue-600 hover:text-blue-700'}`}
                    >
                      {isLoading.mobile ? 'Sending...' : mobileOtpCooldown > 0 ? `Resend in ${mobileOtpCooldown}s` : 'Send OTP'}
                    </button>
                    {errors.mobileOtp && <p className="text-red-500 text-xs mt-1">{errors.mobileOtp}</p>}
                  </div>
                </div>
 
                {/* Password */}
                <div className="text-left">
                  <label className="block text-sm font-semibold text-gray-800 mb-2">Password</label>
                  <div className="relative">
                    <input
                      type="password"
                      value={formData.password}
                      onChange={(e) => handleInputChange('password', e.target.value)}
                      className="w-full pl-4 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Enter your password"
                    />
                    {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
                  </div>
                </div>

                {/* Confirm Password */}
                <div className="text-left">
                  <label className="block text-sm font-semibold text-gray-800 mb-2">Confirm Password</label>
                  <div className="relative">
                    <input
                      type="password"
                      value={formData.confirmPassword}
                      onChange={(e) => handleInputChange('confirmPassword', e.target.value)}
                      className="w-full pl-4 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Confirm your password"
                    />
                    {errors.confirmPassword && <p className="text-red-500 text-xs mt-1">{errors.confirmPassword}</p>}
                  </div>
                </div>
              </>
            )}
          </div>

          {/* Navigation Buttons */}
          <div className="flex justify-between">
            {activeStep === 0 ? (
              <button
                onClick={handleNext}
                className="w-full bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors"
              >
                Next
              </button>
            ) : (
              <>
                <button
                  onClick={handleBack}
                  className="w-1/2 mr-2 bg-gray-200 text-gray-800 font-semibold py-3 px-6 rounded-lg hover:bg-gray-300 transition-colors"
                >
                  Back
                </button>
                <button
                  onClick={handleSubmit}
                  disabled={isLoading.register}
                  className="w-1/2 bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-400"
                >
                  {isLoading.register ? 'Registering...' : 'Register'}
                </button>
              </>
            )}
             
          </div>
            <div className="mt-4">
                      <p className="text-gray-600 text-sm">
                        Already have an account?{' '}
                        <Link to="/login" className="text-blue-600 hover:underline">
                          Login
                        </Link>
                      </p>
                    </div>
        </Card>
      </div>
    </div>
  );
};

export default Register;