<div class="community-invite-modal js-event-invite-modal" aria-hidden="true">
    <div class="community-invite-modal-dialog">
        <button type="button" class="community-invite-close js-event-invite-close" aria-label="Закрыть">×</button>
        <h3>Пригласить друзей</h3>
        <div class="community-invite-status js-event-invite-status">Загрузка...</div>
        <div class="community-invite-content js-event-invite-content"></div>
        <div class="community-invite-footer">
            <button type="button" class="community-invite-submit js-event-invite-submit" disabled>Отправить приглашение</button>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .community-invite-modal {
                position: fixed;
                inset: 0;
                z-index: 10000;
                display: none;
                align-items: center;
                justify-content: center;
                background: rgba(35, 39, 62, 0.65);
                padding: 20px;
            }

            .community-invite-modal.is-open {
                display: flex;
            }

            .community-invite-modal-dialog {
                position: relative;
                width: min(520px, 100%);
                max-height: 82vh;
                overflow: hidden;
                border-radius: 6px;
                background: #fff;
                box-shadow: 0 14px 40px rgba(0, 0, 0, 0.28);
            }

            .community-invite-modal h3 {
                margin: 0;
                padding: 24px 54px 14px 24px;
                color: #2b2d45;
                font-size: 20px;
                font-weight: 700;
                line-height: 1.25;
                text-transform: uppercase;
            }

            .community-invite-close {
                position: absolute;
                top: 16px;
                right: 18px;
                border: 0;
                background: transparent;
                color: #9a9a9a;
                font-size: 28px;
                line-height: 1;
            }

            .community-invite-status {
                padding: 0 24px 14px;
                color: #929292;
                font-size: 14px;
                line-height: 1.45;
            }

            .community-invite-content {
                max-height: 48vh;
                overflow-y: auto;
                padding: 0 24px;
            }

            .community-invite-empty {
                margin: 0 0 18px;
                color: #929292;
                font-size: 14px;
                line-height: 1.45;
            }

            .community-invite-friend {
                display: flex;
                align-items: center;
                gap: 14px;
                min-height: 74px;
                margin: 0;
                padding: 12px 0;
                border-top: 1px solid #e0e1e4;
                cursor: pointer;
            }

            .community-invite-friend:first-child {
                border-top: 0;
            }

            .community-invite-modal .community-invite-friend input[type="checkbox"] {
                position: static !important;
                left: auto !important;
                opacity: 1 !important;
                visibility: visible !important;
                width: 18px !important;
                height: 18px !important;
                margin: 0;
                flex: 0 0 auto;
            }

            .community-invite-friend-avatar {
                width: 52px;
                height: 52px;
                flex: 0 0 52px;
                overflow: hidden;
                border-radius: 50%;
                background: #eaf3f4;
            }

            .community-invite-friend-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .community-invite-friend-text {
                display: flex;
                flex-direction: column;
                min-width: 0;
            }

            .community-invite-friend-name {
                color: #2e7dbc;
                font-size: 16px;
                font-weight: 700;
                line-height: 1.2;
            }

            .community-invite-friend-city {
                margin-top: 4px;
                color: #929292;
                font-size: 14px;
            }

            .community-invite-footer {
                padding: 16px 24px 26px;
                text-align: center;
            }

            .community-invite-submit {
                min-width: 230px;
                height: 44px;
                border: 0;
                border-radius: 8em;
                background: #435a9a;
                box-shadow: none;
                color: #fff;
                font-size: 14px;
                font-weight: 700;
                line-height: 44px;
                text-shadow: none;
                text-transform: uppercase;
                transition: background-color .18s ease, opacity .18s ease;
            }

            .community-invite-submit:hover,
            .community-invite-submit:focus {
                background: #40aaa1;
                color: #fff;
                outline: none;
            }

            .community-invite-submit:active {
                background: #358f88;
            }

            .community-invite-submit:disabled {
                background: #c7cbd6;
                cursor: default;
                opacity: .65;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                const ajaxUrl = '{{ route('front.ajax.handle', ['action' => '__ACTION__']) }}';
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const $modal = $('.js-event-invite-modal');
                const $content = $modal.find('.js-event-invite-content');
                const $status = $modal.find('.js-event-invite-status');
                const $submit = $modal.find('.js-event-invite-submit');

                function inviteAjax(action, data) {
                    return $.ajax({
                        type: 'POST',
                        url: ajaxUrl.replace('__ACTION__', action),
                        data: Object.assign({_token: token}, data),
                    });
                }

                function notice(text, ok) {
                    const className = ok ? 'save_window_ok' : 'save_window_fail';
                    const item = $('<div class="' + className + ' hiden"></div>').text(text);
                    $('body').append(item);
                    setTimeout(function () { item.removeClass('hiden'); }, 50);
                    setTimeout(function () {
                        item.addClass('hiden');
                        setTimeout(function () { item.remove(); }, 900);
                    }, 1800);
                }

                function closeModal() {
                    $modal.removeClass('is-open').attr('aria-hidden', 'true');
                    $modal.removeData('event-id');
                }

                function selectedIds() {
                    return $content.find('.js-community-invite-check:checked').map(function () {
                        return $(this).val();
                    }).get();
                }

                function syncSubmit() {
                    $submit.prop('disabled', selectedIds().length === 0 || $submit.data('loading') === 1);
                }

                window.openEventInviteModal = function (eventId) {
                    $modal.data('event-id', eventId).addClass('is-open').attr('aria-hidden', 'false');
                    $content.empty();
                    $status.text('Загрузка...');
                    $submit.prop('disabled', true).data('loading', 0).text('Отправить приглашение');

                    inviteAjax('get_event_invite_friends', {event_id: eventId})
                        .done(function (response) {
                            if (response.result === 'success') {
                                $content.html(response.html || '');
                                $status.text(response.count > 0 ? 'Выберите друзей для приглашения.' : '');
                                syncSubmit();
                            } else {
                                $status.text('Не удалось загрузить список друзей.');
                            }
                        })
                        .fail(function () {
                            $status.text('Не удалось загрузить список друзей.');
                        });
                };

                $(document).on('change', '.js-event-invite-modal .js-community-invite-check', syncSubmit);

                $(document).on('click', '.js-event-invite-close', closeModal);

                $modal.on('click', function (event) {
                    if (event.target === this) {
                        closeModal();
                    }
                });

                $(document).on('click', '.js-event-invite-submit', function () {
                    const eventId = $modal.data('event-id');
                    const userIds = selectedIds();

                    if (!eventId || userIds.length === 0 || $submit.data('loading') === 1) {
                        return;
                    }

                    $submit.data('loading', 1).prop('disabled', true).text('Отправка...');

                    inviteAjax('send_event_invitation', {
                        event_id: eventId,
                        user_ids: userIds,
                    })
                        .done(function (response) {
                            if (response.result === 'success') {
                                notice('Приглашения отправлены: ' + response.count, true);
                                closeModal();
                            } else {
                                notice('Не удалось отправить приглашения', false);
                            }
                        })
                        .fail(function () {
                            notice('Не удалось отправить приглашения', false);
                        })
                        .always(function () {
                            $submit.data('loading', 0).text('Отправить приглашение');
                            syncSubmit();
                        });
                });
            })();
        </script>
    @endpush
@endonce
