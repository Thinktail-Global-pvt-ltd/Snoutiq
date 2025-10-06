import React, { useState, useEffect, useContext } from "react";
import {
  EyeIcon,
  HeartIcon,
  TrashIcon,
  XMarkIcon,
} from "@heroicons/react/24/outline";
import axios from "axios";
import { AuthContext } from "../auth/AuthContext";
import toast from "react-hot-toast";

const AdminPetsDashboard = () => {
  const [vets, setVets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [selectedVet, setSelectedVet] = useState(null); // Modal state
  const rowsPerPage = 10;
  const { user } = useContext(AuthContext);

  useEffect(() => {
    const fetchVets = async () => {
      try {
        setLoading(true);
        const res = await fetch(
          "https://snoutiq.com/backend/api/users?email=admin@gmail.com"
        );
        const data = await res.json();
        setVets(data.data || []);
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };
    fetchVets();
  }, []);

  const filteredVets = vets.filter((vet) => {
    const q = searchQuery.toLowerCase();
    return (
      vet.name?.toLowerCase().includes(q) ||
      vet.email?.toLowerCase().includes(q) ||
      vet.phone?.toLowerCase().includes(q) ||
      vet.pet_name?.toLowerCase().includes(q)
    );
  });

  const indexOfLastVet = currentPage * rowsPerPage;
  const indexOfFirstVet = indexOfLastVet - rowsPerPage;
  const currentVets = filteredVets.slice(indexOfFirstVet, indexOfLastVet);
  const totalPages = Math.ceil(filteredVets.length / rowsPerPage);

  const handleView = (vet) => setSelectedVet(vet);
  const handleCloseModal = () => setSelectedVet(null);

  // const handleDelete = (id) =>
  //   window.confirm("Are you sure?") && alert(`🗑️ Deleted: ${id}`);

  const handleDelete = async (id) => {
    if (!window.confirm("Are you sure you want to delete this pet?")) return;

    try {
      const res = await axios.delete(
        `https://snoutiq.com/backend/api/petparents/${id}`
      );

      if (res.status === 200) {
        setVets((prev) => prev.filter((vet) => vet.id !== id));
        toast.success(`🗑️ Pet with ID ${id} deleted successfully.`);
      } else {
        toast.error("❌ Could not delete pet. Try again.");
      }
    } catch (error) {
      console.error("Error deleting pet:", error.response || error);
      toast.error("❌ Failed to delete pet. Please try again.");
    }
  };

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      <div className="max-w-7xl mx-auto">
        <h2 className="text-2xl font-bold mb-4">Pets Management</h2>

        {/* Search */}
        <div className="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
          <input
            type="text"
            placeholder="Search by name, email, phone, pet..."
            value={searchQuery}
            onChange={(e) => {
              setSearchQuery(e.target.value);
              setCurrentPage(1);
            }}
            className="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:outline-none"
          />
          <span className="text-gray-600 text-sm">
            Showing {indexOfFirstVet + 1}–
            {Math.min(indexOfLastVet, filteredVets.length)} of{" "}
            {filteredVets.length}
          </span>
        </div>

        {loading ? (
          <div className="flex justify-center items-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-orange-500"></div>
          </div>
        ) : filteredVets.length === 0 ? (
          <div className="text-center py-12 text-gray-500">No pets found</div>
        ) : (
          <>
            {/* Grid Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {currentVets.map((vet) => (
                <div
                  key={vet.id}
                  className="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-shadow"
                >
                  <div className="flex justify-between items-start mb-3">
                    <h3 className="text-lg font-semibold">{vet.name}</h3>
                    <span className="text-sm text-gray-500">ID: {vet.id}</span>
                  </div>

                  <div className="space-y-1 text-sm text-gray-700">
                    <div>Email: {vet.email}</div>
                    {vet.pet_name && <div>Pet: {vet.pet_name}</div>}
                  </div>

                  {/* Actions */}
                  <div className="mt-4 flex justify-end gap-3">
                    <button
                      onClick={() => handleView(vet)}
                      className="text-blue-600 hover:text-blue-800"
                    >
                      <EyeIcon className="h-5 w-5" />
                    </button>
                    <button
                      onClick={() => handleDelete(vet.id)}
                      className="text-red-600 hover:text-red-800"
                    >
                      <TrashIcon className="h-5 w-5" />
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* Pagination */}
            <div className="flex justify-between items-center mt-6">
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
                onClick={() =>
                  setCurrentPage((prev) => Math.min(prev + 1, totalPages))
                }
                disabled={currentPage === totalPages}
                className="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
              >
                Next
              </button>
            </div>
          </>
        )}

        {/* Modal for full vet data */}
        {selectedVet && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div className="bg-white rounded-lg max-w-2xl w-full p-6 relative shadow-lg">
              <button
                onClick={handleCloseModal}
                className="absolute top-3 right-3 text-gray-500 hover:text-gray-800"
              >
                <XMarkIcon className="h-6 w-6" />
              </button>

              <h3 className="text-xl font-bold mb-4">{selectedVet.name}</h3>

              <div className="space-y-2 text-sm text-gray-700">
                <div>Email: {selectedVet.email}</div>
                <div>Phone: {selectedVet.phone}</div>
                <div>Pet Name: {selectedVet.pet_name || "N/A"}</div>
                <div>Pet Age: {selectedVet.pet_age || "N/A"}</div>
                <div>Pet Gender: {selectedVet.pet_gender || "N/A"}</div>
                {selectedVet.breed && <div>Breed: {selectedVet.breed}</div>}
                {selectedVet.latitude && selectedVet.longitude && (
                  <div>
                    Location: {selectedVet.latitude}, {selectedVet.longitude}
                  </div>
                )}
                {selectedVet.pet_doc1 && (
                  <div>
                    <a
                      href={`https://snoutiq.com/${selectedVet.pet_doc1.replace(
                        "/var/www/html/project/backend/public/",
                        "backend/"
                      )}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-orange-600 hover:text-orange-800"
                    >
                      Document 1
                    </a>
                  </div>
                )}
                {selectedVet.pet_doc2 && (
                  <div>
                    <a
                      href={`https://snoutiq.com/${selectedVet.pet_doc2.replace(
                        "/var/www/html/project/backend/public/",
                        "backend/"
                      )}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-orange-600 hover:text-orange-800"
                    >
                      Document 2
                    </a>
                  </div>
                )}
                {selectedVet.summary && (
                  <div className="mt-2">{selectedVet.summary}</div>
                )}
                <div className="text-gray-500 text-xs mt-2">
                  Created:{" "}
                  {new Date(selectedVet.created_at).toLocaleDateString()} <br />
                  Updated:{" "}
                  {new Date(selectedVet.updated_at).toLocaleDateString()}
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminPetsDashboard;
