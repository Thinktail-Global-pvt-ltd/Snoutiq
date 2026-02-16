import React, { lazy, Suspense, useEffect } from "react";
import {
  BrowserRouter as Router,
  Routes,
  Route,
  useLocation,
} from "react-router-dom";
import { Toaster } from "react-hot-toast";
import { FaPaw } from "react-icons/fa";

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

// ✅ Core / Call pages (were direct imports)
const CallLab = lazy(() => import("./pages/CallLab"));
const DoctorReceiver = lazy(() => import("./pages/DoctorReceiver"));

// ✅ Public pages
const PrivacyPolicy = lazy(() => import("./policies/PrivacyPolicy"));
const TearmsCondition = lazy(() => import("./policies/TearmsCondition"));
const Cancelation = lazy(() => import("./policies/Cancelation"));
const CookiePolicy = lazy(() => import("./policies/CookiePolicy"));
const MedicalDataConsent = lazy(() => import("./policies/MedicalDataConsent"));
const ShippingPolicy = lazy(() => import("./policies/ShippingPolicy"));

// ✅ App pages
const DoctorDashboard = lazy(() => import("./pages/DoctorDashboard"));
const CallTestPage = lazy(() => import("./pages/CallTestPage"));
const CallRecordingDemo = lazy(() => import("./pages/CallRecordingDemo"));
const PatientCallTest = lazy(() => import("./pages/PatientCallTest"));
const DoctorReceiverTest = lazy(() => import("./pages/DoctorReceiverTest"));
const Home = lazy(() => import("./pages/Home"));
const Support = lazy(() => import("./pages/Support"));
const NotFoundPage = lazy(() => import("./components/NotFoundPage"));
const S3UploadTest = lazy(() => import("./pages/S3UploadTest"));
const CsvUploadPage = lazy(() => import("./pages/CsvUploadPage"));
const Auth = lazy(() => import("./pages/Auth"));
const VetDashboard = lazy(() => import("./pages/VetDashboard"));
const VetRegisterSuccess = lazy(() => import("./pages/VetRegisterSuccess"));

// ✅ Blog (were direct imports — heavy)
const Blog = lazy(() => import("./blog/Blog"));
const DogWinterCareGuide = lazy(() => import("./blog/DogWinterCareGuide"));
const TickFeverGuide = lazy(() => import("./blog/TickFeverGuide"));
const PetPawProtecteGuide = lazy(() => import("./blog/PetPawProtecteGuide"));
const FirstAidEvery = lazy(() => import("./blog/FirstAidEvery"));
const BoostYourDog = lazy(() => import("./blog/BoostYourDog"));
const VaccinationSchedule = lazy(() => import("./blog/VaccinationSchedule"));
const BestFoodForDog = lazy(() => import("./blog/BestFoodForDog"));
const HowVetsGrow = lazy(() => import("./blog/HowVetsGrow"));
const RegisterAsAnOnlineVet = lazy(() => import("./blog/RegisterAsAnOnlineVet"));
const OnlineVetConsultation = lazy(() => import("./blog/OnlineVetConsultation"));
const VetsIncreaseMonthlyRevenue = lazy(() =>
  import("./blog/VetsIncreaseMonthlyRevenue")
);
const TopFriendlyDogBreeds = lazy(() => import("./blog/TopFriendlyDogBreeds"));
const BestCatBreedsInIndia = lazy(() => import("./blog/BestCatBreedsInIndia"));
const CatVaccinationScheduleIndia = lazy(() =>
  import("./blog/CatVaccinationScheduleIndia")
);
const CatsDiseasesAndSymptoms = lazy(() =>
  import("./blog/CatsDiseasesAndSymptoms")
);
const BestCatFoodInIndia = lazy(() => import("./blog/BestCatFoodInIndia"));
const FoodsGoldenRetrieversShouldNeverEat = lazy(() =>
  import("./blog/FoodsGoldenRetrieversShouldNeverEat")
);
const BestDogFoodForGoldenRetrievers = lazy(() =>
  import("./blog/BestDogFoodForGoldenRetrievers")
);
const GoldenRetrieverVaccinationScheduleIndia = lazy(() =>
  import("./blog/GoldenRetrieverVaccinationScheduleIndia")
);
const WhyWinterGroomingIsImportantForCats = lazy(() =>
  import("./blog/WhyWinterGroomingIsImportantForCats")
);

// ✅ Other pages/components that were direct imports
const DelhiPage = lazy(() => import("./pages/DelhiPage"));
const GurugramPage = lazy(() => import("./pages/GurugramPage"));
const ClinicsSolutionPage = lazy(() => import("./pages/ClinicsSolutionPage"));
const AITriagePage = lazy(() => import("./pages/AITriagePage"));

const HomePage = lazy(() => import("./components/Home"));
const DoctorRegistration = lazy(() => import("./components/DoctorRegistration"));
const ConsultationBooked = lazy(() => import("./pages/ConsultationBooked"));


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
            <Route path="/20+vetsonline" element={<HomePage />} />
            <Route path="/whychooseteleconsult" element={<HomePage />} />
            <Route path="/howwework" element={<HomePage />} />
            <Route path="/auth" element={<Auth />} />
            <Route path="/vet-register-success" element={<VetRegisterSuccess />} />
            <Route path="/vet-dashboard" element={<VetDashboard />} />
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
            <Route
              path="/consultation-booked"
              element={<ConsultationBooked />}
            />
            <Route path="/404" element={<NotFoundPage />} />
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
