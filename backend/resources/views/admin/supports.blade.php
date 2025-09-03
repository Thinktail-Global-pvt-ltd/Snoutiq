@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('supports') }}</div>

                <div class="card-body table-responsive">

                    @if($supports->isNotEmpty())
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    {{-- Dynamic headers from first booking --}}
                                    @foreach(array_keys($supports->first()->toArray()) as $key)
                                        <th>{{ ucfirst($key) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($supports as $booking)
                                    <tr>
                                        @foreach($booking->toArray() as $key => $value)
                                            <td>
                                                {{-- Handle arrays/relations --}}
                                                @if(is_array($value))
                                                    <pre>{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>No supports found.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
