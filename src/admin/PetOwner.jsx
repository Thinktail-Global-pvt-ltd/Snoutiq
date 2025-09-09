import React, { useEffect, useState } from "react";
import axios from "axios";

const PetOwner = () => {
  const [users, setUsers] = useState([]);
  const [filteredUsers, setFilteredUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [filters, setFilters] = useState({
    hasPets: "all",
    hasDocuments: "all"
  });

  useEffect(() => {
    fetchUsers();
  }, []);

  useEffect(() => {
    filterUsers();
  }, [users, searchTerm, filters]);

  const fetchUsers = async () => {
    try {
      setLoading(true);
      setError(null);
      const res = await axios.get(
        "https://snoutiq.com/backend/api/users?email=adminsnoutiq@gmail.com"
      );
      
      // Handle different response formats
      let usersData = [];
      
      if (Array.isArray(res.data)) {
        usersData = res.data;
      } else if (res.data && typeof res.data === 'object') {
        if (Array.isArray(res.data.users)) {
          usersData = res.data.users;
        } else if (Array.isArray(res.data.data)) {
          usersData = res.data.data;
        } else {
          usersData = [res.data];
        }
      } else if (typeof res.data === 'string') {
        try {
          const parsedData = JSON.parse(res.data);
          if (Array.isArray(parsedData)) {
            usersData = parsedData;
          } else if (parsedData && typeof parsedData === 'object') {
            if (Array.isArray(parsedData.users)) {
              usersData = parsedData.users;
            } else if (Array.isArray(parsedData.data)) {
              usersData = parsedData.data;
            } else {
              usersData = [parsedData];
            }
          }
        } catch (parseError) {
          console.error("Error parsing response:", parseError);
          setError("Failed to parse response data");
        }
      }
      
      setUsers(usersData);
    } catch (error) {
      console.error("Error fetching users:", error);
      setError("Failed to fetch users. Please try again later.");
    } finally {
      setLoading(false);
    }
  };

  const filterUsers = () => {
    let result = users;

    // Apply search filter
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      result = result.filter(user => 
        (user.name && user.name.toLowerCase().includes(term)) ||
        (user.email && user.email.toLowerCase().includes(term)) ||
        (user.phone && user.phone.includes(term)) ||
        (user.pet_name && user.pet_name.toLowerCase().includes(term))
      );
    }

    // Apply hasPets filter
    if (filters.hasPets !== "all") {
      result = result.filter(user => 
        filters.hasPets === "yes" ? user.pet_name : !user.pet_name
      );
    }

    // Apply hasDocuments filter
    if (filters.hasDocuments !== "all") {
      result = result.filter(user => 
        filters.hasDocuments === "yes" ? (user.pet_doc1 || user.pet_doc2) : (!user.pet_doc1 && !user.pet_doc2)
      );
    }

    setFilteredUsers(result);
  };

  const handleSearchChange = (e) => {
    setSearchTerm(e.target.value);
  };

  const handleFilterChange = (filterType, value) => {
    setFilters(prev => ({ ...prev, [filterType]: value }));
  };

  const clearFilters = () => {
    setSearchTerm("");
    setFilters({
      hasPets: "all",
      hasDocuments: "all"
    });
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-screen bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-orange-500 mx-auto"></div>
          <p className="text-gray-500 text-lg mt-4">Loading users...</p>
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
            onClick={fetchUsers}
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
            <h1 className="text-2xl font-bold text-gray-800">Pet Owners Management</h1>
            <div className="text-sm text-gray-500">
              Total Users: <span className="font-semibold">{users.length}</span>
            </div>
          </div>
        </div>
      </header>

      {/* Search and Filters */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="md:col-span-2">
              <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
                Search
              </label>
              <div className="relative rounded-md shadow-sm">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg className="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                  </svg>
                </div>
                <input
                  type="text"
                  id="search"
                  className="focus:ring-orange-500 focus:border-orange-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2"
                  placeholder="Search by name, email, phone or pet name"
                  value={searchTerm}
                  onChange={handleSearchChange}
                />
              </div>
            </div>
            
            <div>
              <label htmlFor="hasPets" className="block text-sm font-medium text-gray-700 mb-1">
                Has Pets
              </label>
              <select
                id="hasPets"
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm rounded-md"
                value={filters.hasPets}
                onChange={(e) => handleFilterChange("hasPets", e.target.value)}
              >
                <option value="all">All</option>
                <option value="yes">With Pets</option>
                <option value="no">Without Pets</option>
              </select>
            </div>
            
            <div>
              <label htmlFor="hasDocuments" className="block text-sm font-medium text-gray-700 mb-1">
                Has Documents
              </label>
              <select
                id="hasDocuments"
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm rounded-md"
                value={filters.hasDocuments}
                onChange={(e) => handleFilterChange("hasDocuments", e.target.value)}
              >
                <option value="all">All</option>
                <option value="yes">With Documents</option>
                <option value="no">Without Documents</option>
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
            Showing <span className="font-medium">{filteredUsers.length}</span> of <span className="font-medium">{users.length}</span> users
          </p>
          <button
            onClick={fetchUsers}
            className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
          >
            Refresh Data
          </button>
        </div>

        {/* Users Grid */}
        {filteredUsers.length === 0 ? (
          <div className="bg-white rounded-lg shadow-sm p-8 text-center">
            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 className="mt-2 text-sm font-medium text-gray-900">No users found</h3>
            <p className="mt-1 text-sm text-gray-500">
              Try adjusting your search or filter to find what you're looking for.
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredUsers.map((user) => (
              <div
                key={user.id || user._id || `${user.email}-${user.name}`}
                className="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden border border-gray-200"
              >
                <div className="p-6">
                  <div className="flex items-start justify-between">
                    <h2 className="text-lg font-semibold text-gray-800 truncate">{user.name || "Unknown User"}</h2>
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      ID: {user.id || "N/A"}
                    </span>
                  </div>
                  
                  <div className="mt-4 space-y-3">
                    <div className="flex items-center text-sm text-gray-600">
                      <svg className="flex-shrink-0 mr-2 h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                      </svg>
                      <span className="truncate">{user.email || "N/A"}</span>
                    </div>
                    
                    <div className="flex items-center text-sm text-gray-600">
                      <svg className="flex-shrink-0 mr-2 h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                      </svg>
                      <span>{user.phone || "N/A"}</span>
                    </div>
                    
                    {user.pet_name && (
                      <div className="mt-4 pt-3 border-t border-gray-100">
                        <h3 className="text-sm font-medium text-gray-700 mb-2">Pet Information</h3>
                        <div className="space-y-2">
                          <div className="flex justify-between text-sm">
                            <span className="text-gray-500">Name:</span>
                            <span className="text-gray-900 font-medium">{user.pet_name}</span>
                          </div>
                          
                          {user.pet_age && (
                            <div className="flex justify-between text-sm">
                              <span className="text-gray-500">Age:</span>
                              <span className="text-gray-900">{user.pet_age} years</span>
                            </div>
                          )}
                          
                          {user.pet_gender && (
                            <div className="flex justify-between text-sm">
                              <span className="text-gray-500">Gender:</span>
                              <span className="text-gray-900 capitalize">{user.pet_gender}</span>
                            </div>
                          )}
                        </div>
                      </div>
                    )}
                    
                    {(user.pet_doc1 || user.pet_doc2) && (
                      <div className="mt-4 pt-3 border-t border-gray-100">
                        <h3 className="text-sm font-medium text-gray-700 mb-2">Documents</h3>
                        <div className="flex space-x-2">
                          {user.pet_doc1 && (
                            <a
                              href={`https://snoutiq.com/${user.pet_doc1.replace(
                                "/var/www/html/project/backend/public/",
                                "backend/"
                              )}`}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="inline-flex items-center text-sm text-orange-600 hover:text-orange-800"
                            >
                              <svg className="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clipRule="evenodd" />
                              </svg>
                              Document 1
                            </a>
                          )}
                          
                          {user.pet_doc2 && (
                            <a
                              href={`https://snoutiq.com/${user.pet_doc2.replace(
                                "/var/www/html/project/backend/public/",
                                "backend/"
                              )}`}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="inline-flex items-center text-sm text-orange-600 hover:text-orange-800"
                            >
                              <svg className="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clipRule="evenodd" />
                              </svg>
                              Document 2
                            </a>
                          )}
                        </div>
                      </div>
                    )}
                    
                    {user.summary && (
                      <div className="mt-4 pt-3 border-t border-gray-100">
                        <h3 className="text-sm font-medium text-gray-700 mb-2">Summary</h3>
                        <p className="text-sm text-gray-600 line-clamp-3">{user.summary}</p>
                      </div>
                    )}
                  </div>
                  
                  <div className="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500">
                    <div>Created: {new Date(user.created_at).toLocaleDateString()}</div>
                    <div>Updated: {new Date(user.updated_at).toLocaleDateString()}</div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default PetOwner;