import React, { lazy, Suspense, useEffect } from "react";
import {
  BrowserRouter as Router,
  Routes,
  Route,
  useLocation,
} from "react-router-dom";
import { Toaster } from "react-hot-toast";
import { FaPaw } from "react-icons/fa";

import CallLab from "./pages/CallLab";
import DoctorReceiver from "./pages/DoctorReceiver";

// Public pages
const PrivacyPolicy = lazy(() => import("./policies/PrivacyPolicy"));
const TearmsCondition = lazy(() => import("./policies/TearmsCondition"));
const Cancelation = lazy(() => import("./policies/Cancelation"));
const CookiePolicy = lazy(() => import("./policies/CookiePolicy"));
const MedicalDataConsent = lazy(() => import("./policies/MedicalDataConsent"));
const ShippingPolicy = lazy(() => import("./policies/ShippingPolicy"));
// const RegisterPetOwner = lazy(() => import("./pages/RegisterPetOwner"));
const DoctorDashboard = lazy(() => import("./pages/DoctorDashboard"));
const CallTestPage = lazy(() => import("./pages/CallTestPage"));
const CallRecordingDemo = lazy(() => import("./pages/CallRecordingDemo"));
const PatientCallTest = lazy(() => import("./pages/PatientCallTest"));
const DoctorReceiverTest = lazy(() => import("./pages/DoctorReceiverTest"));

// Core app pages
const Home = lazy(() => import("./pages/Home"));
// import Home from "./pages/Home";

const Support = lazy(() => import("./pages/Support"));

const NotFoundPage = lazy(() => import("./components/NotFoundPage"));
const S3UploadTest = lazy(() => import("./pages/S3UploadTest"));
const CsvUploadPage = lazy(() => import("./pages/CsvUploadPage"));
// App.js mein yeh component replace karo:

const ScrollToTopAndHash = () => {
  const { pathname, hash } = useLocation();

  useEffect(() => {
    if (!hash) {
      window.scrollTo({ top: 0, behavior: "auto" });
      return;
    }

    const elementId = hash.replace("#", "");
    const headerOffset =
      document.querySelector("header")?.getBoundingClientRect().height ?? 0;

    const scrollIntoView = () => {
      const target = document.getElementById(elementId);
      if (!target) return;

      const top =
        window.scrollY +
        target.getBoundingClientRect().top -
        (headerOffset + 16);

      window.scrollTo({
        top: Math.max(0, top),
        behavior: "smooth",
      });
    };

    requestAnimationFrame(scrollIntoView);
  }, [pathname, hash]);

  return null;
};

import Blog from "./blog/Blog";
import DogWinterCareGuide from "./blog/DogWinterCareGuide";
import TickFeverGuide from "./blog/TickFeverGuide";
import PetPawProtecteGuide from "./blog/PetPawProtecteGuide";
// import PricingPage from "./pages/PricingPage";
import DelhiPage from "./pages/DelhiPage";
import GurugramPage from "./pages/GurugramPage";
import ClinicsSolutionPage from "./pages/ClinicsSolutionPage";
// import FeaturesPage from "./pages/FeaturesPage";
// import VideoConsultPage from "./pages/VideoConsultPage";
import AITriagePage from "./pages/AITriagePage";
import FirstAidEvery from "./blog/FirstAidEvery";
import BoostYourDog from "./blog/BoostYourDog";
import VaccinationSchedule from "./blog/VaccinationSchedule";
import BestFoodForDog from "./blog/BestFoodForDog";
import HowVetsGrow from "./blog/HowVetsGrow";
import RegisterAsAnOnlineVet from "./blog/RegisterAsAnOnlineVet";
import OnlineVetConsultation from "./blog/OnlineVetConsultation";
import VetsIncreaseMonthlyRevenue from "./blog/VetsIncreaseMonthlyRevenue";
import TopFriendlyDogBreeds from "./blog/TopFriendlyDogBreeds";
import BestCatBreedsInIndia from "./blog/BestCatBreedsInIndia";
import CatVaccinationScheduleIndia from "./blog/CatVaccinationScheduleIndia";
import CatsDiseasesAndSymptoms from "./blog/CatsDiseasesAndSymptoms";
import BestCatFoodInIndia from "./blog/BestCatFoodInIndia";
import FoodsGoldenRetrieversShouldNeverEat from "./blog/FoodsGoldenRetrieversShouldNeverEat";
import BestDogFoodForGoldenRetrievers from "./blog/BestDogFoodForGoldenRetrievers";
import GoldenRetrieverVaccinationScheduleIndia from "./blog/GoldenRetrieverVaccinationScheduleIndia";
import WhyWinterGroomingIsImportantForCats from "./blog/WhyWinterGroomingIsImportantForCats";
import HomePage from "./components/Home";
import DoctorRegistration from "./components/DoctorRegistration";

function App() {
  return (
    <Router>
      <ScrollToTopAndHash />
      <Toaster
        position="top-center"
        containerStyle={{
          top: 80,
          bottom: 80,
        }}
        toastOptions={{
          duration: 4000,
          style: { fontSize: "14px", borderRadius: "8px" },
        }}
      />

      <div className="bg-white text-black">
        <Suspense
          fallback={
            <div className="flex items-center justify-center h-screen">
              <div className="animate-spin">
                <FaPaw className="w-16 h-16 text-blue-500" />
              </div>
            </div>
          }
        >
          <Routes>
            {/* Home page - will redirect authenticated users to dashboard */}
            <Route path="/" element={<HomePage />} />
             <Route path="/veterinary-practice-software" element={<Home />} />
            {/* <Route
              path="/register-pet-details"
              element={<RegisterPetOwner />}
            /> */}
            {/* <Route path="/pet-data-register" element={<RegisterPetOwner />} /> */}
            {/* Policy pages (public) */}
            <Route path="/privacy-policy" element={<PrivacyPolicy />} />
            <Route path="/terms-of-service" element={<TearmsCondition />} />
            <Route path="/cancellation-policy" element={<Cancelation />} />
            <Route path="/cookie-policy" element={<CookiePolicy />} />
            <Route path="/vet-register" element={<DoctorRegistration />} />
            <Route
              path="/medical-data-consent"
              element={<MedicalDataConsent />}
            />
            <Route path="/shipping-policy" element={<ShippingPolicy />} />
            {/* <Route path="/patient-dashboard" element={<PatientDashboard />} /> */}
            {/* <Route
              path="/doctor-dashboard"
              element={<DoctorDashboard doctorId={501} />}
            /> */}
            <Route path="/patient-call-test" element={<PatientCallTest />} />
            <Route path="/doctor-receiver-test" element={<DoctorReceiverTest />} />
            <Route path="/call-lab" element={<CallLab />} />
            <Route path="/doctor-receiver" element={<DoctorReceiver />} />
            {/* Multiple doctor routes if needed */}
            <Route
              path="/doctor-dashboard/:doctorId"
              element={
                <DoctorDashboard
                  doctorId={
                    parseInt(window.location.pathname.split("/")[2]) || 501
                  }
                />
              }
            />
            {/* Protected Routes - require authentication */}
            <Route path="/call-demo" element={<CallRecordingDemo />} />
            <Route path="/csv-upload" element={<CsvUploadPage />} />
            <Route path="/s3-upload-test" element={<S3UploadTest />} />
            <Route path="/blog" element={<Blog />} />
            <Route
              path="/blog/dog-winter-care-guide"
              element={<DogWinterCareGuide />}
            />
             <Route
              path="/blog/online-vet-consultation"
              element={<OnlineVetConsultation />}
            />
             <Route
              path="/blog/register-as-an-online-vet"
              element={<RegisterAsAnOnlineVet />}
            />
              <Route
              path="/blog/online-vet-consultation"
              element={<OnlineVetConsultation />}
            />
            <Route
              path="/blog/symptoms-of-tick-fever-in-dogs"
              element={<TickFeverGuide />}
            />
             <Route
              path="/blog/Vets-Increase-Monthly-Revenue"
              element={<VetsIncreaseMonthlyRevenue />}
            />
            
            <Route
              path="/blog/protecting-pet-paws-in-winter-tips-guide"
              element={<PetPawProtecteGuide />}
            />
            <Route path="/blog/first-aid-tips-every-pet-parent-should-know" element={<FirstAidEvery/>}/>
            <Route path="/blog/boost-your-dogs-immunity-naturally" element={<BoostYourDog/>}/>
            <Route path="/blog/vaccination-schedule-for-pets-in-india" element={<VaccinationSchedule/>}/>
            <Route path="/blog/best-food-for-dogs-in-winter" element={<BestFoodForDog/>}/>
            <Route path="/blog/how-vets-grow-with-online-consultations" element={<HowVetsGrow/>}/>
            <Route path="/blog/top-friendly-dog-breeds-in-india" element={<TopFriendlyDogBreeds/>}/>
            <Route path="/blog/best-cat-breeds-in-india" element={<BestCatBreedsInIndia/>}/>
            <Route path="/blog/cat-vaccination-schedule-india" element={<CatVaccinationScheduleIndia/>}/>
            <Route path="/blog/cats-diseases-and-symptoms" element={<CatsDiseasesAndSymptoms/>}/>
            <Route path="/blog/best-cat-food-in-india" element={<BestCatFoodInIndia/>}/>
            <Route path="/blog/foods-golden-retrievers-should-never-eat" element={<FoodsGoldenRetrieversShouldNeverEat/>}/>
            <Route path="/blog/best-dog-food-for-golden-retrievers" element={<BestDogFoodForGoldenRetrievers/>}/>
            <Route path="/blog/golden-retriever-vaccination-schedule-india" element={<GoldenRetrieverVaccinationScheduleIndia/>}/>
            <Route path="/blog/why-winter-grooming-is-important-for-cats" element={<WhyWinterGroomingIsImportantForCats/>}/>
            {/* <Route path="/pricing" element={<PricingPage />} /> */}
            <Route path="/delhi" element={<DelhiPage />} />
            <Route path="/gurugram" element={<GurugramPage />} />
            <Route path="/clinics-solution" element={<ClinicsSolutionPage />} />
            {/* <Route path="/features" element={<FeaturesPage />} /> */}
            {/* <Route path="/video-consult" element={<VideoConsultPage />} /> */}
            {/* <Route path="/ai-triage" element={<AITriagePage />} /> */}
            <Route path="*" element={<NotFoundPage />} />

          </Routes>
        </Suspense>
      </div>
    </Router>
  );
}

export default App;
