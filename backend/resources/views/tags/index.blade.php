@extends('layouts.app')
@section('content')
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Tags</h1>
    <a href="{{ route('admin.tags.create') }}" class="btn btn-primary">New</a>
  </div>

  <div class="card shadow-soft">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-start">Name</th>
            <th class="text-center">Slug</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($tags as $t)
          <tr>
            <td class="text-start fw-medium">{{ $t->name }}</td>
            <td class="text-center"><code class="text-muted">{{ $t->slug }}</code></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary me-2" href="{{ route('admin.tags.edit',$t) }}">Edit</a>
              <form class="d-inline" method="POST" action="{{ route('admin.tags.destroy',$t) }}" onsubmit="return confirm('Delete?')">
                @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">{{ $tags->links('pagination::bootstrap-5') }}</div>
</div>
@endsection

