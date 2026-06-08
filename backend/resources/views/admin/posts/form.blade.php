@extends('layouts.admin-panel')

@section('page-title', $post->exists ? 'Edit Post' : 'New Post')

@section('content')
<div class="container-fluid px-0">
    <!-- Back to Index & Form Title -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.posts.index') }}" class="text-decoration-none">Blog Manager</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $post->exists ? 'Edit Post' : 'Create Post' }}</li>
                </ol>
            </nav>
            <h2 class="h4 mb-0 fw-bold text-dark">{{ $post->exists ? 'Edit Post' : 'Create New Post' }}</h2>
        </div>
        <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary rounded-3 px-3">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>

    <!-- Error/Success Alerts -->
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-3" role="alert">
            <div class="fw-bold mb-2">Please correct the following errors:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 rounded-3 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-3 fs-5 text-success"></i>
            <div>{{ session('ok') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Form Submit -->
    <form method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
        @csrf 
        @if($post->exists) 
            @method('PUT') 
        @endif

        <div class="row g-4">
            <!-- Left Side: Content Fields -->
            <div class="col-lg-8">
                <div class="card admin-card border-0 shadow-sm p-4 mb-4 bg-white">
                    <h5 class="fw-bold mb-4 text-secondary pb-2 border-bottom">Post Content</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Title <span class="text-danger">*</span></label>
                        <input id="title-input" name="title" class="form-control form-control-lg rounded-3 border-secondary-subtle" value="{{ old('title', $post->title) }}" placeholder="Enter catchy title..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Slug <span class="text-muted">(Auto-generated or custom)</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">/blog/</span>
                            <input id="slug-input" name="slug" class="form-control rounded-end-3 border-secondary-subtle" value="{{ old('slug', $post->slug) }}" placeholder="example-slug" data-auto="true">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Excerpt <span class="text-muted">(Short description)</span></label>
                        <textarea name="excerpt" class="form-control rounded-3 border-secondary-subtle" rows="3" placeholder="Brief summary of the post for lists and search results...">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Content <span class="text-danger">*</span></label>
                        <div class="editor-container">
                            <textarea name="content" id="content-editor" class="form-control d-none">{{ old('content', $post->content) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- SEO Fields -->
                <div class="card admin-card border-0 shadow-sm p-4 bg-white">
                    <h5 class="fw-bold mb-4 text-secondary pb-2 border-bottom"><i class="bi bi-search me-2"></i>SEO Settings</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Meta Title</label>
                        <input name="meta_title" class="form-control rounded-3 border-secondary-subtle" value="{{ old('meta_title', $post->meta_title) }}" placeholder="Meta Title for Google search...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Meta Description</label>
                        <textarea name="meta_description" class="form-control rounded-3 border-secondary-subtle" rows="3" placeholder="Meta Description for search snippets...">{{ old('meta_description', $post->meta_description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Right Side: Sidebar Meta -->
            <div class="col-lg-4">
                <!-- Status & Publish -->
                <div class="card admin-card border-0 shadow-sm p-4 mb-4 bg-white">
                    <h5 class="fw-bold mb-3 text-secondary pb-2 border-bottom">Publish</h5>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-dark">Status</label>
                        <select name="status" class="form-select rounded-3 border-secondary-subtle">
                            <option value="draft" @selected(old('status', $post->status) == 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $post->status) == 'published')>Published</option>
                        </select>
                    </div>

                    <button class="btn btn-primary w-100 py-2.5 rounded-3 mb-2 shadow-sm" style="background: linear-gradient(135deg, #2563eb, #7c3aed); border: none;">
                        <i class="bi bi-save me-2"></i>{{ $post->exists ? 'Save Changes' : 'Create Post' }}
                    </button>
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary w-100 py-2.5 rounded-3">
                        Cancel
                    </a>
                </div>

                <!-- Featured Image -->
                <div class="card admin-card border-0 shadow-sm p-4 mb-4 bg-white">
                    <h5 class="fw-bold mb-3 text-secondary pb-2 border-bottom">Featured Image</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Image URL</label>
                        <input id="featured_image_input" name="featured_image" class="form-control rounded-3 border-secondary-subtle" value="{{ old('featured_image', $post->featured_image) }}" placeholder="https://example.com/image.jpg">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Upload Image</label>
                        <input type="file" id="upload-image-btn" class="form-control rounded-3 border-secondary-subtle" onchange="uploadImage(this)">
                        <div class="form-text fs-7">Accepted formats: JPG, PNG, WEBP, GIF (Max 5MB)</div>
                    </div>

                    <!-- Image Preview -->
                    <div class="mt-3 text-center bg-light p-2 rounded-3 border">
                        <img id="featured-image-preview" src="{{ $post->featured_image ?: '#' }}" alt="Featured Image Preview" class="img-fluid rounded-3 border {{ $post->featured_image ? '' : 'd-none' }}" style="max-height: 180px; object-fit: contain;">
                        <div id="no-image-text" class="text-muted py-4 {{ $post->featured_image ? 'd-none' : '' }}">
                            <i class="bi bi-image fs-2 d-block mb-1"></i>
                            No image selected
                        </div>
                    </div>
                </div>

                <!-- Categories & Tags -->
                <div class="card admin-card border-0 shadow-sm p-4 bg-white">
                    <h5 class="fw-bold mb-3 text-secondary pb-2 border-bottom">Taxonomy</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Categories</label>
                        <select name="categories[]" class="form-select js-select2 rounded-3" multiple data-placeholder="Select categories">
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" @selected($post->categories->contains($c->id))>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Tags</label>
                        <select name="tags[]" class="form-select js-select2 rounded-3" multiple data-placeholder="Select tags">
                            @foreach($tags as $t)
                                <option value="{{ $t->id }}" @selected($post->tags->contains($t->id))>#{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
  <style>
    .ck-editor__editable_inline {
      min-height: 380px;
    }
    .ck-editor {
      border-radius: 8px !important;
      overflow: hidden;
    }
    .breadcrumb-item a {
      color: #64748b;
    }
    .breadcrumb-item a:hover {
      color: #2563eb;
    }
  </style>
@endpush

@push('scripts')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-3fpdp2VwD5yKYeMC3uylmqYAXoTr4a9L5f3XGJ4s3DY=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@ckeditor/ckeditor5-build-classic@41.3.1/build/ckeditor.js"></script>
  <script>
    // Image Upload Handler
    window.uploadImage = function(input){
      if(!input.files.length) return;
      
      const file = input.files[0];
      const data = new FormData();
      data.append('file', file);
      
      const uploadBtn = document.getElementById('upload-image-btn');
      uploadBtn.disabled = true;
      
      fetch('{{ route('admin.upload.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: data
      })
      .then(response => response.json())
      .then(payload => {
        uploadBtn.disabled = false;
        if(payload.success){
          document.getElementById('featured_image_input').value = payload.url;
          
          const preview = document.getElementById('featured-image-preview');
          const noImageText = document.getElementById('no-image-text');
          
          preview.src = payload.url;
          preview.classList.remove('d-none');
          noImageText.classList.add('d-none');
        } else {
          alert('Upload failed: ' + (payload.message || 'unknown error'));
        }
      })
      .catch(err => {
        uploadBtn.disabled = false;
        alert('Upload failed: ' + err.message);
      });
    };

    document.addEventListener('DOMContentLoaded', function () {
      // CKEditor initialization
      ClassicEditor
        .create(document.querySelector('#content-editor'), {
            toolbar: [
                'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 
                'blockQuote', 'insertTable', 'undo', 'redo'
            ]
        })
        .catch(error => console.error(error));

      // Select2 initialization
      $('.js-select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function(){
          return $(this).data('placeholder');
        }
      });

      // Slug Auto-generation
      const titleInput = document.getElementById('title-input');
      const slugInput = document.getElementById('slug-input');

      if (titleInput && slugInput) {
        // If slug already has a value (editing), set auto to false
        if (slugInput.value) {
            slugInput.dataset.auto = 'false';
        }

        titleInput.addEventListener('input', function() {
            if (slugInput.dataset.auto === 'true') {
                const slug = titleInput.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '') // remove special chars
                    .replace(/\s+/g, '-')          // replace spaces with -
                    .replace(/-+/g, '-');          // remove multiple -
                
                // Trim leading/trailing hyphens
                let trimmedSlug = slug.replace(/^-+|-+$/g, '');
                slugInput.value = trimmedSlug;
            }
        });

        slugInput.addEventListener('input', function() {
            slugInput.dataset.auto = 'false';
        });
      }

      // Sync Manual Image Input to Preview
      const featuredImageInput = document.getElementById('featured_image_input');
      if (featuredImageInput) {
        featuredImageInput.addEventListener('input', function() {
            const val = featuredImageInput.value.trim();
            const preview = document.getElementById('featured-image-preview');
            const noImageText = document.getElementById('no-image-text');
            
            if (val) {
                preview.src = val;
                preview.classList.remove('d-none');
                noImageText.classList.add('d-none');
            } else {
                preview.src = '#';
                preview.classList.add('d-none');
                noImageText.classList.remove('d-none');
            }
        });
      }
    });
  </script>
@endpush
@endsection
