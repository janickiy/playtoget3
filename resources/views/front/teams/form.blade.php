@extends('front.layouts.app')

@section('content')
    @php($showFormTabs = (bool) ($canEditSettings ?? false))

    <div class="content-groups friends">
        @if ($team)
            @include('front.teams._top')
        @endif

        <div class="photo-caption">
            <h3>{{ $title }}</h3>
        </div>

        @if ($errors->any())
            <div class="mutations-both">
                <p>{{ $errors->first() }}</p>
                <a class="delete">x</a>
            </div>
        @endif

        <div class="job_form">
            <form class="form-horizontal create_form" method="POST" action="{{ $action }}" enctype="multipart/form-data" autocomplete="off">
                @csrf
                <div @if($showFormTabs) id="tabs" @endif @class(['community-form-tabs' => $showFormTabs, 'team-form-tabs' => $showFormTabs, 'community-create-form-panel' => ! $showFormTabs])>
                    @if ($showFormTabs)
                        <ul>
                            <li><a href="#info">Информация</a></li>
                            <li><a href="#administrators">Администраторы</a></li>
                            <li><a href="#privacy">Приватность</a></li>
                            <li><a href="#blacklist">Черный список</a></li>
                        </ul>
                    @endif

                    <div id="info">
                        <center><h2>Информация</h2></center>
                        <br>
                        <div class="form-group">
                            <label class="col-lg-3 control-label" for="name">Название</label>
                            <div class="col-lg-6">
                                <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $team?->name) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label" for="about">Описание</label>
                            <div class="col-lg-6">
                                <textarea class="form-control form-dark" id="about" rows="4" name="about">{{ old('about', $team?->about) }}</textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label" for="place">Место</label>
                            <div class="col-lg-6">
                                <input type="hidden" name="id_place" value="{{ old('id_place') }}" class="id_place" data-type="search_city">
                                <input autocomplete="off" class="form-control search_word text-place border-top-none" type="text" value="{{ old('place', $team?->place) }}" name="place" data-type="search_city">
                                <div class="select-place" data-type="search_city"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label" for="sport">Вид спорта</label>
                            <div class="col-lg-6">
                                <input type="hidden" name="id_sport" class="id_place" value="{{ old('id_sport') }}" data-type="search_sport">
                                <input autocomplete="off" class="form-control search_word text-place border-top-none" type="text" value="{{ old('sport', $team?->sport_type) }}" name="sport" data-type="search_sport">
                                <div class="select-place" data-type="search_sport"></div>
                            </div>
                        </div>
                        <div class="form-group team-form-images">
                            <div class="col-sm-6 team-form-image-field">
                                <img id="preview_ava" border="0" src="{{ $team ? $teamData['avatar'] : asset('frontend/images/noimage.png') }}" alt="">
                                <div class="file_upload team-file-upload">
                                    <button type="button">Загрузить аватар</button>
                                    <input class="team-avatar-input" type="file" name="avatar_file" accept="image/jpeg,image/png,image/gif">
                                </div>
                            </div>
                            <div class="col-sm-6 team-form-image-field">
                                <img id="preview_cover" border="0" src="{{ $team ? $teamData['cover'] : asset('frontend/images/default_group.png') }}" alt="">
                                <div class="file_upload team-file-upload">
                                    <button type="button">Загрузить обложку</button>
                                    <input class="team-cover-input" type="file" name="cover_file" accept="image/jpeg,image/png,image/gif">
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($showFormTabs)
                        <div id="administrators">
                            <center><h2>Администраторы</h2></center>
                            <div class="possible-friend">
                                @forelse ($admins as $member)
                                    <div class="col-xs-6 possible-friend-cart">
                                        <a class="possible-avatar" href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><img src="{{ $member['avatar'] }}" alt=""></a>
                                        <a href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><h5><strong>{{ $member['name'] }}</strong></h5></a>
                                        <p>{{ $member['city'] }}</p>
                                    </div>
                                @empty
                                    <p class="no_message">Администраторов пока нет.</p>
                                @endforelse
                            </div>
                        </div>

                        <div id="privacy">
                            <center><h2>Приватность</h2></center>
                            <br>
                            @php($wall = old('community.permission_wall', $settings?->permission_wall ?? 0))
                            @php($photo = old('community.permission_photo', $settings?->permission_photo ?? 0))
                            @php($video = old('community.permission_video', $settings?->permission_video ?? 0))
                            @php($type = old('community.type', $settings?->type ?? 0))
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Лента</label>
                                <div class="col-lg-7">
                                    <div class="styled-select styled-select-4">
                                        <select class="form-control form-primary" name="community[permission_wall]">
                                            <option value="0" @selected((int)$wall === 0)>Открыта</option>
                                            <option value="1" @selected((int)$wall === 1)>Отключена</option>
                                            <option value="2" @selected((int)$wall === 2)>Только участники</option>
                                            <option value="3" @selected((int)$wall === 3)>Только администрация</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Фотографии</label>
                                <div class="col-lg-7">
                                    <div class="styled-select styled-select-4">
                                        <select class="form-control form-primary" name="community[permission_photo]">
                                            <option value="0" @selected((int)$photo === 0)>Открыты</option>
                                            <option value="1" @selected((int)$photo === 1)>Отключены</option>
                                            <option value="2" @selected((int)$photo === 2)>Только участники</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Видео</label>
                                <div class="col-lg-7">
                                    <div class="styled-select styled-select-4">
                                        <select class="form-control form-primary" name="community[permission_video]">
                                            <option value="0" @selected((int)$video === 0)>Открыто</option>
                                            <option value="1" @selected((int)$video === 1)>Отключено</option>
                                            <option value="2" @selected((int)$video === 2)>Только участники</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Тип команды</label>
                                <div class="col-lg-7">
                                    <div class="styled-select styled-select-4">
                                        <select class="form-control form-primary" name="community[type]">
                                            <option value="0" @selected((int)$type === 0)>Открытая</option>
                                            <option value="1" @selected((int)$type === 1)>Закрытая</option>
                                            <option value="2" @selected((int)$type === 2)>Приватная</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="blacklist">
                            <center><h2>Черный список</h2></center>
                            <div class="possible-friend">
                                @forelse ($blocked as $member)
                                    <div class="col-xs-6 possible-friend-cart">
                                        <a class="possible-avatar" href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><img src="{{ $member['avatar'] }}" alt=""></a>
                                        <a href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><h5><strong>{{ $member['name'] }}</strong></h5></a>
                                        <p>{{ $member['city'] }}</p>
                                    </div>
                                @empty
                                    <p class="no_message">Черный список пуст.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>

                <div class="form-group center_text">
                    <input class="btn-form save-button" type="submit" value="{{ $button }}">
                </div>
            </form>
        </div>
    </div>
@endsection

@if ($canEditSettings)
    @include('front.communities._form-tabs-assets')
@endif

@push('styles')
    <style>
        .community-create-form-panel {
            padding-top: 10px;
        }

        .team-form-images {
            margin-top: 18px;
        }

        .team-form-image-field {
            text-align: center;
        }

        .team-form-image-field img {
            display: block;
            width: 200px;
            height: 200px;
            object-fit: cover;
            margin: 0 auto 10px;
            border-radius: 5px;
            border: 1px solid #eaebed;
            background: #eef5f5;
        }

        .team-form-image-field #preview_cover {
            height: 77px;
        }

        .team-file-upload {
            margin: 0 auto;
        }

        .team-file-upload > button {
            width: 145px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        if (typeof selectAction === 'function') {
            selectAction();
        }

        (function () {
            function bindTeamPreview(inputSelector, imageSelector) {
                const input = document.querySelector(inputSelector);
                const image = document.querySelector(imageSelector);

                if (!input || !image) {
                    return;
                }

                input.addEventListener('change', function () {
                    const file = input.files && input.files[0];

                    if (!file || !file.type.match(/^image\//)) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (event) {
                        image.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                });
            }

            bindTeamPreview('.team-avatar-input', '#preview_ava');
            bindTeamPreview('.team-cover-input', '#preview_cover');
        })();
    </script>
@endpush
