@extends('layouts.admin-panel')

@section('page-title', 'Excel Export Doctors')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Doctors imported from Excel</h2>
                        <p class="text-muted mb-0">Upload/replace doctor images (file or base64) exactly like VetRegisterationTempController does.</p>
                    </div>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($doctors->count()) }} total</span>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($doctors->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-clipboard2-pulse display-6 d-block mb-2"></i>
                        <p class="mb-0">No doctors with exported_from_excell = 1.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Doctor</th>
                                    <th scope="col">Clinic</th>
                                    <th scope="col">Current Photo</th>
                                    <th scope="col" class="text-end">Upload / Replace</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($doctors as $doctor)
                                    @php $clinic = $doctor->clinic; @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $doctor->doctor_name ?? 'Unnamed' }}</div>
                                            <div class="small text-muted">ID: {{ $doctor->id }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $clinic->name ?? '—' }}</div>
                                            <div class="small text-muted">{{ $clinic->city ?? '—' }} @if(!empty($clinic?->pincode)) • {{ $clinic->pincode }} @endif</div>
                                        </td>
                                        <td style="width:160px">
                                            @if($doctor->doctor_image)
                                                <img src="/{{ $doctor->doctor_image }}" class="img-fluid rounded border" alt="doctor photo">
                                            @else
                                                <span class="text-muted small">No image</span>
                                            @endif
                                        </td>
                                        <td class="text-end" style="width:300px">
                                            <form action="{{ route('admin.doctors.image', $doctor) }}" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                                                @csrf
                                                <div class="form-text text-start">Upload file <em>or</em> paste base64 data URI:</div>
                                                <input type="file" name="doctor_image" accept="image/*" class="form-control">
                                                <textarea name="doctor_image" class="form-control" rows="2" placeholder="data:image/png;base64,..."></textarea>
                                                <button type="submit" class="btn btn-sm btn-primary">Save image</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
