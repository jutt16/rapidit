@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">✏️ Edit Page - {{ $page->title }}</h2>
        <a href="{{ route('admin.static-pages.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.static-pages.update', $page->id) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" class="form-control" value="{{ $page->slug }}" disabled>
                    <small class="text-muted">Slug is auto-generated and cannot be changed.</small>
                </div>

                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold">Page Title</label>
                    <input type="text" name="title" class="form-control"
                           value="{{ old('title', $page->title) }}" required>
                </div>

                <div class="mb-3">
                    <label for="editor" class="form-label fw-semibold">Page Content</label>
                    <textarea name="content" id="editor" class="form-control" rows="10" required>
                        {{ old('content', $page->content) }}
                    </textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- CKEditor 5 --}}
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css" />
<script src="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.umd.js"></script>

<script>
    const {
        ClassicEditor,
        Essentials,
        Bold,
        Italic,
        Underline,
        Strikethrough,
        Paragraph,
        Heading,
        Font,
        Link,
        List
    } = CKEDITOR;

    ClassicEditor
        .create(document.querySelector('#editor'), {
            plugins: [
                Essentials, Bold, Italic, Underline, Strikethrough,
                Paragraph, Heading, Font, Link, List
            ],
            toolbar: [
                'undo', 'redo', '|',
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                'link', 'bulletedList', 'numberedList'
            ],
        })
        .catch(error => {
            console.error('CKEditor init error:', error);
        });
</script>
@endsection
