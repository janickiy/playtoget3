<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\SportBlock\SportBlockRequest;
use App\Models\SportBlock;
use App\Repositories\PhotoalbumRepository;
use App\Repositories\SportBlockRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaygroundsController extends Controller
{
    private const TYPE = 'playground';

    /**
     * Показывает список площадки с фильтрами или открывает карточку конкретного объекта
     *
     * @param Request $request
     * @param SportBlockRepository $sportBlocks
     * @param PhotoalbumRepository $photoAlbums
     * @param int|null $sportBlock
     * @return View
     */
    public function index(Request $request, SportBlockRepository $sportBlocks, PhotoalbumRepository $photoAlbums, ?int $sportBlock = null): View
    {
        if ($sportBlock) {
            return $this->show($sportBlock, $sportBlocks, $photoAlbums);
        }

        $filters = $this->filters($request);
        $pageSize = 5;

        return view('front.sport-blocks.index', $this->basePayload() + [
            'items' => $sportBlocks->serializedByType(self::TYPE, $filters, $pageSize, 0),
            'itemsPageSize' => $pageSize,
            'itemsTotal' => $sportBlocks->countByType(self::TYPE, $filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Проверяет авторизацию и показывает форму создания площадки.
     *
     * @return View|RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('front.home');
        }

        return view('front.sport-blocks.form', $this->basePayload() + [
            'title' => 'Создание площадки',
            'button' => 'Создать',
            'action' => route('front.playgrounds.store'),
            'sportBlock' => null,
        ]);
    }

    /**
     * Валидирует данные формы, создает площадку и перенаправляет на его карточку
     *
     * @param SportBlockRequest $request
     * @param SportBlockRepository $sportBlocks
     * @return RedirectResponse
     */
    public function store(SportBlockRequest $request, SportBlockRepository $sportBlocks): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $sportBlock = $sportBlocks->createBlock($viewer, self::TYPE, $request->toDto());

        return redirect()->route('front.playgrounds.index', ['sportBlock' => $sportBlock->id]);
    }

    /**
     * Проверяет владельца и показывает форму редактирования площадки.
     *
     * @param int $sportBlock
     * @param SportBlockRepository $sportBlocks
     * @return View
     */
    public function edit(int $sportBlock, SportBlockRepository $sportBlocks): View
    {
        $entity = $this->sportBlockOrFail($sportBlocks, $sportBlock);
        abort_unless($sportBlocks->isOwner($entity, Auth::guard('web')->user()), 403);

        return view('front.sport-blocks.form', $this->basePayload() + [
            'title' => 'Редактирование площадки',
            'button' => 'Сохранить',
            'action' => route('front.playgrounds.update', ['sportBlock' => $entity->id]),
            'sportBlock' => $entity,
        ]);
    }

    /**
     * Проверяет владельца, сохраняет изменения площадки и возвращает на карточку.
     *
     * @param int $sportBlock
     * @param SportBlockRequest $request
     * @param SportBlockRepository $sportBlocks
     * @return RedirectResponse
     */
    public function update(int $sportBlock, SportBlockRequest $request, SportBlockRepository $sportBlocks): RedirectResponse
    {
        $entity = $this->sportBlockOrFail($sportBlocks, $sportBlock);
        abort_unless($sportBlocks->isOwner($entity, Auth::guard('web')->user()), 403);

        $sportBlocks->updateBlock($entity, $request->toDto());

        return redirect()->route('front.playgrounds.index', ['sportBlock' => $entity->id]);
    }

    /**
     * Показывает карточку площадки, фотографии и права текущего пользователя.
     *
     * @param int $sportBlock
     * @param SportBlockRepository $sportBlocks
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    private function show(int $sportBlock, SportBlockRepository $sportBlocks, PhotoalbumRepository $photoAlbums): View
    {
        $entity = $this->sportBlockOrFail($sportBlocks, $sportBlock);
        $viewer = Auth::guard('web')->user();
        $canEdit = $sportBlocks->isOwner($entity, $viewer);

        return view('front.sport-blocks.show', $this->basePayload() + [
            'title' => $entity->name ?: 'Площадка',
            'sportBlock' => $entity,
            'data' => $sportBlocks->serialize($entity),
            'photos' => $photoAlbums->photosForOwner($entity->id, self::TYPE, 20, 0),
            'uploadAlbum' => $canEdit && $viewer ? $photoAlbums->ensureDefaultAlbumForOwner($entity->id, self::TYPE, 'Фото площадки') : null,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Находит объект нужного типа или завершает запрос ошибкой 404.
     */
    private function sportBlockOrFail(SportBlockRepository $sportBlocks, int $id): SportBlock
    {
        $sportBlock = $sportBlocks->findByType($id, self::TYPE);

        abort_unless($sportBlock, 404);

        return $sportBlock;
    }

    /**
     * Готовит общие параметры шаблонов для раздела площадки.
     */
    private function basePayload(): array
    {
        return [
            'sectionType' => self::TYPE,
            'routePrefix' => 'front.playgrounds',
            'indexRoute' => route('front.playgrounds.index'),
            'createRoute' => route('front.playgrounds.create'),
            'listTitle' => 'Площадки',
            'createButton' => 'Создать площадку',
            'searchPlaceholder' => 'Ищу площадку в городе',
            'editLabel' => 'редактирование площадки',
            'viewer' => Auth::guard('web')->user(),
        ];
    }

    /**
     * Собирает фильтры поиска площадки из query-параметров.
     */
    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'place' => trim((string) $request->query('place', '')),
            'id_place' => (int) $request->query('id_place', 0),
        ];
    }

}
