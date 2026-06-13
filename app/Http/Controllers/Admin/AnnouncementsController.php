<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Announcement\AnnouncementData;
use App\Http\Requests\Admin\Announcements\DeleteRequest;
use App\Http\Requests\Admin\Announcements\EditRequest;
use App\Http\Requests\Admin\Announcements\StoreRequest;
use App\Repositories\AnnouncementRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnnouncementsController extends Controller
{
    /**
     * Подключает репозиторий объявлений и базовые настройки админ-контроллера.
     */
    public function __construct(
        private readonly AnnouncementRepository $announcementRepository,
    ) {
        parent::__construct();
    }

    /**
     * Показывает список объявлений.
     */
    public function index(): View
    {
        return view('admin.announcements.index', [
            'title' => 'Объявления',
        ]);
    }

    /**
     * Показывает форму добавления объявления.
     */
    public function create(): View
    {
        return view('admin.announcements.create_edit', [
            'title' => 'Добавление объявления',
        ]);
    }

    /**
     * Создает объявление из валидированных данных формы.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->announcementRepository->createFromData(AnnouncementData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Данные успешно добавлены');
    }

    /**
     * Показывает страницу просмотра выбранного объявления.
     */
    public function show(int $id): View
    {
        $row = $this->announcementRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.announcements.show', [
            'row' => $row,
            'title' => 'Просмотр объявления',
        ]);
    }

    /**
     * Показывает форму редактирования выбранного объявления.
     */
    public function edit(int $id): View
    {
        $row = $this->announcementRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.announcements.create_edit', [
            'row' => $row,
            'title' => 'Редактирование объявления',
        ]);
    }

    /**
     * Обновляет объявление из валидированных данных формы.
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->announcementRepository->updateFromData(AnnouncementData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Данные успешно обновлены');
    }

    /**
     * Удаляет выбранное объявление.
     */
    public function destroy(DeleteRequest $request, int $id): JsonResponse
    {
        $this->announcementRepository->delete($id);

        return response()->json(['message' => 'Данные успешно удалены.']);
    }
}
