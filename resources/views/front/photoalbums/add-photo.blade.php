@extends('front.layouts.app')

@section('content')
    <div class="text-center"><h2>{{ $title }}</h2></div>

    <br>
    <div class="job_form">
        <form class="form-horizontal" id="photo-upload-form">
            @csrf
            <div class="form-group">
                <label class="col-lg-3 control-label" for="photoalbum_id">Choose album:</label>
                <div class="col-lg-7">
                    <div class="styled-select styled-select-4">
                        <select name="photoalbum_id" id="photoalbum_id">
                            @foreach ($albums as $album)
                                <option value="{{ $album->id }}">{{ $album->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div id="photo-upload-actions" class="marginTop20 center_text" style="margin-bottom:30px">
                    <button id="pickfiles" type="button" class="save-button">Add photo</button>
                    <button id="uploadfiles" type="button" class="save-button">Upload photo</button>
                    <input id="photo-files" type="file" accept="image/jpeg,image/png,image/gif,.jpg,.jpeg,.png,.gif" multiple style="display:none">
                </div>
            </div>
        </form>
    </div>

    <div id="photo-upload-status" class="photo-upload-status">Select one or more photos.</div>
    <div id="filelist" class="photo-upload-list"></div>

    <br>
@endsection

@push('scripts')
    <script>
        window.photoUploadUrl = '{{ route('front.ajax.handle', ['action' => 'add_photo_ajax']) }}';
        window.photoAlbumRedirectBase = '{{ url('/photoalbums') }}';
        selectAction();
    </script>
    <script src="{{ asset('frontend/js/photo-upload.js') }}"></script>
@endpush
