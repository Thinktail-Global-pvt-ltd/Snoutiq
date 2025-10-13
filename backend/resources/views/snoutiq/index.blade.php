@extends('snoutiq.layout')

@section('content')
  <h2>Welcome</h2>
  <p>Use these simple views to test SnoutIQ flows.</p>
  <ul>
    <li><a href="{{ route('snoutiq.dev.booking') }}">Booking Tester</a> — create bookings, fetch details, update status, rate provider</li>
  </ul>
@endsection

