<?php

namespace App\Http\Controllers\Admin;

use App\DTO\SportLevel\SportLevelData;
use App\Http\Requests\Admin\SportLevels\DeleteRequest;
use App\Http\Requests\Admin\SportLevels\EditRequest;
use App\Http\Requests\Admin\SportLevels\StoreRequest;
use App\Service\SportLevelService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SportLevelsController extends Controller
{
    /**
     * Connects service sport levels и базовые настройки admin controller.
     */
    public function __construct(
        private readonly SportLevelService $sportLevelService,
    ) {
        parent::__construct();
    }

    /**
     * Shows list sport levels.
     */
    public function index(): View
    {
        return view('admin.sport-levels.index', [
            'title' => __('admin.sport_levels.title'),
        ]);
    }

    /**
     * Shows form adding sport level.
     */
    public function create(): View
    {
        return view('admin.sport-levels.create_edit', [
            'title' => __('admin.sport_levels.create_title'),
        ]);
    }

    /**
     * Creates sport level из валидированных data form.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->sportLevelService->create(SportLevelData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.sport-levels.index')
            ->with('success', __('admin.messages.created'));
    }

    /**
     * Shows page view selected sport level.
     */
    public function show(int $id): View
    {
        $row = $this->sportLevelService->find($id);

        abort_if($row === null, 404);

        return view('admin.sport-levels.show', [
            'row' => $row,
            'title' => __('admin.sport_levels.show_title'),
        ]);
    }

    /**
     * Shows form editing selected sport level.
     */
    public function edit(int $id): View
    {
        $row = $this->sportLevelService->find($id);

        abort_if($row === null, 404);

        return view('admin.sport-levels.create_edit', [
            'row' => $row,
            'title' => __('admin.sport_levels.edit_title'),
        ]);
    }

    /**
     * Updates sport level из валидированных data form.
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->sportLevelService->update(SportLevelData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.sport-levels.index')
            ->with('success', __('admin.messages.updated'));
    }

    /**
     * Deletes selected sport level.
     */
    public function destroy(DeleteRequest $request, int $id): JsonResponse
    {
        $this->sportLevelService->delete($id);

        return response()->json(['message' => __('admin.messages.deleted')]);
    }
}
