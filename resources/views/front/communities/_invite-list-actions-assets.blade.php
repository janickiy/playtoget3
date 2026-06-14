@once
    @push('styles')
        <style>
            .community-invite-actions {
                display: flex;
                gap: 12px;
                align-items: center;
                margin-top: 10px;
            }

            .community-invite-list-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                position: relative;
                width: 32px;
                height: 32px;
                border: 2px solid #49afa2;
                border-radius: 50%;
                background: transparent;
                transition: background-color .15s ease, border-color .15s ease, opacity .15s ease;
            }

            .community-invite-list-action:hover {
                background: #49afa2;
                opacity: .9;
            }

            .community-invite-list-action:before,
            .community-invite-list-action:after {
                content: '';
                position: absolute;
                display: block;
            }

            .community-invite-list-accept:before {
                width: 13px;
                height: 8px;
                margin-top: -3px;
                border-left: 3px solid #49afa2;
                border-bottom: 3px solid #49afa2;
                transform: rotate(-45deg);
            }

            .community-invite-list-accept:hover:before {
                border-color: #fff;
            }

            .community-invite-list-decline {
                border-color: #d95f65;
            }

            .community-invite-list-decline:hover {
                border-color: #cc0000;
                background: #fff5f5;
            }

            .community-invite-list-decline:before,
            .community-invite-list-decline:after {
                width: 14px;
                height: 3px;
                border-radius: 2px;
                background: #d95f65;
            }

            .community-invite-list-decline:before {
                transform: rotate(45deg);
            }

            .community-invite-list-decline:after {
                transform: rotate(-45deg);
            }

            .community-invite-list-decline:hover:before,
            .community-invite-list-decline:hover:after {
                background: #cc0000;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                const ajaxUrl = '{{ route('front.ajax.handle', ['action' => 'change_member_status']) }}';
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                function notice(text, ok) {
                    const className = ok ? 'save_window_ok' : 'save_window_fail';
                    const item = $('<div class="' + className + ' hiden"></div>').text(text);
                    $('body').append(item);
                    setTimeout(function () { item.removeClass('hiden'); }, 50);
                    setTimeout(function () {
                        item.addClass('hiden');
                        setTimeout(function () { item.remove(); }, 900);
                    }, 1600);
                }

                function decrementInviteCounter() {
                    const $counter = $('#main-menu li[data-type="invited"] sup');
                    const value = Math.max((parseInt($counter.text(), 10) || 0) - 1, 0);

                    if (value > 0) {
                        $counter.text(value);
                    } else {
                        $counter.remove();
                    }
                }

                $(document).on('click', '.js-community-invite-list-action', function (event) {
                    event.preventDefault();

                    const $button = $(this);
                    const communityId = $button.data('community-id');
                    const status = Number($button.data('status'));

                    if (!communityId || ![0, 1].includes(status)) {
                        return;
                    }

                    $.ajax({
                        type: 'POST',
                        url: ajaxUrl,
                        data: {
                            _token: token,
                            id: communityId,
                            status: status,
                        },
                    })
                        .done(function (response) {
                            if (response.result !== 'success') {
                                notice('Не удалось изменить статус', false);
                                return;
                            }

                            $button.closest('.event-item').slideUp(200, function () {
                                $(this).remove();
                            });
                            decrementInviteCounter();
                            notice(status === 1 ? 'Приглашение принято' : 'Приглашение отклонено', true);
                        })
                        .fail(function () {
                            notice('Не удалось изменить статус', false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
