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

class ShopsController extends Controller
{
    private const TYPE = 'shop';

    /**
     * Показывает список магазина с фильтрами или открывает карточку конкретного объекта.
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

        return view('front.sport-blocks.index', $this->basePayload() + [
            'items' => $sportBlocks->serializedByType(self::TYPE, $this->filters($request)),
            'filters' => $this->filters($request),
        ]);
    }

    /**
     * Проверяет авторизацию и показывает форму создания магазина.
     *
     * @return View|RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('front.home');
        }

        return view('front.sport-blocks.form', $this->basePayload() + [
            'title' => 'Создание магазина',
            'button' => 'Создать',
            'action' => route('front.shops.store'),
            'sportBlock' => null,
        ]);
    }

    /**
     * Валидирует данные формы, создает магазин и перенаправляет на его карточку.
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

        return redirect()->route('front.shops.index', ['sportBlock' => $sportBlock->id]);
    }

    /**
     * Проверяет владельца и показывает форму редактирования магазина.
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
            'title' => 'Редактирование магазина',
            'button' => 'Сохранить',
            'action' => route('front.shops.update', ['sportBlock' => $entity->id]),
            'sportBlock' => $entity,
        ]);
    }

    /**
     * Проверяет владельца, сохраняет изменения магазина и возвращает на карточку.
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

        return redirect()->route('front.shops.index', ['sportBlock' => $entity->id]);
    }

    /**
     * Показывает карточку магазина, фотографии и права текущего пользователя.
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
            'title' => $entity->name ?: 'Магазин',
            'sportBlock' => $entity,
            'data' => $sportBlocks->serialize($entity),
            'photos' => $photoAlbums->photosForOwner($entity->id, self::TYPE, 20, 0),
            'uploadAlbum' => $canEdit && $viewer ? $photoAlbums->ensureDefaultAlbumForOwner($entity->id, self::TYPE, 'Фото магазина') : null,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Находит объект нужного типа или завершает запрос ошибкой 404.
     *
     * @param SportBlockRepository $sportBlocks
     * @param int $id
     * @return SportBlock
     */
    private function sportBlockOrFail(SportBlockRepository $sportBlocks, int $id): SportBlock
    {
        $sportBlock = $sportBlocks->findByType($id, self::TYPE);

        abort_unless($sportBlock, 404);

        return $sportBlock;
    }

    /**
     * Готовит общие параметры шаблонов для раздела магазина
     *
     * @return array
     */
    private function basePayload(): array
    {
        return [
            'sectionType' => self::TYPE,
            'routePrefix' => 'front.shops',
            'indexRoute' => route('front.shops.index'),
            'createRoute' => route('front.shops.create'),
            'listTitle' => 'Магазины',
            'createButton' => 'Создать магазин',
            'searchPlaceholder' => 'Искать магазин в городе',
            'viewer' => Auth::guard('web')->user(),
        ];
    }

    /**
     * Собирает фильтры поиска магазина из query-параметров
     *
     * @param Request $request
     * @return array
     */
    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'place' => trim((string) $request->query('place', '')),
        ];
    }

}
