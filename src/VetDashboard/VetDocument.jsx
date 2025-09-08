import React, { useState, useEffect } from 'react';

const VetDocumentManager = () => {
  const [documents, setDocuments] = useState({
    aadhar: { 
      file: null, 
      status: 'pending', 
      number: '',
      frontImage: null,
      backImage: null,
      verified: false 
    },
    pan: { 
      file: null, 
      status: 'pending', 
      number: '',
      verified: false 
    },
    gst: { 
      file: null, 
      status: 'pending', 
      number: '',
      verified: false 
    },
    medicalLicense: { 
      file: null, 
      status: 'pending', 
      number: '',
      expiryDate: '',
      verified: false 
    },
    degreeCertificate: { 
      file: null, 
      status: 'pending', 
      degree: '',
      university: '',
      year: '',
      verified: false 
    },
    experienceCertificate: { 
      file: null, 
      status: 'pending', 
      years: '',
      hospital: '',
      verified: false 
    }
  });

  const [activeDocument, setActiveDocument] = useState(null);
  const [editMode, setEditMode] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  // Load saved documents from localStorage on component mount
  useEffect(() => {
    const savedDocuments = localStorage.getItem('vetDocuments');
    if (savedDocuments) {
      setDocuments(JSON.parse(savedDocuments));
    }
  }, []);

  // Save documents to localStorage whenever they change
  useEffect(() => {
    localStorage.setItem('vetDocuments', JSON.stringify(documents));
  }, [documents]);

  const handleFileUpload = (documentType, file, isFront = true) => {
    if (file) {
      setDocuments(prev => ({
        ...prev,
        [documentType]: {
          ...prev[documentType],
          [isFront ? 'file' : 'backImage']: file,
          status: 'uploaded',
          uploadedAt: new Date().toLocaleDateString()
        }
      }));
    }
  };

  const handleInputChange = (documentType, field, value) => {
    setDocuments(prev => ({
      ...prev,
      [documentType]: {
        ...prev[documentType],
        [field]: value
      }
    }));
  };

  const handleRemoveFile = (documentType, isFront = true) => {
    setDocuments(prev => ({
      ...prev,
      [documentType]: {
        ...prev[documentType],
        [isFront ? 'file' : 'backImage']: null,
        status: prev[documentType].file ? 'pending' : 'pending'
      }
    }));
  };

  const handleSubmitDocument = (documentType) => {
    setIsLoading(true);
    setDocuments(prev => ({
      ...prev,
      [documentType]: {
        ...prev[documentType],
        status: 'verifying'
      }
    }));

    // Simulate verification process
    setTimeout(() => {
      setDocuments(prev => ({
        ...prev,
        [documentType]: {
          ...prev[documentType],
          status: 'verified',
          verified: true
        }
      }));
      setIsLoading(false);
      setEditMode(false);
      setActiveDocument(null);
    }, 2000);
  };

  const handleSubmitAll = () => {
    setIsLoading(true);
    // Simulate bulk verification process
    setTimeout(() => {
      setDocuments(prev => {
        const updated = { ...prev };
        Object.keys(updated).forEach(key => {
          if (updated[key].status === 'uploaded') {
            updated[key].status = 'verified';
            updated[key].verified = true;
          }
        });
        return updated;
      });
      setIsLoading(false);
    }, 3000);
  };

  const DocumentCard = ({ title, documentType, required = true, fields = [] }) => {
    const doc = documents[documentType];
    const isAadhar = documentType === 'aadhar';

    return (
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div className="flex items-start justify-between mb-4">
          <div className="flex-1">
            <h3 className="text-lg font-semibold text-gray-800 flex items-center">
              {title}
              {required && (
                <span className="text-red-500 ml-2 text-sm">*</span>
              )}
            </h3>
            {fields.map(field => doc[field] && (
              <p key={field} className="text-sm text-gray-600 mt-1">
                {field.charAt(0).toUpperCase() + field.slice(1)}: {doc[field]}
              </p>
            ))}
          </div>
          
          <div className="flex items-center space-x-2">
            <span className={`px-3 py-1 rounded-full text-xs font-medium ${
              doc.status === 'verified' ? 'bg-green-100 text-green-800' :
              doc.status === 'verifying' ? 'bg-yellow-100 text-yellow-800' :
              doc.status === 'uploaded' ? 'bg-blue-100 text-blue-800' :
              'bg-gray-100 text-gray-800'
            }`}>
              {doc.status.charAt(0).toUpperCase() + doc.status.slice(1)}
            </span>
            
            <button
              onClick={() => {
                setActiveDocument(documentType);
                setEditMode(true);
              }}
              className="p-2 text-gray-400 hover:text-blue-600 transition-colors"
              title="Edit document"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
          </div>
        </div>

        <div className="space-y-3">
          {doc.file ? (
            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div className="flex items-center">
                <svg className="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span className="text-sm text-gray-700">{doc.file.name}</span>
              </div>
              <span className="text-xs text-gray-500">{doc.uploadedAt}</span>
            </div>
          ) : (
            <label className="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 transition-colors p-4">
              <div className="flex flex-col items-center justify-center">
                <svg className="w-6 h-6 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p className="text-xs text-gray-500 text-center">Click to upload document</p>
              </div>
              <input
                type="file"
                className="hidden"
                accept=".pdf,.jpg,.jpeg,.png"
                onChange={(e) => handleFileUpload(documentType, e.target.files[0])}
              />
            </label>
          )}

          {isAadhar && doc.backImage && (
            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div className="flex items-center">
                <svg className="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span className="text-sm text-gray-700">Back Image</span>
              </div>
            </div>
          )}

          {doc.file && (
            <div className="flex space-x-2">
              <button
                onClick={() => handleRemoveFile(documentType)}
                className="text-xs text-red-600 hover:text-red-800 flex items-center px-3 py-1 border border-red-200 rounded-md"
              >
                <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Remove
              </button>
              
              {doc.status === 'uploaded' && !doc.verified && (
                <button
                  onClick={() => handleSubmitDocument(documentType)}
                  className="text-xs bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700 transition-colors"
                >
                  Submit for Verification
                </button>
              )}
            </div>
          )}
        </div>
      </div>
    );
  };

  const DocumentEditModal = () => {
    if (!activeDocument) return null;

    const doc = documents[activeDocument];
    const isAadhar = activeDocument === 'aadhar';

    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div className="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl font-semibold text-gray-800">
              Edit {activeDocument.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())}
            </h2>
            <button
              onClick={() => {
                setActiveDocument(null);
                setEditMode(false);
              }}
              className="text-gray-400 hover:text-gray-600"
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div className="space-y-4">
            {activeDocument === 'aadhar' && (
              <>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Aadhar Number</label>
                  <input
                    type="text"
                    value={doc.number}
                    onChange={(e) => handleInputChange(activeDocument, 'number', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter Aadhar number"
                  />
                </div>
                
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Front Image</label>
                    <FileUploadField
                      file={doc.file}
                      onUpload={(file) => handleFileUpload(activeDocument, file, true)}
                      onRemove={() => handleRemoveFile(activeDocument, true)}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Back Image</label>
                    <FileUploadField
                      file={doc.backImage}
                      onUpload={(file) => handleFileUpload(activeDocument, file, false)}
                      onRemove={() => handleRemoveFile(activeDocument, false)}
                    />
                  </div>
                </div>
              </>
            )}

            {activeDocument === 'pan' && (
              <>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">PAN Number</label>
                  <input
                    type="text"
                    value={doc.number}
                    onChange={(e) => handleInputChange(activeDocument, 'number', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter PAN number"
                  />
                </div>
                <FileUploadField
                  file={doc.file}
                  onUpload={(file) => handleFileUpload(activeDocument, file)}
                  onRemove={() => handleRemoveFile(activeDocument)}
                />
              </>
            )}

            {activeDocument === 'medicalLicense' && (
              <>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">License Number</label>
                  <input
                    type="text"
                    value={doc.number}
                    onChange={(e) => handleInputChange(activeDocument, 'number', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter license number"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                  <input
                    type="date"
                    value={doc.expiryDate}
                    onChange={(e) => handleInputChange(activeDocument, 'expiryDate', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <FileUploadField
                  file={doc.file}
                  onUpload={(file) => handleFileUpload(activeDocument, file)}
                  onRemove={() => handleRemoveFile(activeDocument)}
                />
              </>
            )}

            <div className="flex space-x-3 pt-4">
              <button
                onClick={() => {
                  setActiveDocument(null);
                  setEditMode(false);
                }}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={() => handleSubmitDocument(activeDocument)}
                disabled={!doc.file || isLoading}
                className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {isLoading ? 'Submitting...' : 'Save Changes'}
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  };

  const FileUploadField = ({ file, onUpload, onRemove }) => (
    <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
      {file ? (
        <div className="flex items-center justify-between">
          <span className="text-sm text-gray-700 truncate">{file.name}</span>
          <button
            onClick={onRemove}
            className="text-red-600 hover:text-red-800 ml-2"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      ) : (
        <label className="cursor-pointer">
          <svg className="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <p className="text-sm text-gray-500">Click to upload</p>
          <input
            type="file"
            className="hidden"
            accept=".pdf,.jpg,.jpeg,.png"
            onChange={(e) => onUpload(e.target.files[0])}
          />
        </label>
      )}
    </div>
  );

  const allUploaded = Object.values(documents).every(doc => doc.file !== null);
  const verifiedCount = Object.values(documents).filter(doc => doc.verified).length;
  const totalCount = Object.keys(documents).length;

  return (
    <div className="max-w-6xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Document Management</h1>
            <p className="text-gray-600">
              Manage your professional documents and complete your profile verification
            </p>
          </div>
          <div className="text-right">
            <div className="text-2xl font-bold text-blue-600">{verifiedCount}/{totalCount}</div>
            <div className="text-sm text-gray-500">Documents Verified</div>
          </div>
        </div>
      </div>

      {/* Progress Section */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-gray-800">Verification Progress</h2>
          <span className="text-sm text-gray-500">{verifiedCount} of {totalCount} verified</span>
        </div>
        
        <div className="w-full bg-gray-200 rounded-full h-3 mb-3">
          <div
            className="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300"
            style={{
              width: `${(verifiedCount / totalCount) * 100}%`
            }}
          ></div>
        </div>
        
        <div className="flex justify-between text-sm text-gray-600">
          <span>Pending</span>
          <span>Complete</span>
        </div>
      </div>

      {/* Documents Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <DocumentCard 
          title="Aadhar Card" 
          documentType="aadhar" 
          fields={['number']}
        />
        <DocumentCard 
          title="PAN Card" 
          documentType="pan" 
          fields={['number']}
        />
        <DocumentCard 
          title="GST Certificate" 
          documentType="gst" 
          fields={['number']}
        />
        <DocumentCard 
          title="Medical License" 
          documentType="medicalLicense" 
          fields={['number', 'expiryDate']}
        />
        <DocumentCard 
          title="Degree Certificate" 
          documentType="degreeCertificate" 
          fields={['degree', 'university', 'year']}
        />
        <DocumentCard 
          title="Experience Certificate" 
          documentType="experienceCertificate" 
          required={false}
          fields={['years', 'hospital']}
        />
      </div>

      {/* Bulk Action */}
      {!allUploaded && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
          <h3 className="font-semibold text-yellow-800 mb-2">Complete Your Profile</h3>
          <p className="text-yellow-700 text-sm mb-4">
            Upload all required documents to start accepting appointments
          </p>
          <button
            onClick={() => setEditMode(true)}
            className="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition-colors"
          >
            Upload Missing Documents
          </button>
        </div>
      )}

      {allUploaded && verifiedCount < totalCount && (
        <div className="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
          <h3 className="font-semibold text-blue-800 mb-2">Ready for Verification</h3>
          <p className="text-blue-700 text-sm mb-4">
            All documents are uploaded. Submit for final verification.
          </p>
          <button
            onClick={handleSubmitAll}
            disabled={isLoading}
            className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {isLoading ? 'Submitting...' : 'Submit All for Verification'}
          </button>
        </div>
      )}

      {verifiedCount === totalCount && (
        <div className="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 className="font-semibold text-green-800 mb-2">Profile Verified!</h3>
          <p className="text-green-700 text-sm">
            All your documents have been verified. You can now accept appointments.
          </p>
        </div>
      )}

      {/* Edit Modal */}
      {editMode && <DocumentEditModal />}
    </div>
  );
};

export default VetDocumentManager;