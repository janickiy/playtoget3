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

class FitnessController extends Controller
{
    private const TYPE = 'fitness';

    public function index(Request $request, SportBlockRepository $sportBlocks, PhotoalbumRepository $photoalbums, ?int $sportBlock = null): View
    {
        if ($sportBlock) {
            return $this->show($sportBlock, $sportBlocks, $photoalbums);
        }

        $filters = $this->filters($request);
        $pageSize = 5;

        return view('front.sport-blocks.index', $this->basePayload() + [
            'title' => 'Фитнес',
            'items' => $sportBlocks->serializedByType(self::TYPE, $filters, $pageSize, 0),
            'itemsPageSize' => $pageSize,
            'itemsTotal' => $sportBlocks->countByType(self::TYPE, $filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('front.home');
        }

        return view('front.sport-blocks.form', $this->basePayload() + [
            'title' => 'Добавление фитнеса',
            'button' => 'Создать',
            'action' => route('front.fitness.store'),
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

        return redirect()->route('front.fitness.index', ['sportBlock' => $sportBlock->id]);
    }

    public function edit(int $sportBlock, SportBlockRepository $sportBlocks): View
    {
        $entity = $this->sportBlockOrFail($sportBlocks, $sportBlock);
        abort_unless($sportBlocks->isOwner($entity, Auth::guard('web')->user()), 403);

        return view('front.sport-blocks.form', $this->basePayload() + [
            'title' => 'Редактирование фитнеса',
            'button' => 'Сохранить',
            'action' => route('front.fitness.update', ['sportBlock' => $entity->id]),
            'sportBlock' => $entity,
        ]);
    }

    public function update(int $sportBlock, Request $request, SportBlockRepository $sportBlocks): RedirectResponse
    {
        $entity = $this->sportBlockOrFail($sportBlocks, $sportBlock);
        abort_unless($sportBlocks->isOwner($entity, Auth::guard('web')->user()), 403);

        $sportBlocks->updateBlock($entity, $this->validated($request, $sportBlocks));

        return redirect()->route('front.fitness.index', ['sportBlock' => $entity->id]);
    }

    private function show(int $sportBlock, SportBlockRepository $sportBlocks, PhotoalbumRepository $photoalbums): View
    {
        $entity = $this->sportBlockOrFail($sportBlocks, $sportBlock);
        $viewer = Auth::guard('web')->user();
        $canEdit = $sportBlocks->isOwner($entity, $viewer);

        return view('front.sport-blocks.show', $this->basePayload() + [
            'title' => $entity->name ?: 'Фитнес',
            'sportBlock' => $entity,
            'data' => $sportBlocks->serialize($entity),
            'photos' => $photoalbums->photosForOwner($entity->id, self::TYPE, 20, 0),
            'uploadAlbum' => $canEdit && $viewer ? $photoalbums->ensureDefaultAlbumForOwner($entity->id, self::TYPE, 'Фото фитнеса') : null,
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
            'routePrefix' => 'front.fitness',
            'indexRoute' => route('front.fitness.index'),
            'createRoute' => route('front.fitness.create'),
            'listTitle' => 'Фитнес',
            'createButton' => 'Создать фитнес',
            'searchPlaceholder' => 'Ищу фитнес в городе',
            'viewer' => Auth::guard('web')->user(),
        ];
    }

    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'place' => trim((string) $request->query('place', '')),
            'id_place' => (int) $request->query('id_place', 0),
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
            'name.required' => 'Укажите название фитнеса.',
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
