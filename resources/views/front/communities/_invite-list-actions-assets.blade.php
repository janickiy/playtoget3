@once
    @push('styles')
        <style>
            .community-invite-actions {
                display: flex;
                gap: 10px;
                align-items: center;
                margin-top: 8px;
            }

            .community-invite-list-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 30px;
                height: 30px;
                border-radius: 50%;
            }

            .community-invite-list-accept {
                background: #49afa2;
            }

            .community-invite-list-decline {
                background: #cc0000;
            }

            .community-invite-list-action img {
                max-width: 16px;
                max-height: 16px;
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
