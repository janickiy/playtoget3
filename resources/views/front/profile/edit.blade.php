@extends('front.layouts.app')

@php
    $permissionOptions = [
        0 => __('profile.settings.privacy.options.everyone'),
        1 => __('profile.settings.privacy.options.friends'),
        2 => __('profile.settings.privacy.options.nobody'),
    ];
    $limitedPermissionOptions = [
        0 => __('profile.settings.privacy.options.everyone'),
        1 => __('profile.settings.privacy.options.friends'),
    ];
    $limitedPermissionFields = [
        'permission_send_message' => true,
        'permission_view_profile' => true,
    ];

    $contactFields = [
        'contact_email' => ['label' => __('profile.settings.contacts.contact_email'), 'type' => 'email'],
        'phone' => ['label' => __('profile.settings.contacts.phone'), 'type' => 'text'],
        'telegram' => ['label' => __('profile.settings.contacts.telegram'), 'type' => 'text'],
        'whatsapp' => ['label' => __('profile.settings.contacts.whatsapp'), 'type' => 'text'],
        'viber' => ['label' => __('profile.settings.contacts.viber'), 'type' => 'text'],
        'website' => ['label' => __('profile.settings.contacts.website'), 'type' => 'text'],
    ];

    $profileFields = [
        'nickname' => ['label' => __('profile.settings.profile.fields.nickname'), 'type' => 'text'],
        'firstname' => ['label' => __('profile.settings.profile.fields.firstname'), 'type' => 'text'],
        'lastname' => ['label' => __('profile.settings.profile.fields.lastname'), 'type' => 'text'],
        'birthday' => ['label' => __('profile.settings.profile.fields.birthday'), 'type' => 'date'],
        'country' => ['label' => __('profile.settings.profile.fields.country'), 'type' => 'text'],
        'region' => ['label' => __('profile.settings.profile.fields.region'), 'type' => 'text'],
    ];

    $notificationChecked = static function (string $field) use ($settings): bool {
        if (old('user') !== null) {
            return old("user.{$field}") !== null;
        }

        return ($settings->{$field} ?? 'yes') === 'yes';
    };
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/jquery-ui-1.8.16.custom.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/jquery.Jcrop.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/profile-settings.css') }}?v=2026062004">
@endpush

@section('content')
    @if (session('status'))
        <div class="save_window_ok">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="save_window_fail">{{ __('profile.settings.form_error') }}</div>
    @endif

    <div class="friends profile-edit-page">
        <div class="photo-caption">
            <h3>{{ __('profile.settings.title') }}</h3>
        </div>

        <div class="job_form">
            <form
                id="profile-settings-form"
                class="form-horizontal"
                autocomplete="off"
                method="POST"
                action="{{ route('front.profile.update') }}"
                enctype="multipart/form-data"
            >
                @csrf

                <input id="profile-settings-active-tab" type="hidden" name="active_tab" value="{{ old('active_tab', 'profile') }}">
                <input id="profile-avatar-file" type="hidden" name="file_ava" value="{{ old('file_ava') }}">
                <input id="profile-cover-file" type="hidden" name="file_cover" value="{{ old('file_cover') }}">
                <input id="profile-cover-input" class="profile-asset-input" type="file" accept="image/jpeg,image/png">

                @error('file_ava')
                    <div class="settings-field-error">{{ $message }}</div>
                @enderror

                @error('file_cover')
                    <div class="settings-field-error">{{ $message }}</div>
                @enderror

                @error('cover')
                    <div class="settings-field-error">{{ $message }}</div>
                @enderror

                <div id="tabs" class="six">
                    <ul>
                        <li><a href="#profile">{{ __('profile.settings.tabs.profile') }}</a></li>
                        <li><a href="#contacts">{{ __('profile.settings.tabs.contact') }}</a></li>
                        <li><a href="#privacy">{{ __('profile.settings.tabs.privacy') }}</a></li>
                        <li><a href="#notifications">{{ __('profile.settings.tabs.notifications') }}</a></li>
                        <li><a href="#security">{{ __('profile.settings.tabs.security') }}</a></li>
                        <li><a href="#blacklist">{{ __('profile.settings.tabs.blacklist') }}</a></li>
                    </ul>

                    <div id="profile">
                        <div class="photo-caption marginTopNone">
                            <h3>{{ __('profile.settings.profile.title') }}</h3>
                        </div>
                        <p>{{ __('profile.settings.profile.description') }}</p>

                        @foreach ($profileFields as $field => $meta)
                            @php
                                $profileValue = old("profile.{$field}", $user->{$field});
                                if ($field === 'birthday' && $profileValue instanceof \Carbon\CarbonInterface) {
                                    $profileValue = $profileValue->format('Y-m-d');
                                }
                            @endphp
                            <div class="form-group">
                                <label for="profile-basic-{{ $field }}" class="col-sm-4 control-label">{{ $meta['label'] }}</label>
                                <div class="col-sm-8">
                                    <input
                                        id="profile-basic-{{ $field }}"
                                        class="form-control"
                                        type="{{ $meta['type'] }}"
                                        name="profile[{{ $field }}]"
                                        value="{{ $profileValue }}"
                                    >
                                    @error("profile.{$field}")
                                        <div class="settings-field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach

                        <div class="form-group">
                            <label for="profile-basic-sex" class="col-sm-4 control-label">{{ __('profile.settings.profile.fields.sex') }}</label>
                            <div class="col-sm-8">
                                <select id="profile-basic-sex" class="form-control" name="profile[sex]">
                                    <option value="male" @selected(old('profile.sex', $user->sex) === 'male')>
                                        {{ __('profile.settings.profile.sex.male') }}
                                    </option>
                                    <option value="female" @selected(old('profile.sex', $user->sex) === 'female')>
                                        {{ __('profile.settings.profile.sex.female') }}
                                    </option>
                                </select>
                                @error('profile.sex')
                                    <div class="settings-field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group profile-about-group">
                            <label for="profile-basic-about" class="col-sm-4 control-label">{{ __('profile.settings.profile.fields.about') }}</label>
                            <div class="col-sm-8">
                                <textarea id="profile-basic-about" class="form-control" name="profile[about]" rows="5" maxlength="120">{{ old('profile.about', $user->about) }}</textarea>
                                @error('profile.about')
                                    <div class="settings-field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group profile-about-sport-group">
                            <label for="profile-basic-about-sport" class="col-sm-4 control-label">{{ __('profile.settings.profile.fields.about_sport') }}</label>
                            <div class="col-sm-8">
                                <textarea id="profile-basic-about-sport" class="form-control" name="profile[about_sport]" rows="5">{{ old('profile.about_sport', $user->about_sport) }}</textarea>
                                @error('profile.about_sport')
                                    <div class="settings-field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div id="contacts">
                        <div class="photo-caption marginTopNone">
                            <h3>{{ __('profile.settings.contact.title') }}</h3>
                        </div>
                        <p>{{ __('profile.settings.contact.description') }}</p>

                        @foreach ($contactFields as $field => $meta)
                            <div class="form-group">
                                <label for="profile-{{ $field }}" class="col-sm-4 control-label">{{ $meta['label'] }}</label>
                                <div class="col-sm-8">
                                    <input
                                        id="profile-{{ $field }}"
                                        class="form-control"
                                        type="{{ $meta['type'] }}"
                                        name="user[{{ $field }}]"
                                        value="{{ old("user.{$field}", $user->{$field}) }}"
                                    >
                                    @error("user.{$field}")
                                        <div class="settings-field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="privacy">
                        <div class="photo-caption marginTopNone">
                            <h3>{{ __('profile.settings.privacy.title') }}</h3>
                        </div>
                        <p>{{ __('profile.settings.privacy.description') }}</p>

                        @foreach ($permissionFields as $field => $label)
                            @php
                                $currentPermissionValue = old("user.{$field}", $settings->{$field} ?? 0);
                                if (($limitedPermissionFields[$field] ?? false) && (int) $currentPermissionValue > 1) {
                                    $currentPermissionValue = 1;
                                }
                            @endphp
                            <div class="form-group">
                                <label for="profile-{{ $field }}" class="col-sm-7 control-label">{{ $label }}</label>
                                <div class="col-sm-5">
                                    <select id="profile-{{ $field }}" class="form-control" name="user[{{ $field }}]">
                                        @foreach (($limitedPermissionFields[$field] ?? false) ? $limitedPermissionOptions : $permissionOptions as $value => $text)
                                            <option
                                                value="{{ $value }}"
                                                @selected((string) $currentPermissionValue === (string) $value)
                                            >
                                                {{ $text }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("user.{$field}")
                                        <div class="settings-field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="notifications">
                        <div class="photo-caption marginTopNone">
                            <h3>{{ __('profile.settings.notifications.title') }}</h3>
                        </div>
                        <p>{{ __('profile.settings.notifications.description') }}</p>

                        @foreach ($notificationFields as $field => $label)
                            <div class="form-group notification-row">
                                <label for="profile-{{ $field }}" class="col-sm-8 control-label">{{ $label }}</label>
                                <div class="col-sm-4">
                                    <input
                                        id="profile-{{ $field }}"
                                        type="checkbox"
                                        name="user[{{ $field }}]"
                                        value="yes"
                                        @checked($notificationChecked($field))
                                    >
                                    <label class="checkbox-label" for="profile-{{ $field }}"></label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="security">
                        <div class="photo-caption marginTopNone">
                            <h3>{{ __('profile.settings.security.title') }}</h3>
                        </div>
                        <p>{{ __('profile.settings.security.description', ['ip' => request()->ip()]) }}</p>

                        <div class="settings-log-list">
                            @forelse ($securityLogs as $log)
                                <div class="settings-log-row">
                                    <span>{{ __('profile.settings.security.ip') }}: {{ $log['ip'] ?: __('profile.settings.security.not_detected') }}</span>
                                    <span>{{ __('profile.settings.security.os') }}: {{ $log['os'] }}</span>
                                    <span>{{ __('profile.settings.security.browser') }}: {{ $log['browser'] }}</span>
                                    <span>{{ __('profile.settings.security.time') }}: {{ $log['time'] ?: __('profile.settings.security.not_detected') }}</span>
                                </div>
                            @empty
                                <p class="settings-empty">{{ __('profile.settings.security.empty') }}</p>
                            @endforelse
                        </div>

                    </div>

                    <div id="blacklist">
                        <div class="photo-caption marginTopNone">
                            <h3>{{ __('profile.settings.blacklist.title') }}</h3>
                        </div>
                        <p>{{ __('profile.settings.blacklist.description') }}</p>

                        <div class="possible-friend my-friend">
                            @forelse ($blockedUsers as $blockedUser)
                                <div class="col-xs-6 possible-friend-cart" data-num="{{ $blockedUser['id'] }}">
                                    <a class="possible-avatar" href="{{ $blockedUser['url'] }}">
                                        <img src="{{ $blockedUser['avatar'] }}" alt="">
                                    </a>
                                    <div class="possible-info">
                                        <a class="name" href="{{ $blockedUser['url'] }}">{{ $blockedUser['name'] }}</a>
                                    </div>
                                    <button type="button" class="settings-unblock" onclick="remove_black_list({{ $blockedUser['id'] }})">
                                        <img src="{{ asset('frontend/images/icon-krest.png') }}" alt="">
                                    </button>
                                </div>
                            @empty
                                <p class="settings-empty">{{ __('profile.settings.blacklist.empty') }}</p>
                            @endforelse
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>

                <div class="profile-settings button">
                    <button class="save-button" type="submit">{{ __('profile.settings.apply') }}</button>
                </div>
            </form>
            <form id="profile-delete-account-form" class="settings-delete-account-form" method="POST" action="{{ route('front.profile.delete-account.request') }}">
                @csrf
                <button type="submit" class="settings-delete-account" @disabled($deleteAccountPending ?? false)>
                    {{ __('profile.settings.delete_account.button') }}
                </button>
            </form>
        </div>
    </div>

    <div class="overlay_ava avatar-crop-overlay" id="avatar-crop-overlay">
        <div class="avatarUpload avatar-crop-modal" id="avatar-crop-modal" data-type="avatar">
            <button type="button" class="avatar-crop-close" aria-label="{{ __('profile.settings.crop.close') }}">×</button>
            <div class="avatar-crop-inner">
                <div class="page-header">
                    <h3>{{ __('profile.settings.crop.avatar_title') }}</h3>
                </div>
                <div class="loading-bar" id="avatar-crop-loading">
                    <img src="{{ asset('frontend/images/select2-spinner.gif') }}" width="20" alt="">
                </div>
                <div class="file_upload2 avatar-crop-file">
                    <button type="button" id="avatar-select-button">{{ __('profile.settings.crop.choose_file') }}</button>
                    <input id="profile-avatar-input" type="file" accept="image/jpeg,image/png">
                </div>
                <p class="text-show avatar-crop-hint">{{ __('profile.settings.crop.select_area') }}</p>
                <div class="avatar-crop-stage">
                    <img
                        id="avatar-crop-target"
                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=="
                        alt=""
                    >
                </div>
                <form id="avatar-crop-form" autocomplete="off" class="crop">
                    <input type="hidden" id="avatar-crop-x" name="x" value="0">
                    <input type="hidden" id="avatar-crop-y" name="y" value="0">
                    <input type="hidden" id="avatar-crop-w" name="w" value="0">
                    <input type="hidden" id="avatar-crop-h" name="h" value="0">
                    <button type="submit" class="save-button saveAva">{{ __('profile.settings.crop.save') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="overlay_ava avatar-crop-overlay" id="cover-crop-overlay">
        <div class="avatarUpload avatar-crop-modal cover-crop-modal" id="cover-crop-modal" data-type="cover">
            <button type="button" class="avatar-crop-close cover-crop-close" aria-label="{{ __('profile.settings.crop.close') }}">×</button>
            <div class="avatar-crop-inner">
                <div class="page-header">
                    <h3>{{ __('profile.settings.crop.cover_title') }}</h3>
                </div>
                <div class="loading-bar" id="cover-crop-loading">
                    <img src="{{ asset('frontend/images/select2-spinner.gif') }}" width="20" alt="">
                </div>
                <div class="file_upload2 avatar-crop-file">
                    <button type="button" id="cover-select-button">{{ __('profile.settings.crop.choose_file') }}</button>
                </div>
                <p class="text-show avatar-crop-hint">{{ __('profile.settings.crop.select_area') }}</p>
                <div class="avatar-crop-stage cover-crop-stage">
                    <img
                        id="cover-crop-target"
                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=="
                        alt=""
                    >
                </div>
                <form id="cover-crop-form" autocomplete="off" class="crop">
                    <input type="hidden" id="cover-crop-x" name="x" value="0">
                    <input type="hidden" id="cover-crop-y" name="y" value="0">
                    <input type="hidden" id="cover-crop-w" name="w" value="0">
                    <input type="hidden" id="cover-crop-h" name="h" value="0">
                    <button type="submit" class="save-button saveCover">{{ __('profile.settings.crop.save') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('frontend/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery.Jcrop.min.js') }}"></script>
    <script src="{{ asset('frontend/js/profile-settings.js') }}?v=2026062004"></script>
@endpush
