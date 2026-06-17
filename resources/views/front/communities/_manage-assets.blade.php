<div class="community-admin-modal js-community-admin-modal" aria-hidden="true">
    <div class="community-admin-modal-dialog">
        <button type="button" class="community-admin-modal-close js-community-admin-modal-close" aria-label="Close">×</button>
        <h3>Add administrator</h3>
        <div class="community-admin-search">
            <input type="text" class="js-community-admin-search" placeholder="user name or ID">
        </div>
        <div class="community-admin-status js-community-admin-status">Enter a user name or ID.</div>
        <div class="community-admin-results js-community-admin-results"></div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .community-card-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 8px;
            }

            .community-card-action,
            .community-admin-add-open,
            .community-admin-candidate-add {
                border: 0;
                border-radius: 8em;
                background: #435a9a;
                color: #fff;
                font-size: 13px;
                line-height: 1;
                padding: 9px 14px;
            }

            .community-card-action-danger {
                background: #d95f65;
            }

            .community-card-action-warning {
                background: #d6a640;
            }

            .community-member-control {
                display: flex;
                align-items: center;
            }

            .community-member-icon-action,
            .friends .possible-friend-cart .community-member-icon-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 27px;
                height: 27px;
                padding: 0;
                border: 0;
                background: transparent;
                line-height: 1;
                transition: opacity .15s ease;
            }

            .community-member-icon-action:hover {
                opacity: .88;
            }

            .community-member-icon-action img {
                display: block;
                width: 27px;
                height: 27px;
            }

            .community-admin-add-open {
                display: inline-block;
                margin: 0 0 18px;
                font-size: 15px;
                text-transform: uppercase;
            }

            .community-admin-modal {
                position: fixed;
                inset: 0;
                z-index: 10000;
                display: none;
                align-items: center;
                justify-content: center;
                background: rgba(35, 39, 62, 0.65);
                padding: 20px;
            }

            .community-admin-modal.is-open {
                display: flex;
            }

            .community-admin-modal-dialog {
                position: relative;
                width: min(620px, 100%);
                max-height: 82vh;
                overflow: hidden;
                border-radius: 6px;
                background: #fff;
                box-shadow: 0 14px 40px rgba(0, 0, 0, 0.28);
            }

            .community-admin-modal h3 {
                margin: 0;
                padding: 22px 56px 18px 24px;
                color: #2b2d45;
                font-size: 24px;
                font-weight: 700;
                text-transform: uppercase;
            }

            .community-admin-modal-close {
                position: absolute;
                top: 16px;
                right: 18px;
                border: 0;
                background: transparent;
                color: #9a9a9a;
                font-size: 32px;
                line-height: 1;
            }

            .community-admin-search {
                padding: 0 24px 14px;
            }

            .community-admin-search input {
                width: 100%;
                height: 42px;
                border: 1px solid #e0e1e4;
                border-radius: 4px;
                color: #2b2d45;
                font-size: 18px;
                padding: 0 14px;
            }

            .community-admin-status {
                padding: 0 24px 14px;
                color: #929292;
                font-size: 16px;
            }

            .community-admin-results {
                max-height: 48vh;
                overflow-y: auto;
                padding: 0 24px 24px;
            }

            .community-admin-empty {
                margin: 0;
                color: #929292;
                font-size: 16px;
            }

            .community-admin-candidate {
                display: flex;
                align-items: center;
                gap: 14px;
                min-height: 74px;
                padding: 12px 0;
                border-top: 1px solid #e0e1e4;
            }

            .community-admin-candidate:first-child {
                border-top: 0;
            }

            .community-admin-candidate-avatar {
                width: 52px;
                height: 52px;
                flex: 0 0 52px;
                overflow: hidden;
                border-radius: 50%;
                background: #eaf3f4;
            }

            .community-admin-candidate-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .community-admin-candidate-text {
                display: flex;
                flex: 1 1 auto;
                flex-direction: column;
                min-width: 0;
                color: #929292;
                font-size: 14px;
            }

            .community-admin-candidate-name {
                color: #2e7dbc;
                font-size: 18px;
                font-weight: 700;
                line-height: 1.2;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                const ajaxUrl = '{{ route('front.ajax.handle', ['action' => '__ACTION__']) }}';
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const $modal = $('.js-community-admin-modal');
                const $search = $modal.find('.js-community-admin-search');
                const $status = $modal.find('.js-community-admin-status');
                const $results = $modal.find('.js-community-admin-results');
                let searchTimer = null;

                function communityAjax(action, data) {
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
                    }, 1700);
                }

                function closeAdminModal() {
                    $modal.removeClass('is-open').attr('aria-hidden', 'true');
                    $modal.removeData('community-id');
                    $search.val('');
                    $results.empty();
                    $status.text('Enter a user name or ID.');
                }

                function searchAdmins() {
                    const communityId = $modal.data('community-id');
                    const query = $.trim($search.val());

                    if (!communityId || (query.length < 2 && !/^\d+$/.test(query))) {
                        $results.empty();
                        $status.text('Enter at least 2 characters or a user ID.');
                        return;
                    }

                    $status.text('Search...');

                    communityAjax('search_community_admin_candidates', {
                        community_id: communityId,
                        q: query,
                    })
                        .done(function (response) {
                            $results.html(response.html || '');
                            $status.text(response.count > 0 ? 'Select a user.' : '');
                        })
                        .fail(function () {
                            $results.empty();
                            $status.text('Search failed.');
                        });
                }

                $(document).on('click', '.js-community-member-action', function (event) {
                    event.preventDefault();

                    const $button = $(this);
                    const message = $button.data('confirm') || 'Confirm this action?';

                    if (!window.confirm(message)) {
                        return;
                    }

                    communityAjax($button.data('action'), {
                        community_id: $button.data('community-id'),
                        user_id: $button.data('user-id'),
                    })
                        .done(function (response) {
                            if (response.result !== 'success') {
                                notice('Action failed', false);
                                return;
                            }

                            $button.closest('.possible-friend-cart').fadeOut(180, function () {
                                $(this).remove();
                            });
                            notice($button.data('success') || 'Done', true);
                        })
                        .fail(function () {
                            notice('Action failed', false);
                        });
                });

                $(document).on('click', '.js-community-admin-open', function () {
                    $modal.data('community-id', $(this).data('community-id')).addClass('is-open').attr('aria-hidden', 'false');
                    $search.trigger('focus');
                });

                $(document).on('click', '.js-community-admin-modal-close', closeAdminModal);

                $modal.on('click', function (event) {
                    if (event.target === this) {
                        closeAdminModal();
                    }
                });

                $(document).on('input', '.js-community-admin-search', function () {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(searchAdmins, 300);
                });

                $(document).on('click', '.js-community-admin-add', function () {
                    const communityId = $modal.data('community-id');
                    const userId = $(this).data('user-id');

                    communityAjax('add_community_admin', {
                        community_id: communityId,
                        user_id: userId,
                    })
                        .done(function (response) {
                            if (response.result !== 'success') {
                                notice('Failed to add administrator', false);
                                return;
                            }

                            notice('Administrator added', true);
                            window.location.reload();
                        })
                        .fail(function () {
                            notice('Failed to add administrator', false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
