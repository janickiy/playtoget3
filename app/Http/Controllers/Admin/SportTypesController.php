<?php

namespace App\Http\Controllers\Admin;

use App\DTO\SportType\SportTypeData;
use App\Http\Requests\Admin\SportTypes\DeleteRequest;
use App\Http\Requests\Admin\SportTypes\EditRequest;
use App\Http\Requests\Admin\SportTypes\StoreRequest;
use App\Service\SportTypeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SportTypesController extends Controller
{
    /**
     * Connects service sport types и базовые настройки admin controller.
     */
    public function __construct(
        private readonly SportTypeService $sportTypeService,
    ) {
        parent::__construct();
    }

    /**
     * Shows list sport types.
     */
    public function index(): View
    {
        return view('admin.sport-types.index', [
            'title' => __('admin.sport_types.title'),
        ]);
    }

    /**
     * Shows form adding sport type.
     */
    public function create(): View
    {
        return view('admin.sport-types.create_edit', [
            'title' => __('admin.sport_types.create_title'),
            'parentOptions' => $this->sportTypeService->parentOptions(),
        ]);
    }

    /**
     * Creates sport type из валидированных data form.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->sportTypeService->create(SportTypeData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.sport-types.index')
            ->with('success', __('admin.messages.created'));
    }

    /**
     * Shows page view selected sport type.
     */
    public function show(int $id): View
    {
        $row = $this->sportTypeService->find($id);

        abort_if($row === null, 404);

        return view('admin.sport-types.show', [
            'row' => $row,
            'title' => __('admin.sport_types.show_title'),
        ]);
    }

    /**
     * Shows form editing selected sport type.
     */
    public function edit(int $id): View
    {
        $row = $this->sportTypeService->find($id);

        abort_if($row === null, 404);

        return view('admin.sport-types.create_edit', [
            'row' => $row,
            'title' => __('admin.sport_types.edit_title'),
            'parentOptions' => $this->sportTypeService->parentOptions($row->id),
        ]);
    }

    /**
     * Updates sport type из валидированных data form.
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->sportTypeService->update(SportTypeData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.sport-types.index')
            ->with('success', __('admin.messages.updated'));
    }

    /**
     * Deletes selected sport type.
     */
    public function destroy(DeleteRequest $request, int $id): JsonResponse
    {
        $this->sportTypeService->delete($id);

        return response()->json(['message' => __('admin.messages.deleted')]);
    }
}
