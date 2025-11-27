import React, { useState } from 'react';
import { 
    Calendar, Video, User, Settings, Clock, Phone, Mail, MapPin, DollarSign, Edit3, 
    ArrowLeft, Bell, MessageSquare, Briefcase, CheckSquare, Home, LayoutGrid
} from 'lucide-react';

// --- Global UI Components ---

// Simplified Card for Mobile UI with elevated style
const MobileCard = ({ children, className = '' }) => (
  <div className={`bg-white p-4 rounded-2xl shadow-xl transition-all duration-300 hover:shadow-2xl ${className}`}>
    {children}
  </div>
);

// Mobile Button with Icon (Stylized for quick actions)
const IconButton = ({ icon: Icon, label, primary = true, onClick, className = '' }) => (
  <button
    onClick={onClick}
    className={`flex flex-col items-center justify-center p-3 font-semibold text-xs rounded-xl transition-all duration-200 h-20 w-full shadow-md 
      ${primary
        ? 'bg-indigo-600 text-white hover:bg-indigo-700'
        : 'bg-white text-indigo-600 border border-indigo-200 hover:bg-indigo-50'
      } ${className}`}
  >
    <Icon size={24} className="mb-1" />
    <span>{label}</span>
  </button>
);

// Appointment/Queue Item
const AppointmentItem = ({ appointment, actionLabel, onAction, icon: Icon, timeIcon: TimeIcon }) => (
    <MobileCard className="mb-3 flex justify-between items-center p-3">
        <div className="flex items-center space-x-3">
            <div className={`p-2 rounded-full ${appointment.status === 'Waiting' ? 'bg-red-100 text-red-600' : 'bg-indigo-100 text-indigo-600'}`}>
                <Icon size={20} />
            </div>
            <div>
                <p className="font-bold text-gray-800">{appointment.pet} ({appointment.owner})</p>
                <p className="text-xs text-gray-500 flex items-center mt-0.5">
                    <TimeIcon size={12} className="mr-1" />
                    {appointment.time} | {appointment.reason}
                </p>
            </div>
        </div>
        <button 
            onClick={onAction}
            className={`text-xs font-semibold px-3 py-1.5 rounded-full transition shadow-lg ${
                appointment.status === 'Waiting' 
                ? 'bg-red-600 text-white hover:bg-red-700' 
                : 'bg-indigo-600 text-white hover:bg-indigo-700'
            }`}
        >
            {actionLabel}
        </button>
    </MobileCard>
);

// --- Mock Data ---
const DOCTOR_DATA = {
    name: 'Dr. Priya Sharma',
    specialty: 'Small Animal Internal Medicine',
    license: 'VCI-4589',
    email: 'priya.sharma@vetcare.com',
    phone: '+91 98765 43210',
    location: 'Clinic 2, Main Branch',
    // Settings
    teleconsultRate: 75, // USD
    teleconsultDuration: 30, // minutes
    availability: 'Mon-Fri, 9:00 AM - 5:00 PM',
};

const MOCK_APPOINTMENTS = [
    { id: 1, time: '10:00 AM', pet: 'Max (Labrador)', owner: 'A. Smith', reason: 'Annual Checkup', type: 'In-Clinic', status: 'Confirmed' },
    { id: 2, time: '11:00 AM', pet: 'Whiskers (Cat)', owner: 'J. Doe', reason: 'Post-op Follow-up', type: 'Teleconsult', status: 'Waiting' },
    { id: 3, time: '01:00 PM', pet: 'Rocky (GSD)', owner: 'M. Brown', reason: 'Limping Evaluation', type: 'In-Clinic', status: 'Confirmed' },
];

const MOCK_TELEMED_QUEUE = [
    { id: 101, time: '11:00 AM', pet: 'Whiskers (Cat)', owner: 'J. Doe', reason: 'Post-op Follow-up', waitTime: 'Ready now', status: 'Waiting' },
    { id: 102, time: '11:30 AM', pet: 'Koko (Parrot)', owner: 'D. Patel', reason: 'Feather loss', waitTime: '5 min', status: 'Scheduled' },
];

// --- Screens ---

const DoctorDashboard = ({ onNavigate }) => {
    // Find the next critical appointment
    const nextUp = MOCK_TELEMED_QUEUE.find(a => a.status === 'Waiting') || MOCK_APPOINTMENTS.find(a => a.status === 'Confirmed');

    return (
        <div className="p-4 space-y-6">
            
            {/* Personalized Welcome Card */}
            <div className="p-5 bg-gradient-to-br from-indigo-600 to-purple-700 text-white rounded-2xl shadow-xl flex justify-between items-center">
                <div>
                    <p className="text-lg font-light">Good Morning,</p>
                    <h1 className="text-3xl font-extrabold">{DOCTOR_DATA.name.split(' ')[2]}!</h1>
                    <p className="text-sm opacity-80 mt-1">Ready for a busy day?</p>
                </div>
                <div className="w-14 h-14 bg-white/30 rounded-full flex items-center justify-center text-xl font-bold">
                    PS
                </div>
            </div>

            {/* Next Up / Urgent Focus Card */}
            {nextUp && (
                <MobileCard className="bg-red-50 border-red-200 border-l-4 border-l-red-500">
                    <div className="flex justify-between items-center mb-2">
                        <h2 className="text-xl font-bold text-red-800 flex items-center">
                            <Bell size={20} className="mr-2" />
                            NEXT UP: {nextUp.type === 'Teleconsult' ? 'URGENT TELEMED' : 'IN-CLINIC'}
                        </h2>
                        <span className="text-xs font-semibold text-red-600 bg-red-100 px-2 py-0.5 rounded-full">
                            {nextUp.time}
                        </span>
                    </div>
                    <p className="text-lg font-medium text-gray-900">{nextUp.pet} ({nextUp.owner})</p>
                    <p className="text-sm text-gray-600 mt-1">Reason: {nextUp.reason}</p>
                    <div className="mt-4">
                        <button 
                            onClick={() => onNavigate(nextUp.type === 'Teleconsult' ? 'Telemed' : 'Schedule')}
                            className="w-full py-2.5 bg-red-600 text-white font-bold rounded-xl shadow-md hover:bg-red-700 transition"
                        >
                            {nextUp.type === 'Teleconsult' ? 'START CALL NOW' : 'GO TO RECORD'}
                        </button>
                    </div>
                </MobileCard>
            )}

            {/* Quick Actions Grid */}
            <h2 className="text-lg font-semibold text-gray-700 pt-2">Quick Access</h2>
            <div className="grid grid-cols-4 gap-3">
                <IconButton icon={Video} label="Telemed Queue" primary={false} className="bg-green-100 text-green-700 hover:bg-green-200" onClick={() => onNavigate('Telemed')} />
                <IconButton icon={Calendar} label="Full Schedule" primary={false} className="bg-blue-100 text-blue-700 hover:bg-blue-200" onClick={() => onNavigate('Schedule')} />
                <IconButton icon={MessageSquare} label="Patient Chat" primary={false} className="bg-yellow-100 text-yellow-700 hover:bg-yellow-200" onClick={() => console.log('Patient Chat')} />
                <IconButton icon={Briefcase} label="Patient Search" primary={false} className="bg-purple-100 text-purple-700 hover:bg-purple-200" onClick={() => console.log('Search')} />
            </div>

            {/* Daily Summary */}
            <MobileCard>
                <h2 className="text-xl font-semibold mb-3">Today's Summary</h2>
                <div className="space-y-3">
                    <div className="flex justify-between items-center py-2 border-b">
                        <p className="text-gray-600">Appointments (In-Clinic)</p>
                        <span className="font-bold text-indigo-600">2</span>
                    </div>
                    <div className="flex justify-between items-center py-2 border-b">
                        <p className="text-gray-600">Teleconsults</p>
                        <span className="font-bold text-indigo-600">1</span>
                    </div>
                    <div className="flex justify-between items-center py-2">
                        <p className="text-gray-600">Pending Follow-ups</p>
                        <span className="font-bold text-red-600">4</span>
                    </div>
                </div>
            </MobileCard>
        </div>
    );
};

const DoctorSchedule = ({ onNavigate }) => (
    <div className="p-4 space-y-4">
        <h1 className="text-2xl font-bold text-gray-900 mb-6">Today's Appointments</h1>
        
        <MobileCard className='p-3 bg-indigo-50 border-indigo-200'>
            <IconButton icon={Bell} label="Check-in Notifications" primary={false} className='w-full !bg-white' onClick={() => console.log('View notifications')}/>
        </MobileCard>
        

        <div className="space-y-4">
            {MOCK_APPOINTMENTS.map(appt => (
                <AppointmentItem
                    key={appt.id}
                    appointment={{ ...appt, type: appt.type }}
                    actionLabel={appt.type === 'Teleconsult' ? 'Start Call' : 'Open Record'}
                    onAction={() => appt.type === 'Teleconsult' ? onNavigate('Telemed') : console.log('Open Record')}
                    icon={appt.type === 'Teleconsult' ? Video : Briefcase}
                    timeIcon={Clock}
                />
            ))}
        </div>
        <div className="text-center pt-4">
            <p className="text-sm text-gray-500">Full schedule loaded.</p>
        </div>
    </div>
);

const DoctorTelemedQueue = () => (
    <div className="p-4 space-y-6">
        <h1 className="text-2xl font-bold text-gray-900">Telemedicine Queue</h1>
        
        <MobileCard className="bg-red-50 border-red-200">
            <p className="font-semibold text-red-800 flex items-center">
                <Bell size={20} className="mr-2 animate-pulse" />
                1 patient is ready for connection now.
            </p>
        </MobileCard>

        <div className="space-y-4">
            {MOCK_TELEMED_QUEUE.map(queueItem => (
                <AppointmentItem
                    key={queueItem.id}
                    appointment={queueItem}
                    actionLabel={queueItem.status === 'Waiting' ? 'Connect Now' : 'Call Soon'}
                    onAction={() => console.log(`Starting call for ${queueItem.pet}`)}
                    icon={Phone}
                    timeIcon={Clock}
                />
            ))}
        </div>
        
    </div>
);

const DoctorProfile = () => {
    const [isEditing, setIsEditing] = useState(false);
    const [profile, setProfile] = useState(DOCTOR_DATA);

    const handleSave = () => {
        // Mock save logic
        console.log('Profile saved:', profile);
        setIsEditing(false);
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setProfile(prev => ({ ...prev, [name]: value }));
    };

    const DetailItem = ({ icon: Icon, label, value, name, editable = false }) => (
        <div className="py-3 border-b border-gray-100 last:border-b-0 flex justify-between items-center">
            <div className='flex items-center text-gray-600'>
                <Icon size={18} className="mr-3" />
                <span className="font-medium text-sm">{label}</span>
            </div>
            {editable && isEditing ? (
                <input
                    type={name.includes('Rate') || name.includes('Duration') ? 'number' : 'text'}
                    name={name}
                    value={value}
                    onChange={handleChange}
                    className="text-right border-b border-indigo-400 focus:outline-none focus:border-indigo-600 text-gray-800 font-semibold"
                />
            ) : (
                <span className="font-semibold text-gray-800 text-sm">{value}</span>
            )}
        </div>
    );

    return (
        <div className="p-4 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold text-gray-900">My Profile & Settings</h1>
                {isEditing ? (
                    <button onClick={handleSave} className="flex items-center text-sm font-semibold text-white bg-green-600 px-3 py-1.5 rounded-lg shadow-md hover:bg-green-700 transition">
                        <CheckSquare size={18} className="mr-1" /> Save
                    </button>
                ) : (
                    <button onClick={() => setIsEditing(true)} className="flex items-center text-sm font-semibold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg shadow-md hover:bg-indigo-100 transition">
                        <Edit3 size={18} className="mr-1" /> Edit
                    </button>
                )}
            </div>

            {/* Profile Card */}
            <MobileCard>
                <div className="flex items-center space-x-4 mb-4 pb-4 border-b border-gray-100">
                    <div className="w-16 h-16 bg-indigo-200 rounded-full flex items-center justify-center text-3xl font-bold text-indigo-800 shadow-inner">
                        PS
                    </div>
                    <div>
                        <p className="text-xl font-bold">{profile.name}</p>
                        <p className="text-sm text-gray-500">{profile.specialty}</p>
                    </div>
                </div>
                <DetailItem icon={Briefcase} label="License ID" value={profile.license} />
                <DetailItem icon={MapPin} label="Base Location" value={profile.location} />
                <DetailItem icon={Mail} label="Email" value={profile.email} />
                <DetailItem icon={Phone} label="Contact" value={profile.phone} />
            </MobileCard>

            {/* Rates & Availability Settings */}
            <MobileCard>
                <h2 className="text-xl font-semibold mb-4">Rates & Availability</h2>
                <DetailItem 
                    icon={DollarSign} 
                    label="Teleconsult Rate (USD)" 
                    value={`$${profile.teleconsultRate}`} 
                    name="teleconsultRate" 
                    editable={true}
                />
                <DetailItem 
                    icon={Clock} 
                    label="Session Duration (mins)" 
                    value={`${profile.teleconsultDuration} mins`} 
                    name="teleconsultDuration" 
                    editable={true}
                />
                <DetailItem 
                    icon={Calendar} 
                    label="Clinic Hours" 
                    value={profile.availability} 
                    name="availability" 
                    editable={true}
                />
            </MobileCard>

            <button className="w-full text-red-500 p-3 bg-white rounded-xl shadow-lg border border-gray-200 mt-6 font-semibold hover:bg-red-50 transition">Log Out</button>
        </div>
    );
};

// --- Main App Component ---

const DoctorMobileApp = () => {
    const [currentScreen, setCurrentScreen] = useState('Dashboard'); // Dashboard, Schedule, Telemed, Profile

    const renderScreen = () => {
        switch (currentScreen) {
            case 'Schedule':
                return <DoctorSchedule onNavigate={setCurrentScreen} />;
            case 'Telemed':
                return <DoctorTelemedQueue />;
            case 'Profile':
                return <DoctorProfile />;
            case 'Dashboard':
            default:
                return <DoctorDashboard onNavigate={setCurrentScreen} />;
        }
    };

    const navItems = [
        { name: 'Home', icon: Home, screen: 'Dashboard' },
        { name: 'Schedule', icon: Calendar, screen: 'Schedule' },
        { name: 'Telemed', icon: Video, screen: 'Telemed' },
        { name: 'Profile', icon: User, screen: 'Profile' },
    ];

    const getHeaderTitle = (screen) => {
        switch (screen) {
            case 'Schedule': return "Today's Schedule";
            case 'Telemed': return "Telemedicine Queue";
            case 'Profile': return "My Settings";
            default: return "Dashboard";
        }
    };

    return (
        <div className="flex justify-center min-h-screen bg-gray-100 font-[Inter] p-4 sm:p-6 md:p-8">
            <div className="w-full max-w-md bg-white rounded-3xl shadow-2xl h-[95vh] sm:h-[90vh] flex flex-col relative overflow-hidden">
                
                {/* Header (Dynamic and stylized) */}
                <header className="sticky top-0 bg-white p-4 shadow-sm border-b border-gray-100 flex items-center justify-between z-10">
                    {currentScreen !== 'Dashboard' ? (
                        <button onClick={() => setCurrentScreen('Dashboard')} className="text-indigo-600 hover:text-indigo-800 transition">
                            <ArrowLeft size={24} />
                        </button>
                    ) : (
                        <span className="text-xl font-bold text-gray-800">VetCare Doctor</span>
                    )}
                    <span className="text-xl font-bold text-gray-800 flex-1 text-center">{getHeaderTitle(currentScreen)}</span>
                    <Bell size={20} className="text-gray-500 cursor-pointer hover:text-indigo-600 transition" />
                </header>

                {/* Content Area (Scrollable) */}
                <main className="flex-1 overflow-y-auto pb-20">
                    {renderScreen()}
                </main>

                {/* Bottom Navigation (Sleek, always visible) */}
                <nav className="absolute bottom-0 w-full bg-white border-t border-gray-200 shadow-2xl p-2 rounded-t-3xl">
                    <div className="flex justify-around">
                        {navItems.map(item => (
                            <button
                                key={item.screen}
                                onClick={() => setCurrentScreen(item.screen)}
                                className={`flex flex-col items-center p-2 transition-colors duration-200 ${
                                    currentScreen === item.screen
                                        ? 'text-indigo-600 font-bold'
                                        : 'text-gray-400 hover:text-indigo-400'
                                }`}
                            >
                                <item.icon size={24} />
                                <span className="text-xs mt-1 font-medium">{item.name}</span>
                            </button>
                        ))}
                    </div>
                </nav>
            </div>
        </div>
    );
};

export default DoctorMobileApp;