@extends('layouts.app')
@section('content')
<div class="container py-4">
  <h1 class="h3 fw-bold mb-3">{{ $post->exists ? 'Edit' : 'New' }} Post</h1>

  <form method="POST" action="{{ $post->exists ? route('admin.posts.update',$post) : route('admin.posts.store') }}">
    @csrf @if($post->exists) @method('PUT') @endif

    <div class="row g-4">
      <div class="col-md-8">
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input name="title" class="form-control" value="{{ old('title',$post->title) }}" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Slug (optional)</label>
          <input name="slug" class="form-control" value="{{ old('slug',$post->slug) }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Excerpt</label>
          <textarea name="excerpt" class="form-control" rows="3">{{ old('excerpt',$post->excerpt) }}</textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Content</label>
          <textarea name="content" class="form-control" rows="12">{{ old('content',$post->content) }}</textarea>
        </div>
      </div>

      <div class="col-md-4">
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="draft"     @selected(old('status',$post->status)=='draft')>Draft</option>
            <option value="published" @selected(old('status',$post->status)=='published')>Published</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Featured Image URL</label>
          <input name="featured_image" class="form-control" value="{{ old('featured_image',$post->featured_image) }}">
          <input type="file" id="upload" class="form-control mt-2" onchange="uploadImage(this)">
        </div>

        <div class="mb-3">
          <label class="form-label">Categories</label>
          <select name="categories[]" class="form-select" multiple size="6">
            @foreach($categories as $c)
              <option value="{{ $c->id }}" @selected($post->categories->contains($c->id))>{{ $c->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Tags</label>
          <select name="tags[]" class="form-select" multiple size="6">
            @foreach($tags as $t)
              <option value="{{ $t->id }}" @selected($post->tags->contains($t->id))>{{ $t->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Meta Title</label>
          <input name="meta_title" class="form-control" value="{{ old('meta_title',$post->meta_title) }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Meta Description</label>
          <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description',$post->meta_description) }}</textarea>
        </div>
      </div>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary">Save</button>
      <a href="{{ route('admin.posts.index') }}" class="btn btn-link">Cancel</a>
    </div>
  </form>

  <script>
  function uploadImage(input){
    if(!input.files.length) return;
    const data = new FormData();
    data.append('file', input.files[0]);
    fetch('{{ route('admin.upload.store') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:data})
      .then(r=>r.json()).then(j=>{
        if(j.success){ document.querySelector('[name="featured_image"]').value = j.url; }
        else alert('Upload failed');
      });
  }
  </script>
</div>
@endsection
