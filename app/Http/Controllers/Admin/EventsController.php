<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\EventData;
use App\Helpers\FrontAssets;
use App\Http\Requests\Admin\Events\DeleteRequest;
use App\Http\Requests\Admin\Events\EditRequest;
use App\Repositories\AdminEventRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventsController extends Controller
{
    /**
     * Подключает репозиторий мероприятий и базовые настройки админ-контроллера.
     */
    public function __construct(
        private readonly AdminEventRepository $eventRepository,
    ) {
        parent::__construct();
    }

    /**
     * Показывает список мероприятий.
     */
    public function index(): View
    {
        return view('admin.events.index', [
            'title' => 'Мероприятия',
        ]);
    }

    /**
     * Показывает страницу просмотра выбранного мероприятия.
     */
    public function show(int $id): View
    {
        $row = $this->eventRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.events.show', [
            'row' => $row,
            'title' => 'Просмотр мероприятия',
            'statusLabel' => $this->eventRepository->statusLabel((int) $row->status),
            'coverUrl' => FrontAssets::eventAvatar($row),
        ]);
    }

    /**
     * Показывает форму редактирования выбранного мероприятия.
     */
    public function edit(int $id): View
    {
        $row = $this->eventRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.events.create_edit', [
            'row' => $row,
            'title' => 'Редактирование мероприятия',
            'statusOptions' => $this->eventRepository->statusOptions(),
            'coverUrl' => FrontAssets::eventAvatar($row),
        ]);
    }

    /**
     * Обновляет мероприятие из валидированных данных формы.
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $updated = $this->eventRepository->updateFromData(EventData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        abort_if(! $updated, 404);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Данные успешно обновлены');
    }

    /**
     * Удаляет выбранное мероприятие.
     */
    public function destroy(DeleteRequest $request): JsonResponse
    {
        $id = (int) $request->route('id');

        $this->eventRepository->delete($id);

        return response()->json(['message' => 'Данные успешно удалены.']);
    }
}
