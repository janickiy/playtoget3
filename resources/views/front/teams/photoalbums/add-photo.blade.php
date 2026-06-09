@extends('front.layouts.app')

@section('content')
    @php
        $communityView = $communityView ?? [
            'kind' => 'team',
            'route' => 'front.teams',
            'top' => 'front.teams._top',
            'entity' => $team,
        ];
        $community = $communityView['entity'] ?? $team;
        $communityKind = $communityView['kind'];
        $photoRedirectBase = $communityView['basePath'] ?? url('/' . ($communityKind === 'group' ? 'groups' : 'teams') . '/' . $community->id . '/photoalbums');
    @endphp
    <div class="content-groups friends">
        @include($communityView['top'])

        <center><h2>{{ $title }}</h2></center>

        <br>
        <div class="job_form">
            <form class="form-horizontal" id="photo-upload-form">
                @csrf
                <div class="form-group">
                    <label class="col-lg-3 control-label" for="photoalbum_id">Выберите альбом:</label>
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
                        <a id="pickfiles" href="javascript:;" class="save-button">Добавить файлы</a>
                        <a id="uploadfiles" href="javascript:;" class="save-button">Загрузить файлы</a>
                        <input id="photo-files" type="file" accept="image/jpeg,image/png,image/gif" multiple style="display:none">
                    </div>
                </div>
            </form>
        </div>

        <div id="photo-upload-status" class="photo-upload-status">Выберите одну или несколько фотографий.</div>
        <div id="filelist" class="photo-upload-list"></div>

        <br>
    </div>
@endsection

@push('scripts')
    <script>
        window.photoUploadUrl = '{{ route('front.ajax.handle', ['action' => 'add_photo_ajax']) }}';
        window.photoAlbumRedirectBase = '{{ $photoRedirectBase }}';
        window.photoalbumableType = '{{ $communityKind }}';
        selectAction();
    </script>
    <script src="{{ asset('frontend/js/photo-upload.js') }}"></script>
@endpush
