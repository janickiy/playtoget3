<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Catalog\CatalogData;
use App\Repositories\CatalogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Http\Requests\Admin\Catalog\{
    StoreRequest,
    EditRequest,
    DeleteRequest,
};
use Exception;

class CatalogController extends Controller
{
    public function __construct(
        private CatalogRepository $categoryRepository,
    )
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.catalog.index')->with('title', 'Категории');
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.catalog.create_edit')->with('title', 'Добавить категорию');
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->categoryRepository->createFromData(CatalogData::fromArray($request->validated()));
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.catalog.index')->with('success', 'Информация успешно добавлена');
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->categoryRepository->find($id);

        if (!$row) abort(404);

        return view('admin.catalog.create_edit', compact('row'))->with('title', 'Редактирование категории');
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->categoryRepository->updateFromData(CatalogData::fromArray($request->validated()));
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.catalog.index')->with('success', 'Данные обновлены');
    }

    /**
     * @param DeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(DeleteRequest $request): JsonResponse
    {
        $this->categoryRepository->delete($request->id);

        return response()->json(['message' => 'Данные успешно удалены.']);
    }
}
