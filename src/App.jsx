import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import GTMRouteListener from './GTMRouteListener'
import Home from './pages/Home';
import Register from './pages/Register';
import PetInfo from './pages/PetInfo';
import Dashboard from './pages/Dashboard';
import { Toaster } from 'react-hot-toast';
import Login from './pages/Login';
import ProtectedRoute from './ProtectedRoute';
import ChatInterface from './pages/ChatInterface';
import ForgotPassword from './pages/ForgotPassword';
import AddPet from './pages/AddPet';
import EditPet from './pages/EditPet';
import TokenLogin from './components/TokenLogin';
import Videocall from './pages/VideoCall';
import SearchingDoctor from './pages/SearchingDoctor';

import PrivacyPolicy from './policies/PrivacyPolicy';
import TearmsCondition from './policies/TearmsCondition';
import Cancelation from './policies/Cancelation';
import CookiePolicy from './policies/CookiePolicy';
import MedicalDataConsent from './policies/MedicalDataConsent';
import ShippingPolicy from './policies/ShippingPolicy';

function App() {

  return (
    <Router>
      <GTMRouteListener />
      <Toaster position="bottom-right" reverseOrder={false} />

      <div className="bg-white text-black">

        <Routes>
          {/* Home hamesha accessible hai */}

          <Route
            path="/"
            element={
              <ProtectedRoute>
                <Home />
              </ProtectedRoute>
            }
          />

          {/* Public Routes */}
          <Route path="/login" element={<Login />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/register" element={<Register />} />
          <Route path="/token-login/:token" element={<TokenLogin />} />

          <Route path='/privacy-policy' element={<PrivacyPolicy/>}/>
          <Route path='/terms-of-service' element={<TearmsCondition/>}/>
          <Route path='/cancellation-policy' element={<Cancelation/>}/>
          <Route path='/cookie-policy' element={<CookiePolicy/>}/>
          <Route path='/medical-data-consent' element={<MedicalDataConsent/>}/>
          <Route path='/shipping-policy' element={<ShippingPolicy/>}/>

          {/* Protected Routes */}
          <Route
            path="/pet-info"
            element={
              <ProtectedRoute>
                <PetInfo />
              </ProtectedRoute>
            }
          />
          <Route
            path="/user/pets"
            element={
              <ProtectedRoute>
                <PetInfo />
              </ProtectedRoute>
            }
          />
          <Route
            path="/user/pets/add"
            element={
              <ProtectedRoute>
                <AddPet />
              </ProtectedRoute>
            }
          />
          <Route
            path="/user/pets/edit/:id"
            element={
              <ProtectedRoute>
                <EditPet />
              </ProtectedRoute>
            }
          />
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Dashboard />
              </ProtectedRoute>
            }
          />
           <Route
            path="/chat/:chat_room_token"
            element={
              <ProtectedRoute>
                <Dashboard />
              </ProtectedRoute>
            }
          />
           <Route
            path="/video-call"
            element={
              <ProtectedRoute>
                <Videocall />
              </ProtectedRoute>
            }
          />
          <Route
            path="searching-doctor"
            element={
              <ProtectedRoute>
                <SearchingDoctor />
              </ProtectedRoute>
            }
          />
          <Route
            path="/chats/:chatToken"
            element={
              <ProtectedRoute>
                <ChatInterface />
              </ProtectedRoute>
            }
          />
        </Routes>
      </div>
    </Router>
  );
}

export default App;
