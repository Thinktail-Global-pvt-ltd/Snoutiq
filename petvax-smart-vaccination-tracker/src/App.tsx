import { useState, useEffect, useMemo } from 'react';
import { 
  Calendar, 
  Plus, 
  CheckCircle2, 
  AlertCircle, 
  Clock, 
  Video, 
  ChevronRight, 
  Dog, 
  Cat, 
  ArrowLeft,
  Info,
  Stethoscope
} from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

// --- Types & Data ---

type Species = 'dog' | 'cat';

interface Vaccine {
  id: string;
  name: string;
  ageInDays: number;
  description: string;
}

interface VaccinationRecord {
  vaccineId: string;
  date?: string;
  isCompleted: boolean;
}

interface Pet {
  name: string;
  species: Species;
  birthDate: string;
  records: VaccinationRecord[];
  hasConfirmedHistory?: boolean; // null/undefined = not asked, true = yes, false = no
}

const DOG_SCHEDULE: Vaccine[] = [
  { id: 'd1', name: 'Puppy DP', ageInDays: 30, description: 'Protects against Distemper and Parvovirus.' },
  { id: 'd2', name: 'DHPPiL/9 in 1 (1st Shot)', ageInDays: 45, description: 'Core vaccine for Distemper, Hepatitis, Parvovirus, Parainfluenza, and Leptospirosis.' },
  { id: 'd3', name: 'Canine Corona (1st) + Kennel Cough', ageInDays: 60, description: 'Protects against Coronavirus and respiratory infections.' },
  { id: 'd4', name: 'DHPPiL/9 in 1 (2nd Shot)', ageInDays: 75, description: 'Second dose of the core vaccine.' },
  { id: 'd5', name: 'Anti-Rabies (1st) + Canine Corona (2nd)', ageInDays: 90, description: 'First Rabies shot and second Corona dose.' },
  { id: 'd6', name: 'DHPPiL/9 in 1 (3rd Shot)', ageInDays: 105, description: 'Third dose of the core vaccine.' },
  { id: 'd7', name: 'Anti-Rabies (2nd Shot)', ageInDays: 120, description: 'Second Rabies booster.' },
];

const CAT_SCHEDULE: Vaccine[] = [
  { id: 'c1', name: 'Trivalent/3 in 1 (1st Shot)', ageInDays: 60, description: 'Protects against Feline Panleukopenia, Calicivirus, and Rhinotracheitis.' },
  { id: 'c2', name: 'Trivalent/3 in 1 (2nd Shot)', ageInDays: 90, description: 'Second dose of the trivalent vaccine.' },
  { id: 'c3', name: 'Anti-Rabies (1st Shot)', ageInDays: 97, description: 'First Rabies vaccination.' },
  { id: 'c4', name: 'Trivalent/3 in 1 (3rd Shot)', ageInDays: 120, description: 'Third dose of the trivalent vaccine.' },
  { id: 'c5', name: 'Anti-Rabies (2nd Shot)', ageInDays: 127, description: 'Second Rabies booster.' },
];

const BOOSTER_TEMPLATES: Record<Species, Vaccine> = {
  dog: { id: 'd8', name: 'Annual Booster', ageInDays: 365, description: 'Annual shots for DHPPiL, ARV, CCV, and KC.' },
  cat: { id: 'c6', name: 'Annual Booster', ageInDays: 365, description: 'Annual shots for Trivalent and Rabies.' },
};

const VACCINE_SCHEDULES: Record<Species, Vaccine[]> = {
  dog: DOG_SCHEDULE,
  cat: CAT_SCHEDULE,
};

// --- Helper Functions ---

const calculateAgeInDays = (birthDate: string) => {
  const birth = new Date(birthDate);
  birth.setHours(0, 0, 0, 0);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const diffTime = today.getTime() - birth.getTime();
  return Math.floor(diffTime / (1000 * 60 * 60 * 24));
};

const getVaccineDate = (birthDate: string, ageInDays: number) => {
  const date = new Date(birthDate);
  date.setDate(date.getDate() + ageInDays);
  return date.toISOString().split('T')[0];
};

const getFullSchedule = (species: Species, ageInDays: number) => {
  const baseSchedule = VACCINE_SCHEDULES[species];
  const schedule = [...baseSchedule];
  const boosterTemplate = BOOSTER_TEMPLATES[species];
  
  // Generate boosters for all years lived plus one future year
  const currentYear = Math.floor(ageInDays / 365);
  const maxBoosterYear = currentYear + 1;
  
  for (let y = 1; y <= maxBoosterYear; y++) {
    schedule.push({
      ...boosterTemplate,
      id: `${boosterTemplate.id}-y${y}`,
      name: `${boosterTemplate.name} (Year ${y})`,
      ageInDays: y * 365
    });
  }
  
  return schedule.sort((a, b) => a.ageInDays - b.ageInDays);
};

const getStatus = (vaccine: Vaccine, ageInDays: number, record?: VaccinationRecord) => {
  if (record?.isCompleted) return 'completed';
  
  const diff = ageInDays - vaccine.ageInDays;
  if (diff > 30) return 'critical';
  if (diff > 0) return 'overdue';
  if (diff === 0) return 'due_today';
  if (Math.abs(diff) <= 14) return 'upcoming';
  return 'future';
};

// --- Components ---

export default function App() {
  const [pet, setPet] = useState<Pet | null>(null);
  const [step, setStep] = useState<'setup' | 'wizard' | 'dashboard'>('setup');
  const [wizardIndex, setWizardIndex] = useState(0);

  // Persistence
  useEffect(() => {
    const saved = localStorage.getItem('pet_vax_data');
    if (saved) {
      const parsed = JSON.parse(saved);
      setPet(parsed);
      setStep('dashboard');
    }
  }, []);

  useEffect(() => {
    if (pet) {
      localStorage.setItem('pet_vax_data', JSON.stringify(pet));
    }
  }, [pet]);

  const handleSetup = (name: string, species: Species, birthDate: string) => {
    const newPet: Pet = { name, species, birthDate, records: [] };
    setPet(newPet);
    setStep('wizard');
  };

  const ageInDays = pet ? calculateAgeInDays(pet.birthDate) : 0;

  const currentSchedule = useMemo(() => {
    if (!pet) return [];
    return getFullSchedule(pet.species, ageInDays);
  }, [pet, ageInDays]);

  const pastVaccines = useMemo(() => {
    const allPast = currentSchedule.filter(v => v.ageInDays <= ageInDays);
    // For adult pets, only ask about the last 365 days
    if (ageInDays > 365) {
      return allPast.filter(v => v.ageInDays > ageInDays - 365);
    }
    return allPast;
  }, [currentSchedule, ageInDays]);

  useEffect(() => {
    if (step === 'wizard' && pastVaccines.length === 0) {
      setStep('dashboard');
    }
  }, [step, pastVaccines]);

  const handleWizardResponse = (isCompleted: boolean, date?: string) => {
    if (!pet) return;

    const vaccine = pastVaccines[wizardIndex];
    const newRecords = [...pet.records];
    const existingIndex = newRecords.findIndex(r => r.vaccineId === vaccine.id);

    const record: VaccinationRecord = {
      vaccineId: vaccine.id,
      isCompleted,
      date: isCompleted ? (date || new Date().toISOString().split('T')[0]) : undefined
    };

    if (existingIndex >= 0) {
      newRecords[existingIndex] = record;
    } else {
      newRecords.push(record);
    }

    setPet({ ...pet, records: newRecords });

    if (wizardIndex < pastVaccines.length - 1) {
      setWizardIndex(wizardIndex + 1);
    } else {
      setStep('dashboard');
    }
  };

  const handleConfirmHistory = (confirmed: boolean) => {
    if (!pet) return;
    
    let newRecords = [...pet.records];
    if (confirmed) {
      // Auto-complete everything older than 1 year
      const cutoff = ageInDays - 365;
      currentSchedule.forEach(v => {
        if (v.ageInDays <= cutoff) {
          if (!newRecords.find(r => r.vaccineId === v.id)) {
            newRecords.push({ vaccineId: v.id, isCompleted: true });
          }
        }
      });
    }
    
    setPet({ ...pet, hasConfirmedHistory: confirmed, records: newRecords });
  };

  const resetApp = () => {
    localStorage.removeItem('pet_vax_data');
    setPet(null);
    setStep('setup');
    setWizardIndex(0);
  };

  return (
    <div className="min-h-screen bg-[#FDFCFB] text-[#1A1A1A] font-sans selection:bg-[#F27D26]/20">
      <main className="max-w-md mx-auto px-6 py-12">
        <AnimatePresence mode="wait">
          {step === 'setup' && (
            <div key="setup">
              <SetupView onComplete={handleSetup} />
            </div>
          )}

          {step === 'wizard' && pet && (
            <div key="wizard">
              <WizardView 
                pet={pet}
                vaccine={pastVaccines[wizardIndex]}
                onResponse={handleWizardResponse}
                onConfirmHistory={handleConfirmHistory}
                currentIndex={wizardIndex}
                total={pastVaccines.length}
                ageInDays={ageInDays}
              />
            </div>
          )}

          {step === 'dashboard' && pet && (
            <div key="dashboard">
              <DashboardView 
                pet={pet}
                ageInDays={ageInDays}
                schedule={currentSchedule}
                onReset={resetApp}
                onUpdateRecord={(vId, date) => {
                  const newRecords = [...pet.records];
                  const idx = newRecords.findIndex(r => r.vaccineId === vId);
                  const record = { vaccineId: vId, isCompleted: true, date };
                  if (idx >= 0) newRecords[idx] = record;
                  else newRecords.push(record);
                  setPet({ ...pet, records: newRecords });
                }}
                onMarkNotDone={(vId) => {
                  const newRecords = [...pet.records];
                  const idx = newRecords.findIndex(r => r.vaccineId === vId);
                  const record = { vaccineId: vId, isCompleted: false };
                  if (idx >= 0) newRecords[idx] = record;
                  else newRecords.push(record);
                  setPet({ ...pet, records: newRecords });
                }}
              />
            </div>
          )}
        </AnimatePresence>
      </main>
    </div>
  );
}

function SetupView({ onComplete }: { onComplete: (name: string, species: Species, birthDate: string) => void }) {
  const [name, setName] = useState('');
  const [species, setSpecies] = useState<Species>('dog');
  const [birthDate, setBirthDate] = useState('');

  return (
    <motion.div 
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -20 }}
      className="space-y-8"
    >
      <div className="space-y-2">
        <h1 className="text-4xl font-bold tracking-tight text-[#F27D26]">PetVax</h1>
        <p className="text-sm text-gray-500 uppercase tracking-widest font-semibold">Smart Vaccination Tracker</p>
      </div>

      <div className="space-y-6">
        <div className="space-y-2">
          <label className="text-xs font-bold uppercase text-gray-400">Pet's Name</label>
          <input 
            type="text" 
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="e.g. Buddy"
            className="w-full bg-white border-b-2 border-gray-100 py-3 focus:border-[#F27D26] outline-none transition-colors text-lg"
          />
        </div>

        <div className="space-y-2">
          <label className="text-xs font-bold uppercase text-gray-400">Species</label>
          <div className="grid grid-cols-2 gap-4">
            <button 
              onClick={() => setSpecies('dog')}
              className={`flex items-center justify-center gap-3 p-4 rounded-2xl border-2 transition-all ${species === 'dog' ? 'border-[#F27D26] bg-[#F27D26]/5 text-[#F27D26]' : 'border-gray-100 text-gray-400'}`}
            >
              <Dog size={24} />
              <span className="font-bold">Dog</span>
            </button>
            <button 
              onClick={() => setSpecies('cat')}
              className={`flex items-center justify-center gap-3 p-4 rounded-2xl border-2 transition-all ${species === 'cat' ? 'border-[#F27D26] bg-[#F27D26]/5 text-[#F27D26]' : 'border-gray-100 text-gray-400'}`}
            >
              <Cat size={24} />
              <span className="font-bold">Cat</span>
            </button>
          </div>
        </div>

        <div className="space-y-2">
          <label className="text-xs font-bold uppercase text-gray-400">Birth Date</label>
          <input 
            type="date" 
            value={birthDate}
            onChange={(e) => setBirthDate(e.target.value)}
            max={new Date().toISOString().split('T')[0]}
            className="w-full bg-white border-b-2 border-gray-100 py-3 focus:border-[#F27D26] outline-none transition-colors text-lg"
          />
        </div>

        <button 
          disabled={!name || !birthDate}
          onClick={() => onComplete(name, species, birthDate)}
          className="w-full bg-[#F27D26] text-white py-4 rounded-2xl font-bold shadow-lg shadow-[#F27D26]/20 disabled:opacity-50 disabled:shadow-none transition-all active:scale-95"
        >
          Get Started
        </button>

        <div className="pt-8 border-t border-gray-100 space-y-4">
          <h3 className="text-xs font-bold uppercase text-gray-400 flex items-center gap-2">
            <Stethoscope size={14} /> Quick Test Presets
          </h3>
          <div className="grid grid-cols-1 gap-2">
            {[
              { label: '2 Month Old Puppy', species: 'dog', days: 60 },
              { label: '4 Month Old Puppy (Critical)', species: 'dog', days: 120 },
              { label: '1 Year Old Cat', species: 'cat', days: 365 },
              { label: '2 Year Old Cat', species: 'cat', days: 365 * 2 },
              { label: '4 Year Old Dog', species: 'dog', days: 365 * 4 },
              { label: '5 Year Old Dog', species: 'dog', days: 365 * 5 }
            ].map((preset) => (
              <button
                key={preset.label}
                onClick={() => {
                  const date = new Date();
                  date.setDate(date.getDate() - preset.days);
                  onComplete(preset.label.split(' ')[0], preset.species as Species, date.toISOString().split('T')[0]);
                }}
                className="text-left p-3 rounded-xl bg-gray-50 hover:bg-gray-100 text-xs font-bold text-gray-600 transition-colors flex justify-between items-center"
              >
                {preset.label}
                <ChevronRight size={14} className="text-gray-300" />
              </button>
            ))}
          </div>
        </div>
      </div>
    </motion.div>
  );
}

function WizardView({ pet, vaccine, onResponse, onConfirmHistory, currentIndex, total, ageInDays }: { 
  pet: Pet, 
  vaccine: Vaccine, 
  onResponse: (isCompleted: boolean, date?: string) => void,
  onConfirmHistory: (confirmed: boolean) => void,
  currentIndex: number,
  total: number,
  ageInDays: number
}) {
  const [date, setDate] = useState(new Date().toISOString().split('T')[0]);
  const [showDatePicker, setShowDatePicker] = useState(false);

  const isAdult = ageInDays > 365;
  const needsHistoryCheck = isAdult && pet.hasConfirmedHistory === undefined;

  if (needsHistoryCheck) {
    return (
      <motion.div 
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className="space-y-8"
      >
        <div className="p-6 rounded-3xl bg-blue-50 border-2 border-blue-100 space-y-6">
          <div className="w-16 h-16 rounded-2xl bg-blue-500 text-white flex items-center justify-center shadow-lg shadow-blue-500/20">
            <Stethoscope size={32} />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-blue-900">History Check</h2>
            <p className="text-blue-700 leading-relaxed">
              Since <strong>{pet.name}</strong> is an adult, has he/she completed all {pet.species === 'dog' ? 'puppy' : 'kitten'} shots and early annual boosters in previous years?
            </p>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <button 
              onClick={() => onConfirmHistory(true)}
              className="bg-blue-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-blue-600/20 active:scale-95 transition-all"
            >
              Yes, all done
            </button>
            <button 
              onClick={() => onConfirmHistory(false)}
              className="bg-white border-2 border-blue-200 text-blue-600 py-4 rounded-2xl font-bold active:scale-95 transition-all"
            >
              No / Not sure
            </button>
          </div>
        </div>
      </motion.div>
    );
  }

  return (
    <motion.div 
      initial={{ opacity: 0, x: 20 }}
      animate={{ opacity: 1, x: 0 }}
      exit={{ opacity: 0, x: -20 }}
      className="space-y-8"
    >
      <div className="flex items-center justify-between">
        <span className="text-xs font-bold text-[#F27D26] uppercase tracking-widest">Question {currentIndex + 1} of {total}</span>
        <div className="h-1 w-24 bg-gray-100 rounded-full overflow-hidden">
          <div 
            className="h-full bg-[#F27D26] transition-all duration-500" 
            style={{ width: `${((currentIndex + 1) / total) * 100}%` }}
          />
        </div>
      </div>

      <div className="space-y-4">
        <h2 className="text-3xl font-bold leading-tight">
          Has <span className="text-[#F27D26]">{pet.name}</span> been vaccinated with <span className="italic">{vaccine.name}</span>?
        </h2>
        <p className="text-gray-500">{vaccine.description}</p>
      </div>

      <div className="space-y-4">
        {showDatePicker ? (
          <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="space-y-4">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase text-gray-400">Vaccination Date</label>
              <input 
                type="date" 
                value={date}
                onChange={(e) => setDate(e.target.value)}
                max={new Date().toISOString().split('T')[0]}
                className="w-full bg-white border-b-2 border-[#F27D26] py-3 outline-none text-lg"
              />
            </div>
            <div className="flex gap-3">
              <button 
                onClick={() => onResponse(false)}
                className="flex-1 border-2 border-red-100 py-4 rounded-2xl font-bold text-red-500 hover:bg-red-50 transition-colors"
              >
                No
              </button>
              <button 
                onClick={() => onResponse(true, date)}
                className="flex-[2] bg-[#F27D26] text-white py-4 rounded-2xl font-bold shadow-lg shadow-[#F27D26]/20"
              >
                Confirm Date
              </button>
            </div>
          </motion.div>
        ) : (
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <button 
                onClick={() => setShowDatePicker(true)}
                className="flex flex-col items-center justify-center gap-3 p-8 rounded-3xl border-2 border-gray-100 hover:border-green-500 hover:bg-green-50 transition-all group"
              >
                <div className="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center text-green-600 group-hover:scale-110 transition-transform">
                  <CheckCircle2 size={28} />
                </div>
                <span className="text-xl font-bold">Yes</span>
              </button>

              <button 
                onClick={() => onResponse(false)}
                className="flex flex-col items-center justify-center gap-3 p-8 rounded-3xl border-2 border-gray-100 hover:border-red-500 hover:bg-red-50 transition-all group"
              >
                <div className="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center text-red-600 group-hover:scale-110 transition-transform">
                  <Clock size={28} />
                </div>
                <span className="text-xl font-bold">No</span>
              </button>
            </div>
            
            <button 
              onClick={() => onResponse(false)}
              className="w-full py-4 text-gray-400 font-bold text-sm uppercase tracking-widest hover:text-[#F27D26] transition-colors"
            >
              I'm not sure / Skip for now
            </button>
          </div>
        )}
      </div>
    </motion.div>
  );
}

function DashboardView({ pet, ageInDays, schedule, onReset, onUpdateRecord, onMarkNotDone }: { 
  pet: Pet, 
  ageInDays: number, 
  schedule: Vaccine[], 
  onReset: () => void,
  onUpdateRecord: (vId: string, date: string) => void,
  onMarkNotDone: (vId: string) => void
}) {
  const [updatingId, setUpdatingId] = useState<string | null>(null);
  const [updateDate, setUpdateDate] = useState(new Date().toISOString().split('T')[0]);
  const [showAll, setShowAll] = useState(false);

  const stats = useMemo(() => {
    const records = schedule.map(v => {
      const record = pet.records.find(r => r.vaccineId === v.id);
      const status = getStatus(v, ageInDays, record);
      return { ...v, status, record };
    });

    const overdueCount = records.filter(r => r.status === 'overdue' || r.status === 'critical' || r.status === 'due_today').length;
    let filteredRecords = overdueCount >= 2 
      ? records.filter(r => r.status !== 'future')
      : records;

    // Sort descending: most recent/future first
    filteredRecords = [...filteredRecords].sort((a, b) => b.ageInDays - a.ageInDays);

    return {
      completed: records.filter(r => r.status === 'completed'),
      overdue: records.filter(r => r.status === 'overdue' || r.status === 'critical' || r.status === 'due_today'),
      upcoming: records.filter(r => r.status === 'upcoming' || r.status === 'future'),
      all: filteredRecords,
      isScheduleReset: overdueCount >= 2
    };
  }, [pet, ageInDays, schedule]);

  const criticalVaccine = stats.overdue.find(v => v.status === 'critical');
  const dueTodayVaccine = stats.overdue.find(v => v.status === 'due_today');
  const isScheduleReset = stats.isScheduleReset;

  const visibleRecords = showAll ? stats.all : stats.all.slice(0, 6);

  return (
    <motion.div 
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      className="space-y-8"
    >
      <header className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 rounded-2xl bg-[#F27D26] flex items-center justify-center text-white shadow-lg shadow-[#F27D26]/20">
            {pet.species === 'dog' ? <Dog size={24} /> : <Cat size={24} />}
          </div>
          <div>
            <h1 className="text-2xl font-bold">{pet.name}</h1>
            <p className="text-xs font-bold text-gray-400 uppercase tracking-widest">
              {Math.floor(ageInDays / 30)} Months Old
            </p>
          </div>
        </div>
        <button onClick={onReset} className="p-2 text-gray-300 hover:text-red-500 transition-colors">
          <ArrowLeft size={20} />
        </button>
      </header>

      {/* Urgency Alert */}
      <motion.div 
        initial={{ scale: 0.95, opacity: 0 }}
        animate={{ scale: 1, opacity: 1 }}
        className={`p-6 rounded-3xl border-2 ${
          pet.hasConfirmedHistory === false ? 'border-blue-500 bg-blue-50' :
          stats.overdue.length === 0 ? 'border-green-500 bg-green-50' :
          criticalVaccine ? 'border-red-600 bg-red-50' : 
          isScheduleReset ? 'border-orange-500 bg-orange-50' : 
          'border-orange-400 bg-orange-50'
        } space-y-4`}
      >
        <div className="flex items-start gap-4">
          <div className={`p-2 rounded-xl ${
            pet.hasConfirmedHistory === false ? 'bg-blue-500' :
            stats.overdue.length === 0 ? 'bg-green-500' :
            criticalVaccine ? 'bg-red-500' : 
            isScheduleReset ? 'bg-orange-500' :
            'bg-orange-500'
          } text-white`}>
            {pet.hasConfirmedHistory === false ? <Stethoscope size={20} /> : 
             stats.overdue.length === 0 ? <CheckCircle2 size={20} /> :
             <AlertCircle size={20} />}
          </div>
          <div className="space-y-1">
            <h3 className={`font-bold ${
              pet.hasConfirmedHistory === false ? 'text-blue-700' :
              stats.overdue.length === 0 ? 'text-green-700' :
              criticalVaccine ? 'text-red-700' : 
              isScheduleReset ? 'text-orange-700' :
              'text-orange-700'
            }`}>
              {pet.hasConfirmedHistory === false ? 'SnoutIQ Advice' :
               stats.overdue.length === 0 ? 'Great Job!' :
               criticalVaccine ? 'Critical: Action Required' : 
               isScheduleReset ? 'Schedule Review Needed' :
               dueTodayVaccine ? 'Vaccination Due Today' : 'Vaccination Overdue'}
            </h3>
            <p className={`text-sm ${
              pet.hasConfirmedHistory === false ? 'text-blue-600' : 
              stats.overdue.length === 0 ? 'text-green-600' :
              criticalVaccine ? 'text-red-600' : 
              'text-orange-600'
            }`}>
              {pet.hasConfirmedHistory === false
                ? `Since ${pet.name} past vaccine history is missing would you like to consult a doctor online to understand his vaccines needs better?`
                : stats.overdue.length === 0
                  ? "You have nailed Pet parenting 101. Keep tracking daily health signals as well."
                  : criticalVaccine 
                    ? `${pet.name} is more than 30 days overdue for ${criticalVaccine.name}. A missed dose can reset the entire schedule.`
                    : isScheduleReset 
                      ? `${pet.name} has multiple vaccines overdue. We recommend a quick consultation to understand if any doses need to be repeated to keep ${pet.name} fully protected.`
                      : dueTodayVaccine
                        ? `${pet.name} is due for ${dueTodayVaccine.name} today. Please book an appointment to stay on schedule.`
                        : `${pet.name} has missed the scheduled date for ${stats.overdue[0].name}. Please book an appointment immediately.`
              }
            </p>
          </div>
        </div>

        {stats.overdue.length > 0 || pet.hasConfirmedHistory === false ? (
          <div className="flex gap-3">
            {pet.hasConfirmedHistory === false ? (
              <button className="w-full bg-blue-600 text-white py-4 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-blue-600/20 active:scale-[0.98] transition-all">
                <Video size={18} />
                Video Consult
              </button>
            ) : isScheduleReset || criticalVaccine ? (
              <div className="flex flex-1 gap-2">
                <button className={`flex-1 ${
                  criticalVaccine ? 'bg-red-600 shadow-red-600/20' : 
                  isScheduleReset ? 'bg-orange-600 shadow-orange-600/20' :
                  'bg-blue-600 shadow-blue-600/20'
                } text-white py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg`}>
                  <Video size={16} />
                  Video Consult
                </button>
                <button className={`flex-1 bg-white border-2 ${
                  criticalVaccine ? 'border-red-100 text-red-600' : 
                  isScheduleReset ? 'border-orange-100 text-orange-600' :
                  'border-blue-100 text-blue-600'
                } py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2`}>
                  <Calendar size={16} />
                  Clinic Visit
                </button>
              </div>
            ) : (
              <button className="flex-1 bg-orange-600 text-white py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-orange-600/20">
                <Calendar size={16} />
                Book Clinic
              </button>
            )}
            {!isScheduleReset && (
              <button 
                onClick={() => setUpdatingId(stats.overdue[0].id)}
                className="flex-1 bg-white border-2 border-gray-100 py-3 rounded-xl font-bold text-sm text-gray-600"
              >
                Update Record
              </button>
            )}
          </div>
        ) : null}
      </motion.div>

      {/* Timeline */}
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h2 className="text-xl font-bold">Vaccination Timeline</h2>
          <div className="flex gap-2">
            <span className="flex items-center gap-1 text-[10px] font-bold uppercase text-gray-400">
              <div className="w-2 h-2 rounded-full bg-green-500" /> Done
            </span>
            <span className="flex items-center gap-1 text-[10px] font-bold uppercase text-gray-400">
              <div className="w-2 h-2 rounded-full bg-red-500" /> Overdue
            </span>
          </div>
        </div>

        <div className="space-y-4 relative">
          <div className="absolute left-[23px] top-4 bottom-4 w-0.5 bg-gray-100" />
          
          {visibleRecords.map((item, idx) => (
            <div key={item.id} className="relative pl-12">
              <div className={`absolute left-0 top-1 w-12 h-12 rounded-2xl border-4 border-white shadow-sm flex items-center justify-center transition-colors ${
                item.status === 'completed' ? 'bg-green-500 text-white' : 
                item.status === 'critical' || item.status === 'overdue' || item.status === 'due_today' ? 'bg-red-500 text-white' :
                item.status === 'upcoming' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-300'
              }`}>
                {item.status === 'completed' ? <CheckCircle2 size={20} /> : 
                 item.status === 'critical' || item.status === 'overdue' || item.status === 'due_today' ? <AlertCircle size={20} /> :
                 <Clock size={20} />}
              </div>

              <div className={`p-5 rounded-2xl border-2 transition-all ${
                updatingId === item.id ? 'border-[#F27D26] bg-white ring-4 ring-[#F27D26]/5' : 'border-gray-100 bg-white'
              }`}>
                <div className="flex items-start justify-between mb-1">
                  <h4 className="font-bold text-lg">{item.name}</h4>
                  <div className="text-right">
                    <span className="block text-[10px] font-black uppercase text-gray-300 tracking-tighter">
                      Ideal: {Math.floor(item.ageInDays / 30)}M
                    </span>
                    {item.record?.isCompleted && item.record.date && (
                      <span className="block text-[10px] font-bold uppercase text-[#F27D26] tracking-tighter">
                        At: {Math.floor((new Date(item.record.date).getTime() - new Date(pet.birthDate).getTime()) / (1000 * 60 * 60 * 24 * 30))}M
                      </span>
                    )}
                  </div>
                </div>
                
                {updatingId === item.id ? (
                  <div className="mt-4 space-y-4">
                    <input 
                      type="date" 
                      value={updateDate}
                      onChange={(e) => setUpdateDate(e.target.value)}
                      max={new Date().toISOString().split('T')[0]}
                      className="w-full bg-gray-50 p-3 rounded-xl outline-none focus:ring-2 focus:ring-[#F27D26]/20"
                    />
                    <div className="flex gap-2">
                      <button 
                        onClick={() => {
                          onMarkNotDone(item.id);
                          setUpdatingId(null);
                        }}
                        className="flex-1 py-2 text-xs font-bold text-red-500 border border-red-100 rounded-xl"
                      >
                        Mark as Not Done
                      </button>
                      <button 
                        onClick={() => {
                          onUpdateRecord(item.id, updateDate);
                          setUpdatingId(null);
                        }}
                        className="flex-2 bg-[#F27D26] text-white py-2 rounded-xl text-xs font-bold"
                      >
                        Save Record
                      </button>
                    </div>
                  </div>
                ) : (
                  <>
                    <p className="text-xs text-gray-500 mb-3">{item.description}</p>
                    <div className="flex items-center justify-between">
                      <span className={`text-[10px] font-bold uppercase tracking-widest ${
                        item.status === 'completed' ? 'text-green-600' :
                        item.status === 'critical' ? 'text-red-600' :
                        item.status === 'overdue' ? 'text-orange-600' :
                        item.status === 'due_today' ? 'text-red-600' :
                        item.status === 'upcoming' ? 'text-blue-600' : 'text-gray-400'
                      }`}>
                        {item.status === 'completed' ? (item.record?.date ? `Done: ${item.record.date}` : 'Marked done by parent') :
                         item.status === 'critical' ? 'Urgent: Consult Doctor' :
                         item.status === 'overdue' ? 'Overdue: Book Now' :
                         item.status === 'due_today' ? 'Due Today: Action Required' :
                         `Due: ${getVaccineDate(pet.birthDate, item.ageInDays)}`}
                      </span>
                      {!item.record?.isCompleted && (
                        <button 
                          onClick={() => setUpdatingId(item.id)}
                          className="p-1 text-[#F27D26] hover:bg-[#F27D26]/5 rounded-lg transition-colors"
                        >
                          <Plus size={18} />
                        </button>
                      )}
                    </div>
                  </>
                )}
              </div>
            </div>
          ))}

          {!showAll && stats.all.length > 6 && (
            <button 
              onClick={() => setShowAll(true)}
              className="w-full py-4 bg-gray-50 rounded-2xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition-colors flex items-center justify-center gap-2"
            >
              Show {stats.all.length - 6} Older Records
              <ChevronRight size={14} className="rotate-90" />
            </button>
          )}
        </div>
      </div>

      {/* Footer Info */}
      <div className="p-6 rounded-3xl bg-gray-50 border border-gray-100 flex gap-4">
        <div className="text-[#F27D26]">
          <Info size={20} />
        </div>
        <p className="text-xs text-gray-500 leading-relaxed">
          Vaccination starts working after 2 weeks. Avoid outside walks and meeting non-vaccinated pets until the first Rabies and 3rd core vaccine shots are completed.
        </p>
      </div>
    </motion.div>
  );
}
