import {
    Box,
    Typography,
    TextField,
    MenuItem,
    Button,
    Avatar,
    IconButton,
    useTheme,
    FormControl,
    InputLabel,
    Select,
    Grid,
    Paper,
    Chip,
    Fade,
    Alert,
    Dialog,
    DialogTitle,
    DialogContent,
    List,
    ListItem,
    ListItemText,
    CircularProgress,
    InputAdornment,
    Card,
    CardContent,
    FormControlLabel, Checkbox, Link
} from '@mui/material';
import { useState, useEffect, useRef } from 'react';
import CameraAltIcon from '@mui/icons-material/CameraAlt';
import SearchIcon from '@mui/icons-material/Search';
import BusinessIcon from '@mui/icons-material/Business';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import { GoogleMap, Marker, Autocomplete, useLoadScript } from "@react-google-maps/api";
import toast from 'react-hot-toast';
import { useNavigate } from 'react-router-dom';
import logo from '../assets/images/logo-dark.png';
import axios from 'axios';

const LIBRARIES = ["places"];

const DoctorRegistration = () => {
    const theme = useTheme();
    const navigate = useNavigate();
    const [isLoading, setIsLoading] = useState(false);
    const [isProfileSaving, setIsProfileSaving] = useState(false);
    const [isProfilePictureUpdated, setIsProfilePictureUpdated] = useState(false);
    const [currentPic, setCurrentPic] = useState("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEyMCIgaGVpZ2h0PSIxMjAiIGZpbGw9IiNFMkUyRTIiIHJ4PSI2MCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0iY2VudHJhbCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk5OSIgZm9udC1zaXplPSIxNCI+VXNlcjwvdGV4dD48L3N2Zz4=");

    const [currentPic1, setCurrentPic1] = useState("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEyMCIgaGVpZ2h0PSIxMjAiIGZpbGw9IiNFMkUyRTIiIHJ4PSI2MCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0iY2VudHJhbCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk5OSIgZm9udC1zaXplPSIxNCI+Q2xpbmljPC90ZXh0Pjwvc3ZnPg==");
    const [profilePictureFile, setProfilePictureFile] = useState(null);
    const [clinicPictureFile, setClinicPictureFile] = useState(null);
    const [showAutofillAlert, setShowAutofillAlert] = useState(false);
    const [businessSearchDialogOpen, setBusinessSearchDialogOpen] = useState(false);
    const [businessQuery, setBusinessQuery] = useState('');
    const [businessSuggestions, setBusinessSuggestions] = useState([]);
    const [isSearchingBusiness, setIsSearchingBusiness] = useState(false);
    const [businessDetails, setBusinessDetails] = useState(null);
    const [employeeId, setEmployeeId] = useState("")
    const [acceptedTerms, setAcceptedTerms] = useState(false);

    // Individual state variables
    const [name, setName] = useState("");
    const [bio, setBio] = useState("");
    const [chatPrice, setChatPrice] = useState("");
    const [address, setAddress] = useState("");
    const [city, setCity] = useState("");
    const [pinCode, setPinCode] = useState("");
    const [license_no, setLicense] = useState("");
    const [inhome_grooming_services, set_inhome_grooming_services] = useState(0);
    const [coordinates, setCoordinates] = useState({
        "lat": null,
        "lng": null
    });
    const [mobileNumber, setMobileNumber] = useState("")
    const [email, setEmail] = useState("")

    // State for multiple doctors
    const [doctors, setDoctors] = useState([
        {
            doctor_name: "",
            doctor_email: "",
            doctor_mobile: "",
            doctor_license: "",
            doctor_image: null,
            doctor_image_preview: "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEyMCIgaGVpZ2h0PSIxMjAiIGZpbGw9IiNFMkUyRTIiIHJ4PSI2MCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0iY2VudHJhbCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk5OSIgZm9udC1zaXplPSIxNCI+RG9jdG9yPC90ZXh0Pjwvc3ZnPg=="
        }
    ]);

    const LIBRARIES = ["places"];

    const { isLoaded, loadError } = useLoadScript({
        googleMapsApiKey: "AIzaSyDEFWG5jYxYTXBouOr43vjV4Aj6WEOXBps",
        libraries: LIBRARIES,
    });

    const reverseGeocode = async (lat, lng) => {
        try {
            const response = await fetch(
                `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=AIzaSyDEFWG5jYxYTXBouOr43vjV4Aj6WEOXBps`
            );
            const data = await response.json();

            if (data.results && data.results.length > 0) {
                const addressResult = data.results[0];
                let cityName = "";
                let pinCodeValue = "";
                let fullAddress = addressResult.formatted_address;

                // Extract city and postal code from address components
                for (const component of addressResult.address_components) {
                    if (component.types.includes("locality") || component.types.includes("administrative_area_level_2")) {
                        cityName = component.long_name;
                    }
                    if (component.types.includes("postal_code")) {
                        pinCodeValue = component.long_name;
                    }
                }

                return {
                    address: fullAddress,
                    city: cityName,
                    pinCode: pinCodeValue,
                    coordinates: { lat, lng }
                };
            }
            return null;
        } catch (error) {
            console.error("Reverse geocoding error:", error);
            throw error;
        }
    };

    const handleBusinessSearch = async (query) => {
        if (!query || query.length < 3) {
            setBusinessSuggestions([]);
            return;
        }

        setIsSearchingBusiness(true);
        try {
            if (!window.google?.maps?.places) {
                toast.error("Maps service not loaded. Please refresh the page.");
                return;
            }

            if (!window.placesService) {
                const mapEl = document.createElement("div");
                window.placesService = new window.google.maps.places.PlacesService(mapEl);
            }
            const request = {
                query,
                fields: [
                    "place_id",
                    "business_status",
                    "formatted_address",
                    "name",
                    "geometry",
                    "address_components",
                    "rating",
                    "user_ratings_total",
                    "photos",
                    "icon",
                    "icon_background_color",
                    "icon_mask_base_uri",
                    "opening_hours",
                    "types", "website", "formatted_phone_number"

                ],
            };

            const results = await new Promise((resolve, reject) => {
                window.placesService.textSearch(request, (results, status) => {
                    if (status === window.google.maps.places.PlacesServiceStatus.OK) resolve(results);
                    else if (status === window.google.maps.places.PlacesServiceStatus.ZERO_RESULTS) resolve([]);
                    else reject(new Error(`Places API error: ${status}`));
                });
            });

            setBusinessSuggestions(results);
        } catch (error) {
            console.error(error);
            toast.error("Error searching for businesses: " + error.message);
            setBusinessSuggestions([]);
        } finally {
            setIsSearchingBusiness(false);
        }
    };

    const handleBusinessSelect = async (business) => {
        try {
            if (!window.placesService) {
                const mapEl = document.createElement("div");
                window.placesService = new window.google.maps.places.PlacesService(mapEl);
            }

            const detailedRequest = {
                placeId: business.place_id,
                fields: [
                    "place_id",
                    "business_status",
                    "formatted_address",
                    "name",
                    "geometry",
                    "address_components",
                    "rating",
                    "user_ratings_total",
                    "photos",
                    "icon",
                    "icon_background_color",
                    "icon_mask_base_uri",
                    "opening_hours",
                    "types",
                    "plus_code", "website", "formatted_phone_number"
                ]
            };

            const placeDetails = await new Promise((resolve, reject) => {
                window.placesService.getDetails(detailedRequest, (place, status) => {
                    if (status === window.google.maps.places.PlacesServiceStatus.OK) resolve(place);
                    else reject(new Error(`Places details error: ${status}`));
                });
            });

            const extractedData = {
                name: placeDetails.name,
                formatted_address: placeDetails.formatted_address,
                formatted_phone_number: placeDetails.formatted_phone_number,
                website: placeDetails.website,
                place_id: placeDetails.place_id,
                business_status: placeDetails.business_status || 'OPERATIONAL',
                geometry: placeDetails.geometry,
                rating: placeDetails.rating || 0,
                user_ratings_total: placeDetails.user_ratings_total || 0,
                photos: placeDetails.photos ? placeDetails.photos.map(photo => ({
                    height: photo.height,
                    width: photo.width,
                    photo_reference: photo.getUrl({ maxWidth: 400 })
                })) : [],
                icon: placeDetails.icon,
                icon_background_color: placeDetails.icon_background_color,
                icon_mask_base_uri: placeDetails.icon_mask_base_uri,
                opening_hours: placeDetails.opening_hours ? {
                    open_now: placeDetails.opening_hours.open_now
                } : { open_now: true },
                types: placeDetails.types || ['point_of_interest', 'establishment'],
                plus_code: placeDetails.plus_code ? {
                    compound_code: placeDetails.plus_code.compound_code,
                    global_code: placeDetails.plus_code.global_code
                } : null
            };

            setBusinessDetails(extractedData);

            // Update form fields
            setName(extractedData.name);
            setAddress(extractedData.formatted_address);
            let cleanedPhone = extractedData.formatted_phone_number?.replace(/\D/g, '');
            if (cleanedPhone.length > 10) {
                cleanedPhone = cleanedPhone.slice(-10);
            }
            setMobileNumber(cleanedPhone);

            // Extract city and postal code
            let cityName = "";
            let pinCodeValue = "";

            if (placeDetails.address_components) {
                placeDetails.address_components.forEach((comp) => {
                    if (comp.types.includes("locality") || comp.types.includes("administrative_area_level_2")) {
                        cityName = comp.long_name;
                    }
                    if (comp.types.includes("postal_code")) {
                        pinCodeValue = comp.long_name;
                    }
                });
            }

            setCity(cityName);
            setPinCode(pinCodeValue);

            // Set coordinates
            if (placeDetails.geometry?.location) {
                setCoordinates({
                    lat: placeDetails.geometry.location.lat(),
                    lng: placeDetails.geometry.location.lng(),
                });
            }

            setBusinessSearchDialogOpen(false);
            setBusinessSuggestions([]);
            toast.success("Business details filled successfully!");

        } catch (error) {
            console.error("Error getting place details:", error);
            toast.error("Could not get complete business details");
        }
    };


    const handleMarkerDragEnd = async (event) => {
        const newLat = event.latLng.lat();
        const newLng = event.latLng.lng();
        setCoordinates({ lat: newLat, lng: newLng });

        try {
            const addressDetails = await reverseGeocode(newLat, newLng);
            if (addressDetails) {
                setAddress(addressDetails.address);
                setCity(addressDetails.city);
                setPinCode(addressDetails.pinCode);
            }
        } catch (error) {
            toast.error("Error updating address");
        }
    };


    const handleClinicImageChange = async (e) => {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith("image/")) return;

        const compressedFile = await compressImage(file);
        setClinicPictureFile(compressedFile);

        const previewReader = new FileReader();
        previewReader.onload = () => {
            setCurrentPic1(previewReader.result);
        };
        previewReader.readAsDataURL(compressedFile);
    };

    // Helper function for image compression
    const compressImage = (file) => {
        return new Promise((resolve) => {
            const maxWidth = 400;
            const quality = 0.7;

            const reader = new FileReader();
            reader.onload = (event) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement("canvas");
                    const scaleSize = maxWidth / img.width;
                    canvas.width = maxWidth;
                    canvas.height = img.height * scaleSize;

                    const ctx = canvas.getContext("2d");
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    canvas.toBlob((blob) => {
                        if (blob) {
                            const compressedFile = new File([blob], file.name, {
                                type: "image/jpeg",
                                lastModified: Date.now()
                            });
                            resolve(compressedFile);
                        }
                    }, "image/jpeg", quality);
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });
    };

    // Functions to handle multiple doctors
    const handleAddDoctor = () => {
        setDoctors([...doctors, {
            doctor_name: "",
            doctor_email: "",
            doctor_mobile: "",
            doctor_license: "",
            doctor_image: null,
            doctor_image_preview: "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEyMCIgaGVpZ2h0PSIxMjAiIGZpbGw9IiNFMkUyRTIiIHJ4PSI2MCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0iY2VudHJhbCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk5OSIgZm9udC1zaXplPSIxNCI+RG9jdG9yPC90ZXh0Pjwvc3ZnPg=="
        }]);
    };

    const handleRemoveDoctor = (index) => {
        if (doctors.length <= 1) return;
        const updatedDoctors = [...doctors];
        updatedDoctors.splice(index, 1);
        setDoctors(updatedDoctors);
    };

    const handleDoctorChange = (index, field, value) => {
        const updatedDoctors = [...doctors];
        updatedDoctors[index][field] = value;
        setDoctors(updatedDoctors);
    };

    const handleDoctorImageChange = async (e, index) => {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith("image/")) return;

        const compressedFile = await compressImage(file);
        const updatedDoctors = [...doctors];
        updatedDoctors[index].doctor_image = compressedFile;

        const previewReader = new FileReader();
        previewReader.onload = () => {
            updatedDoctors[index].doctor_image_preview = previewReader.result;
            setDoctors(updatedDoctors);
        };
        previewReader.readAsDataURL(compressedFile);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!acceptedTerms) {
            alert("Please accept the terms before submitting!");
            return;
        }

        setIsProfileSaving(true);
        try {
            const formData = new FormData();
            formData.append('clinic_name', name);
            formData.append('city', city);
            formData.append('pincode', pinCode);
            formData.append('mobile', mobileNumber);
            formData.append('email', email);
            formData.append('employee_id', employeeId)

            // Send coordinates as an array instead of JSON string
            if (coordinates.lat && coordinates.lng) {
                formData.append('coordinates[]', coordinates.lat);
                formData.append('coordinates[]', coordinates.lng);
            }

            formData.append('address', address);
            formData.append('chat_price', chatPrice);
            formData.append('bio', bio);
            formData.append('inhome_grooming_services', inhome_grooming_services);
            formData.append('acceptedTerms', acceptedTerms);

            // Add all the Google Places data if available
            if (businessDetails) {
                formData.append('place_id', businessDetails.place_id || '');
                formData.append('business_status', businessDetails.business_status || 'OPERATIONAL');
                formData.append('formatted_address', businessDetails.formatted_address || address);
                formData.append('lat', coordinates.lat);
                formData.append('lng', coordinates.lng);

                // Viewport data
                if (businessDetails.geometry?.viewport) {
                    const viewport = businessDetails.geometry.viewport;
                    formData.append('viewport_ne_lat', viewport.getNorthEast().lat());
                    formData.append('viewport_ne_lng', viewport.getNorthEast().lng());
                    formData.append('viewport_sw_lat', viewport.getSouthWest().lat());
                    formData.append('viewport_sw_lng', viewport.getSouthWest().lng());
                }

                formData.append('icon', businessDetails.icon || '');
                formData.append('icon_background_color', businessDetails.icon_background_color || '');
                formData.append('icon_mask_base_uri', businessDetails.icon_mask_base_uri || '');

                // Send open_now as boolean (not string)
                formData.append('open_now', businessDetails.opening_hours?.open_now ? "1" : "0");


                // Send types as individual array items
                if (businessDetails.types && Array.isArray(businessDetails.types)) {
                    businessDetails.types.forEach((type, index) => {
                        formData.append(`types[${index}]`, type);
                    });
                }

                // Send photos as individual array items
                if (businessDetails.photos && Array.isArray(businessDetails.photos)) {
                    businessDetails.photos.forEach((photo, index) => {
                        formData.append(`photos[${index}][height]`, photo.height);
                        formData.append(`photos[${index}][width]`, photo.width);
                        formData.append(`photos[${index}][photo_reference]`, photo.photo_reference);
                    });
                }

                // Plus code
                if (businessDetails.plus_code) {
                    formData.append('compound_code', businessDetails.plus_code.compound_code || '');
                    formData.append('global_code', businessDetails.plus_code.global_code || '');
                }

                formData.append('rating', businessDetails.rating?.toString() || '0');
                formData.append('user_ratings_total', businessDetails.user_ratings_total?.toString() || '0');
            }

            if (profilePictureFile) {
                formData.append('clinic_profile', profilePictureFile);
            }

            // Add clinic image (NEW)
            if (clinicPictureFile) {
                formData.append('hospital_profile', clinicPictureFile);
            }

            // Add doctors data
            doctors.forEach((doctor, index) => {
                formData.append(`doctors[${index}][doctor_name]`, doctor.doctor_name);
                formData.append(`doctors[${index}][doctor_email]`, doctor.doctor_email);
                formData.append(`doctors[${index}][doctor_mobile]`, doctor.doctor_mobile);
                formData.append(`doctors[${index}][doctor_license]`, doctor.doctor_license);
                if (doctor.doctor_image) {
                    formData.append(`doctors[${index}][doctor_image]`, doctor.doctor_image);
                }
            });

            console.log('Form data to be sent:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            const res = await axios.post('https://snoutiqai.com/public/api/vet-registerations/store', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            if (res.status === 200 || res.status === 201) {
                toast.success(res.data.message || "Profile saved successfully!");
                alert("Form submitted successfully ✅");
                window.location.reload();
            }



            toast.success(res.data.message);

        } catch (error) {
            const errorMessage = error.response && error.response.data && error.response.data.message
                ? error.response.data.message
                : 'Error saving profile';
            toast.error(errorMessage);
            console.log('Error details:', error.response?.data);
        } finally {
            setIsProfileSaving(false);
        }
    };

    if (loadError) {
        return (
            <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
                <Typography>Error loading Google Maps: {loadError.message}</Typography>
            </Box>
        );
    }

    if (isLoading) {
        return (
            <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
                <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                    <Box sx={{ animation: 'spin 1s linear infinite', border: '4px solid #f3f3f3', borderTop: '4px solid #3498db', borderRadius: '50%', width: 50, height: 50 }} />
                    <Typography sx={{ mt: 2 }}>Loading...</Typography>
                </Box>
            </Box>
        );
    }

    return (
        <Box
            sx={{
                width: '100%',
                minHeight: '100vh',
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'flex-start',
                bgcolor: '#F0F2F5',
                p: 2,
                overflow: 'auto',
            }}
        >
            <Box sx={{ width: '100%', maxWidth: 920, display: 'flex', alignItems: 'center', mx: 'auto' }}>
                <Paper
                    sx={{
                        backgroundColor: '#FFFFFF',
                        borderRadius: '16px',
                        boxShadow: '0px 4px 20px rgba(0, 0, 0, 0.08)',
                        width: '100%',
                        p: { xs: 3, md: 4 },
                        mx: 'auto',
                        my: 2
                    }}
                >
                    {/* Enhanced Professional Header Section */}
                    <Box
                        sx={{
                            pb: 1,
                            borderBottom: '1px solid',
                            borderColor: 'rgba(0, 0, 0, 0.08)',
                            flexDirection: { xs: 'column', sm: 'row' },
                            gap: { xs: 2, sm: 0 }
                        }}
                    >
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, justifyContent: 'space-between' }}>
                            <Box
                                sx={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    width: 120,
                                    height: 120,
                                    borderRadius: '12px',
                                    color: 'white',
                                }}
                            >
                                <img src={logo} />
                            </Box>

                            <Box>
                                <Typography
                                    variant="h4"
                                    sx={{
                                        fontWeight: 700,
                                        color: '#1A1A1A',
                                        fontSize: { xs: '1.5rem', sm: '1.75rem', md: '2rem' },
                                        lineHeight: 1.2
                                    }}
                                >
                                    Doctor Registration
                                </Typography>
                                <Typography
                                    variant="body2"
                                    sx={{
                                        color: 'text.secondary',
                                        mt: 0.5,
                                        display: { xs: 'none', sm: 'block' }
                                    }}
                                >
                                    Complete your profile to start your practice
                                </Typography>
                            </Box>

                            <Box>
                                <Button
                                    variant="outlined"
                                    startIcon={<BusinessIcon />}
                                    onClick={() => setBusinessSearchDialogOpen(true)}
                                    sx={{
                                        borderRadius: '10px',
                                        textTransform: 'none',
                                        fontWeight: 600,
                                        fontSize: '0.875rem',
                                        py: 1.25,
                                        px: 2.5,
                                        color: 'primary.main',
                                        borderColor: 'primary.main',
                                        borderWidth: '1.5px',
                                        '&:hover': {
                                            borderColor: 'primary.dark',
                                            backgroundColor: 'primary.light',
                                            color: 'primary.dark',
                                            boxShadow: '0px 4px 8px rgba(25, 118, 210, 0.15)',
                                        },
                                    }}
                                >
                                    Find Business
                                </Button>
                            </Box>
                        </Box>


                    </Box>

                    <Dialog
                        open={businessSearchDialogOpen}
                        onClose={() => setBusinessSearchDialogOpen(false)}
                        maxWidth="sm"
                        fullWidth
                    >
                        <DialogTitle sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <BusinessIcon color="primary" />
                            Find your business
                        </DialogTitle>
                        <DialogContent>
                            <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                                Search for your business to automatically fill in your details
                            </Typography>

                            <TextField
                                fullWidth
                                value={businessQuery}
                                onChange={(e) => {
                                    setBusinessQuery(e.target.value);
                                    handleBusinessSearch(e.target.value);
                                }}
                                placeholder="Enter your business name"
                                InputProps={{
                                    startAdornment: (
                                        <InputAdornment position="start">
                                            <SearchIcon />
                                        </InputAdornment>
                                    ),
                                    endAdornment: isSearchingBusiness ? (
                                        <CircularProgress size={20} />
                                    ) : null,
                                }}
                                sx={{ mb: 2 }}
                            />

                            {businessSuggestions.length > 0 ? (
                                <Paper elevation={1} sx={{ maxHeight: 300, overflow: 'auto' }}>
                                    <List>
                                        {businessSuggestions.map((business, index) => (
                                            <ListItem
                                                key={index}
                                                button
                                                onClick={() => handleBusinessSelect(business)}
                                                divider={index < businessSuggestions.length - 1}
                                            >
                                                <ListItemText
                                                    primary={business.name}
                                                    secondary={business.formatted_address}
                                                />
                                            </ListItem>
                                        ))}
                                    </List>
                                </Paper>
                            ) : businessQuery.length >= 3 && !isSearchingBusiness ? (
                                <Typography variant="body2" color="text.secondary" sx={{ textAlign: 'center', py: 2 }}>
                                    No businesses found. Try a different search term.
                                </Typography>
                            ) : null}
                        </DialogContent>
                    </Dialog>

                    {/* Autofill Alert */}
                    <Fade in={showAutofillAlert}>
                        <Alert severity="success" sx={{ mb: 1 }}>
                            Form has been autofilled with your location data. Please review and customize the information.
                        </Alert>
                    </Fade>

                    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', mb: 2 }}>
                        <Box
                            sx={{
                                width: 120,
                                height: 120,
                                borderRadius: '50%',
                                border: '2px dashed #BDBDBD',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                position: 'relative',
                                overflow: 'hidden',
                                bgcolor: '#F8F8F8',
                                '&:hover': {
                                    borderColor: theme.palette.primary.main,
                                    backgroundColor: '#f0f8ff',
                                },
                            }}
                        >
                            <Avatar
                                src={currentPic1}
                                sx={{ width: 120, height: 120 }}
                            />
                            <input
                                type="file"
                                accept="image/*"
                                style={{ position: 'absolute', width: '100%', height: '100%', opacity: 0, cursor: 'pointer' }}
                                onChange={handleClinicImageChange}
                            />
                            <IconButton
                                sx={{
                                    position: 'absolute',
                                    bottom: 8,
                                    right: 8,
                                    bgcolor: '#fff',
                                    border: `2px solid ${theme.palette.primary.main}`,
                                    width: 36,
                                    height: 36,
                                    p: 0,
                                    zIndex: 2,
                                    boxShadow: 2,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    '&:hover': { bgcolor: '#F5F5F5' },
                                }}
                                component="label"
                            >
                                <CameraAltIcon sx={{ color: theme.palette.primary.main, fontSize: 20 }} />
                                <input
                                    type="file"
                                    accept="image/*"
                                    hidden
                                    onChange={handleClinicImageChange}
                                />
                            </IconButton>
                        </Box>
                        <Typography sx={{ color: '#666', fontSize: 14, mt: 0.5 }}>Drag & drop or click to upload Clinic</Typography>
                    </Box>

                    {/* Form */}
                    <Box component="form" onSubmit={handleSubmit} >
                        <Box className="form-grid-container"
                            sx={{ width: '100%' }}>
                            {/* First Row - 2 columns */}
                            <Box>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    Business Name *
                                </Typography>
                                <TextField
                                    fullWidth
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="Enter your business name"
                                    size="small"
                                    required
                                    sx={{
                                        '& .MuiOutlinedInput-root': {
                                            borderRadius: '8px',
                                            '& fieldset': { borderColor: '#E0E0E0' },
                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                        },
                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                    }}
                                />
                            </Box>

                            <Box>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    Mobile Number *
                                </Typography>
                                <TextField
                                    fullWidth
                                    value={mobileNumber}
                                    onChange={(e) => setMobileNumber(e.target.value)}
                                    placeholder="Enter Your Mobile Number"
                                    size="small"
                                    required
                                    inputProps={{
                                        maxLength: 10,
                                        inputMode: 'numeric'
                                    }}
                                    sx={{
                                        '& .MuiOutlinedInput-root': {
                                            borderRadius: '8px',
                                            '& fieldset': { borderColor: '#E0E0E0' },
                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                        },
                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                    }}
                                />
                            </Box>

                            {/* Second Row - 2 columns */}
                            <Box>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    Email ID *
                                </Typography>
                                <TextField
                                    fullWidth
                                    placeholder="Enter Email Address"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    size="small"
                                    required
                                    sx={{
                                        '& .MuiOutlinedInput-root': {
                                            borderRadius: '8px',
                                            '& fieldset': { borderColor: '#E0E0E0' },
                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                        },
                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                    }}
                                />
                            </Box>

                            {/* Third Row - 2 columns */}
                            <Box>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    Address *
                                </Typography>
                                {isLoaded && (
                                    <Autocomplete
                                        onLoad={(autocompleteInstance) => {
                                            window.autocompleteRef = autocompleteInstance;
                                        }}
                                        onPlaceChanged={() => {
                                            const place = window.autocompleteRef.getPlace();
                                            if (!place.geometry) {
                                                alert("Please select a place from suggestions.");
                                                return;
                                            }

                                            setAddress(place.formatted_address);
                                            setCoordinates({
                                                lat: place.geometry.location.lat(),
                                                lng: place.geometry.location.lng(),
                                            });
                                            const addressComponents = place.address_components || [];
                                            const cityComponent = addressComponents.find((c) =>
                                                c.types.includes("locality") || c.types.includes("administrative_area_level_2")
                                            );
                                            const pinCodeComponent = addressComponents.find((c) => c.types.includes("postal_code"));
                                            setCity(cityComponent ? cityComponent.long_name : "");
                                            setPinCode(pinCodeComponent ? pinCodeComponent.long_name : "");
                                        }}
                                    >
                                        <TextField
                                            fullWidth
                                            value={address}
                                            onChange={(e) => setAddress(e.target.value)}
                                            placeholder="Enter your address, city, or village"
                                            size="small"
                                            required
                                            sx={{
                                                '& .MuiOutlinedInput-root': {
                                                    borderRadius: '8px',
                                                    '& fieldset': { borderColor: '#E0E0E0' },
                                                    '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                                    '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                                },
                                                '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                            }}
                                        />
                                    </Autocomplete>
                                )}
                            </Box>

                            <Box>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    City *
                                </Typography>
                                <TextField
                                    fullWidth
                                    value={city}
                                    onChange={(e) => setCity(e.target.value)}
                                    placeholder="Enter city"
                                    size="small"
                                    required
                                    sx={{
                                        '& .MuiOutlinedInput-root': {
                                            borderRadius: '8px',
                                            '& fieldset': { borderColor: '#E0E0E0' },
                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                        },
                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                    }}
                                />
                            </Box>

                            {/* Fourth Row - 2 columns */}
                            <Box>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    PIN Code *
                                </Typography>
                                <TextField
                                    fullWidth
                                    value={pinCode}
                                    onChange={(e) => setPinCode(e.target.value)}
                                    placeholder="Enter PIN code"
                                    size="small"
                                    required
                                    sx={{
                                        '& .MuiOutlinedInput-root': {
                                            borderRadius: '8px',
                                            '& fieldset': { borderColor: '#E0E0E0' },
                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                        },
                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                    }}
                                />
                            </Box>

                            <Box>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    Consultation Fee (INR) *
                                </Typography>
                                <TextField
                                    fullWidth
                                    type='number'
                                    value={chatPrice}
                                    onChange={(e) => setChatPrice(e.target.value)}
                                    placeholder='Enter Your Fees'
                                    size="small"
                                    required
                                    sx={{
                                        '& .MuiOutlinedInput-root': {
                                            borderRadius: '8px',
                                            '& fieldset': { borderColor: '#E0E0E0' },
                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                        },
                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                    }}
                                />
                            </Box>

                            {/* Fifth Row - 2 columns */}
                            <Box>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    Do you offer at home services? *
                                </Typography>
                                <FormControl fullWidth size="small">
                                    <Select
                                        value={inhome_grooming_services}
                                        onChange={(e) => set_inhome_grooming_services(e.target.value)}
                                        sx={{
                                            borderRadius: '8px',
                                            '& fieldset': { borderColor: '#E0E0E0' },
                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                            '& .MuiSelect-select': { color: '#333', fontSize: '14px', py: 1.5 },
                                        }}
                                    >
                                        <MenuItem value={1}>Yes</MenuItem>
                                        <MenuItem value={0}>No</MenuItem>
                                    </Select>
                                </FormControl>
                            </Box>
                        </Box>

                        {/* Bio - Full width */}
                        <Box>
                            <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>Comments (Optional)</Typography>
                            <TextField
                                fullWidth
                                multiline
                                rows={3}
                                value={bio}
                                onChange={(e) => setBio(e.target.value)}
                                placeholder="Write about your medical experience and specialization"
                                sx={{
                                    '& .MuiOutlinedInput-root': {
                                        borderRadius: '8px',
                                        '& fieldset': { borderColor: '#E0E0E0' },
                                        '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                        '&.Mui-focused fieldset': {
                                            borderColor: theme.palette.primary.main,
                                            borderWidth: '1.5px',
                                        },
                                    },
                                    '& .MuiInputBase-input': { color: '#333', fontSize: '14px' },
                                }}
                            />
                        </Box>
                        {/* Doctors Section */}
                        <Box sx={{ mt: 3 }}>
                            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                                <Typography variant="h6" sx={{ fontWeight: 600 }}>
                                    Doctors Information
                                </Typography>
                                <Button
                                    variant="outlined"
                                    startIcon={<AddIcon />}
                                    onClick={handleAddDoctor}
                                    sx={{
                                        borderRadius: '8px',
                                        textTransform: 'none',
                                        fontWeight: 600,
                                    }}
                                >
                                    Add Doctor
                                </Button>
                            </Box>

                            {doctors.map((doctor, index) => (
                                <Card key={index} sx={{ mb: 3, border: '1px solid #e0e0e0' }}>
                                    <CardContent>
                                        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 2 }}>
                                            <Typography variant="h6" sx={{ fontWeight: 600 }}>
                                                Doctor {index + 1}
                                            </Typography>
                                            {doctors.length > 1 && (
                                                <IconButton
                                                    color="error"
                                                    onClick={() => handleRemoveDoctor(index)}
                                                    size="small"
                                                >
                                                    <DeleteIcon />
                                                </IconButton>
                                            )}
                                        </Box>



                                        <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                                            <Box
                                                sx={{
                                                    width: 100,
                                                    height: 100,
                                                    borderRadius: '50%',
                                                    border: '2px dashed #BDBDBD',
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'center',
                                                    position: 'relative',
                                                    overflow: 'hidden',
                                                    bgcolor: '#F8F8F8',
                                                    '&:hover': {
                                                        borderColor: theme.palette.primary.main,
                                                        backgroundColor: '#f0f8ff',
                                                    },
                                                }}
                                            >
                                                <Avatar
                                                    src={doctor.doctor_image_preview}
                                                    sx={{ width: 100, height: 100 }}
                                                />
                                                <input
                                                    type="file"
                                                    accept="image/*"
                                                    style={{ position: 'absolute', width: '100%', height: '100%', opacity: 0, cursor: 'pointer' }}
                                                    onChange={(e) => handleDoctorImageChange(e, index)}
                                                />
                                                <IconButton
                                                    sx={{
                                                        position: 'absolute',
                                                        bottom: 4,
                                                        right: 4,
                                                        bgcolor: '#fff',
                                                        border: `2px solid ${theme.palette.primary.main}`,
                                                        width: 30,
                                                        height: 30,
                                                        p: 0,
                                                        zIndex: 2,
                                                        boxShadow: 2,
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent: 'center',
                                                        '&:hover': { bgcolor: '#F5F5F5' },
                                                    }}
                                                    component="label"
                                                >
                                                    <CameraAltIcon sx={{ color: theme.palette.primary.main, fontSize: 16 }} />
                                                    <input
                                                        type="file"
                                                        accept="image/*"
                                                        hidden
                                                        onChange={(e) => handleDoctorImageChange(e, index)}
                                                    />
                                                </IconButton>
                                            </Box>
                                            <Typography sx={{ color: '#666', fontSize: 12, mt: 0.5 }}>Doctor Photo</Typography>
                                        </Box>


                                        <Box className="form-grid-container"
                                            sx={{ width: '100%' }}>
                                            <Box>
                                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                                    Doctor Name *
                                                </Typography>
                                                <TextField
                                                    fullWidth
                                                    value={doctor.doctor_name}
                                                    onChange={(e) => handleDoctorChange(index, 'doctor_name', e.target.value)}
                                                    placeholder="Enter doctor name"
                                                    size="small"
                                                    required
                                                    sx={{
                                                        '& .MuiOutlinedInput-root': {
                                                            borderRadius: '8px',
                                                            '& fieldset': { borderColor: '#E0E0E0' },
                                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                                        },
                                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                                    }}
                                                />
                                            </Box>

                                            <Box>
                                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                                    Doctor Email *
                                                </Typography>
                                                <TextField
                                                    fullWidth
                                                    type="email"
                                                    value={doctor.doctor_email}
                                                    onChange={(e) => handleDoctorChange(index, 'doctor_email', e.target.value)}
                                                    placeholder="Enter doctor email"
                                                    size="small"
                                                    required
                                                    sx={{
                                                        '& .MuiOutlinedInput-root': {
                                                            borderRadius: '8px',
                                                            '& fieldset': { borderColor: '#E0E0E0' },
                                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                                        },
                                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                                    }}
                                                />
                                            </Box>

                                            <Box>
                                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                                    Doctor Mobile *
                                                </Typography>
                                                <TextField
                                                    fullWidth
                                                    value={doctor.doctor_mobile}
                                                    onChange={(e) => handleDoctorChange(index, 'doctor_mobile', e.target.value)}
                                                    placeholder="Enter doctor mobile"
                                                    size="small"
                                                    required
                                                    inputProps={{
                                                        maxLength: 10,
                                                        inputMode: 'numeric'
                                                    }}
                                                    sx={{
                                                        '& .MuiOutlinedInput-root': {
                                                            borderRadius: '8px',
                                                            '& fieldset': { borderColor: '#E0E0E0' },
                                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                                        },
                                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                                    }}
                                                />
                                            </Box>

                                            <Box>
                                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                                    Doctor License No. *
                                                </Typography>
                                                <TextField
                                                    fullWidth
                                                    value={doctor.doctor_license}
                                                    onChange={(e) => handleDoctorChange(index, 'doctor_license', e.target.value)}
                                                    placeholder="Enter doctor license number"
                                                    size="small"
                                                    required
                                                    sx={{
                                                        '& .MuiOutlinedInput-root': {
                                                            borderRadius: '8px',
                                                            '& fieldset': { borderColor: '#E0E0E0' },
                                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                                        },
                                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                                    }}
                                                />
                                            </Box>


                                        </Box>
                                    </CardContent>
                                </Card>
                            ))}
                            <Grid item xs={12} md={6}>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    Employee ID *
                                </Typography>
                                <TextField
                                    fullWidth
                                    value={employeeId}
                                    onChange={(e) => setEmployeeId(e.target.value)}
                                    placeholder='Enter Your Employee ID'
                                    size="small"
                                    required
                                    sx={{
                                        '& .MuiOutlinedInput-root': {
                                            borderRadius: '8px',
                                            '& fieldset': { borderColor: '#E0E0E0' },
                                            '&:hover fieldset': { borderColor: theme.palette.primary.light },
                                            '&.Mui-focused fieldset': { borderColor: theme.palette.primary.main, borderWidth: '1.5px' },
                                        },
                                        '& .MuiInputBase-input': { color: '#333', fontSize: '14px', py: 1.5 },
                                    }}
                                />
                            </Grid>

                            <Grid item xs={12}>
                                <FormControlLabel
                                    control={
                                        <Checkbox
                                            checked={acceptedTerms}
                                            onChange={(e) => setAcceptedTerms(e.target.checked)}
                                            color="primary"
                                        />
                                    }
                                    label={
                                        // <Typography variant="body2">
                                        //     I agree to the{" "}
                                        //     <Link href="" target="_blank" underline="hover">
                                        //         Privacy Policy
                                        //     </Link>
                                        //     ,{" "}
                                        //     <Link href="" target="_blank" underline="hover">
                                        //         Document
                                        //     </Link>{" "}
                                        //     and{" "}
                                        //     <Link href="" target="_blank" underline="hover">
                                        //         Terms & Conditions
                                        //     </Link>
                                        // </Typography>
                                         <Typography variant="body2">I agree to the{" "}
                                          <span className='text-blue-600'>Privacy Policy ,</span>
                                            <span className='text-blue-600'> Document </span>
                                            and{" "}<span className='text-blue-600'>Terms & Conditions</span>
                                        
                                        </Typography>
                                    }
                                />
                            </Grid>
                        </Box>

                        {/* Google Maps for fine-tuning location */}
                        {isLoaded && coordinates.lat && coordinates.lng && (
                            <Box sx={{ mb: 3, mt: 2 }}>
                                <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#333', mb: 1 }}>
                                    Fine-tune Your Location
                                </Typography>
                                <GoogleMap
                                    mapContainerStyle={{ height: "300px", width: "100%", borderRadius: "12px", border: '1px solid #E0E0E0' }}
                                    center={coordinates}
                                    zoom={15}
                                >
                                    <Marker position={coordinates} draggable onDragEnd={handleMarkerDragEnd} />
                                </GoogleMap>
                                <Typography sx={{ fontSize: '13px', color: '#666', mt: 1, fontStyle: 'italic' }}>
                                    Drag the marker to precisely set your location
                                </Typography>
                            </Box>
                        )}

                        {/* Submit Button */}
                        <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
                            <Button
                                variant="contained"
                                type="submit"
                                disabled={isProfileSaving}
                                sx={{
                                    bgcolor: theme.palette.primary.main,
                                    color: '#FFFFFF',
                                    textTransform: 'none',
                                    fontWeight: 600,
                                    fontSize: '16px',
                                    py: 1.5,
                                    px: 6,
                                    borderRadius: '10px',
                                    boxShadow: '0px 4px 12px rgba(25, 118, 210, 0.3)',
                                    minWidth: 220,
                                    '&:hover': {
                                        bgcolor: theme.palette.primary.dark,
                                        boxShadow: '0px 6px 14px rgba(25, 118, 210, 0.4)',
                                    },
                                    '&:disabled': {
                                        bgcolor: '#ccc',
                                        boxShadow: 'none'
                                    }
                                }}
                                onClick={handleSubmit}
                            >
                                {isProfileSaving ? 'Saving...' : 'Complete Registration'}
                            </Button>
                        </Box>
                    </Box>
                </Paper>
            </Box>
        </Box>
    );
};

export default DoctorRegistration;