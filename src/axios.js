import axios from 'axios';


const axiosClient = axios.create({
  baseURL: 'https://app.snoutiq.com/public/api',
  // baseURL: 'http://localhost/snoutiq-backend-latest/public/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});
 
export default axiosClient;
