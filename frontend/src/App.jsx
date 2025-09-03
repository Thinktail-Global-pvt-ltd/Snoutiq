import Home from './components/Home';
import DoctorRegistration from './components/DoctorRegistration';
import DoctorApp from './components/DoctorApp'
import './App.css'
import { BrowserRouter as Router , Routes ,Route } from 'react-router-dom';
import Contact from './components/Contact';
import ScrollToTop from './components/ScrollToTop';
import PrivacyPolicy from './components/PrivacyPolicy';
import TearmsCondition from './components/TearmsCondition';
import Cancellation from './components/Cancelation';
import ShippingPolicy from './components/ShippingPolicy';
import MedicalDataConsent from './components/MedicalDataConsent';
import CookiePolicy from './components/CookiePolicy';

function App() {


  return (
    <div>
      <Router basename="/frontend/files">
         <ScrollToTop /> 
        <Routes>
          <Route path='/' element={<Home/>}/>
          <Route path='/contact us' element={<Contact/>}/>
          <Route path='/doctor registration form' element={<DoctorRegistration/>}/>
          <Route path='/doctor demo' element={<DoctorApp/>}/>
          <Route path='/privacypolicy' element={<PrivacyPolicy/>}/>
          <Route path='/tearms' element={<TearmsCondition/>}/>
          <Route path='/Cancellation_Refund_Policy' element={<Cancellation/>}/>
          <Route path='/shipping_policy' element={<ShippingPolicy/>}/>
          <Route path='/MedicalDataConsent' element={<MedicalDataConsent/>}/>
          <Route path='/Cookie_policy' element={<CookiePolicy/>}/>
        </Routes>
      </Router>
    </div>
  );
}

export default App;
