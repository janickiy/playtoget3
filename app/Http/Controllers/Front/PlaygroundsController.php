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
     * Shows list playground with filters or opens a card for a specific object
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
     * Checks authorization and shows form creation playground.
     *
     * @return View|RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('front.home');
        }

        return view('front.sport-blocks.form', $this->basePayload() + [
            'title' => 'Create playground',
            'button' => 'Create',
            'action' => route('front.playgrounds.store'),
            'sportBlock' => null,
        ]);
    }

    /**
     * Validates the data form, creates a platform and redirects to its card
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
     * Checks the owner and shows the form editing playground.
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
            'title' => 'Edit playground',
            'button' => 'Save',
            'action' => route('front.playgrounds.update', ['sportBlock' => $entity->id]),
            'sportBlock' => $entity,
        ]);
    }

    /**
     * Checks the owner, saves playground changes and returns to the card.
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
     * Shows playground card, photo and permissions current user.
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
            'title' => $entity->name ?: 'Playground',
            'sportBlock' => $entity,
            'data' => $sportBlocks->serialize($entity),
            'photos' => $photoAlbums->photosForOwner($entity->id, self::TYPE, 20, 0),
            'uploadAlbum' => $canEdit && $viewer ? $photoAlbums->ensureDefaultAlbumForOwner($entity->id, self::TYPE, 'Photos playground') : null,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Finds object of the desired type or ends the request with a 404 error.
     */
    private function sportBlockOrFail(SportBlockRepository $sportBlocks, int $id): SportBlock
    {
        $sportBlock = $sportBlocks->findByType($id, self::TYPE);

        abort_unless($sportBlock, 404);

        return $sportBlock;
    }

    /**
     * Prepares general template parameters for the section playground.
     */
    private function basePayload(): array
    {
        return [
            'sectionType' => self::TYPE,
            'routePrefix' => 'front.playgrounds',
            'indexRoute' => route('front.playgrounds.index'),
            'createRoute' => route('front.playgrounds.create'),
            'listTitle' => 'Playgrounds',
            'createButton' => 'Create playground',
            'searchPlaceholder' => 'Search playgrounds by city',
            'editLabel' => 'edit playground',
            'viewer' => Auth::guard('web')->user(),
        ];
    }

    /**
     * Collects playground search filters from query parameters.
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
