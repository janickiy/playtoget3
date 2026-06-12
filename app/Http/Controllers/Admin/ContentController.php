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
     * Подключает репозиторий страниц и базовые настройки админ-контроллера.
     */
    public function __construct(
        private readonly ContentRepository $contentRepository,
    ) {
        parent::__construct();
    }

    /**
     * Показывает список страниц и разделов.
     */
    public function index(): View
    {
        return view('admin.content.index', [
            'title' => 'Страницы и разделы',
        ]);
    }

    /**
     * Показывает форму добавления страницы или раздела.
     */
    public function create(): View
    {
        return view('admin.content.create_edit', [
            'title' => 'Добавление раздела',
        ]);
    }

    /**
     * Создает страницу или раздел из валидированных данных формы.
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
            ->with('success', 'Данные успешно добавлены');
    }

    /**
     * Показывает страницу просмотра выбранной записи.
     */
    public function show(int $id): View
    {
        $row = $this->contentRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.content.show', [
            'row' => $row,
            'title' => 'Просмотр раздела',
        ]);
    }

    /**
     * Показывает форму редактирования выбранной страницы или раздела.
     */
    public function edit(int $id): View
    {
        $row = $this->contentRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.content.create_edit', [
            'row' => $row,
            'title' => 'Редактирование раздела',
        ]);
    }

    /**
     * Обновляет страницу или раздел из валидированных данных формы.
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
            ->with('success', 'Данные успешно обновлены');
    }

    /**
     * Удаляет выбранную страницу или раздел.
     */
    public function destroy(DeleteRequest $request, int $id): JsonResponse
    {
        $this->contentRepository->delete($id);

        return response()->json(['message' => 'Данные успешно удалены.']);
    }
}
