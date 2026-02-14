import React, { useState } from 'react';
import { Button } from './Button';
import { 
  ChevronLeft, 
  Stethoscope, 
  Phone, 
  Mail, 
  MapPin, 
  Clock, 
  DollarSign, 
  ShieldCheck, 
  CheckCircle2, 
  TrendingUp, 
  Users, 
  Star, 
  Calendar,
  AlertCircle,
  Moon,
  Sun,
  History,
  Lock
} from 'lucide-react';

// --- Internal Helper Components ---

const VetHeader: React.FC<{ onBack?: () => void; title: string }> = ({ onBack, title }) => (
  <div className="sticky top-0 z-50 bg-white/90 backdrop-blur-md px-4 py-3 flex items-center shadow-sm border-b border-stone-100">
    {onBack && (
      <button onClick={onBack} className="p-2 -ml-2 text-stone-500 hover:bg-stone-100 rounded-full transition-colors">
        <ChevronLeft size={24} />
      </button>
    )}
    <h1 className="flex-1 text-center font-bold text-lg text-stone-800">{title}</h1>
    {onBack && <div className="w-10" />}
  </div>
);

// --- 1. Vet Login Screen ---

export const VetLoginScreen: React.FC<{ 
  onLogin: () => void; 
  onRegisterClick: () => void; 
  onBack: () => void; 
}> = ({ onLogin, onRegisterClick, onBack }) => {
  const [mobile, setMobile] = useState('');
  const [otp, setOtp] = useState('');
  const [step, setStep] = useState<'mobile' | 'otp'>('mobile');

  const handleSendOtp = () => {
    // Demo logic: Accept any number length > 0
    if (mobile.length > 0) setStep('otp');
  };

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up">
      <VetHeader onBack={onBack} title="Vet Partner Login" />
      
      <div className="flex-1 px-6 py-8 flex flex-col justify-center max-w-sm mx-auto w-full">
        <div className="text-center mb-8">
          <div className="w-16 h-16 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <Stethoscope size={32} />
          </div>
          <h2 className="text-2xl font-bold text-stone-800">Welcome, Doctor</h2>
          <p className="text-stone-500 mt-2 text-sm">Log in to manage your consultations.</p>
          <span className="inline-block mt-2 bg-stone-100 text-stone-500 text-[10px] px-2 py-1 rounded">DEMO MODE: Use any number</span>
        </div>

        <div className="bg-white p-6 rounded-2xl shadow-sm border border-stone-100 space-y-6">
          {step === 'mobile' ? (
            <>
              <div>
                <label className="block text-xs font-bold uppercase text-stone-400 mb-1">Mobile Number</label>
                <div className="flex items-center border border-stone-200 rounded-xl px-3 bg-stone-50 focus-within:ring-2 focus-within:ring-brand-200">
                  <span className="text-stone-500 font-medium border-r border-stone-200 pr-3 mr-3">+91</span>
                  <input 
                    type="tel" 
                    value={mobile}
                    onChange={(e) => setMobile(e.target.value.replace(/\D/g, '').slice(0, 10))}
                    placeholder="98765 43210"
                    className="flex-1 py-3 bg-transparent outline-none font-medium text-stone-800"
                  />
                </div>
              </div>
              <Button onClick={handleSendOtp} disabled={mobile.length < 10} fullWidth>
                Send OTP
              </Button>
            </>
          ) : (
            <>
              <div>
                <label className="block text-xs font-bold uppercase text-stone-400 mb-1">Enter OTP</label>
                <input 
                  type="text" 
                  value={otp}
                  onChange={(e) => setOtp(e.target.value.slice(0, 4))}
                  placeholder="Any 4 digits"
                  className="w-full py-3 px-4 text-center text-2xl tracking-widest border border-stone-200 rounded-xl bg-stone-50 focus:outline-none focus:ring-2 focus:ring-brand-200"
                />
                <p className="text-xs text-center text-stone-400 mt-2">Sent to +91 {mobile}</p>
              </div>
              <Button onClick={onLogin} disabled={otp.length < 4} fullWidth>
                Verify & Login
              </Button>
              <button onClick={() => setStep('mobile')} className="w-full text-xs text-brand-600 font-medium py-2">
                Change Number
              </button>
            </>
          )}
        </div>

        <div className="mt-8 text-center">
          <p className="text-stone-500 text-sm">New to Snoutiq?</p>
          <button onClick={onRegisterClick} className="text-brand-600 font-bold text-sm hover:underline mt-1">
            Register as a Partner
          </button>
        </div>
      </div>
    </div>
  );
};

// --- 2. Vet Registration Screen ---

export const VetRegisterScreen: React.FC<{ 
  onSubmit: () => void; 
  onBack: () => void; 
}> = ({ onSubmit, onBack }) => {
  const [agreed, setAgreed] = useState(false);
  const [dayPrice, setDayPrice] = useState<string>('');
  const [nightPrice, setNightPrice] = useState<string>('');
  const [isNightShift, setIsNightShift] = useState(false);

  // Commission Logic: Max(25%, 99)
  const calculateCommission = (priceStr: string) => {
    const price = parseFloat(priceStr);
    if (isNaN(price) || price === 0) return null;
    
    const commission = Math.max(price * 0.25, 99);
    const earning = price - commission;
    
    return {
      commission: Math.ceil(commission),
      earning: Math.floor(earning)
    };
  };

  const dayMath = calculateCommission(dayPrice);
  const nightMath = calculateCommission(nightPrice);

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up">
      <VetHeader onBack={onBack} title="Partner Registration" />
      
      <div className="flex-1 px-4 py-6 pb-32 overflow-y-auto no-scrollbar">
        <p className="text-sm text-stone-500 mb-6 px-2">
          Join India's most trusted network of empathetic veterinarians.
        </p>

        <div className="space-y-6">
          
          {/* Section 1: Basic */}
          <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4">
            <h3 className="font-bold text-stone-800 flex items-center gap-2">
              <span className="bg-brand-100 text-brand-700 w-6 h-6 rounded-full flex items-center justify-center text-xs">1</span>
              Basic Details
            </h3>
            <input type="text" placeholder="Full Name (Dr. ...)" className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
            <input type="text" placeholder="Clinic Name" className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
            <div className="grid grid-cols-2 gap-3">
              <input type="text" placeholder="City" className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
              <input type="tel" placeholder="WhatsApp Number" className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
            </div>
            <p className="text-[10px] text-stone-400 flex items-center gap-1">
              <Lock size={10} /> Your number is kept private and never shared directly with pet parents.
            </p>
            <input type="email" placeholder="Email Address" className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
          </section>

          {/* Section 2: Professional */}
          <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4">
            <h3 className="font-bold text-stone-800 flex items-center gap-2">
              <span className="bg-brand-100 text-brand-700 w-6 h-6 rounded-full flex items-center justify-center text-xs">2</span>
              Professional Details
            </h3>
            
            <input type="text" placeholder="Vet Registration Number (Required)" className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
            
            <select className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm text-stone-600">
              <option>Select Qualification</option>
              <option>BVSc & AH</option>
              <option>MVSc</option>
              <option>PhD</option>
            </select>
            
            <div>
              <label className="block text-xs font-bold text-stone-400 mb-2">Specialization</label>
              <div className="flex flex-wrap gap-2">
                {['Dogs', 'Cats', 'Birds', 'Exotic'].map(spec => (
                  <label key={spec} className="flex items-center gap-2 px-3 py-2 border border-stone-200 rounded-lg text-sm bg-stone-50">
                    <input type="checkbox" className="accent-brand-600" />
                    {spec}
                  </label>
                ))}
              </div>
            </div>
            
            <input type="number" placeholder="Years of Experience" className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
          </section>

          {/* Section 3: Availability */}
          <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4">
            <h3 className="font-bold text-stone-800 flex items-center gap-2">
              <span className="bg-brand-100 text-brand-700 w-6 h-6 rounded-full flex items-center justify-center text-xs">3</span>
              Availability & Timing
            </h3>
            
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-bold text-stone-400 mb-1 flex items-center gap-1">
                  <Sun size={10} /> Online Start
                </label>
                <input type="time" defaultValue="09:00" className="w-full p-2 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
              </div>
              <div>
                 <label className="block text-xs font-bold text-stone-400 mb-1 flex items-center gap-1">
                  <Moon size={10} /> Online End
                </label>
                <input type="time" defaultValue="20:00" className="w-full p-2 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3 mt-2">
              <div className="col-span-2">
                <label className="block text-xs font-bold text-stone-400 mb-1">Do Not Disturb / Sleep Time</label>
                <div className="flex gap-2 items-center">
                  <input type="time" defaultValue="22:00" className="flex-1 p-2 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
                  <span className="text-stone-400 text-xs">to</span>
                  <input type="time" defaultValue="07:00" className="flex-1 p-2 rounded-xl border border-stone-200 bg-stone-50 text-sm" />
                </div>
              </div>
            </div>

            <div className={`p-4 rounded-xl border transition-all ${isNightShift ? 'bg-indigo-50 border-indigo-200' : 'bg-stone-50 border-stone-200'}`}>
              <div className="flex justify-between items-start">
                <div>
                   <h4 className={`font-bold text-sm ${isNightShift ? 'text-indigo-800' : 'text-stone-700'}`}>Available for Night Shift?</h4>
                   <p className="text-xs text-stone-500 mt-1">Emergency consults (10PM - 6AM)</p>
                </div>
                <input 
                  type="checkbox" 
                  checked={isNightShift} 
                  onChange={e => setIsNightShift(e.target.checked)} 
                  className="w-5 h-5 accent-indigo-600" 
                />
              </div>
              {isNightShift && (
                <div className="mt-2 bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded inline-flex items-center gap-1">
                  <TrendingUp size={10} /> High Revenue Potential
                </div>
              )}
            </div>
          </section>

          {/* Section 4: Pricing & Commission */}
          <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4">
            <h3 className="font-bold text-stone-800 flex items-center gap-2">
              <span className="bg-brand-100 text-brand-700 w-6 h-6 rounded-full flex items-center justify-center text-xs">4</span>
              Pricing & Commission
            </h3>
            
            <div className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-stone-400 mb-1">Day Consult Fee (₹)</label>
                <input 
                  type="number" 
                  value={dayPrice}
                  onChange={e => setDayPrice(e.target.value)}
                  placeholder="399" 
                  className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm font-bold text-stone-800" 
                />
                {dayMath && (
                  <div className="mt-1 text-[10px] flex justify-between bg-green-50 text-green-800 px-2 py-1 rounded">
                    <span>You earn: <strong>₹{dayMath.earning}</strong></span>
                    <span className="text-green-600/70">Snoutiq Fee: ₹{dayMath.commission}</span>
                  </div>
                )}
              </div>
              
              <div>
                <label className="block text-xs font-bold text-stone-400 mb-1">Night Consult Fee (₹)</label>
                <input 
                  type="number" 
                  value={nightPrice}
                  onChange={e => setNightPrice(e.target.value)}
                  placeholder="599" 
                  className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm font-bold text-stone-800" 
                />
                {nightMath && (
                  <div className="mt-1 text-[10px] flex justify-between bg-green-50 text-green-800 px-2 py-1 rounded">
                    <span>You earn: <strong>₹{nightMath.earning}</strong></span>
                    <span className="text-green-600/70">Snoutiq Fee: ₹{nightMath.commission}</span>
                  </div>
                )}
              </div>
            </div>

            <div className="bg-amber-50 p-4 rounded-xl border border-amber-100">
              <h4 className="text-amber-800 font-bold text-xs uppercase mb-2 flex items-center gap-1">
                <DollarSign size={12} /> Commission Structure
              </h4>
              <ul className="text-xs text-amber-900/80 space-y-1 list-disc pl-4">
                <li>We charge 25% OR ₹99 per consultation (whichever is higher).</li>
                <li>The remaining amount is yours.</li>
                <li>No monthly subscription fees.</li>
              </ul>
            </div>

            <label className="flex gap-3 items-start p-2 cursor-pointer">
              <input 
                type="checkbox" 
                checked={agreed} 
                onChange={e => setAgreed(e.target.checked)} 
                className="mt-1 accent-brand-600" 
              />
              <span className="text-xs text-stone-600 leading-relaxed">
                I understand and agree to the pricing & commission structure defined above.
              </span>
            </label>
          </section>
        </div>
      </div>

      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-stone-100 safe-area-pb max-w-md mx-auto z-20">
        <Button onClick={onSubmit} fullWidth disabled={!agreed} className={!agreed ? 'opacity-50' : ''}>
          Submit Application
        </Button>
      </div>
    </div>
  );
};

// --- 3. Pending Approval Screen ---

export const VetPendingScreen: React.FC<{ onHome: () => void }> = ({ onHome }) => {
  return (
    <div className="min-h-screen bg-white flex flex-col items-center justify-center p-8 text-center animate-fade-in">
      <div className="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mb-6 text-amber-600">
        <Clock size={40} />
      </div>
      
      <h2 className="text-2xl font-bold text-stone-800 mb-2">Application Received</h2>
      <p className="text-stone-500 mb-8 max-w-[280px] mx-auto leading-relaxed">
        Thanks, Doctor. Our team will verify your credentials and activate your profile within 24-48 hours.
      </p>

      <div className="w-full max-w-xs space-y-3">
        <Button onClick={onHome} variant="secondary" fullWidth>
          Back to Home
        </Button>
      </div>
    </div>
  );
};

// --- 4. Vet Dashboard Screen ---

export const VetDashboardScreen: React.FC<{ onLogout: () => void }> = ({ onLogout }) => {
  const [isAvailable, setIsAvailable] = useState(true);

  // Mock History Data
  const history = [
    { id: 1, petParent: 'Rahul M.', pet: 'Rocky (Dog)', time: 'Today, 10:30 AM', fee: '₹399' },
    { id: 2, petParent: 'Priya S.', pet: 'Luna (Cat)', time: 'Yesterday, 8:15 PM', fee: '₹599' },
    { id: 3, petParent: 'Amit K.', pet: 'Coco (Dog)', time: '21 Oct, 4:00 PM', fee: '₹399' },
  ];

  return (
    <div className="min-h-screen bg-stone-50 flex flex-col animate-slide-up">
      {/* Dashboard Header */}
      <div className="bg-brand-900 text-white pt-8 pb-12 px-6 rounded-b-[2rem] relative shadow-lg">
        <div className="flex justify-between items-center mb-6">
          <div className="flex items-center gap-3">
             <div className="w-12 h-12 rounded-full bg-white/20 border-2 border-white/30 flex items-center justify-center text-xl font-bold">
               DR
             </div>
             <div>
               <h1 className="font-bold text-lg">Dr. Rajesh</h1>
               <div className="flex items-center gap-1 text-brand-200 text-xs">
                 <MapPin size={10} /> Delhi, India
               </div>
             </div>
          </div>
          <button onClick={onLogout} className="text-xs text-brand-200 hover:text-white font-medium">
            Logout
          </button>
        </div>

        {/* Availability Toggle */}
        <div className="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 flex items-center justify-between">
           <div className="flex items-center gap-3">
             <div className={`w-3 h-3 rounded-full ${isAvailable ? 'bg-green-400 animate-pulse' : 'bg-stone-400'}`} />
             <span className="font-medium text-sm">{isAvailable ? 'You are Online' : 'You are Offline'}</span>
           </div>
           <button 
             onClick={() => setIsAvailable(!isAvailable)}
             className={`px-4 py-1.5 rounded-full text-xs font-bold transition-colors ${
               isAvailable ? 'bg-white text-brand-900' : 'bg-brand-800 text-brand-300'
             }`}
           >
             {isAvailable ? 'Go Offline' : 'Go Online'}
           </button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="px-4 -mt-8 mb-8 grid grid-cols-2 gap-3 z-10">
         <div className="bg-white p-4 rounded-xl shadow-sm border border-stone-100">
           <p className="text-stone-400 text-xs uppercase font-bold mb-1">Today's Earnings</p>
           <p className="text-2xl font-bold text-stone-800">₹1,240</p>
           <p className="text-[10px] text-green-600 flex items-center gap-1 mt-1">
             <TrendingUp size={10} /> +12% vs yest
           </p>
         </div>
         <div className="bg-white p-4 rounded-xl shadow-sm border border-stone-100">
           <p className="text-stone-400 text-xs uppercase font-bold mb-1">Total Consults</p>
           <p className="text-2xl font-bold text-stone-800">42</p>
           <p className="text-[10px] text-stone-400 mt-1">Lifetime</p>
         </div>
      </div>

      <div className="flex-1 px-4 pb-20 overflow-y-auto no-scrollbar space-y-6">
        
        {/* Analytics Section */}
        <section>
          <h3 className="font-bold text-stone-800 mb-3 text-sm">Performance</h3>
          <div className="bg-white rounded-2xl p-1 shadow-sm border border-stone-100">
            <div className="grid grid-cols-3 divide-x divide-stone-100">
              <div className="p-4 text-center">
                 <p className="text-stone-400 text-[10px] uppercase font-bold mb-1">Avg Rating</p>
                 <div className="flex items-center justify-center gap-1 font-bold text-stone-800">
                   4.9 <Star size={12} className="text-amber-400 fill-current" />
                 </div>
              </div>
              <div className="p-4 text-center">
                 <p className="text-stone-400 text-[10px] uppercase font-bold mb-1">Response</p>
                 <div className="font-bold text-stone-800">
                   8m
                 </div>
              </div>
              <div className="p-4 text-center">
                 <p className="text-stone-400 text-[10px] uppercase font-bold mb-1">Return Rate</p>
                 <div className="font-bold text-stone-800">
                   24%
                 </div>
              </div>
            </div>
          </div>
        </section>

        {/* Recent History (New) */}
        <section>
          <h3 className="font-bold text-stone-800 mb-3 text-sm flex items-center gap-2">
            <History size={14} /> Recent Consultations
          </h3>
          <div className="bg-white rounded-2xl shadow-sm border border-stone-100 overflow-hidden">
            {history.map((item, idx) => (
              <div key={item.id} className={`p-4 flex justify-between items-center ${idx !== history.length -1 ? 'border-b border-stone-100' : ''}`}>
                 <div>
                    <p className="font-bold text-stone-800 text-sm">{item.petParent}</p>
                    <p className="text-xs text-stone-500">{item.pet}</p>
                 </div>
                 <div className="text-right">
                    <p className="font-bold text-stone-800 text-sm">{item.fee}</p>
                    <p className="text-[10px] text-stone-400">{item.time}</p>
                 </div>
              </div>
            ))}
          </div>
          <p className="text-[10px] text-stone-400 mt-2 flex items-center gap-1">
            <Lock size={10} /> Patient contact details are hidden for privacy.
          </p>
        </section>

        {/* Recent Feedback */}
        <section>
          <h3 className="font-bold text-stone-800 mb-3 text-sm">Recent Feedback</h3>
          <div className="space-y-3">
             {[1, 2].map((i) => (
               <div key={i} className="bg-white p-4 rounded-xl shadow-sm border border-stone-100">
                 <div className="flex justify-between items-start mb-2">
                   <div className="flex gap-1">
                     {[1,2,3,4,5].map(s => <Star key={s} size={10} className="text-amber-400 fill-current" />)}
                   </div>
                   <span className="text-[10px] text-stone-400">2h ago</span>
                 </div>
                 <p className="text-xs text-stone-600 italic">"Doctor was very calm and explained everything clearly about my dog's diet. Highly recommended!"</p>
                 <p className="text-[10px] text-stone-400 font-bold mt-2">- Sneha P.</p>
               </div>
             ))}
          </div>
        </section>

        <div className="bg-blue-50 p-4 rounded-xl border border-blue-100 flex gap-3">
          <AlertCircle className="text-blue-600 flex-shrink-0" size={20} />
          <div className="text-xs text-blue-800">
            <strong>Pro Tip:</strong> Updating your availability accurately helps you get 3x more consultations.
          </div>
        </div>

      </div>
    </div>
  );
};