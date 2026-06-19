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

class FitnessController extends Controller
{
    private const TYPE = 'fitness';


    /**
     * Shows list fitness with filters or opens a card for a specific object.
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
                'title' => 'Fitness',
                'items' => $sportBlocks->serializedByType(self::TYPE, $filters, $pageSize, 0),
                'itemsPageSize' => $pageSize,
                'itemsTotal' => $sportBlocks->countByType(self::TYPE, $filters),
                'filters' => $filters,
            ]);
    }

    /**
     * Checks authorization and shows form creation fitness.
     *
     * @return View|RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('front.home');
        }

        return view('front.sport-blocks.form', $this->basePayload() + [
                'title' => 'Add fitness',
                'button' => 'Create',
                'action' => route('front.fitness.store'),
                'sportBlock' => null,
            ]);
    }

    /**
     * Validates data form, creates fitness and redirects to its card
     *
     * @param SportBlockRequest $request
     * @param SportBlockRepository $sportBlocks
     * @return RedirectResponse
     */
    public function store(SportBlockRequest $request, SportBlockRepository $sportBlocks): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $sportBlock = $sportBlocks->createBlock($viewer, self::TYPE, $request->toDto());

        return redirect()->route('front.fitness.index', ['sportBlock' => $sportBlock->id]);
    }

    /**
     * Checks the owner and shows form editing fitness.
     */
    public function edit(int $sportBlock, SportBlockRepository $sportBlocks): View
    {
        $entity = $this->sportBlockOrFail($sportBlocks, $sportBlock);
        abort_unless($sportBlocks->isOwner($entity, Auth::guard('web')->user()), 403);

        return view('front.sport-blocks.form', $this->basePayload() + [
                'title' => 'Edit fitness',
                'button' => 'Save',
                'action' => route('front.fitness.update', ['sportBlock' => $entity->id]),
                'sportBlock' => $entity,
            ]);
    }

    /**
     * Checks the owner, saves fitness changes and returns to the card.
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

        return redirect()->route('front.fitness.index', ['sportBlock' => $entity->id]);
    }

    /**
     * Shows the card fitness, photo and permissions current user.
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
                'title' => $entity->name ?: 'Fitness',
                'sportBlock' => $entity,
                'data' => $sportBlocks->serialize($entity),
                'photos' => $photoAlbums->photosForOwner($entity->id, self::TYPE, 20, 0),
                'uploadAlbum' => $canEdit && $viewer ? $photoAlbums->ensureDefaultAlbumForOwner($entity->id, self::TYPE, 'Photos fitness') : null,
                'canEdit' => $canEdit,
            ]);
    }

    /**
     * Finds object of the desired type or ends the request with a 404 error.
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
     * Prepares general template parameters for section fitness.
     *
     * @return array
     */
    private function basePayload(): array
    {
        return [
            'sectionType' => self::TYPE,
            'routePrefix' => 'front.fitness',
            'indexRoute' => route('front.fitness.index'),
            'createRoute' => route('front.fitness.create'),
            'listTitle' => 'Fitness',
            'createButton' => 'Create fitness',
            'searchPlaceholder' => 'Search fitness by city',
            'viewer' => Auth::guard('web')->user(),
        ];
    }

    /**
     * Collects fitness search filters from query parameters.
     *
     * @param Request $request
     * @return array
     */
    private function filters(Request $request): array
    {
        return [
            'search' => trim((string)$request->query('search', '')),
            'place' => trim((string)$request->query('place', '')),
            'id_place' => (int)$request->query('id_place', 0),
        ];
    }

}
