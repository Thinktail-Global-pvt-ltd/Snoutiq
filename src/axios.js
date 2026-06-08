import axios from 'axios';

const getBaseURL = () => {
  const envUrl = import.meta.env.VITE_BACKEND_BASE_URL;
  if (envUrl) {
    return `${envUrl}/api`;
  }
  
  if (typeof window !== 'undefined') {
    const origin = window.location.origin;
    if (origin.includes('snoutiq.com') && !origin.includes('app.snoutiq.com')) {
      return `${origin}/backend/api`;
    }
  }
  
  return 'https://app.snoutiq.com/public/api';
};

const axiosClient = axios.create({
  baseURL: getBaseURL(),
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});
 
export default axiosClient;
