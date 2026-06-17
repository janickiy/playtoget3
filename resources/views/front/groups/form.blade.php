@extends('front.layouts.app')

@section('content')
    @php($showFormTabs = (bool) ($canEditSettings ?? false))
    @php($isCommunityOwner = (int) ($role ?? 0) === 1)

    <div class="content-groups friends">
        @if ($group)
            @include('front.groups._top')
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
                <div @if($showFormTabs) id="tabs" @endif @class(['community-form-tabs' => $showFormTabs, 'group-form-tabs' => $showFormTabs, 'community-create-form-panel' => ! $showFormTabs])>
                    @if ($showFormTabs)
                        <ul>
                            <li><a href="#info">Information</a></li>
                            <li><a href="#administrators">Administrators</a></li>
                            <li><a href="#privacy">Privacy</a></li>
                            <li><a href="#blacklist">Blacklist</a></li>
                        </ul>
                    @endif

                    <div id="info">
                        <div class="text-center"><h2>Information</h2></div>
                        <br>
                        <div class="form-group">
                            <label class="col-lg-3 control-label" for="name">Name</label>
                            <div class="col-lg-6">
                                <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $group?->name) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label" for="about">Description</label>
                            <div class="col-lg-6">
                                <textarea class="form-control form-dark" id="about" rows="4" name="about">{{ old('about', $group?->about) }}</textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label" for="place">Place</label>
                            <div class="col-lg-6">
                                <input type="hidden" name="id_place" value="{{ old('id_place') }}" class="id_place" data-type="search_city">
                                <input autocomplete="off" class="form-control search_word text-place border-top-none" type="text" value="{{ old('place', $group?->place) }}" name="place" data-type="search_city">
                                <div class="select-place" data-type="search_city"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label" for="sport">Sport type</label>
                            <div class="col-lg-6">
                                <input type="hidden" name="id_sport" class="id_place" value="{{ old('id_sport') }}" data-type="search_sport">
                                <input autocomplete="off" class="form-control search_word text-place border-top-none" type="text" value="{{ old('sport', $group?->sport_type) }}" name="sport" data-type="search_sport">
                                <div class="select-place" data-type="search_sport"></div>
                            </div>
                        </div>
                        <div class="form-group group-form-images">
                            <div class="col-sm-6 group-form-image-field">
                                <img id="preview_ava" src="{{ $group ? $groupData['avatar'] : asset('frontend/images/noimage.png') }}" alt="">
                                <div class="file_upload group-file-upload">
                                    <button type="button">Upload avatar</button>
                                    <input class="group-avatar-input" type="file" name="avatar_file" accept="image/jpeg,image/png,image/gif">
                                </div>
                            </div>
                            <div class="col-sm-6 group-form-image-field">
                                <img id="preview_cover" src="{{ $group ? $groupData['cover'] : asset('frontend/images/default_group.png') }}" alt="">
                                <div class="file_upload group-file-upload">
                                    <button type="button">Upload cover</button>
                                    <input class="group-cover-input" type="file" name="cover_file" accept="image/jpeg,image/png,image/gif">
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($showFormTabs)
                        <div id="administrators">
                            <div class="text-center"><h2>Administrators</h2></div>
                            @if ($isCommunityOwner)
                                <button type="button" class="community-admin-add-open js-community-admin-open" data-community-id="{{ $group->id }}">
                                    Add administrator
                                </button>
                            @endif
                            <div class="possible-friend">
                                @forelse ($admins as $member)
                                    <div class="col-xs-6 possible-friend-cart" data-user-id="{{ $member['id'] }}">
                                        <a class="possible-avatar" href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><img src="{{ $member['avatar'] }}" alt=""></a>
                                        <a href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><h5><strong>{{ $member['name'] }}</strong></h5></a>
                                        <p>{{ $member['city'] }}</p>
                                        @if ($isCommunityOwner)
                                            <div class="community-card-actions">
                                                <button type="button" class="community-card-action community-card-action-danger js-community-member-action"
                                                        data-action="remove_community_admin"
                                                        data-community-id="{{ $group->id }}"
                                                        data-user-id="{{ $member['id'] }}"
                                                        data-confirm="Remove this group administrator?"
                                                        data-success="Administrator removed">
                                                    Remove administrator
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <p class="no_message">No administrators yet.</p>
                                @endforelse
                            </div>
                        </div>

                        <div id="privacy">
                            <div class="text-center"><h2>Privacy</h2></div>
                            <br>
                            @php($wall = old('community.permission_wall', $settings?->permission_wall ?? 0))
                            @php($photo = old('community.permission_photo', $settings?->permission_photo ?? 0))
                            @php($video = old('community.permission_video', $settings?->permission_video ?? 0))
                            @php($type = old('community.type', $settings?->type ?? 0))
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Feed</label>
                                <div class="col-lg-7">
                                    <div class="styled-select styled-select-4">
                                        <select class="form-control form-primary" name="community[permission_wall]">
                                            <option value="0" @selected((int)$wall === 0)>Open</option>
                                            <option value="1" @selected((int)$wall === 1)>Disabled</option>
                                            <option value="2" @selected((int)$wall === 2)>Members only</option>
                                            <option value="3" @selected((int)$wall === 3)>Administration only</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Photos</label>
                                <div class="col-lg-7">
                                    <div class="styled-select styled-select-4">
                                        <select class="form-control form-primary" name="community[permission_photo]">
                                            <option value="0" @selected((int)$photo === 0)>Open</option>
                                            <option value="1" @selected((int)$photo === 1)>Disabled</option>
                                            <option value="2" @selected((int)$photo === 2)>Members only</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Video</label>
                                <div class="col-lg-7">
                                    <div class="styled-select styled-select-4">
                                        <select class="form-control form-primary" name="community[permission_video]">
                                            <option value="0" @selected((int)$video === 0)>Open</option>
                                            <option value="1" @selected((int)$video === 1)>Disabled</option>
                                            <option value="2" @selected((int)$video === 2)>Members only</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Type group</label>
                                <div class="col-lg-7">
                                    <div class="styled-select styled-select-4">
                                        <select class="form-control form-primary" name="community[type]">
                                            <option value="0" @selected((int)$type === 0)>Open</option>
                                            <option value="1" @selected((int)$type === 1)>Closed</option>
                                            <option value="2" @selected((int)$type === 2)>Private</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="blacklist">
                            <div class="text-center"><h2>Blacklist</h2></div>
                            <div class="possible-friend">
                                @forelse ($blocked as $member)
                                    <div class="col-xs-6 possible-friend-cart" data-user-id="{{ $member['id'] }}">
                                        <a class="possible-avatar" href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><img src="{{ $member['avatar'] }}" alt=""></a>
                                        <a href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><h5><strong>{{ $member['name'] }}</strong></h5></a>
                                        <p>{{ $member['city'] }}</p>
                                        <div class="community-card-actions">
                                            <button type="button" class="community-card-action js-community-member-action"
                                                    data-action="unblock_community_member"
                                                    data-community-id="{{ $group->id }}"
                                                    data-user-id="{{ $member['id'] }}"
                                                    data-confirm="Remove this user from the group blacklist?"
                                                    data-success="User removed from blacklist">
                                                Remove from list
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <p class="no_message">Blacklist is empty.</p>
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

        @if ($showFormTabs)
            @include('front.communities._manage-assets')
        @endif
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

        .group-form-tabs .form-group .col-lg-6,
        .community-create-form-panel .form-group .col-lg-6 {
            position: relative;
        }

        .group-form-tabs .form-group .select-place,
        .community-create-form-panel .form-group .select-place {
            border-radius: 0 0 5px 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
            left: 0;
            max-height: 240px;
            top: 36px;
            width: 100%;
            z-index: 200;
        }

        .group-form-tabs .form-group .select-place .place-item,
        .community-create-form-panel .form-group .select-place .place-item {
            font-size: 18px;
            line-height: 30px;
            padding: 6px 10px;
        }

        .group-form-images {
            margin-top: 18px;
        }

        .group-form-image-field {
            text-align: center;
        }

        .group-form-image-field img {
            display: block;
            width: 200px;
            height: 200px;
            object-fit: cover;
            margin: 0 auto 10px;
            border-radius: 5px;
            border: 1px solid #eaebed;
            background: #eef5f5;
        }

        .group-form-image-field #preview_cover {
            height: 77px;
        }

        .group-file-upload {
            margin: 0 auto;
        }

        .group-file-upload > button {
            width: 145px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/search.js') }}"></script>
    <script>
        if (typeof selectAction === 'function') {
            selectAction();
        }

        (function () {
            function bindGroupPreview(inputSelector, imageSelector) {
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

            bindGroupPreview('.group-avatar-input', '#preview_ava');
            bindGroupPreview('.group-cover-input', '#preview_cover');
        })();
    </script>
@endpush
