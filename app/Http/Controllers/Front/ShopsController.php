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
     * Shows list shop с фильтрами or открывает карточку конкретного object.
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
     * Checks авторизацию и показывает form creation shop.
     *
     * @return View|RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('front.home');
        }

        return view('front.sport-blocks.form', $this->basePayload() + [
            'title' => 'Create shop',
            'button' => 'Create',
            'action' => route('front.shops.store'),
            'sportBlock' => null,
        ]);
    }

    /**
     * Валидирует data form, creates магазин и перенаправляет на его карточку.
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
     * Checks владельца и показывает form editing shop.
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
            'title' => 'Edit shop',
            'button' => 'Save',
            'action' => route('front.shops.update', ['sportBlock' => $entity->id]),
            'sportBlock' => $entity,
        ]);
    }

    /**
     * Checks владельца, сохраняет изменения shop и возвращает на карточку.
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
     * Shows карточку shop, photo и permissions current user.
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
            'title' => $entity->name ?: 'Shop',
            'sportBlock' => $entity,
            'data' => $sportBlocks->serialize($entity),
            'photos' => $photoAlbums->photosForOwner($entity->id, self::TYPE, 20, 0),
            'uploadAlbum' => $canEdit && $viewer ? $photoAlbums->ensureDefaultAlbumForOwner($entity->id, self::TYPE, 'Photos shop') : null,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Finds object нужного typeа or завершает запрос ошибкой 404.
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
     * Готовит общие параметры шаблонов для section shop
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
            'listTitle' => 'Shops',
            'createButton' => 'Create shop',
            'searchPlaceholder' => 'Search shop by city',
            'viewer' => Auth::guard('web')->user(),
        ];
    }

    /**
     * Собирает фильтры поиска shop из query-параметров
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
