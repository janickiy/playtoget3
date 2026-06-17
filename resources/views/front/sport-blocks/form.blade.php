@extends('front.layouts.app')

@php use App\Helpers\FrontAssets; @endphp

@section('content')
    <div class="photo-caption">
        <h3>{{ $title }}</h3>
    </div>

    <div class="job_form">
        <form autocomplete="off" class="form-horizontal create_form" enctype="multipart/form-data" method="post" action="{{ $action }}">
            @csrf

            <div class="form-group">
                <label class="col-lg-3 control-label" for="name">Name</label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" name="name" value="{{ old('name', $sportBlock?->name) }}">
                    @error('name')<label class="error_label" name="name">{{ $message }}</label>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label" for="about">Description</label>
                <div class="col-lg-6">
                    <textarea class="form-control form-dark" name="about" rows="4">{{ old('about', $sportBlock?->about) }}</textarea>
                    @error('about')<label class="error_label" name="about">{{ $message }}</label>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label" for="place">City</label>
                <div class="col-lg-6">
                    <input type="hidden" name="id_place" value="{{ old('id_place') }}" class="id_place" data-type="search_city">
                    <input autocomplete="off" class="form-control search_word text-place border-top-none" type="text" value="{{ old('place', $sportBlock?->place) }}" name="place" data-type="search_city">
                    <div class="select-place" data-type="search_city"></div>
                    @error('place')<label class="error_label" name="place">{{ $message }}</label>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label" for="address">Address</label>
                <div class="col-lg-6">
                    <textarea class="form-control form-dark" name="address" rows="4">{{ old('address', $sportBlock?->address) }}</textarea>
                    @error('address')<label class="error_label" name="address">{{ $message }}</label>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label" for="phone">Phone</label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" name="phone" value="{{ old('phone', $sportBlock?->phone) }}">
                    @error('phone')<label class="error_label" name="phone">{{ $message }}</label>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label" for="email">Email</label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" name="email" value="{{ old('email', $sportBlock?->email) }}">
                    @error('email')<label class="error_label" name="email">{{ $message }}</label>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label" for="website">Website</label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" name="website" value="{{ old('website', $sportBlock?->website) }}">
                    @error('website')<label class="error_label" name="website">{{ $message }}</label>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label" for="avatar_file"></label>
                <div class="col-lg-6">
                    <img id="preview_ava" width="200" src="{{ $sportBlock ? FrontAssets::sportBlockAvatar($sportBlock) : asset('frontend/images/default_group.png') }}" alt="">
                    <div class="file_upload">
                        <button type="button" id="avatar">Edit photo</button>
                        <input type="file" name="avatar_file" id="avatar_file" accept="image/jpeg,image/png,image/gif">
                    </div>
                    @error('avatar_file')<label class="error_label" name="avatar_file">{{ $message }}</label>@enderror
                </div>
            </div>

            <div class="form-group center_text">
                <input class="btn-form save-button" type="submit" value="{{ $button }}">
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('frontend/js/search.js') }}"></script>
    <script>
        if (typeof selectAction === 'function') {
            selectAction();
        }

        (function () {
            const fileInput = document.getElementById('avatar_file');
            const button = document.getElementById('avatar');
            const preview = document.getElementById('preview_ava');

            if (!fileInput || !button || !preview) {
                return;
            }

            button.addEventListener('click', function () {
                fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                const file = fileInput.files && fileInput.files[0];

                if (!file) {
                    return;
                }

                preview.src = URL.createObjectURL(file);
            });
        })();
    </script>
@endpush
