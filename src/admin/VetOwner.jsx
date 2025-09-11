import React, { useEffect, useState } from "react";
import axios from "axios";

const AdminVetsPage = () => {
  const [vets, setVets] = useState([]);
  const [filteredVets, setFilteredVets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [filters, setFilters] = useState({
    city: "all",
    hasLicense: "all",
    hasPhotos: "all",
  });

  useEffect(() => {
    fetchVets();
  }, []);

  useEffect(() => {
    filterVets();
  }, [vets, searchTerm, filters]);

  const fetchVets = async () => {
    try {
      setLoading(true);
      setError(null);
      const res = await axios.get(
        "https://snoutiq.com/backend/api/vets?email=adminsnoutiq@gmail.com"
      );

      // Handle different response formats
      let vetsData = [];

      if (Array.isArray(res.data)) {
        vetsData = res.data;
      } else if (res.data && typeof res.data === "object") {
        if (Array.isArray(res.data.vets)) {
          vetsData = res.data.vets;
        } else if (Array.isArray(res.data.data)) {
          vetsData = res.data.data;
        } else {
          vetsData = [res.data];
        }
      } else if (typeof res.data === "string") {
        try {
          const parsedData = JSON.parse(res.data);
          if (Array.isArray(parsedData)) {
            vetsData = parsedData;
          } else if (parsedData && typeof parsedData === "object") {
            if (Array.isArray(parsedData.vets)) {
              vetsData = parsedData.vets;
            } else if (Array.isArray(parsedData.data)) {
              vetsData = parsedData.data;
            } else {
              vetsData = [parsedData];
            }
          }
        } catch (parseError) {
          console.error("Error parsing response:", parseError);
          setError("Failed to parse response data");
        }
      }

      setVets(vetsData);
    } catch (error) {
      console.error("Error fetching vets:", error);
      setError("Failed to fetch veterinarians. Please try again later.");
    } finally {
      setLoading(false);
    }
  };

  const filterVets = () => {
    let result = vets;

    // Apply search filter
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      result = result.filter(
        (vet) =>
          (vet.email && vet.email.toLowerCase().includes(term)) ||
          (vet.city && vet.city.toLowerCase().includes(term)) ||
          (vet.address && vet.address.toLowerCase().includes(term)) ||
          (vet.license_no && vet.license_no.toLowerCase().includes(term))
      );
    }

    // Apply city filter
    if (filters.city !== "all") {
      result = result.filter(
        (vet) =>
          vet.city && vet.city.toLowerCase() === filters.city.toLowerCase()
      );
    }

    // Apply license filter
    if (filters.hasLicense !== "all") {
      result = result.filter((vet) =>
        filters.hasLicense === "yes" ? vet.license_no : !vet.license_no
      );
    }

    // Apply photos filter
    if (filters.hasPhotos !== "all") {
      result = result.filter((vet) => {
        if (filters.hasPhotos === "yes") {
          return vet.photos && vet.photos !== "null" && vet.photos !== null;
        } else {
          return !vet.photos || vet.photos === "null" || vet.photos === null;
        }
      });
    }

    setFilteredVets(result);
  };


  const handleSearchChange = (e) => {
    setSearchTerm(e.target.value);
  };

  const handleFilterChange = (filterType, value) => {
    setFilters((prev) => ({ ...prev, [filterType]: value }));
  };

  const clearFilters = () => {
    setSearchTerm("");
    setFilters({
      city: "all",
      hasLicense: "all",
      hasPhotos: "all",
    });
  };

  // Helper function to safely parse photos
  const parsePhotos = (photosString) => {
    if (!photosString || photosString === "null") return [];

    try {
      // Clean the string if it has escaped quotes
      let cleanedString = photosString;
      if (typeof cleanedString === "string") {
        cleanedString = cleanedString.replace(/\\"/g, '"');
        if (cleanedString.startsWith('"') && cleanedString.endsWith('"')) {
          cleanedString = cleanedString.slice(1, -1);
        }
      }

      const parsed = JSON.parse(cleanedString);
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      console.error("Error parsing photos:", error);
      return [];
    }
  };

  // Get unique cities for filter dropdown
  const cities = [...new Set(vets.map((vet) => vet.city).filter(Boolean))];

  if (loading) {
    return (
      <div className="flex justify-center items-center h-screen bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-orange-500 mx-auto"></div>
          <p className="text-gray-500 text-lg mt-4">Loading veterinarians...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex justify-center items-center h-screen bg-gray-50">
        <div className="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg max-w-md">
          <h2 className="font-bold text-lg mb-2">Error</h2>
          <p>{error}</p>
          <button
            onClick={fetchVets}
            className="mt-4 bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex justify-between items-center">
            <h1 className="text-2xl font-bold text-gray-800">
              Veterinarians Management
            </h1>
            <div className="text-sm text-gray-500">
              Total Vets: <span className="font-semibold">{vets.length}</span>
            </div>
          </div>
        </div>
      </header>

      {/* Search and Filters */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="md:col-span-2">
              <label
                htmlFor="search"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Search
              </label>
              <div className="relative rounded-md shadow-sm">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg
                    className="h-5 w-5 text-gray-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fillRule="evenodd"
                      d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                      clipRule="evenodd"
                    />
                  </svg>
                </div>
                <input
                  type="text"
                  id="search"
                  className="focus:ring-orange-500 focus:border-orange-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2"
                  placeholder="Search by email, city, address or license"
                  value={searchTerm}
                  onChange={handleSearchChange}
                />
              </div>
            </div>

            <div>
              <label
                htmlFor="city"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                City
              </label>
              <select
                id="city"
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm rounded-md"
                value={filters.city}
                onChange={(e) => handleFilterChange("city", e.target.value)}
              >
                <option value="all">All Cities</option>
                {cities.map((city) => (
                  <option key={city} value={city}>
                    {city}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label
                htmlFor="hasLicense"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Has License
              </label>
              <select
                id="hasLicense"
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm rounded-md"
                value={filters.hasLicense}
                onChange={(e) =>
                  handleFilterChange("hasLicense", e.target.value)
                }
              >
                <option value="all">All</option>
                <option value="yes">With License</option>
                <option value="no">Without License</option>
              </select>
            </div>
          </div>

          <div className="mt-4 flex justify-end">
            <button
              onClick={clearFilters}
              className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
            >
              Clear Filters
            </button>
          </div>
        </div>

        {/* Results Info */}
        <div className="mb-4 flex justify-between items-center">
          <p className="text-sm text-gray-700">
            Showing <span className="font-medium">{filteredVets.length}</span>{" "}
            of <span className="font-medium">{vets.length}</span> veterinarians
          </p>
          <button
            onClick={fetchVets}
            className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
          >
            Refresh Data
          </button>
        </div>

        {/* Vets Grid */}
        {filteredVets.length === 0 ? (
          <div className="bg-white rounded-lg shadow-sm p-8 text-center">
            <svg
              className="mx-auto h-12 w-12 text-gray-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 极速加速器 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
              />
            </svg>
            <h3 className="mt-2 text-sm font-medium text-gray-900">
              No veterinarians found
            </h3>
            <p className="mt-1 text-sm text-gray-500">
              Try adjusting your search or filter to find what you're looking
              for.
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredVets.map((vet) => {
              const photos = parsePhotos(vet.photos);

              return (
                <div
                  key={vet.id}
                  className="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden border border-gray-200"
                >
                  <div className="p-6">
                    <div className="flex items-start justify-between">
                      <h2 className="text-lg font-semibold text-gray-800 truncate">
                        {vet.email || "Unknown Veterinarian"}
                      </h2>
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ID: {vet.id || "N/A"}
                      </span>
                    </div>

                    <div className="mt-4 space-y-3">
                      {vet.mobile && (
                        <div className="flex items-center text-sm text-gray-600">
                          <svg
                            className="flex-shrink-0 mr-2 h-4 w-4 text-gray-500"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                          >
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 极速加速器 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.极速加速器 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                          </svg>
                          <span>{vet.mobile}</span>
                        </div>
                      )}

                      {vet.city && (
                        <div className="flex items-center text-sm text-gray-600">
                          <svg
                            className="flex-shrink-0 mr-2 h-4 w-4 text-gray-500"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 极速加速器 0 002 2z"
                              clipRule="evenodd"
                            />
                          </svg>
                          <span>
                            {vet.city}
                            {vet.pincode && `, ${vet.pincode}`}
                          </span>
                        </div>
                      )}

                      {vet.address && (
                        <div className="text-sm text-gray-600">
                          <span className="font-medium">Address:</span>{" "}
                          {vet.address}
                        </div>
                      )}

                      {vet.license_no && (
                        <div className="text-sm text-gray-600">
                          <span className="font-medium">License:</span>{" "}
                          {vet.license_no}
                        </div>
                      )}

                      {vet.chat_price && (
                        <div className="text-sm text-gray-600">
                          <span className="font-medium">Consultation Fee (INR)*</span> ₹
                          {vet.chat_price}
                        </div>
                      )}

                      {(vet.rating || vet.user_ratings_total) && (
                        <div className="flex items-center text-sm text-gray-600">
                          <svg
                            className="flex-shrink-0 mr-1 h-4 w-4 text-yellow-400"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                          <span>
                            {vet.rating || "N/A"} ({vet.user_ratings_total || 0}{" "}
                            reviews)
                          </span>
                        </div>
                      )}

                      {vet.bio && (
                        <div className="mt-2 pt-2 border-t border-gray-100">
                          <h3 className="text-sm font-medium text-gray-700 mb-1">
                            Bio
                          </h3>
                          <p className="text-sm text-gray-600 line-clamp-3">
                            {vet.bio}
                          </p>
                        </div>
                      )}
                    </div>

                    {photos.length > 0 && (
                      <div className="mt-4 pt-3 border-t border-gray-100">
                        <h3 className="text-sm font-medium text-gray-700 mb-2">
                          Clinic Photos
                        </h3>
                        <div className="grid grid-cols-2 gap-2">
                          {photos.slice(0, 4).map((photo, idx) => (
                            <div key={idx} className="relative group">
                              <img
                                src={photo.photo_reference}
                                alt={`Clinic photo ${idx + 1}`}
                                className="w-full h-20 object-cover rounded border border-gray-300"
                                onError={(e) => {
                                  e.target.src =
                                    "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath fill='%23999' d='M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z'/%3E%3C/svg%3E";
                                  e.target.alt = "Image failed to load";
                                }}
                              />
                              {idx === 3 && photos.length > 4 && (
                                <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center rounded text-white text-xs font-medium">
                                  +{photos.length - 4} more
                                </div>
                              )}
                            </div>
                          ))}
                        </div>
                      </div>
                    )}

                    <div className="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500">
                      <div>
                        Created: {new Date(vet.created_at).toLocaleDateString()}
                      </div>
                      <div>
                        Updated: {new Date(vet.updated_at).toLocaleDateString()}
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminVetsPage;
