<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\SportBlockData;
use App\Helpers\FrontAssets;
use App\Http\Requests\Admin\SportBlocks\DeleteRequest;
use App\Http\Requests\Admin\SportBlocks\EditRequest;
use App\Repositories\AdminSportBlockRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SportBlocksController extends Controller
{
    /**
     * Connects репозиторий sport blocks и базовые настройки админ-контроллера.
     */
    public function __construct(
        private readonly AdminSportBlockRepository $sportBlockRepository,
    ) {
        parent::__construct();
    }

    /**
     * Shows list sport blocks.
     */
    public function index(): View
    {
        return view('admin.sport-blocks.index', [
            'title' => 'Sports blocks',
        ]);
    }

    /**
     * Shows page view selected sport block.
     */
    public function show(int $id): View
    {
        $row = $this->sportBlockRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.sport-blocks.show', [
            'row' => $row,
            'title' => 'View sport block',
            'typeLabel' => $this->sportBlockRepository->typeLabel((string) $row->type),
            'statusLabel' => $this->sportBlockRepository->statusLabel((int) $row->status),
            'avatarUrl' => FrontAssets::sportBlockAvatar($row),
        ]);
    }

    /**
     * Shows form editing selected sport block.
     */
    public function edit(int $id): View
    {
        $row = $this->sportBlockRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.sport-blocks.create_edit', [
            'row' => $row,
            'title' => 'Edit sport block',
            'typeOptions' => $this->sportBlockRepository->typeOptions(),
            'statusOptions' => $this->sportBlockRepository->statusOptions(),
            'avatarUrl' => FrontAssets::sportBlockAvatar($row),
        ]);
    }

    /**
     * Updates sport block из валидированных data form.
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $updated = $this->sportBlockRepository->updateFromData(SportBlockData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        abort_if(! $updated, 404);

        return redirect()
            ->route('admin.sport-blocks.index')
            ->with('success', 'Data updated successfully');
    }

    /**
     * Deletes выбранный sport block.
     */
    public function destroy(DeleteRequest $request): JsonResponse
    {
        $this->sportBlockRepository->delete((int) $request->route('id'));

        return response()->json(['message' => 'Data deleted successfully.']);
    }
}
