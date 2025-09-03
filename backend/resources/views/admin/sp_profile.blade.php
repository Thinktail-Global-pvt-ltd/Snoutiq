@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Profile') }}</div>

                <div class="card-body">
                 
                <!-- $users -->
        @if(isset($profile))
            
            <table class="table table-bordered">
                <tr>
                    <th>Name</th>
                    <td>{{ $profile->name }}</td>
                </tr>
                <tr>
                    <th>Bio</th>
                    <td>{{ $profile->bio }}</td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td>{{ $profile->address }}</td>
                </tr>
                <tr>
                    <th>Coordinates</th>
                    <td>{{ $profile->coordinates }}</td>
                </tr>
                <tr>
                    <th>City</th>
                    <td>{{ $profile->city }}</td>
                </tr>
                <tr>
                    <th>Pincode</th>
                    <td>{{ $profile->pincode }}</td>
                </tr>
                <tr>
                    <th>Working Hours</th>
                    <td>{{ $profile->working_hours }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $profile->status }}</td>
                </tr>
                <tr>
                    <th>In-home Grooming Services</th>
                    <td>{{ $profile->inhome_grooming_services ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>License No</th>
                    <td>{{ $profile->license_no }}</td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td>{{ $profile->type }}</td>
                </tr>
            </table>
        @else
            <p>No profile data available.</p>
        @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
