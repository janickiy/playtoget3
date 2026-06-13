@extends('front.layouts.app')

@php
    $permissionOptions = [
        0 => 'Все',
        1 => 'Друзья',
        2 => 'Никто',
    ];

    $contactFields = [
        'contact_email' => ['label' => 'Email', 'type' => 'email'],
        'phone' => ['label' => 'Телефон', 'type' => 'text'],
        'telegram' => ['label' => 'Telegram', 'type' => 'text'],
        'whatsapp' => ['label' => 'WhatsApp', 'type' => 'text'],
        'viber' => ['label' => 'Viber', 'type' => 'text'],
        'website' => ['label' => 'Сайт', 'type' => 'text'],
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
        <div class="save_window_fail">Проверьте заполнение формы</div>
    @endif

    <div class="friends profile-edit-page">
        <div class="photo-caption">
            <h3>Настройки</h3>
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
                        <li><a href="#contacts">Контакт</a></li>
                        <li><a href="#privacy">Приватность</a></li>
                        <li><a href="#notifications">Оповещения</a></li>
                        <li><a href="#security">Безопасность</a></li>
                        <li><a href="#blacklist">Черный список</a></li>
                    </ul>

                    <div id="contacts">
                        <div class="photo-caption marginTopNone">
                            <h3>Настройка контактной информации</h3>
                        </div>
                        <p>Эти данные отображаются в вашем профиле согласно настройкам приватности.</p>

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
                            <h3>Настройка приватности</h3>
                        </div>
                        <p>Выберите, кому доступны разделы профиля и действия на вашей странице.</p>

                        @foreach ($permissionFields as $field => $label)
                            <div class="form-group">
                                <label for="profile-{{ $field }}" class="col-sm-7 control-label">{{ $label }}</label>
                                <div class="col-sm-5">
                                    <select id="profile-{{ $field }}" class="form-control" name="user[{{ $field }}]">
                                        @foreach ($permissionOptions as $value => $text)
                                            <option
                                                value="{{ $value }}"
                                                @selected((string) old("user.{$field}", $settings->{$field} ?? 0) === (string) $value)
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
                            <h3>Настройка оповещений</h3>
                        </div>
                        <p>Отметьте события, по которым хотите получать уведомления.</p>

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
                            <h3>Безопасность</h3>
                        </div>
                        <p>Журнал учета посещений. Ваш текущий IP-адрес: {{ request()->ip() }}</p>

                        <div class="settings-log-list">
                            @forelse ($securityLogs as $log)
                                <div class="settings-log-row">
                                    <span>IP: {{ $log['ip'] ?: 'Не определено' }}</span>
                                    <span>ОС: {{ $log['os'] }}</span>
                                    <span>Браузер: {{ $log['browser'] }}</span>
                                    <span>Время: {{ $log['time'] ?: 'Не определено' }}</span>
                                </div>
                            @empty
                                <p class="settings-empty">Записей пока нет.</p>
                            @endforelse
                        </div>
                    </div>

                    <div id="blacklist">
                        <div class="photo-caption marginTopNone">
                            <h3>Черный список</h3>
                        </div>
                        <p>Пользователи из черного списка не могут взаимодействовать с вашим профилем.</p>

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
                                <p class="settings-empty">Черный список пуст.</p>
                            @endforelse
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>

                <div class="profile-settings button">
                    <button class="save-button" type="submit">Применить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="overlay_ava avatar-crop-overlay" id="avatar-crop-overlay">
        <div class="avatarUpload avatar-crop-modal" id="avatar-crop-modal" data-type="avatar">
            <button type="button" class="avatar-crop-close" aria-label="Закрыть">×</button>
            <div class="avatar-crop-inner">
                <div class="page-header">
                    <h3>Загрузка аватара</h3>
                </div>
                <div class="loading-bar" id="avatar-crop-loading">
                    <img border="0" src="{{ asset('frontend/images/select2-spinner.gif') }}" width="20" alt="">
                </div>
                <div class="file_upload2 avatar-crop-file">
                    <button type="button" id="avatar-select-button">Выберите файл</button>
                    <input id="profile-avatar-input" type="file" accept="image/jpeg,image/png">
                </div>
                <p class="text-show avatar-crop-hint">Выберите область, которую хотите использовать</p>
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
                    <button type="submit" class="save-button saveAva">Сохранить</button>
                </form>
            </div>
        </div>
    </div>

    <div class="overlay_ava avatar-crop-overlay" id="cover-crop-overlay">
        <div class="avatarUpload avatar-crop-modal cover-crop-modal" id="cover-crop-modal" data-type="cover">
            <button type="button" class="avatar-crop-close cover-crop-close" aria-label="Закрыть">×</button>
            <div class="avatar-crop-inner">
                <div class="page-header">
                    <h3>Загрузка Обложки</h3>
                </div>
                <div class="loading-bar" id="cover-crop-loading">
                    <img border="0" src="{{ asset('frontend/images/select2-spinner.gif') }}" width="20" alt="">
                </div>
                <div class="file_upload2 avatar-crop-file">
                    <button type="button" id="cover-select-button">Выберите файл</button>
                </div>
                <p class="text-show avatar-crop-hint">Выберите область, которую хотите использовать</p>
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
                    <button type="submit" class="save-button saveCover">Сохранить</button>
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
