<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
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

    public function index(Request $request, SportBlockRepository $sportBlocks, PhotoalbumRepository $photoalbums, ?int $sportBlock = null): View
    {
        if ($sportBlock) {
            return $this->show($sportBlock, $sportBlocks, $photoalbums);
        }

        return view('front.sport-blocks.index', $this->basePayload() + [
            'items' => $sportBlocks->serializedByType(self::TYPE, $this->filters($request)),
            'filters' => $this->filters($request),
        ]);
    }

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

    public function store(Request $request, SportBlockRepository $sportBlocks): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $sportBlock = $sportBlocks->createBlock($viewer, self::TYPE, $this->validated($request, $sportBlocks));

        return redirect()->route('front.shops.index', ['sportBlock' => $sportBlock->id]);
    }

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

    public function update(int $sportBlock, Request $request, SportBlockRepository $sportBlocks): RedirectResponse
    {
        $entity = $this->sportBlockOrFail($sportBlocks, $sportBlock);
        abort_unless($sportBlocks->isOwner($entity, Auth::guard('web')->user()), 403);

        $sportBlocks->updateBlock($entity, $this->validated($request, $sportBlocks));

        return redirect()->route('front.shops.index', ['sportBlock' => $entity->id]);
    }

    private function show(int $sportBlock, SportBlockRepository $sportBlocks, PhotoalbumRepository $photoalbums): View
    {
        $entity = $this->sportBlockOrFail($sportBlocks, $sportBlock);
        $viewer = Auth::guard('web')->user();
        $canEdit = $sportBlocks->isOwner($entity, $viewer);

        return view('front.sport-blocks.show', $this->basePayload() + [
            'title' => $entity->name ?: 'Магазин',
            'sportBlock' => $entity,
            'data' => $sportBlocks->serialize($entity),
            'photos' => $photoalbums->photosForOwner($entity->id, self::TYPE, 20, 0),
            'uploadAlbum' => $canEdit && $viewer ? $photoalbums->ensureDefaultAlbumForOwner($entity->id, self::TYPE, 'Фото магазина') : null,
            'canEdit' => $canEdit,
        ]);
    }

    private function sportBlockOrFail(SportBlockRepository $sportBlocks, int $id): SportBlock
    {
        $sportBlock = $sportBlocks->findByType($id, self::TYPE);

        abort_unless($sportBlock, 404);

        return $sportBlock;
    }

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

    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'place' => trim((string) $request->query('place', '')),
        ];
    }

    private function validated(Request $request, SportBlockRepository $sportBlocks): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:5000'],
            'id_place' => ['nullable', 'integer'],
            'place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
            'avatar_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
        ], [
            'name.required' => 'Укажите название магазина.',
        ]);

        $cityId = (int) ($validated['id_place'] ?? 0);

        return [
            'name' => trim($validated['name']),
            'about' => trim((string) ($validated['about'] ?? '')),
            'city_id' => $cityId,
            'place' => trim((string) ($validated['place'] ?? '')) ?: $sportBlocks->cityName($cityId),
            'address' => trim((string) ($validated['address'] ?? '')),
            'phone' => trim((string) ($validated['phone'] ?? '')),
            'email' => trim((string) ($validated['email'] ?? '')),
            'website' => trim((string) ($validated['website'] ?? '')),
            'avatar_file' => $request->file('avatar_file'),
        ];
    }
}
