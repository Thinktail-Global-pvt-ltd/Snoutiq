import React, { useState, useEffect } from 'react';
import { 
  Plus, 
  Calendar, 
  Shield, 
  AlertCircle, 
  CheckCircle, 
  Clock, 
  Bell,
  Syringe,
  Heart,
  Edit,
  Trash2,
  Download,
  Filter,
  X,
  Search,
  ChevronDown,
  ChevronUp,
  Dog,
  Cat
} from 'lucide-react';

const VaccinationTracker = () => {
  const [pets, setPets] = useState([
    { id: 1, name: 'Buddy', species: 'Dog', breed: 'Golden Retriever', age: 3 },
    { id: 2, name: 'Whiskers', species: 'Cat', breed: 'Siamese', age: 2 }
  ]);
  const [vaccinations, setVaccinations] = useState([
    {
      id: 1,
      petId: '1',
      vaccineName: 'Rabies',
      vaccineType: 'Core',
      dateGiven: '2023-05-15',
      nextDueDate: '2024-05-15',
      veterinarian: 'Dr. Smith',
      clinic: 'Animal Care Clinic',
      batchNumber: 'RB12345',
      notes: 'No adverse reactions',
      reminderDays: 30,
      createdAt: '2023-05-15T10:30:00Z'
    },
    {
      id: 2,
      petId: '1',
      vaccineName: 'DHPP',
      vaccineType: 'Core',
      dateGiven: '2023-06-10',
      nextDueDate: '2024-06-10',
      veterinarian: 'Dr. Johnson',
      clinic: 'Pet Wellness Center',
      batchNumber: 'DH9876',
      notes: 'Booster shot',
      reminderDays: 30,
      createdAt: '2023-06-10T14:20:00Z'
    },
    {
      id: 3,
      petId: '2',
      vaccineName: 'FVRCP',
      vaccineType: 'Core',
      dateGiven: '2023-07-20',
      nextDueDate: '2024-07-20',
      veterinarian: 'Dr. Williams',
      clinic: 'Feline Friends Clinic',
      batchNumber: 'FV5567',
      notes: 'Annual vaccination',
      reminderDays: 30,
      createdAt: '2023-07-20T09:15:00Z'
    }
  ]);
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingVaccination, setEditingVaccination] = useState(null);
  const [selectedPet, setSelectedPet] = useState('all');
  const [viewMode, setViewMode] = useState('upcoming');
  const [searchQuery, setSearchQuery] = useState('');
  const [sortConfig, setSortConfig] = useState({ key: 'nextDueDate', direction: 'asc' });
  const [expandedVaccination, setExpandedVaccination] = useState(null);

  const [formData, setFormData] = useState({
    petId: '',
    vaccineName: '',
    vaccineType: '',
    dateGiven: '',
    nextDueDate: '',
    veterinarian: '',
    clinic: '',
    batchNumber: '',
    notes: '',
    reminderDays: 7
  });

  const commonVaccines = [
    { name: 'DHPP (Distemper, Hepatitis, Parvovirus, Parainfluenza)', type: 'Core', frequency: 'Annual' },
    { name: 'Rabies', type: 'Core', frequency: '1-3 years' },
    { name: 'Bordetella (Kennel Cough)', type: 'Non-core', frequency: '6-12 months' },
    { name: 'Lyme Disease', type: 'Non-core', frequency: 'Annual' },
    { name: 'Canine Influenza', type: 'Non-core', frequency: 'Annual' },
    { name: 'FVRCP (Feline Viral Rhinotracheitis, Calicivirus, Panleukopenia)', type: 'Core', frequency: 'Annual' },
    { name: 'FeLV (Feline Leukemia)', type: 'Non-core', frequency: 'Annual' }
  ];

  useEffect(() => {
    const savedPets = localStorage.getItem('pets');
    const savedVaccinations = localStorage.getItem('vaccinations');
    
    if (savedPets) {
      setPets(JSON.parse(savedPets));
    }
    if (savedVaccinations) {
      setVaccinations(JSON.parse(savedVaccinations));
    }
  }, []);

  const saveVaccinationsToStorage = (vaccinationData) => {
    localStorage.setItem('vaccinations', JSON.stringify(vaccinationData));
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!formData.petId || !formData.vaccineName || !formData.dateGiven) {
      alert('Please fill in required fields');
      return;
    }

    const vaccinationData = {
      ...formData,
      id: editingVaccination ? editingVaccination.id : Date.now(),
      createdAt: editingVaccination ? editingVaccination.createdAt : new Date().toISOString()
    };

    let updatedVaccinations;
    if (editingVaccination) {
      updatedVaccinations = vaccinations.map(vacc => 
        vacc.id === editingVaccination.id ? vaccinationData : vacc
      );
    } else {
      updatedVaccinations = [...vaccinations, vaccinationData];
    }

    setVaccinations(updatedVaccinations);
    saveVaccinationsToStorage(updatedVaccinations);
    resetForm();
  };

  const resetForm = () => {
    setFormData({
      petId: '',
      vaccineName: '',
      vaccineType: '',
      dateGiven: '',
      nextDueDate: '',
      veterinarian: '',
      clinic: '',
      batchNumber: '',
      notes: '',
      reminderDays: 7
    });
    setShowAddForm(false);
    setEditingVaccination(null);
  };

  const handleEdit = (vaccination) => {
    setFormData(vaccination);
    setEditingVaccination(vaccination);
    setShowAddForm(true);
  };

  const handleDelete = (vaccinationId) => {
    if (window.confirm('Are you sure you want to delete this vaccination record?')) {
      const updatedVaccinations = vaccinations.filter(vacc => vacc.id !== vaccinationId);
      setVaccinations(updatedVaccinations);
      saveVaccinationsToStorage(updatedVaccinations);
    }
  };

  const getPetName = (petId) => {
    const pet = pets.find(p => p.id.toString() === petId.toString());
    return pet ? pet.name : 'Unknown Pet';
  };

  const getPetSpeciesIcon = (petId) => {
    const pet = pets.find(p => p.id.toString() === petId.toString());
    if (!pet) return <Shield className="w-4 h-4" />;
    return pet.species === 'Dog' ? <Dog className="w-4 h-4" /> : <Cat className="w-4 h-4" />;
  };

  const getVaccinationStatus = (vaccination) => {
    const today = new Date();
    const nextDue = new Date(vaccination.nextDueDate);
    const daysDiff = Math.ceil((nextDue - today) / (1000 * 60 * 60 * 24));

    if (daysDiff < 0) {
      return { status: 'overdue', days: Math.abs(daysDiff), color: 'text-red-600', bgColor: 'bg-red-100' };
    } else if (daysDiff <= 30) {
      return { status: 'due-soon', days: daysDiff, color: 'text-yellow-600', bgColor: 'bg-yellow-100' };
    } else {
      return { status: 'current', days: daysDiff, color: 'text-green-600', bgColor: 'bg-green-100' };
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'overdue':
        return <AlertCircle className="w-5 h-5 text-red-500" />;
      case 'due-soon':
        return <Clock className="w-5 h-5 text-yellow-500" />;
      case 'current':
        return <CheckCircle className="w-5 h-5 text-green-500" />;
      default:
        return <Shield className="w-5 h-5 text-gray-500" />;
    }
  };

  const handleSort = (key) => {
    let direction = 'asc';
    if (sortConfig.key === key && sortConfig.direction === 'asc') {
      direction = 'desc';
    }
    setSortConfig({ key, direction });
  };

  const getFilteredVaccinations = () => {
    let filtered = vaccinations;

    // Filter by pet
    if (selectedPet !== 'all') {
      filtered = filtered.filter(vacc => vacc.petId.toString() === selectedPet);
    }

    // Filter by search query
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(vacc => 
        vacc.vaccineName.toLowerCase().includes(query) ||
        getPetName(vacc.petId).toLowerCase().includes(query) ||
        (vacc.veterinarian && vacc.veterinarian.toLowerCase().includes(query)) ||
        (vacc.clinic && vacc.clinic.toLowerCase().includes(query))
      );
    }

    // Filter by view mode
    if (viewMode !== 'all') {
      filtered = filtered.filter(vacc => {
        const status = getVaccinationStatus(vacc);
        switch (viewMode) {
          case 'upcoming':
            return status.status === 'due-soon' || status.status === 'current';
          case 'overdue':
            return status.status === 'overdue';
          case 'completed':
            return vacc.dateGiven && new Date(vacc.dateGiven) <= new Date();
          default:
            return true;
        }
      });
    }

    // Sort the vaccinations
    filtered.sort((a, b) => {
      if (a[sortConfig.key] < b[sortConfig.key]) {
        return sortConfig.direction === 'asc' ? -1 : 1;
      }
      if (a[sortConfig.key] > b[sortConfig.key]) {
        return sortConfig.direction === 'asc' ? 1 : -1;
      }
      return 0;
    });

    return filtered;
  };

  const getUpcomingCount = () => {
    return vaccinations.filter(vacc => {
      const status = getVaccinationStatus(vacc);
      return status.status === 'due-soon';
    }).length;
  };

  const getOverdueCount = () => {
    return vaccinations.filter(vacc => {
      const status = getVaccinationStatus(vacc);
      return status.status === 'overdue';
    }).length;
  };

  const generateVaccinationSchedule = (petId) => {
    const pet = pets.find(p => p.id.toString() === petId);
    if (!pet) return;

    const scheduleDate = new Date();
    const scheduleItems = [];

    commonVaccines.forEach((vaccine, index) => {
      if ((pet.species === 'Dog' && !vaccine.name.includes('Feline')) ||
          (pet.species === 'Cat' && !vaccine.name.includes('Canine')) ||
          vaccine.name.includes('DHPP') ||
          vaccine.name.includes('FVRCP') ||
          vaccine.name.includes('Rabies')) {
        
        const nextDate = new Date(scheduleDate);
        nextDate.setMonth(nextDate.getMonth() + (index + 1) * 2); // Stagger vaccines

        scheduleItems.push({
          id: Date.now() + index,
          petId: petId,
          vaccineName: vaccine.name,
          vaccineType: vaccine.type,
          dateGiven: '',
          nextDueDate: nextDate.toISOString().split('T')[0],
          veterinarian: '',
          clinic: '',
          batchNumber: '',
          notes: `Recommended ${vaccine.frequency} - ${vaccine.type} vaccine`,
          reminderDays: 7,
          createdAt: new Date().toISOString()
        });
      }
    });

    const updatedVaccinations = [...vaccinations, ...scheduleItems];
    setVaccinations(updatedVaccinations);
    saveVaccinationsToStorage(updatedVaccinations);
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'Not administered';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
  };

  const filteredVaccinations = getFilteredVaccinations();

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Vaccination Tracker</h1>
            <p className="text-gray-600 mt-1">Manage your pets' vaccination records and schedules</p>
          </div>
          <button
            onClick={() => setShowAddForm(true)}
            className="flex items-center px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-md"
          >
            <Plus className="w-5 h-5 mr-2" />
            Add Vaccination
          </button>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div className="flex items-center">
              <div className="p-3 bg-green-100 rounded-xl">
                <Shield className="w-6 h-6 text-green-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Total Vaccinations</p>
                <p className="text-2xl font-bold text-gray-900">{vaccinations.length}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div className="flex items-center">
              <div className="p-3 bg-yellow-100 rounded-xl">
                <Clock className="w-6 h-6 text-yellow-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Due Soon</p>
                <p className="text-2xl font-bold text-yellow-600">{getUpcomingCount()}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div className="flex items-center">
              <div className="p-3 bg-red-100 rounded-xl">
                <AlertCircle className="w-6 h-6 text-red-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Overdue</p>
                <p className="text-2xl font-bold text-red-600">{getOverdueCount()}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Filters and Search */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex flex-col lg:flex-row gap-4">
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-1">Search Vaccinations</label>
              <div className="relative">
                <Search className="w-5 h-5 absolute left-3 top-3 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search by vaccine, pet, or vet..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full pl-10 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>
            </div>

            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-1">Filter by Pet</label>
              <select
                value={selectedPet}
                onChange={(e) => setSelectedPet(e.target.value)}
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="all">All Pets</option>
                {pets.map(pet => (
                  <option key={pet.id} value={pet.id}>{pet.name}</option>
                ))}
              </select>
            </div>

            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-1">View Mode</label>
              <div className="flex rounded-lg border border-gray-300 overflow-hidden">
                {[
                  { value: 'upcoming', label: 'Upcoming' },
                  { value: 'overdue', label: 'Overdue' },
                  { value: 'completed', label: 'Completed' },
                  { value: 'all', label: 'All' }
                ].map(mode => (
                  <button
                    key={mode.value}
                    onClick={() => setViewMode(mode.value)}
                    className={`flex-1 px-4 py-3 text-sm font-medium ${
                      viewMode === mode.value
                        ? 'bg-indigo-600 text-white'
                        : 'text-gray-700 hover:bg-gray-50'
                    }`}
                  >
                    {mode.label}
                  </button>
                ))}
              </div>
            </div>
          </div>

          <div className="mt-4 flex justify-end">
            <button
              onClick={() => {
                if (pets.length > 0) {
                  generateVaccinationSchedule(pets[0].id);
                  alert('Vaccination schedule generated for ' + pets[0].name);
                }
              }}
              className="px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center shadow-md"
            >
              <Calendar className="w-4 h-4 mr-2" />
              Generate Schedule
            </button>
          </div>
        </div>

        {/* Vaccinations List */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div className="p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900">Vaccination Records</h2>
            <p className="text-sm text-gray-600">{filteredVaccinations.length} records found</p>
          </div>
          
          {filteredVaccinations.length === 0 ? (
            <div className="p-12 text-center">
              <Shield className="w-12 h-12 text-gray-300 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">No vaccination records found</h3>
              <p className="text-gray-500 mb-4">Add a new vaccination record or adjust your filters</p>
              <button
                onClick={() => setShowAddForm(true)}
                className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
              >
                Add Your First Vaccination
              </button>
            </div>
          ) : (
            <div className="divide-y divide-gray-200">
              {filteredVaccinations.map((vaccination) => {
                const status = getVaccinationStatus(vaccination);
                const isExpanded = expandedVaccination === vaccination.id;
                
                return (
                  <div key={vaccination.id} className="p-6 hover:bg-gray-50 transition-colors">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                          <div className={`p-2 rounded-full ${status.bgColor}`}>
                            {getStatusIcon(status.status)}
                          </div>
                          <h3 className="text-lg font-semibold text-gray-900">{vaccination.vaccineName}</h3>
                          <span className={`px-2 py-1 rounded-full text-xs font-medium ${status.bgColor} ${status.color}`}>
                            {status.status === 'overdue' ? `Overdue by ${status.days} days` : 
                             status.status === 'due-soon' ? `Due in ${status.days} days` : 
                             `Due in ${status.days} days`}
                          </span>
                        </div>
                        
                        <div className="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                          <div className="flex items-center gap-1">
                            {getPetSpeciesIcon(vaccination.petId)}
                            <span>{getPetName(vaccination.petId)}</span>
                          </div>
                          <span>•</span>
                          <span>Type: {vaccination.vaccineType}</span>
                          <span>•</span>
                          <span>Given: {formatDate(vaccination.dateGiven)}</span>
                          <span>•</span>
                          <span>Next due: {formatDate(vaccination.nextDueDate)}</span>
                        </div>
                      </div>
                      
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => setExpandedVaccination(isExpanded ? null : vaccination.id)}
                          className="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                        >
                          {isExpanded ? <ChevronUp className="w-5 h-5" /> : <ChevronDown className="w-5 h-5" />}
                        </button>
                        <button
                          onClick={() => handleEdit(vaccination)}
                          className="p-2 text-blue-400 hover:text-blue-600 rounded-lg hover:bg-blue-50"
                        >
                          <Edit className="w-5 h-5" />
                        </button>
                        <button
                          onClick={() => handleDelete(vaccination.id)}
                          className="p-2 text-red-400 hover:text-red-600 rounded-lg hover:bg-red-50"
                        >
                          <Trash2 className="w-5 h-5" />
                        </button>
                      </div>
                    </div>
                    
                    {isExpanded && (
                      <div className="mt-4 pl-12 border-t pt-4 border-gray-200">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div>
                            <h4 className="text-sm font-medium text-gray-700 mb-1">Veterinarian</h4>
                            <p className="text-gray-900">{vaccination.veterinarian || 'Not specified'}</p>
                          </div>
                          <div>
                            <h4 className="text-sm font-medium text-gray-700 mb-1">Clinic</h4>
                            <p className="text-gray-900">{vaccination.clinic || 'Not specified'}</p>
                          </div>
                          <div>
                            <h4 className="text-sm font-medium text-gray-700 mb-1">Batch Number</h4>
                            <p className="text-gray-900">{vaccination.batchNumber || 'Not specified'}</p>
                          </div>
                          <div>
                            <h4 className="text-sm font-medium text-gray-700 mb-1">Reminder</h4>
                            <p className="text-gray-900">{vaccination.reminderDays} days before due date</p>
                          </div>
                          {vaccination.notes && (
                            <div className="md:col-span-2">
                              <h4 className="text-sm font-medium text-gray-700 mb-1">Notes</h4>
                              <p className="text-gray-900">{vaccination.notes}</p>
                            </div>
                          )}
                        </div>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>

      {/* Add/Edit Vaccination Modal */}
      {showAddForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl shadow-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-200">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-semibold text-gray-900">
                  {editingVaccination ? 'Edit Vaccination' : 'Add New Vaccination'}
                </h2>
                <button
                  onClick={resetForm}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>
            </div>
            
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Pet</label>
                <select
                  name="petId"
                  value={formData.petId}
                  onChange={handleInputChange}
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  required
                >
                  <option value="">Select a pet</option>
                  {pets.map(pet => (
                    <option key={pet.id} value={pet.id}>{pet.name} ({pet.species})</option>
                  ))}
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Vaccine Name</label>
                <select
                  name="vaccineName"
                  value={formData.vaccineName}
                  onChange={handleInputChange}
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  required
                >
                  <option value="">Select a vaccine</option>
                  {commonVaccines.map(vaccine => (
                    <option key={vaccine.name} value={vaccine.name}>
                      {vaccine.name} ({vaccine.type}) - {vaccine.frequency}
                    </option>
                  ))}
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Vaccine Type</label>
                <select
                  name="vaccineType"
                  value={formData.vaccineType}
                  onChange={handleInputChange}
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                  <option value="">Select type</option>
                  <option value="Core">Core</option>
                  <option value="Non-core">Non-core</option>
                </select>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Date Given</label>
                  <input
                    type="date"
                    name="dateGiven"
                    value={formData.dateGiven}
                    onChange={handleInputChange}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    required
                  />
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Next Due Date</label>
                  <input
                    type="date"
                    name="nextDueDate"
                    value={formData.nextDueDate}
                    onChange={handleInputChange}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    required
                  />
                </div>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Veterinarian</label>
                  <input
                    type="text"
                    name="veterinarian"
                    value={formData.veterinarian}
                    onChange={handleInputChange}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Clinic</label>
                  <input
                    type="text"
                    name="clinic"
                    value={formData.clinic}
                    onChange={handleInputChange}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Batch Number</label>
                <input
                  type="text"
                  name="batchNumber"
                  value={formData.batchNumber}
                  onChange={handleInputChange}
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Reminder Days Before Due</label>
                <select
                  name="reminderDays"
                  value={formData.reminderDays}
                  onChange={handleInputChange}
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                  <option value="7">7 days</option>
                  <option value="14">14 days</option>
                  <option value="30">30 days</option>
                  <option value="60">60 days</option>
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea
                  name="notes"
                  value={formData.notes}
                  onChange={handleInputChange}
                  rows={3}
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>
              
              <div className="flex justify-end gap-3 pt-4">
                <button
                  type="button"
                  onClick={resetForm}
                  className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                >
                  {editingVaccination ? 'Update' : 'Add'} Vaccination
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default VaccinationTracker;