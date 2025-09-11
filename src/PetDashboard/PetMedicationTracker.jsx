import React, { useState, useEffect } from "react";
import {
  PlusIcon,
  TrashIcon,
  PencilIcon,
  CheckCircleIcon,
  ClockIcon,
  XCircleIcon,
  BellAlertIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  XMarkIcon
} from "@heroicons/react/24/outline";

const PetMedicationTracker = () => {
  const [medications, setMedications] = useState([]);
  const [pets, setPets] = useState([
    { id: 1, name: 'Buddy', species: 'Dog', breed: 'Golden Retriever', age: 3 },
    { id: 2, name: 'Whiskers', species: 'Cat', breed: 'Siamese', age: 2 }
  ]);
  const [selectedPet, setSelectedPet] = useState(1);
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [activeFilter, setActiveFilter] = useState("all");
  const [expandedMedication, setExpandedMedication] = useState(null);

  const PillIcon = (props) => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" {...props}>
    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 4.5l-15 15m0-15l15 15" />
  </svg>
);

  const [form, setForm] = useState({
    name: "",
    dosage: "",
    frequency: "",
    startDate: "",
    endDate: "",
    notes: "",
    status: "active",
    reminder: false,
    reminderTime: "",
    veterinarian: "",
    purpose: ""
  });

  useEffect(() => {
    const savedMeds = localStorage.getItem("petMedications");
    const savedPets = localStorage.getItem("pets");
    
    if (savedMeds) setMedications(JSON.parse(savedMeds));
    if (savedPets) setPets(JSON.parse(savedPets));
  }, []);

  useEffect(() => {
    localStorage.setItem("petMedications", JSON.stringify(medications));
  }, [medications]);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleCheckboxChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.checked });
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    if (!form.name || !form.dosage || !form.startDate) return;

    const newEntry = {
      id: editingId || Date.now(),
      petId: selectedPet,
      ...form,
    };

    if (editingId) {
      setMedications(medications.map((m) => (m.id === editingId ? newEntry : m)));
      setEditingId(null);
    } else {
      setMedications([...medications, newEntry]);
    }

    resetForm();
  };

  const resetForm = () => {
    setForm({
      name: "",
      dosage: "",
      frequency: "",
      startDate: "",
      endDate: "",
      notes: "",
      status: "active",
      reminder: false,
      reminderTime: "",
      veterinarian: "",
      purpose: ""
    });
    setShowForm(false);
    setEditingId(null);
  };

  const handleEdit = (med) => {
    setForm(med);
    setEditingId(med.id);
    setShowForm(true);
  };

  const handleDelete = (id) => {
    if (window.confirm('Are you sure you want to delete this medication?')) {
      setMedications(medications.filter((m) => m.id !== id));
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case "active":
        return (
          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            <ClockIcon className="w-3 h-3 mr-1" /> Active
          </span>
        );
      case "completed":
        return (
          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
            <CheckCircleIcon className="w-3 h-3 mr-1" /> Completed
          </span>
        );
      case "missed":
        return (
          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
            <XCircleIcon className="w-3 h-3 mr-1" /> Missed
          </span>
        );
      default:
        return null;
    }
  };

  const getPetName = (petId) => {
    const pet = pets.find(p => p.id.toString() === petId.toString());
    return pet ? pet.name : 'Unknown Pet';
  };

  const getFilteredMedications = () => {
    const petMeds = medications.filter(m => m.petId === selectedPet);
    
    if (activeFilter === "all") return petMeds;
    return petMeds.filter(m => m.status === activeFilter);
  };

  const getStatusCounts = () => {
    const petMeds = medications.filter(m => m.petId === selectedPet);
    return {
      active: petMeds.filter(m => m.status === "active").length,
      completed: petMeds.filter(m => m.status === "completed").length,
      missed: petMeds.filter(m => m.status === "missed").length,
      total: petMeds.length
    };
  };

  const formatDate = (dateString) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleDateString([], { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric' 
    });
  };

  const isMedicationActive = (medication) => {
    if (medication.status !== "active") return false;
    
    const today = new Date();
    const startDate = new Date(medication.startDate);
    const endDate = medication.endDate ? new Date(medication.endDate) : null;
    
    return today >= startDate && (!endDate || today <= endDate);
  };

  const statusCounts = getStatusCounts();
  const filteredMedications = getFilteredMedications();

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-6xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <div className="flex items-center gap-3">
              <PillIcon className="w-8 h-8 text-indigo-600" />
              <h1 className="text-2xl font-bold text-gray-900">Medication Tracker</h1>
            </div>
            <p className="text-gray-600 mt-1">
              Manage your pet's medications, schedules, and reminders
            </p>
          </div>
          
          <div className="flex items-center gap-3">
            <select
              value={selectedPet}
              onChange={(e) => setSelectedPet(parseInt(e.target.value))}
              className="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            >
              {pets.map(pet => (
                <option key={pet.id} value={pet.id}>{pet.name}</option>
              ))}
            </select>
            <button
              onClick={() => setShowForm(true)}
              className="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-md"
            >
              <PlusIcon className="w-5 h-5 mr-2" />
              Add Medication
            </button>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div className="flex items-center">
              <div className="p-2 bg-indigo-100 rounded-lg">
                <PillIcon className="w-6 h-6 text-indigo-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Total Medications</p>
                <p className="text-xl font-bold text-gray-900">{statusCounts.total}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div className="flex items-center">
              <div className="p-2 bg-blue-100 rounded-lg">
                <ClockIcon className="w-6 h-6 text-blue-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Active</p>
                <p className="text-xl font-bold text-blue-600">{statusCounts.active}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div className="flex items-center">
              <div className="p-2 bg-green-100 rounded-lg">
                <CheckCircleIcon className="w-6 h-6 text-green-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Completed</p>
                <p className="text-xl font-bold text-green-600">{statusCounts.completed}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div className="flex items-center">
              <div className="p-2 bg-red-100 rounded-lg">
                <XCircleIcon className="w-6 h-6 text-red-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Missed</p>
                <p className="text-xl font-bold text-red-600">{statusCounts.missed}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div className="flex items-center gap-4">
            <span className="text-sm font-medium text-gray-700">Filter by:</span>
            <div className="flex gap-2">
              {["all", "active", "completed", "missed"].map((filter) => {
                const filterLabels = {
                  all: "All Medications",
                  active: "Active",
                  completed: "Completed",
                  missed: "Missed"
                };
                
                return (
                  <button
                    key={filter}
                    onClick={() => setActiveFilter(filter)}
                    className={`px-3 py-1 rounded-full text-sm font-medium ${
                      activeFilter === filter
                        ? 'bg-indigo-100 text-indigo-800'
                        : 'text-gray-600 hover:bg-gray-100'
                    }`}
                  >
                    {filterLabels[filter]}
                  </button>
                );
              })}
            </div>
          </div>
        </div>

        {/* Medications List */}
        <div className="space-y-4">
          {filteredMedications.length === 0 ? (
            <div className="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
              <PillIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900">No medications found</h3>
              <p className="mt-1 text-sm text-gray-500">
                {activeFilter === 'all' 
                  ? "Add your pet's first medication to get started." 
                  : `No ${activeFilter} medications found.`}
              </p>
              {activeFilter === 'all' && (
                <div className="mt-6">
                  <button
                    onClick={() => setShowForm(true)}
                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                  >
                    <PlusIcon className="w-5 h-5 mr-2" />
                    Add Medication
                  </button>
                </div>
              )}
            </div>
          ) : (
            filteredMedications.map((med) => {
              const isActive = isMedicationActive(med);
              const isExpanded = expandedMedication === med.id;
              
              return (
                <div key={med.id} className={`bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-all ${isActive ? 'border-l-4 border-blue-500' : ''}`}>
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        <h3 className="text-lg font-semibold text-gray-900">{med.name}</h3>
                        {getStatusBadge(med.status)}
                        {med.reminder && (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <BellAlertIcon className="w-3 h-3 mr-1" /> Reminder
                          </span>
                        )}
                      </div>
                      
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 mb-3">
                        <div>
                          <span className="font-medium">Dosage:</span> {med.dosage}
                        </div>
                        <div>
                          <span className="font-medium">Frequency:</span> {med.frequency || "Not specified"}
                        </div>
                        <div>
                          <span className="font-medium">Period:</span> {formatDate(med.startDate)} {med.endDate ? `- ${formatDate(med.endDate)}` : "(Ongoing)"}
                        </div>
                      </div>
                      
                      {isExpanded && (
                        <div className="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            {med.veterinarian && (
                              <div>
                                <span className="font-medium">Veterinarian:</span> {med.veterinarian}
                              </div>
                            )}
                            {med.purpose && (
                              <div>
                                <span className="font-medium">Purpose:</span> {med.purpose}
                              </div>
                            )}
                            {med.reminder && med.reminderTime && (
                              <div>
                                <span className="font-medium">Reminder Time:</span> {med.reminderTime}
                              </div>
                            )}
                            {med.notes && (
                              <div className="md:col-span-2">
                                <span className="font-medium">Notes:</span> {med.notes}
                              </div>
                            )}
                          </div>
                        </div>
                      )}
                    </div>
                    
                    <div className="flex space-x-2 ml-4">
                      <button
                        onClick={() => setExpandedMedication(isExpanded ? null : med.id)}
                        className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                      >
                        {isExpanded ? (
                          <ChevronUpIcon className="w-5 h-5" />
                        ) : (
                          <ChevronDownIcon className="w-5 h-5" />
                        )}
                      </button>
                      <button
                        onClick={() => handleEdit(med)}
                        className="p-2 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                      >
                        <PencilIcon className="w-5 h-5" />
                      </button>
                      <button
                        onClick={() => handleDelete(med.id)}
                        className="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      >
                        <TrashIcon className="w-5 h-5" />
                      </button>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Add/Edit Medication Modal */}
        {showForm && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
              <div className="p-6 border-b border-gray-200">
                <div className="flex justify-between items-center">
                  <h2 className="text-xl font-bold text-gray-900">
                    {editingId ? "Edit Medication" : "Add New Medication"}
                  </h2>
                  <button
                    onClick={resetForm}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <XMarkIcon className="w-6 h-6" />
                  </button>
                </div>
              </div>
              
              <form onSubmit={handleSubmit} className="p-6 space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Medicine Name *
                    </label>
                    <input
                      type="text"
                      name="name"
                      value={form.name}
                      onChange={handleChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="e.g. Antibiotic, Pain Relief"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Dosage *
                    </label>
                    <input
                      type="text"
                      name="dosage"
                      value={form.dosage}
                      onChange={handleChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="e.g. 1 tablet, 5ml"
                      required
                    />
                  </div>
                                   <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Frequency
                    </label>
                    <input
                      type="text"
                      name="frequency"
                      value={form.frequency}
                      onChange={handleChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="e.g. Twice a day"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Start Date *
                    </label>
                    <input
                      type="date"
                      name="startDate"
                      value={form.startDate}
                      onChange={handleChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      End Date
                    </label>
                    <input
                      type="date"
                      name="endDate"
                      value={form.endDate}
                      onChange={handleChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Veterinarian
                    </label>
                    <input
                      type="text"
                      name="veterinarian"
                      value={form.veterinarian}
                      onChange={handleChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="Doctor's Name"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Purpose
                    </label>
                    <input
                      type="text"
                      name="purpose"
                      value={form.purpose}
                      onChange={handleChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="Why this medicine is given?"
                    />
                  </div>
                </div>

                {/* Reminder Checkbox */}
                <div className="flex items-center">
                  <input
                    type="checkbox"
                    name="reminder"
                    checked={form.reminder}
                    onChange={handleCheckboxChange}
                    className="h-4 w-4 text-indigo-600 border-gray-300 rounded"
                  />
                  <label className="ml-2 block text-sm text-gray-700">
                    Set Reminder
                  </label>
                </div>

                {form.reminder && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Reminder Time
                    </label>
                    <input
                      type="time"
                      name="reminderTime"
                      value={form.reminderTime}
                      onChange={handleChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                  </div>
                )}

                {/* Notes */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Notes
                  </label>
                  <textarea
                    name="notes"
                    value={form.notes}
                    onChange={handleChange}
                    rows="3"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    placeholder="Additional instructions"
                  ></textarea>
                </div>

                {/* Status */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Status
                  </label>
                  <select
                    name="status"
                    value={form.status}
                    onChange={handleChange}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  >
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="missed">Missed</option>
                  </select>
                </div>

                {/* Buttons */}
                <div className="flex justify-end gap-3 pt-4 border-t border-gray-200">
                  <button
                    type="button"
                    onClick={resetForm}
                    className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                  >
                    {editingId ? "Update Medication" : "Add Medication"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default PetMedicationTracker;
