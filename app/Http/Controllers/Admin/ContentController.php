<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Content\ContentData;
use App\Http\Requests\Admin\Pages\DeleteRequest;
use App\Http\Requests\Admin\Pages\EditRequest;
use App\Http\Requests\Admin\Pages\StoreRequest;
use App\Repositories\ContentRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentController extends Controller
{
    /**
     * Connects репозиторий страниц и базовые настройки админ-контроллера.
     */
    public function __construct(
        private readonly ContentRepository $contentRepository,
    ) {
        parent::__construct();
    }

    /**
     * Shows list pages and sections.
     */
    public function index(): View
    {
        return view('admin.content.index', [
            'title' => 'Pages and sections',
        ]);
    }

    /**
     * Shows form adding page or section.
     */
    public function create(): View
    {
        return view('admin.content.create_edit', [
            'title' => 'Add section',
        ]);
    }

    /**
     * Creates page or section из валидированных data form.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->contentRepository->createFromData(ContentData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.content.index')
            ->with('success', 'Data added successfully');
    }

    /**
     * Shows page view выбранной записи.
     */
    public function show(int $id): View
    {
        $row = $this->contentRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.content.show', [
            'row' => $row,
            'title' => 'View section',
        ]);
    }

    /**
     * Shows form editing выбранной page or section.
     */
    public function edit(int $id): View
    {
        $row = $this->contentRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.content.create_edit', [
            'row' => $row,
            'title' => 'Edit section',
        ]);
    }

    /**
     * Updates page or section из валидированных data form.
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->contentRepository->updateFromData(ContentData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.content.index')
            ->with('success', 'Data updated successfully');
    }

    /**
     * Deletes selected page or section.
     */
    public function destroy(DeleteRequest $request, int $id): JsonResponse
    {
        $this->contentRepository->delete($id);

        return response()->json(['message' => 'Data deleted successfully.']);
    }
}
