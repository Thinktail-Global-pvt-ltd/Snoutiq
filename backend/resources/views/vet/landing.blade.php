await axios.post('/api/groomer/bookings', {
  customer_type: 'groomer',     // 'walkin' | 'groomer' | 'user'
  customer_id: 345,
  customer_pet_id: 789,
  date: '2025-10-02',
  start_time: '10:00',
  end_time: '11:00',
  services: [
    { service_id: 3, price: 1200 },
    { service_id: 9, price: 500 }
  ],
  groomer_employees_id: 12,
  user_id: 123                  // IMPORTANT: controller yahi se leta hai
});
toast.success('Booking created');
await loadMonth();
