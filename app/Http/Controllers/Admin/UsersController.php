<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\UserData;
use App\Enums\UserStatus;
use App\Http\Requests\Admin\Users\BulkActionRequest;
use App\Http\Requests\Admin\Users\DeleteRequest;
use App\Http\Requests\Admin\Users\EditRequest;
use App\Http\Requests\Admin\Users\StatusRequest;
use App\Models\User;
use App\Repositories\AdminUserRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsersController extends Controller
{
    /**
     * Подключает репозиторий пользователей и базовые настройки админ-контроллера.
     */
    public function __construct(
        private readonly AdminUserRepository $userRepository,
    ) {
        parent::__construct();
    }

    /**
     * Показывает страницу со списком пользователей сайта.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.users.index', [
            'title' => 'Пользователи',
        ]);
    }

    /**
     * Показывает карточку выбранного пользователя
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $row = $this->userRepository->find($id);

        abort_if(! $row instanceof User, 404);

        return view('admin.users.show', [
            'row' => $row,
            'title' => 'Просмотр пользователя',
        ]);
    }

    /**
     * Показывает форму редактирования выбранного пользователя.
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->userRepository->find($id);

        abort_if(! $row instanceof User, 404);

        return view('admin.users.create_edit', [
            'row' => $row,
            'statusOptions' => UserStatus::options(),
            'title' => 'Редактирование пользователя',
        ]);
    }

    /**
     * Обновляет пользователя из валидированных данных формы.
     *
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $updated = $this->userRepository->updateFromData(UserData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        abort_if(! $updated, 404);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Данные успешно обновлены!');
    }

    /**
     * Блокирует выбранного пользователя.
     *
     * @param StatusRequest $request
     * @return JsonResponse
     */
    public function block(StatusRequest $request): JsonResponse
    {
        $this->userRepository->setBlocked($request->id, true);

        return response()->json(['message' => 'Пользователь заблокирован.']);
    }


    /**
     * Разблокирует выбранного пользователя
     *
     * @param StatusRequest $request
     * @return JsonResponse
     */
    public function unblock(StatusRequest $request): JsonResponse
    {
        $this->userRepository->setBlocked($request->id, false);

        return response()->json(['message' => 'Пользователь разблокирован.']);
    }

    /**
     * Помечает выбранного пользователя как удаленного.
     *
     * @param DeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(DeleteRequest $request): JsonResponse
    {
        $this->userRepository->markDeleted($request->id);

        return response()->json(['message' => 'Пользователь удален.']);
    }

    /**
     * Выполняет массовое действие над выбранными пользователями.
     *
     * @param BulkActionRequest $request
     * @return JsonResponse
     */
    public function bulk(BulkActionRequest $request): JsonResponse
    {
        $count = $this->userRepository->bulkAction(
            (string) $request->validated('action'),
            $request->validated('ids'),
        );

        return response()->json([
            'message' => 'Действие успешно выполнено.',
            'count' => $count,
        ]);
    }
}
