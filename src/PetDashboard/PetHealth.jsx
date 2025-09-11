import React, { useState, useEffect } from 'react';
import { 
  PlusIcon, 
  DocumentTextIcon, 
  CalendarIcon, 
  UserIcon, 
  HeartIcon,
  ClipboardDocumentListIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  DocumentArrowUpIcon,
  XMarkIcon
} from '@heroicons/react/24/outline';

const PetHealth = () => {
  const [records, setRecords] = useState([]);
  const [pets, setPets] = useState([
    { id: 1, name: 'Buddy', species: 'Dog', breed: 'Golden Retriever', age: 3 },
    { id: 2, name: 'Whiskers', species: 'Cat', breed: 'Siamese', age: 2 }
  ]);
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingRecord, setEditingRecord] = useState(null);
  const [selectedRecord, setSelectedRecord] = useState(null);
  const [activeTab, setActiveTab] = useState('all');

  const [formData, setFormData] = useState({
    petId: '',
    recordType: '',
    title: '',
    date: '',
    veterinarian: '',
    clinic: '',
    diagnosis: '',
    symptoms: '',
    treatment: '',
    medications: '',
    notes: '',
    followUpDate: '',
    attachments: []
  });

  const recordTypes = [
    'Checkup',
    'Vaccination',
    'Surgery',
    'Dental Care',
    'Emergency Visit',
    'Lab Results',
    'X-Ray',
    'Medication',
    'Behavior',
    'Other'
  ];

  useEffect(() => {
    // Load data from localStorage
    const savedRecords = localStorage.getItem('healthRecords');
    const savedPets = localStorage.getItem('pets');
    
    if (savedRecords) {
      setRecords(JSON.parse(savedRecords));
    }
    if (savedPets) {
      setPets(JSON.parse(savedPets));
    }
  }, []);

  const saveRecordsToStorage = (recordsData) => {
    localStorage.setItem('healthRecords', JSON.stringify(recordsData));
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleFileUpload = (e) => {
    const files = Array.from(e.target.files);
    const filePromises = files.map(file => {
      return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = () => {
          resolve({
            name: file.name,
            type: file.type,
            data: reader.result,
            size: file.size
          });
        };
        reader.readAsDataURL(file);
      });
    });

    Promise.all(filePromises).then(fileData => {
      setFormData(prev => ({
        ...prev,
        attachments: [...prev.attachments, ...fileData]
      }));
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!formData.petId || !formData.title || !formData.date) {
      alert('Please fill in required fields');
      return;
    }

    const recordData = {
      ...formData,
      id: editingRecord ? editingRecord.id : Date.now(),
      createdAt: editingRecord ? editingRecord.createdAt : new Date().toISOString()
    };

    let updatedRecords;
    if (editingRecord) {
      updatedRecords = records.map(record => record.id === editingRecord.id ? recordData : record);
    } else {
      updatedRecords = [...records, recordData];
    }

    setRecords(updatedRecords);
    saveRecordsToStorage(updatedRecords);
    resetForm();
  };

  const resetForm = () => {
    setFormData({
      petId: '',
      recordType: '',
      title: '',
      date: '',
      veterinarian: '',
      clinic: '',
      diagnosis: '',
      symptoms: '',
      treatment: '',
      medications: '',
      notes: '',
      followUpDate: '',
      attachments: []
    });
    setShowAddForm(false);
    setEditingRecord(null);
  };

  const handleEdit = (record) => {
    setFormData(record);
    setEditingRecord(record);
    setShowAddForm(true);
  };

  const handleDelete = (recordId) => {
    if (window.confirm('Are you sure you want to delete this health record?')) {
      const updatedRecords = records.filter(record => record.id !== recordId);
      setRecords(updatedRecords);
      saveRecordsToStorage(updatedRecords);
    }
  };

  const getPetName = (petId) => {
    const pet = pets.find(p => p.id.toString() === petId.toString());
    return pet ? pet.name : 'Unknown Pet';
  };

  const getFilteredRecords = () => {
    if (activeTab === 'all') return records;
    return records.filter(record => record.recordType && record.recordType.toLowerCase() === activeTab);
  };

  const getRecordTypeColor = (type) => {
    const colors = {
      'Checkup': 'bg-blue-100 text-blue-800',
      'Vaccination': 'bg-green-100 text-green-800',
      'Surgery': 'bg-red-100 text-red-800',
      'Emergency Visit': 'bg-red-100 text-red-800',
      'Lab Results': 'bg-purple-100 text-purple-800',
      'Medication': 'bg-yellow-100 text-yellow-800',
      'Dental Care': 'bg-indigo-100 text-indigo-800',
      'X-Ray': 'bg-gray-100 text-gray-800',
      'Behavior': 'bg-pink-100 text-pink-800',
      'Other': 'bg-gray-100 text-gray-800'
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
  };

  const filteredRecords = getFilteredRecords().sort((a, b) => new Date(b.date) - new Date(a.date));

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Health Records</h1>
            <p className="text-gray-600 mt-1">Track your pet's medical history and health information</p>
          </div>
          <button
            onClick={() => setShowAddForm(true)}
            className="flex items-center px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-md"
          >
            <PlusIcon className="w-5 h-5 mr-2" />
            Add Health Record
          </button>
        </div>

        {/* Filter Tabs */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div className="flex flex-wrap">
            {['all', 'checkup', 'vaccination', 'surgery', 'emergency', 'lab'].map((tab) => {
              const tabLabels = {
                'all': 'All Records',
                'checkup': 'Checkups',
                'vaccination': 'Vaccinations',
                'surgery': 'Surgeries',
                'emergency': 'Emergency Visits',
                'lab': 'Lab Results'
              };
              
              return (
                <button
                  key={tab}
                  onClick={() => setActiveTab(tab)}
                  className={`px-4 py-3 text-sm font-medium ${
                    activeTab === tab
                      ? 'text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50'
                      : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                  }`}
                >
                  {tabLabels[tab]}
                </button>
              );
            })}
          </div>
        </div>

        {/* Records List */}
        <div className="space-y-4">
          {filteredRecords.map((record) => (
            <div key={record.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-3">
                    <h3 className="text-lg font-semibold text-gray-900">{record.title}</h3>
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${getRecordTypeColor(record.recordType)}`}>
                      {record.recordType}
                    </span>
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 mb-4">
                    <div className="flex items-center">
                      <HeartIcon className="w-4 h-4 mr-2 text-indigo-500" />
                      <span>{getPetName(record.petId)}</span>
                    </div>
                    <div className="flex items-center">
                      <CalendarIcon className="w-4 h-4 mr-2 text-indigo-500" />
                      <span>{new Date(record.date).toLocaleDateString()}</span>
                    </div>
                    {record.veterinarian && (
                      <div className="flex items-center">
                        <UserIcon className="w-4 h-4 mr-2 text-indigo-500" />
                        <span>Dr. {record.veterinarian}</span>
                      </div>
                    )}
                  </div>

                  {record.diagnosis && (
                    <p className="text-sm text-gray-700 mb-2">
                      <span className="font-medium">Diagnosis:</span> {record.diagnosis}
                    </p>
                  )}

                  {record.symptoms && (
                    <p className="text-sm text-gray-700 mb-2">
                      <span className="font-medium">Symptoms:</span> {record.symptoms}
                    </p>
                  )}

                  {record.attachments && record.attachments.length > 0 && (
                    <div className="flex items-center text-sm text-indigo-600 mt-2">
                      <DocumentTextIcon className="w-4 h-4 mr-1" />
                      <span>{record.attachments.length} attachment(s)</span>
                    </div>
                  )}

                  {record.followUpDate && (
                    <div className="mt-2 p-2 bg-yellow-50 border-l-4 border-yellow-400 text-sm">
                      <p className="text-yellow-800">
                        Follow-up scheduled: {new Date(record.followUpDate).toLocaleDateString()}
                      </p>
                    </div>
                  )}
                </div>

                <div className="flex space-x-2 ml-4">
                  <button
                    onClick={() => setSelectedRecord(record)}
                    className="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                  >
                    <EyeIcon className="w-5 h-5" />
                  </button>
                  <button
                    onClick={() => handleEdit(record)}
                    className="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  >
                    <PencilIcon className="w-5 h-5" />
                  </button>
                  <button
                    onClick={() => handleDelete(record.id)}
                    className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  >
                    <TrashIcon className="w-5 h-5" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Add/Edit Record Form Modal */}
        {showAddForm && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
              <div className="p-6 border-b border-gray-200">
                <div className="flex justify-between items-center">
                  <h2 className="text-xl font-bold text-gray-900">
                    {editingRecord ? 'Edit Health Record' : 'Add New Health Record'}
                  </h2>
                  <button
                    onClick={resetForm}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <XMarkIcon className="w-6 h-6" />
                  </button>
                </div>
              </div>
              
              <form onSubmit={handleSubmit} className="p-6 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Pet *</label>
                    <select
                      name="petId"
                      value={formData.petId}
                      onChange={handleInputChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      required
                    >
                      <option value="">Select Pet</option>
                      {pets.map(pet => (
                        <option key={pet.id} value={pet.id}>{pet.name}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Record Type</label>
                    <select
                      name="recordType"
                      value={formData.recordType}
                      onChange={handleInputChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                      <option value="">Select Type</option>
                      {recordTypes.map(type => (
                        <option key={type} value={type}>{type}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <input
                      type="text"
                      name="title"
                      value={formData.title}
                      onChange={handleInputChange}
                      placeholder="e.g., Annual Checkup, Vaccination Update"
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                    <input
                      type="date"
                      name="date"
                      value={formData.date}
                      onChange={handleInputChange}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Veterinarian</label>
                    <input
                      type="text"
                      name="veterinarian"
                      value={formData.veterinarian}
                      onChange={handleInputChange}
                      placeholder="Dr. Smith"
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Clinic/Hospital</label>
                    <input
                      type="text"
                      name="clinic"
                      value={formData.clinic}
                      onChange={handleInputChange}
                      placeholder="City Veterinary Hospital"
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                  </div>
                {/* </div> */}

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Diagnosis</label>
                  <input
                    type="text"
                    name="diagnosis"
                    value={formData.diagnosis}
                    onChange={handleInputChange}
                    placeholder="Primary diagnosis or condition"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Symptoms</label>
                  <textarea
                    name="symptoms"
                    value={formData.symptoms}
                    onChange={handleInputChange}
                    rows={3}
                    placeholder="Observed symptoms or behaviors"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Treatment</label>
                  <textarea
                    name="treatment"
                    value={formData.treatment}
                    onChange={handleInputChange}
                    rows={3}
                    placeholder="Treatment provided or recommended"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Medications</label>
                  <textarea
                    name="medications"
                    value={formData.medications}
                    onChange={handleInputChange}
                    rows={2}
                    placeholder="Prescribed medications, dosages, and instructions"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Follow-up Date</label>
                  <input
                    type="date"
                    name="followUpDate"
                    value={formData.followUpDate}
                    onChange={handleInputChange}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                  <textarea
                    name="notes"
                    value={formData.notes}
                    onChange={handleInputChange}
                    rows={3}
                    placeholder="Any additional information, observations, or instructions"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Attachments</label>
                  <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                    <div className="space-y-1 text-center">
                      {/* <Upload className="mx-auto h-12 w-12 text-gray-400" /> */}
                      <DocumentArrowUpIcon className="w-5 h-5 mr-2 text-gray-400" />

                      <div className="flex text-sm text-gray-600">
                        <label htmlFor="file-upload" className="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                          <span>Upload files</span>
                          <input
                            id="file-upload"
                            name="file-upload"
                            type="file"
                            multiple
                            accept="image/*,application/pdf,.doc,.docx"
                            onChange={handleFileUpload}
                            className="sr-only"
                          />
                        </label>
                        <p className="pl-1">or drag and drop</p>
                      </div>
                      <p className="text-xs text-gray-500">
                        PDF, DOC, DOCX, PNG, JPG up to 10MB each
                      </p>
                    </div>
                  </div>
                  {formData.attachments.length > 0 && (
                    <div className="mt-4 space-y-2">
                      {formData.attachments.map((file, index) => (
                        <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                          <span className="text-sm text-gray-700">{file.name}</span>
                          <button
                            onClick={() => {
                              const newAttachments = formData.attachments.filter((_, i) => i !== index);
                              setFormData(prev => ({ ...prev, attachments: newAttachments }));
                            }}
                            className="text-red-500 hover:text-red-700"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="flex justify-end space-x-3 pt-4">
                  <button
                    onClick={resetForm}
                    className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={handleSubmit}
                    className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    {editingRecord ? 'Update Record' : 'Add Record'}
                  </button>
                </div>
              </div>
         </form>
         </div>
         </div>
      )}

      {/* View Record Modal */}
      {selectedRecord && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-xl font-bold text-gray-900">{selectedRecord.title}</h2>
                <button
                  onClick={() => setSelectedRecord(null)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  ✕
                </button>
              </div>

              <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-4">
                    <div>
                      <h3 className="font-medium text-gray-900 mb-2">Basic Information</h3>
                      <div className="space-y-2 text-sm">
                        <p><span className="font-medium">Pet:</span> {getPetName(selectedRecord.petId)}</p>
                        <p><span className="font-medium">Type:</span> {selectedRecord.recordType}</p>
                        <p><span className="font-medium">Date:</span> {new Date(selectedRecord.date).toLocaleDateString()}</p>
                        {selectedRecord.veterinarian && (
                          <p><span className="font-medium">Veterinarian:</span> Dr. {selectedRecord.veterinarian}</p>
                        )}
                        {selectedRecord.clinic && (
                          <p><span className="font-medium">Clinic:</span> {selectedRecord.clinic}</p>
                        )}
                      </div>
                    </div>

                    {selectedRecord.followUpDate && (
                      <div className="p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                        <p className="text-sm text-yellow-800">
                          <span className="font-medium">Follow-up:</span> {new Date(selectedRecord.followUpDate).toLocaleDateString()}
                        </p>
                      </div>
                    )}
                  </div>

                  <div className="space-y-4">
                    {selectedRecord.diagnosis && (
                      <div>
                        <h3 className="font-medium text-gray-900 mb-2">Diagnosis</h3>
                        <p className="text-sm text-gray-700 bg-gray-50 p-3 rounded">{selectedRecord.diagnosis}</p>
                      </div>
                    )}

                    {selectedRecord.symptoms && (
                      <div>
                        <h3 className="font-medium text-gray-900 mb-2">Symptoms</h3>
                        <p className="text-sm text-gray-700 bg-gray-50 p-3 rounded">{selectedRecord.symptoms}</p>
                      </div>
                    )}
                  </div>
                </div>

                {selectedRecord.treatment && (
                  <div>
                    <h3 className="font-medium text-gray-900 mb-2">Treatment</h3>
                    <p className="text-sm text-gray-700 bg-gray-50 p-3 rounded">{selectedRecord.treatment}</p>
                  </div>
                )}

                {selectedRecord.medications && (
                  <div>
                    <h3 className="font-medium text-gray-900 mb-2">Medications</h3>
                    <p className="text-sm text-gray-700 bg-blue-50 p-3 rounded border-l-4 border-blue-400">{selectedRecord.medications}</p>
                  </div>
                )}

                {selectedRecord.notes && (
                  <div>
                    <h3 className="font-medium text-gray-900 mb-2">Additional Notes</h3>
                    <p className="text-sm text-gray-700 bg-gray-50 p-3 rounded">{selectedRecord.notes}</p>
                  </div>
                )}

                {selectedRecord.attachments && selectedRecord.attachments.length > 0 && (
                  <div>
                    <h3 className="font-medium text-gray-900 mb-2">Attachments ({selectedRecord.attachments.length})</h3>
                    <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                      {selectedRecord.attachments.map((file, index) => (
                        <div key={index} className="border border-gray-200 rounded-lg p-3 hover:bg-gray-50">
                          <div className="flex items-center space-x-2">
                            <FileText className="w-5 h-5 text-gray-400" />
                            <span className="text-sm text-gray-700 truncate">{file.name}</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                <div className="flex space-x-3 pt-4 border-t">
                  <button
                    onClick={() => {
                      handleEdit(selectedRecord);
                      setSelectedRecord(null);
                    }}
                    className="flex-1 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    Edit Record
                  </button>
                  <button
                    onClick={() => setSelectedRecord(null)}
                    className="flex-1 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Empty State */}
{filteredRecords.length === 0 && (
  <div className="text-center py-12">
    <ClipboardDocumentListIcon className="mx-auto h-12 w-12 text-gray-400" />
    <h3 className="mt-2 text-sm font-medium text-gray-900">No health records found</h3>
    <p className="mt-1 text-sm text-gray-500">
      {activeTab === 'all' 
        ? "Start tracking your pet's health by adding the first record." 
        : `No ${activeTab} records found. Try a different filter.`}
    </p>
    {activeTab === 'all' && (
      <div className="mt-6">
        <button
          onClick={() => setShowAddForm(true)}
          className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
        >
          <PlusIcon className="w-5 h-5 mr-2" />
          Add Health Record
        </button>
      </div>
    )}
  </div>
)}

      
      </div>
    </div>
  );
};
export default PetHealth;