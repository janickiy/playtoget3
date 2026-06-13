@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/jquery-ui-1.8.16.custom.css') }}">
    <style>
        #tabs.community-form-tabs {
            margin-top: 10px;
        }

        #tabs.community-form-tabs > ul {
            background: transparent !important;
            border: 0 !important;
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 5px 0 28px !important;
            padding: 0 !important;
            width: 100%;
        }

        #tabs.community-form-tabs > ul > li {
            box-sizing: border-box;
            display: block !important;
            float: none !important;
            margin: 0 !important;
            min-width: 0;
            top: 0 !important;
            width: auto !important;
        }

        #tabs.community-form-tabs > ul > li:nth-child(4) {
            grid-column: 2;
        }

        #tabs.community-form-tabs > ul > li > a {
            text-decoration: none;
        }

        #tabs.community-form-tabs > ul > li.ui-tabs-active,
        #tabs.community-form-tabs > ul > li.ui-state-active,
        #tabs.community-form-tabs > ul > li:hover {
            border-color: #40aaa1;
        }

        #tabs.community-form-tabs > ul > li.ui-tabs-active > a,
        #tabs.community-form-tabs > ul > li.ui-state-active > a,
        #tabs.community-form-tabs > ul > li.active > a,
        #tabs.community-form-tabs > ul > li.ui-state-hover > a,
        #tabs.community-form-tabs > ul > li > a:hover {
            background: #40aaa1;
            color: #fff;
            opacity: 1 !important;
            text-decoration: none;
        }

        #tabs.community-form-tabs.ui-tabs .ui-tabs-panel {
            margin-top: 0 !important;
            padding: 1em 0 !important;
        }

        @media (max-width: 640px) {
            #tabs.community-form-tabs > ul {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 390px) {
            #tabs.community-form-tabs > ul {
                grid-template-columns: 1fr;
            }

            #tabs.community-form-tabs > ul > li:nth-child(4) {
                grid-column: 1;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/jquery-ui.min.js') }}"></script>
    <script>
        (function ($) {
            $(function () {
                var $tabs = $('.community-form-tabs');

                if (!$tabs.length || typeof $.fn.tabs !== 'function') {
                    return;
                }

                $tabs.each(function () {
                    var $tab = $(this);

                    if ($tab.data('ui-tabs')) {
                        $tab.tabs('option', 'active', 0);
                        return;
                    }

                    $tab.tabs({active: 0});
                });
            });
        })(jQuery);
    </script>
@endpush
