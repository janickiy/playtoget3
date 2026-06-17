@extends('front.layouts.app')

@php
    $permissionOptions = [
        0 => 'Everyone',
        1 => 'Friends',
        2 => 'Nobody',
    ];
    $limitedPermissionOptions = [
        0 => 'Everyone',
        1 => 'Friends',
    ];
    $limitedPermissionFields = [
        'permission_send_message' => true,
        'permission_view_profile' => true,
    ];

    $contactFields = [
        'contact_email' => ['label' => 'Email', 'type' => 'email'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'telegram' => ['label' => 'Telegram', 'type' => 'text'],
        'whatsapp' => ['label' => 'WhatsApp', 'type' => 'text'],
        'viber' => ['label' => 'Viber', 'type' => 'text'],
        'website' => ['label' => 'Website', 'type' => 'text'],
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
    <link rel="stylesheet" href="{{ asset('frontend/css/profile-settings.css') }}">
@endpush

@section('content')
    @if (session('status'))
        <div class="save_window_ok">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="save_window_fail">Check the form fields</div>
    @endif

    <div class="friends profile-edit-page">
        <div class="photo-caption">
            <h3>Settings</h3>
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

                <div id="tabs" class="five">
                    <ul>
                        <li><a href="#contacts">Contact</a></li>
                        <li><a href="#privacy">Privacy</a></li>
                        <li><a href="#notifications">Notifications</a></li>
                        <li><a href="#security">Security</a></li>
                        <li><a href="#blacklist">Blacklist</a></li>
                    </ul>

                    <div id="contacts">
                        <div class="photo-caption marginTopNone">
                            <h3>Contact settings</h3>
                        </div>
                        <p>This data is displayed in your profile according to your privacy settings.</p>

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
                            <h3>Privacy settings</h3>
                        </div>
                        <p>Choose who can access profile sections and actions on your page.</p>

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
                            <h3>Notification settings</h3>
                        </div>
                        <p>Select the events you want to receive notifications for.</p>

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
                            <h3>Security</h3>
                        </div>
                        <p>Login history. Your current IP address: {{ request()->ip() }}</p>

                        <div class="settings-log-list">
                            @forelse ($securityLogs as $log)
                                <div class="settings-log-row">
                                    <span>IP: {{ $log['ip'] ?: 'Not detected' }}</span>
                                    <span>OS: {{ $log['os'] }}</span>
                                    <span>Browser: {{ $log['browser'] }}</span>
                                    <span>Time: {{ $log['time'] ?: 'Not detected' }}</span>
                                </div>
                            @empty
                                <p class="settings-empty">No records yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div id="blacklist">
                        <div class="photo-caption marginTopNone">
                            <h3>Blacklist</h3>
                        </div>
                        <p>Users in the blacklist cannot interact with your profile.</p>

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
                                <p class="settings-empty">Blacklist is empty.</p>
                            @endforelse
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>

                <div class="profile-settings button">
                    <button class="save-button" type="submit">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="overlay_ava avatar-crop-overlay" id="avatar-crop-overlay">
        <div class="avatarUpload avatar-crop-modal" id="avatar-crop-modal" data-type="avatar">
            <button type="button" class="avatar-crop-close" aria-label="Close">×</button>
            <div class="avatar-crop-inner">
                <div class="page-header">
                    <h3>Avatar upload</h3>
                </div>
                <div class="loading-bar" id="avatar-crop-loading">
                    <img src="{{ asset('frontend/images/select2-spinner.gif') }}" width="20" alt="">
                </div>
                <div class="file_upload2 avatar-crop-file">
                    <button type="button" id="avatar-select-button">Choose a file</button>
                    <input id="profile-avatar-input" type="file" accept="image/jpeg,image/png">
                </div>
                <p class="text-show avatar-crop-hint">Select the area you want to use</p>
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
                    <button type="submit" class="save-button saveAva">Save</button>
                </form>
            </div>
        </div>
    </div>

    <div class="overlay_ava avatar-crop-overlay" id="cover-crop-overlay">
        <div class="avatarUpload avatar-crop-modal cover-crop-modal" id="cover-crop-modal" data-type="cover">
            <button type="button" class="avatar-crop-close cover-crop-close" aria-label="Close">×</button>
            <div class="avatar-crop-inner">
                <div class="page-header">
                    <h3>Cover upload</h3>
                </div>
                <div class="loading-bar" id="cover-crop-loading">
                    <img src="{{ asset('frontend/images/select2-spinner.gif') }}" width="20" alt="">
                </div>
                <div class="file_upload2 avatar-crop-file">
                    <button type="button" id="cover-select-button">Choose a file</button>
                </div>
                <p class="text-show avatar-crop-hint">Select the area you want to use</p>
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
                    <button type="submit" class="save-button saveCover">Save</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('frontend/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery.Jcrop.min.js') }}"></script>
    <script src="{{ asset('frontend/js/profile-settings.js') }}"></script>
@endpush
