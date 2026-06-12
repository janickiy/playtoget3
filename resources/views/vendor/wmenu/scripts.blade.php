@php
	$locale = (string) config('menu.locale', 'en');
	$locale = in_array($locale, (array) config('menu.supported_locales', ['en']), true) ? $locale : 'en';
	$t = static fn (string $key): string => (string) trans("wmenu::messages.{$key}", [], $locale);
@endphp
<script>
	var menus = {
		"oneThemeLocationNoMenus" : "",
		"moveUp" : @json($t('move_up')),
		"moveDown" : @json($t('move_down')),
		"moveToTop" : @json($t('move_to_top')),
		"moveUnder" : @json($t('move_under')),
		"moveOutFrom" : @json($t('move_out_from')),
		"under" : @json($t('under')),
		"outFrom" : @json($t('out_from')),
		"menuFocus" : @json($t('menu_focus')),
		"subMenuFocus" : @json($t('submenu_focus'))
	};
	window.menuTranslations = {
		"confirmDeleteMenu": @json($t('confirm_delete_menu')),
		"enterMenuName": @json($t('enter_menu_name_alert'))
	};
	var arraydata = [];     
	var addcustommenur= '{{ route("haddcustommenu") }}';
	var updateitemr= '{{ route("hupdateitem")}}';
	var generatemenucontrolr= '{{ route("hgeneratemenucontrol") }}';
	var deleteitemmenur= '{{ route("hdeleteitemmenu") }}';
	var deletemenugr= '{{ route("hdeletemenug") }}';
	var createnewmenur= '{{ route("hcreatenewmenu") }}';
	var csrftoken="{{ csrf_token() }}";
	var menuwr = "{{ url()->current() }}";

	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': csrftoken
		}
	});
</script>
<script type="text/javascript" src="{{asset('vendor/harimayco-menu/scripts.js')}}"></script>
<script type="text/javascript" src="{{asset('vendor/harimayco-menu/scripts2.js')}}"></script>
<script type="text/javascript" src="{{asset('vendor/harimayco-menu/menu.js')}}"></script>
