import React, { useState, useEffect } from "react";
import { EyeIcon, HeartIcon, TrashIcon, XMarkIcon } from "@heroicons/react/24/outline";
import logo from '../assets/images/dark bg.webp'

const AdminVetsDashboard = () => {
  const [vets, setVets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [selectedVet, setSelectedVet] = useState(null); // Modal state
  const rowsPerPage = 10;

  useEffect(() => {
    const fetchVets = async () => {
      try {
        setLoading(true);
        const response = await fetch(
          "https://snoutiq.com/backend/api/vets?email=admin@gmail.com"
        );
        const data = await response.json();
        setVets(data || []);
      } catch (err) {
        console.error("Error fetching vets:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchVets();
  }, []);

  const filteredVets = vets.filter((vet) => {
    const q = searchQuery.toLowerCase();
    return (
      vet.email?.toLowerCase().includes(q) ||
      vet.mobile?.toLowerCase().includes(q) ||
      vet.city?.toLowerCase().includes(q) ||
      vet.license_no?.toLowerCase().includes(q) ||
      vet.business_status?.toLowerCase().includes(q)
    );
  });

  const indexOfLastVet = currentPage * rowsPerPage;
  const indexOfFirstVet = indexOfLastVet - rowsPerPage;
  const currentVets = filteredVets.slice(indexOfFirstVet, indexOfLastVet);
  const totalPages = Math.ceil(filteredVets.length / rowsPerPage);

  const handleView = (vet) => setSelectedVet(vet);
  const handleCloseModal = () => setSelectedVet(null);
  const handleDelete = (id) =>
    window.confirm("Are you sure?") && alert(`🗑️ Deleted vet: ${id}`);

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      <div className="max-w-7xl mx-auto bg-white shadow-md rounded-lg p-4">
        <h2 className="text-2xl font-bold mb-4">Veterinarians Management</h2>

        {/* Search */}
        <div className="mb-4 flex justify-between items-center">
          <input
            type="text"
            placeholder="Search by email, city, status..."
            value={searchQuery}
            onChange={(e) => {
              setSearchQuery(e.target.value);
              setCurrentPage(1);
            }}
            className="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />
          <span className="text-gray-600 text-sm ml-4">
            Showing {indexOfFirstVet + 1}–
            {Math.min(indexOfLastVet, filteredVets.length)} of {filteredVets.length}
          </span>
        </div>

        {/* Table */}
        {loading ? (
          <div className="flex justify-center items-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
              <thead className="bg-gray-100">
                <tr>
                  <th className="px-6 py-3 text-left font-semibold">Email</th>
                  <th className="px-6 py-3 text-left font-semibold">Mobile</th>
                  <th className="px-6 py-3 text-left font-semibold">City</th>
                  <th className="px-6 py-3 text-left font-semibold">License</th>
                  <th className="px-6 py-3 text-left font-semibold">Rating</th>
                  <th className="px-6 py-3 text-left font-semibold">Status</th>
                  <th className="px-6 py-3 text-center font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {currentVets.map((vet) => (
                  <tr key={vet.id} className="hover:bg-gray-50">
                    <td className="px-6 py-3">{vet.email}</td>
                    <td className="px-6 py-3">{vet.mobile}</td>
                    <td className="px-6 py-3">{vet.city}</td>
                    <td className="px-6 py-3">{vet.license_no || <span className="text-red-500">Missing</span>}</td>
                    <td className="px-6 py-3">{vet.rating || "-"}</td>
                    <td className="px-6 py-3">
                      <span
                        className={`px-2 py-1 rounded-full text-xs ${
                          vet.business_status === "OPERATIONAL"
                            ? "bg-green-100 text-green-700"
                            : "bg-red-100 text-red-600"
                        }`}
                      >
                        {vet.business_status || "N/A"}
                      </span>
                    </td>
                    <td className="px-6 py-3 flex justify-center gap-3">
                      <button onClick={() => handleView(vet)} className="text-blue-600 hover:text-blue-800">
                        <EyeIcon className="h-5 w-5" />
                      </button>
                      <button onClick={() => handleDelete(vet.id)} className="text-red-600 hover:text-red-800">
                        <TrashIcon className="h-5 w-5" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            {/* Pagination */}
            <div className="flex justify-between items-center mt-4">
              <button
                onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
                disabled={currentPage === 1}
                className="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
              >
                Prev
              </button>
              <span className="text-sm">
                Page {currentPage} of {totalPages}
              </span>
              <button
                onClick={() => setCurrentPage((prev) => Math.min(prev + 1, totalPages))}
                disabled={currentPage === totalPages}
                className="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
              >
                Next
              </button>
            </div>
          </div>
        )}

        {/* Modal */}
        {selectedVet && (
          <div className="fixed inset-0 z-50 flex items-center justify-center">
            <div>
            <img src={logo} alt="logo" className="h-6"/>
            </div>
            {/* Overlay */}
            <div
              className="absolute inset-0 bg-black bg-opacity-50"
              onClick={handleCloseModal}
            ></div>

            {/* Modal Box */}
            <div className="bg-white rounded-lg shadow-2xl max-w-3xl w-full z-10 overflow-y-auto max-h-[90vh] p-6 relative">
              <button
                onClick={handleCloseModal}
                className="absolute top-4 right-4 text-gray-600 hover:text-gray-800"
              >
                <XMarkIcon className="h-6 w-6" />
              </button>

              <h3 className="text-2xl font-bold mb-4">{selectedVet.email}</h3>
              <div className="grid grid-cols-1 gap-2 text-sm text-gray-700">
                <div><strong>Mobile:</strong> {selectedVet.mobile}</div>
                <div><strong>City:</strong> {selectedVet.city}</div>
                <div><strong>Address:</strong> {selectedVet.address}</div>
                <div><strong>License:</strong> {selectedVet.license_no || "N/A"}</div>
                <div><strong>Business Status:</strong> {selectedVet.business_status || "N/A"}</div>
                <div><strong>Chat Price:</strong> {selectedVet.chat_price || "-"}</div>
                <div><strong>Rating:</strong> {selectedVet.rating || "-"} ({selectedVet.user_ratings_total || 0} ratings)</div>
                <div><strong>Coordinates:</strong> {selectedVet.coordinates || `${selectedVet.lat},${selectedVet.lng}`}</div>
                <div><strong>Bio:</strong> {selectedVet.bio || "N/A"}</div>
              </div>

              {selectedVet.photos && (
  <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-4">
    {JSON.parse(selectedVet.photos).map((photo, idx) => (
      <img
        key={idx}
        src={photo.photo_reference}
        alt={`vet-photo-${idx}`}
        className="w-full h-48 object-cover rounded-lg shadow-md"
      />
    ))}
  </div>
)}


              <div className="text-gray-500 text-xs mt-4 border-t pt-2">
                Created: {new Date(selectedVet.created_at).toLocaleString()}
                <br />
                Updated: {new Date(selectedVet.updated_at).toLocaleString()}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminVetsDashboard;
