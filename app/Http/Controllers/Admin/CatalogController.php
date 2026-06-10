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
    /**
     * Подключает репозиторий категорий и базовые настройки админ-контроллера.
     */
    public function __construct(
        private CatalogRepository $categoryRepository,
    )
    {
        parent::__construct();
    }

    /**
     * Показывает страницу со списком категорий.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.catalog.index')->with('title', 'Категории');
    }

    /**
     * Показывает форму добавления категории.
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.catalog.create_edit')->with('title', 'Добавить категорию');
    }

    /**
     * Создает категорию из валидированных данных формы.
     *
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
     * Показывает форму редактирования выбранной категории.
     *
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
     * Обновляет категорию из валидированных данных формы.
     *
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
     * Удаляет категорию и возвращает JSON-ответ для интерфейса админки.
     *
     * @param DeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(DeleteRequest $request): JsonResponse
    {
        $this->categoryRepository->delete($request->id);

        return response()->json(['message' => 'Данные успешно удалены.']);
    }
}
