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
          <textarea name="content" id="content-editor" class="form-control" rows="12">{{ old('content',$post->content) }}</textarea>
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
          <select name="categories[]" class="form-select js-select2" multiple data-placeholder="Select categories">
            @foreach($categories as $c)
              <option value="{{ $c->id }}" @selected($post->categories->contains($c->id))>{{ $c->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Tags</label>
          <select name="tags[]" class="form-select js-select2" multiple data-placeholder="Select tags">
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

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
  <style>
    .ck-editor__editable_inline {
      min-height: 320px;
    }
  </style>
@endpush

@push('scripts')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-3fpdp2VwD5yKYeMC3uylmqYAXoTr4a9L5f3XGJ4s3DY=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@ckeditor/ckeditor5-build-classic@41.3.1/build/ckeditor.js"></script>
  <script>
    window.uploadImage = function(input){
      if(!input.files.length) return;
      const data = new FormData();
      data.append('file', input.files[0]);
      fetch('{{ route('admin.upload.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: data
      })
        .then(response => response.json())
        .then(payload => {
          if(payload.success){
            document.querySelector('[name="featured_image"]').value = payload.url;
          } else {
            alert('Upload failed');
          }
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
      ClassicEditor
        .create(document.querySelector('#content-editor'))
        .catch(error => console.error(error));

      $('.js-select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function(){
          return $(this).data('placeholder');
        }
      });
    });
  </script>
@endpush

</div>
@endsection
