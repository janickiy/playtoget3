<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Admin\StoreRequest;
use App\Http\Requests\Admin\Admin\EditRequest;
use App\Http\Requests\Admin\Admin\DeleteRequest;
use App\Repositories\AdminRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class AdminController extends Controller
{
    public function __construct(
        private AdminRepository $adminRepository,
    )
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.admin.index')->with('title', 'Пользователи');
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $options = $this->adminRepository->roleOptions();

        return view('admin.admin.create_edit', compact('options'))->with('title', 'Добавить пользователя');
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        $this->adminRepository->createFromArray($request->validated());

        return redirect()->route('admin.admin.index')->with('success', 'Информация успешно добавлена!');
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->adminRepository->find($id);

        if (!$row) abort(404);

        $options = $this->adminRepository->roleOptions();

        return view('admin.admin.create_edit', compact('row', 'options'))->with('title', 'Редактировать пользователя');
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        if (!$this->adminRepository->updateFromArray($request->validated())) abort(404);

        return redirect()->route('admin.admin.index')->with('success', 'Данные успешно обновлены!');
    }

    /**
     * @param DeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(DeleteRequest $request): JsonResponse
    {
        if ($request->id === (int)Auth::id()) {
            return response()->json(
                ['message' => 'Нельзя удалить текущего пользователя.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $this->adminRepository->delete($request->id);

        return response()->json(['message' => 'Данные успешно удалены.']);
    }
}
