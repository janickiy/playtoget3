<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Photoalbum;
use App\Models\Videoalbum;
use App\Repositories\EventRepository;
use App\Repositories\PhotoalbumRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\VideoalbumRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventsController extends Controller
{
    private const EVENTS_PAGE_SIZE = 5;
    private const PHOTOS_LIMIT = 6;
    private const ALBUM_PHOTOS_LIMIT = 9;
    private const VIDEOS_LIMIT = 6;
    private const COMMENTS_LIMIT = 10;


    /**
     * Показывает раздел мероприятий с фильтрами, вкладками и первой страницей списков.
     *
     * @param Request $request
     * @param EventRepository $events
     * @return View
     */
    public function index(Request $request, EventRepository $events): View
    {
        $viewer = Auth::guard('web')->user();
        $filters = $this->eventFilters($request);
        $viewerId = (int) ($viewer?->id ?? 0);

        return view('front.events.index', [
            'title' => 'Мероприятия',
            'viewer' => $viewer,
            'eventsPageSize' => self::EVENTS_PAGE_SIZE,
            'popularEvents' => $events->popularEvents(self::EVENTS_PAGE_SIZE, 0, $filters, $viewer),
            'popularEventsTotal' => $events->popularEventsCount($filters),
            'myEvents' => $viewerId > 0 ? $events->myEvents($viewerId, self::EVENTS_PAGE_SIZE, 0, $filters) : collect(),
            'myEventsTotal' => $viewerId > 0 ? $events->myEventsCount($viewerId, $filters) : 0,
            'invitedEvents' => $viewerId > 0 ? $events->invitedEvents($viewerId, self::EVENTS_PAGE_SIZE, 0, $filters) : collect(),
            'invitedEventsTotal' => $viewerId > 0 ? $events->invitedEventsCount($viewerId, $filters) : 0,
        ]);
    }

    /**
     * Показывает карточку мероприятия, профильный верхний блок и комментарии.
     *
     * @param int $event
     * @param EventRepository $events
     * @param ProfileRepository $profiles
     * @return View
     */
    public function show(int $event, EventRepository $events, ProfileRepository $profiles): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        $payload = $this->eventPayload($eventModel, $events, 'feed');

        return view('front.teams.feed', $payload + [
            'comments' => $payload['permissions']['wall']
                ? $profiles->comments('event', $eventModel->id, self::COMMENTS_LIMIT, 0, Auth::guard('web')->user())
                : collect(),
            'commentsPageSize' => self::COMMENTS_LIMIT,
            'hasMoreComments' => $payload['permissions']['wall']
                ? $profiles->hasMoreComments('event', $eventModel->id, self::COMMENTS_LIMIT, 0)
                : false,
        ]);
    }

    /**
     * Проверяет авторизацию и показывает форму создания
     *
     * @return View|RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        return view('front.events.form', [
            'title' => 'Создание мероприятия',
            'action' => route('front.events.store'),
            'button' => 'Создать мероприятие',
            'event' => null,
            'eventData' => null,
        ]);
    }

    /**
     * Валидирует данные формы, создает мероприятие и перенаправляет на его карточку.
     *
     * @param Request $request
     * @param EventRepository $events
     * @return RedirectResponse
     */
    public function store(Request $request, EventRepository $events): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $event = $events->createEvent($viewer, $this->validateEvent($request, $events));

        return redirect()->route('front.events.show', ['event' => $event->id]);
    }

    /**
     * Проверяет права участника и показывает форму редактирования мероприятия.
     *
     * @param int $event
     * @param EventRepository $events
     * @return View
     */
    public function edit(int $event, EventRepository $events): View
    {
        $eventModel = $this->eventOrFail($event, $events);

        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        return view('front.events.form', $this->eventPayload($eventModel, $events, 'edit') + [
            'title' => 'Редактирование мероприятия',
            'action' => route('front.events.update', ['event' => $eventModel->id]),
            'button' => 'Сохранить',
            'event' => $eventModel,
        ]);
    }

    /**
     * Проверяет права участника, сохраняет изменения мероприятия и возвращает на карточку.
     *
     * @param int $event
     * @param Request $request
     * @param EventRepository $events
     * @return RedirectResponse
     */
    public function update(int $event, Request $request, EventRepository $events): RedirectResponse
    {
        $eventModel = $this->eventOrFail($event, $events);

        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $events->updateEvent($eventModel, $this->validateEvent($request, $events));

        return redirect()->route('front.events.show', ['event' => $eventModel->id]);
    }

    /**
     * Показывает участников мероприятия: пользователей, команды и группы.
     *
     * @param int $event
     * @param EventRepository $events
     * @return View
     */
    public function members(int $event, EventRepository $events): View
    {
        $eventModel = $this->eventOrFail($event, $events);

        return view('front.events.members', $this->eventPayload($eventModel, $events, 'members') + [
            'members' => $events->members($eventModel->id),
            'teams' => $events->communities($eventModel->id, 'team'),
            'groups' => $events->communities($eventModel->id, 'group'),
            'applications' => $events->canManage($eventModel, Auth::guard('web')->user())
                ? $events->applications($eventModel->id)
                : collect(),
        ]);
    }

    /**
     * Показывает фотоальбомы мероприятия.
     *
     * @param int $event
     * @param EventRepository $events
     * @param PhotoalbumRepository $photoalbums
     * @return View
     */
    public function photoAlbums(int $event, EventRepository $events, PhotoalbumRepository $photoalbums): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        $payload = $this->eventPayload($eventModel, $events, 'photoalbums');

        return view('front.teams.photoalbums.index', $payload + [
            'canManage' => $events->canManage($eventModel, Auth::guard('web')->user()),
            'albums' => $photoalbums->albumsForOwner($eventModel->id, 'event'),
            'photos' => $photoalbums->photosForOwner($eventModel->id, 'event', self::PHOTOS_LIMIT, 0),
            'photosPageSize' => self::PHOTOS_LIMIT,
            'hasMorePhotos' => $photoalbums->hasMoreOwnerPhotos($eventModel->id, 'event', self::PHOTOS_LIMIT, 0),
            'popularPhotos' => $photoalbums->popularPhotos(9, 0, 'event'),
        ]);
    }

    /**
     * Показывает фотографии выбранного фотоальбома мероприятия.
     *
     * @param int $event
     * @param int $album
     * @param EventRepository $events
     * @param PhotoalbumRepository $photoalbums
     * @return View
     */
    public function showPhotoalbum(int $event, int $album, EventRepository $events, PhotoalbumRepository $photoalbums): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        $photoalbum = $this->eventPhotoalbumOrFail($album, $eventModel, $photoalbums);

        return view('front.teams.photoalbums.show', $this->eventPayload($eventModel, $events, 'photoalbums') + [
            'photoalbum' => $photoalbum,
            'photos' => $photoalbums->albumPhotos($photoalbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'photosPageSize' => self::ALBUM_PHOTOS_LIMIT,
            'hasMorePhotos' => $photoalbums->hasMoreAlbumPhotos($photoalbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'canManage' => $events->canManage($eventModel, Auth::guard('web')->user()),
            'openPhotoId' => null,
        ]);
    }

    /**
     * Показывает конкретную фотографию из фотоальбома мероприятия.
     *
     * @param int $event
     * @param int $album
     * @param int $photo
     * @param EventRepository $events
     * @param PhotoalbumRepository $photoalbums
     * @return View
     */
    public function photo(int $event, int $album, int $photo, EventRepository $events, PhotoalbumRepository $photoalbums): View
    {
        $view = $this->showPhotoalbum($event, $album, $events, $photoalbums);
        $view->with('openPhotoId', $photo);

        return $view;
    }

    /**
     * Показывает фотографию мероприятия без привязки к выбранному альбому.
     *
     * @param int $event
     * @param int $photo
     * @param EventRepository $events
     * @param PhotoalbumRepository $photoalbums
     * @return View
     */
    public function photoWithoutAlbum(int $event, int $photo, EventRepository $events, PhotoalbumRepository $photoalbums): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        $photoModel = $photoalbums->photo($photo, ['event']);
        abort_if(! $photoModel, 404);

        $photoalbum = $this->eventPhotoalbumOrFail((int) $photoModel->photoalbum_id, $eventModel, $photoalbums);

        return $this->photo($event, $photoalbum->id, $photo, $events, $photoalbums);
    }

    /**
     * Показывает форму добавления фотографии в фотоальбом мероприятия.
     *
     * @param int $event
     * @param EventRepository $events
     * @param PhotoalbumRepository $photoalbums
     * @return View
     */
    public function addPhoto(int $event, EventRepository $events, PhotoalbumRepository $photoalbums): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $photoalbums->ensureDefaultAlbumForOwner($eventModel->id, 'event', 'Альбом мероприятия');

        return view('front.teams.photoalbums.add-photo', $this->eventPayload($eventModel, $events, 'photoalbums') + [
            'title' => 'Добавление фотографий',
            'albums' => $photoalbums->editableAlbumsForOwner($eventModel->id, 'event'),
        ]);
    }

    /**
     * Показывает форму создания фотоальбома мероприятия.
     *
     * @param int $event
     * @param EventRepository $events
     * @return View
     */
    public function createPhotoAlbum(int $event, EventRepository $events): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->eventPayload($eventModel, $events, 'photoalbums') + [
            'title' => 'Создание фотоальбома',
            'action' => route('front.events.photoalbums.store', ['event' => $eventModel->id]),
            'name' => old('name', ''),
            'button' => 'Создать',
        ]);
    }

    /**
     * Создает фотоальбом мероприятия из валидированных данных формы.
     *
     * @param int $event
     * @param Request $request
     * @param EventRepository $events
     * @param PhotoalbumRepository $photoalbums
     * @return RedirectResponse
     */
    public function storePhotoAlbum(int $event, Request $request, EventRepository $events, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $eventModel = $this->eventOrFail($event, $events);
        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $name = $this->validateAlbumName($request);

        if ($photoalbums->nameExistsForOwner($eventModel->id, 'event', $name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $photoalbums->createAlbumForOwner($eventModel->id, 'event', $name);

        return redirect()->route('front.events.photoalbums', ['event' => $eventModel->id]);
    }

    /**
     * Проверяет доступ и показывает форму редактирования фотоальбома мероприятия.
     */

    /**
     * @param int $event
     * @param int $album
     * @param EventRepository $events
     * @param PhotoalbumRepository $photoalbums
     * @return View
     */
    public function editPhotoalbum(int $event, int $album, EventRepository $events, PhotoalbumRepository $photoalbums): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        $photoalbum = $this->eventPhotoalbumOrFail($album, $eventModel, $photoalbums);

        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->eventPayload($eventModel, $events, 'photoalbums') + [
            'title' => 'Редактирование фотоальбома',
            'action' => route('front.events.photoalbum.update', ['event' => $eventModel->id, 'album' => $photoalbum->id]),
            'name' => old('name', $photoalbum->name),
            'button' => 'Редактировать',
        ]);
    }

    /**
     * Проверяет доступ и сохраняет изменения фотоальбома мероприятия.
     */
    public function updatePhotoalbum(int $event, int $album, Request $request, EventRepository $events, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $eventModel = $this->eventOrFail($event, $events);
        $photoalbum = $this->eventPhotoalbumOrFail($album, $eventModel, $photoalbums);

        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $photoalbums->updateUserAlbum($photoalbum, $this->validateAlbumName($request));

        return redirect()->route('front.events.photoalbums', ['event' => $eventModel->id]);
    }

    /**
     * Проверяет доступ и удаляет фотоальбом мероприятия.
     */
    public function destroyPhotoalbum(int $event, int $album, EventRepository $events, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $eventModel = $this->eventOrFail($event, $events);
        $photoalbum = $this->eventPhotoalbumOrFail($album, $eventModel, $photoalbums);

        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $photoalbums->deleteAlbum($photoalbum);

        return redirect()->route('front.events.photoalbums', ['event' => $eventModel->id]);
    }

    /**
     * Показывает видеоальбомы мероприятия.
     */
    public function videoAlbums(int $event, EventRepository $events, VideoalbumRepository $videoalbums): View
    {
        $eventModel = $this->eventOrFail($event, $events);

        return view('front.teams.videoalbums.index', $this->eventPayload($eventModel, $events, 'videoalbums') + [
            'canManage' => $events->canManage($eventModel, Auth::guard('web')->user()),
            'albums' => $videoalbums->albumsForOwner($eventModel->id, 'event'),
            'videos' => $videoalbums->videosForOwner($eventModel->id, 'event', self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoalbums->hasMoreOwnerVideos($eventModel->id, 'event', self::VIDEOS_LIMIT, 0),
            'popularVideos' => $videoalbums->popularVideos(6, 0, 'event'),
        ]);
    }

    /**
     * Показывает видео выбранного видеоальбома мероприятия.
     */
    public function showVideoAlbum(int $event, int $album, EventRepository $events, VideoalbumRepository $videoalbums): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        $videoalbum = $this->eventVideoalbumOrFail($album, $eventModel, $videoalbums);

        return view('front.teams.videoalbums.show', $this->eventPayload($eventModel, $events, 'videoalbums') + [
            'videoalbum' => $videoalbum,
            'videos' => $videoalbums->albumVideos($videoalbum, self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoalbums->hasMoreAlbumVideos($videoalbum, self::VIDEOS_LIMIT, 0),
            'canManage' => $events->canManage($eventModel, Auth::guard('web')->user()),
        ]);
    }

    /**
     * Показывает форму добавления видео в видеоальбом мероприятия.
     */
    public function addVideo(int $event, EventRepository $events, VideoalbumRepository $videoalbums): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $videoalbums->ensureDefaultAlbumForOwner($eventModel->id, 'event', 'Альбом мероприятия');

        return view('front.teams.videoalbums.add-video', $this->eventPayload($eventModel, $events, 'videoalbums') + [
            'title' => 'Добавление видеозаписи',
            'albums' => $videoalbums->editableAlbumsForOwner($eventModel->id, 'event'),
        ]);
    }

    /**
     * Валидирует ссылку и добавляет видео в видеоальбом мероприятия.
     */
    public function storeVideo(int $event, Request $request, EventRepository $events, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $eventModel = $this->eventOrFail($event, $events);
        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $validated = $request->validate([
            'video' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'videoalbum_id' => ['required', 'integer', 'min:1'],
        ]);

        $album = $this->eventVideoalbumOrFail((int) $validated['videoalbum_id'], $eventModel, $videoalbums);
        $videoalbums->addVideoToAlbum(Auth::guard('web')->user(), $album, $validated['video'], trim((string) ($validated['description'] ?? '')));

        return redirect()->route('front.events.videoalbums', ['event' => $eventModel->id]);
    }

    /**
     * Показывает форму создания видеоальбома мероприятия.
     */
    public function createVideoAlbum(int $event, EventRepository $events): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->eventPayload($eventModel, $events, 'videoalbums') + [
            'title' => 'Создание видеоальбома',
            'action' => route('front.events.videoalbums.store', ['event' => $eventModel->id]),
            'name' => old('name', ''),
            'button' => 'Создать',
        ]);
    }

    /**
     * Создает видеоальбом мероприятия из валидированных данных формы.
     */
    public function storeVideoAlbum(int $event, Request $request, EventRepository $events, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $eventModel = $this->eventOrFail($event, $events);
        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $name = $this->validateAlbumName($request);

        if ($videoalbums->nameExistsForOwner($eventModel->id, 'event', $name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $videoalbums->createAlbumForOwner($eventModel->id, 'event', $name);

        return redirect()->route('front.events.videoalbums', ['event' => $eventModel->id]);
    }

    /**
     * Проверяет доступ и показывает форму редактирования видеоальбома мероприятия.
     */
    public function editVideoalbum(int $event, int $album, EventRepository $events, VideoalbumRepository $videoalbums): View
    {
        $eventModel = $this->eventOrFail($event, $events);
        $videoalbum = $this->eventVideoalbumOrFail($album, $eventModel, $videoalbums);

        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->eventPayload($eventModel, $events, 'videoalbums') + [
            'title' => 'Редактирование видеоальбома',
            'action' => route('front.events.videoalbum.update', ['event' => $eventModel->id, 'album' => $videoalbum->id]),
            'name' => old('name', $videoalbum->name),
            'button' => 'Редактировать',
        ]);
    }

    /**
     * Проверяет доступ и сохраняет изменения видеоальбома мероприятия.
     */
    public function updateVideoalbum(int $event, int $album, Request $request, EventRepository $events, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $eventModel = $this->eventOrFail($event, $events);
        $videoalbum = $this->eventVideoalbumOrFail($album, $eventModel, $videoalbums);

        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $videoalbums->updateUserAlbum($videoalbum, $this->validateAlbumName($request));

        return redirect()->route('front.events.videoalbums', ['event' => $eventModel->id]);
    }

    /**
     * Проверяет доступ и удаляет видеоальбом мероприятия.
     */
    public function destroyVideoalbum(int $event, int $album, EventRepository $events, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $eventModel = $this->eventOrFail($event, $events);
        $videoalbum = $this->eventVideoalbumOrFail($album, $eventModel, $videoalbums);

        abort_unless($events->canManage($eventModel, Auth::guard('web')->user()), 403);

        $videoalbums->deleteAlbum($videoalbum);

        return redirect()->route('front.events.videoalbums', ['event' => $eventModel->id]);
    }

    /**
     * Готовит общие данные мероприятия для страниц вложенных разделов.
     */
    private function eventPayload(Event $event, EventRepository $events, string $section): array
    {
        $viewer = Auth::guard('web')->user();
        $eventData = $events->serialize($event);

        return [
            'title' => $event->name ?: 'Мероприятие',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'event' => $event,
            'eventData' => $eventData,
            'team' => $event,
            'teamData' => $eventData,
            'permissions' => $events->permissions($event, $viewer),
            'role' => $events->role($event->id, $viewer?->id),
            'membershipType' => $events->membershipType($event, $viewer),
            'canManageEvent' => $events->canManage($event, $viewer),
            'section' => $section,
            'communityView' => [
                'kind' => 'event',
                'route' => 'front.events',
                'routeParam' => 'event',
                'basePath' => url('/events/' . $event->id . '/photoalbums'),
                'top' => 'front.events._top',
                'label' => 'Мероприятие',
                'labelLower' => 'мероприятие',
                'labelGenitive' => 'мероприятия',
                'pluralGenitive' => 'участников',
                'entity' => $event,
                'data' => $eventData,
            ],
        ];
    }

    /**
     * Валидирует форму мероприятия и нормализует город, спорт и даты.
     */
    private function validateEvent(Request $request, EventRepository $events): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'place' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'id_place' => ['nullable', 'integer'],
            'sport' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cover_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
        ], [
            'name.required' => 'Укажите название мероприятия.',
        ]);

        $cityId = (int) ($validated['id_place'] ?? 0);

        return [
            'name' => trim($validated['name']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'place' => trim((string) ($validated['place'] ?? '')) ?: $events->cityName($cityId),
            'address' => trim((string) ($validated['address'] ?? '')),
            'city_id' => $cityId,
            'sport_type' => trim((string) ($validated['sport'] ?? '')),
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'cover_file' => $request->file('cover_file'),
        ];
    }

    /**
     * Валидирует название альбома и возвращает очищенную строку.
     */
    private function validateAlbumName(Request $request): string
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Укажите название альбома.',
        ]);

        return trim($validated['name']);
    }

    /**
     * Собирает фильтры списка мероприятий из query-параметров.
     */
    private function eventFilters(Request $request): array
    {
        $date = (string) $request->input('date', '');
        $validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));

        return [
            'place' => trim((string) $request->input('place', '')),
            'sport' => trim((string) $request->input('sport', '')),
            'search' => trim((string) $request->input('search', '')),
            'date' => $validDate ? $date : '',
            'id_place' => (int) $request->input('id_place', 0),
            'id_sport' => (int) $request->input('id_sport', 0),
        ];
    }

    /**
     * Находит активное мероприятие или завершает запрос ошибкой 404.
     */
    private function eventOrFail(int $event, EventRepository $events): Event
    {
        $eventModel = $events->findActive($event);

        abort_if(! $eventModel, 404);

        return $eventModel;
    }

    /**
     * Находит фотоальбом, принадлежащий мероприятию, или завершает запрос ошибкой 404.
     */
    private function eventPhotoalbumOrFail(int $album, Event $event, PhotoalbumRepository $photoalbums): Photoalbum
    {
        $photoalbum = $photoalbums->album($album, ['event']);

        abort_if(! $photoalbum || (int) $photoalbum->owner_id !== (int) $event->id, 404);

        return $photoalbum;
    }

    /**
     * Находит видеоальбом, принадлежащий мероприятию, или завершает запрос ошибкой 404.
     */
    private function eventVideoalbumOrFail(int $album, Event $event, VideoalbumRepository $videoalbums): Videoalbum
    {
        $videoalbum = $videoalbums->album($album, ['event']);

        abort_if(! $videoalbum || (int) $videoalbum->owner_id !== (int) $event->id, 404);

        return $videoalbum;
    }
}
