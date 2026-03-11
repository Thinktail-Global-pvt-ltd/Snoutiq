import "./app.css";

import React, { lazy } from "react";
import { HelmetProvider } from "react-helmet-async";
import { Navigate, Route, Routes } from "react-router-dom";
import TalkToVet from "./newflow/TalkToVet";
import PetDoctorOnline from "./newflow/PetDoctorOnline";

const CallLab = lazy(() => import("./pages/CallLab"));
const DoctorReceiver = lazy(() => import("./pages/DoctorReceiver"));
const Uiapp = lazy(() => import("./screen/Uiapp"));

const PrivacyPolicy = lazy(() => import("./policies/PrivacyPolicy"));
const TearmsCondition = lazy(() => import("./policies/TearmsCondition"));
const Cancelation = lazy(() => import("./policies/Cancelation"));
const CookiePolicy = lazy(() => import("./policies/CookiePolicy"));
const MedicalDataConsent = lazy(() => import("./policies/MedicalDataConsent"));
const ShippingPolicy = lazy(() => import("./policies/ShippingPolicy"));

const DoctorDashboard = lazy(() => import("./pages/DoctorDashboard"));
const CallRecordingDemo = lazy(() => import("./pages/CallRecordingDemo"));
const PatientCallTest = lazy(() => import("./pages/PatientCallTest"));
const DoctorReceiverTest = lazy(() => import("./pages/DoctorReceiverTest"));
const Home = lazy(() => import("./pages/Home"));
const NotFoundPage = lazy(() => import("./components/NotFoundPage"));
const S3UploadTest = lazy(() => import("./pages/S3UploadTest"));
const CsvUploadPage = lazy(() => import("./pages/CsvUploadPage"));
const InternalUserDeletePage = lazy(() =>
  import("./pages/InternalUserDeletePage")
);
const Auth = lazy(() => import("./pages/Auth"));
const VetDashboard = lazy(() => import("./pages/VetDashboard"));
const VetRegisterSuccess = lazy(() => import("./pages/VetRegisterSuccess"));

const Blog = lazy(() => import("./blog/Blog"));
const DogWinterCareGuide = lazy(() => import("./blog/DogWinterCareGuide"));
const TickFeverGuide = lazy(() => import("./blog/TickFeverGuide"));
const PetPawProtecteGuide = lazy(() => import("./blog/PetPawProtecteGuide"));
const FirstAidEvery = lazy(() => import("./blog/FirstAidEvery"));
const BoostYourDog = lazy(() => import("./blog/BoostYourDog"));
const VaccinationSchedule = lazy(() => import("./blog/VaccinationSchedule"));
const BestFoodForDog = lazy(() => import("./blog/BestFoodForDog"));
const HowVetsGrow = lazy(() => import("./blog/HowVetsGrow"));
const RegisterAsAnOnlineVet = lazy(() =>
  import("./blog/RegisterAsAnOnlineVet")
);
const OnlineVetConsultation = lazy(() =>
  import("./blog/OnlineVetConsultation")
);
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

const DelhiPage = lazy(() => import("./pages/DelhiPage"));
const GurugramPage = lazy(() => import("./pages/GurugramPage"));
const ClinicsSolutionPage = lazy(() => import("./pages/ClinicsSolutionPage"));

const LegacyHomePage = lazy(() => import("./components/Home"));
const DoctorRegistration = lazy(() =>
  import("./components/DoctorRegistration")
);
const ConsultationBooked = lazy(() => import("./pages/ConsultationBooked"));

const NewAbout = lazy(() => import("./newflow/NewAbout"));
const NewCounsult = lazy(() => import("./newflow/NewCounsult"));
const NewClinics = lazy(() => import("./newflow/NewClinics"));
const NewVets = lazy(() => import("./newflow/NewVets"));
const NewVideoConsultation = lazy(() =>
  import("./newflow/NewVideoConsultationLP")
);
const VideoConsultLP = lazy(() => import("./newflow/VideoConsultLP"));
const VideoConsultPaymentPage = lazy(() =>
  import("./newflow/VideoConsultLP").then((module) => ({
    default: module.VideoConsultPaymentPage,
  }))
);
const SymptomsHub = lazy(() => import("./newflow/SymptomsHub"));
const PuppyVaccinationDelhi = lazy(() =>
  import("./newflow/PuppyVaccinationDelhi")
);
const KittenVaccinationDelhi = lazy(() =>
  import("./newflow/KittenVaccinationDelhi")
);
const DogNeuteringDelhi = lazy(() => import("./newflow/DogNeuteringDelhi"));
const CatNeuteringDelhi = lazy(() => import("./newflow/CatNeuteringDelhi"));
const VaccinationLP = lazy(() => import("./newflow/VaccinationLP"));
const NeuteringLP = lazy(() => import("./newflow/NeuteringLP"));
const VetInsightsHub = lazy(() => import("./newflow/VetInsightsHub"));
const DrSharmaInterview = lazy(() => import("./newflow/DrSharmaInterview"));
const DogVomitingPage = lazy(() => import("./newflow/DogVomitingPage"));

export default function AppRoutes() {
  return (
    <HelmetProvider>
      <Routes>
        <Route path="/about" element={<NewAbout />} />
        <Route path="/parents" element={<NewCounsult />} />
        <Route path="/clinics" element={<NewClinics />} />
        <Route path="/vets" element={<NewVets />} />
        <Route
          path="/veterinary-doctor-online-india"
          element={<NewVideoConsultation />}
        />
        <Route
          path="/online-vet-consultation/payment"
          element={<VideoConsultPaymentPage />}
        />
        <Route
          path="/online-vet-consultation/thank-you"
          element={<Navigate to="/consultation-booked" replace />}
        />
        <Route
          path="/online-vet-consultation/:view"
          element={<VideoConsultLP />}
        />
        <Route path="/online-vet-consultation" element={<VideoConsultLP />} />
        <Route path="/talk-to-vet-online" element={<TalkToVet />} />
        <Route path="/pet-doctor-online" element={<PetDoctorOnline/>}/>

        <Route path="/symptoms" element={<SymptomsHub />} />
        <Route
          path="/puppy-vaccination-delhi"
          element={<PuppyVaccinationDelhi />}
        />
        <Route
          path="/kitten-vaccination-delhi"
          element={<KittenVaccinationDelhi />}
        />
        <Route path="/dog-neutering-delhi" element={<DogNeuteringDelhi />} />
        <Route path="/cat-neutering-delhi" element={<CatNeuteringDelhi />} />
        <Route path="/lp/vaccination" element={<VaccinationLP />} />
        <Route path="/lp/neutering" element={<NeuteringLP />} />
        <Route path="/vet-insights" element={<VetInsightsHub />} />
        <Route
          path="/vet-insights/interview-dr-sharma-emergency-care"
          element={<DrSharmaInterview />}
        />
        <Route
          path="/dog-vomiting-treatment-india"
          element={<DogVomitingPage />}
        />

        <Route path="/20+vetsonline" element={<LegacyHomePage />} />
        <Route path="/consult" element={<LegacyHomePage />} />
        <Route path="/whychooseteleconsult" element={<LegacyHomePage />} />
        <Route path="/howwework" element={<LegacyHomePage />} />
        <Route path="/commitment" element={<LegacyHomePage />} />
        <Route path="/auth" element={<Auth />} />
        <Route path="/vet-register-success" element={<VetRegisterSuccess />} />
        <Route path="/vet-dashboard" element={<VetDashboard />} />
        <Route path="/veterinary-practice-software" element={<Home />} />

        <Route path="/privacy-policy" element={<PrivacyPolicy />} />
        <Route path="/terms-of-service" element={<TearmsCondition />} />
        <Route path="/cancellation-policy" element={<Cancelation />} />
        <Route path="/cookie-policy" element={<CookiePolicy />} />
        <Route path="/vetclinic-register" element={<DoctorRegistration />} />
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

        <Route path="/patient-call-test" element={<PatientCallTest />} />
        <Route
          path="/doctor-receiver-test"
          element={<DoctorReceiverTest />}
        />
        <Route path="/call-lab" element={<CallLab />} />
        <Route path="/doctor-receiver" element={<DoctorReceiver />} />
        <Route path="/UI-test" element={<Uiapp />} />
        <Route
          path="/doctor-dashboard/:doctorId"
          element={
            <DoctorDashboard
              doctorId={parseInt(window.location.pathname.split("/")[2]) || 501}
            />
          }
        />
        <Route path="/call-demo" element={<CallRecordingDemo />} />
        <Route path="/csv-upload" element={<CsvUploadPage />} />
        <Route path="/s3-upload-test" element={<S3UploadTest />} />
        <Route
          path="/__ops/company-user-archive-r4k9d2x"
          element={<InternalUserDeletePage />}
        />

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
        <Route
          path="/blog/first-aid-tips-every-pet-parent-should-know"
          element={<FirstAidEvery />}
        />
        <Route
          path="/blog/boost-your-dogs-immunity-naturally"
          element={<BoostYourDog />}
        />
        <Route
          path="/blog/vaccination-schedule-for-pets-in-india"
          element={<VaccinationSchedule />}
        />
        <Route
          path="/blog/best-food-for-dogs-in-winter"
          element={<BestFoodForDog />}
        />
        <Route
          path="/blog/how-vets-grow-with-online-consultations"
          element={<HowVetsGrow />}
        />
        <Route
          path="/blog/top-friendly-dog-breeds-in-india"
          element={<TopFriendlyDogBreeds />}
        />
        <Route
          path="/blog/best-cat-breeds-in-india"
          element={<BestCatBreedsInIndia />}
        />
        <Route
          path="/blog/cat-vaccination-schedule-india"
          element={<CatVaccinationScheduleIndia />}
        />
        <Route
          path="/blog/cats-diseases-and-symptoms"
          element={<CatsDiseasesAndSymptoms />}
        />
        <Route
          path="/blog/best-cat-food-in-india"
          element={<BestCatFoodInIndia />}
        />
        <Route
          path="/blog/foods-golden-retrievers-should-never-eat"
          element={<FoodsGoldenRetrieversShouldNeverEat />}
        />
        <Route
          path="/blog/best-dog-food-for-golden-retrievers"
          element={<BestDogFoodForGoldenRetrievers />}
        />
        <Route
          path="/blog/golden-retriever-vaccination-schedule-india"
          element={<GoldenRetrieverVaccinationScheduleIndia />}
        />
        <Route
          path="/blog/why-winter-grooming-is-important-for-cats"
          element={<WhyWinterGroomingIsImportantForCats />}
        />

        <Route path="/delhi" element={<DelhiPage />} />
        <Route path="/gurugram" element={<GurugramPage />} />
        <Route path="/clinics-solution" element={<ClinicsSolutionPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </HelmetProvider>
  );
}
