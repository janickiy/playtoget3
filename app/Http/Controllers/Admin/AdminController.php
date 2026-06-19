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
    /**
     * Connects administrator repository and basic settings of the admin controller.
     */
    public function __construct(
        private AdminRepository $adminRepository,
    )
    {
        parent::__construct();
    }

    /**
     * Shows page with list of users admin panel.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.admin.index')->with('title', 'Users');
    }

    /**
     * Shows form adding user admin panel.
     *
     * @return View
     */
    public function create(): View
    {
        $options = $this->adminRepository->roleOptions();

        return view('admin.admin.create_edit', compact('options'))->with('title', 'Add user');
    }

    /**
     * Creates a new user admin panel from validated data form.
     *
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        $this->adminRepository->createFromArray($request->validated());

        return redirect()->route('admin.admin.index')->with('success', 'Information added successfully!');
    }

    /**
     * Shows form editing selected user admin panel.
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->adminRepository->find($id);

        if (!$row) abort(404);

        $options = $this->adminRepository->roleOptions();

        return view('admin.admin.create_edit', compact('row', 'options'))->with('title', 'Edit user');
    }

    /**
     * Updates data user admin panel from validated data form.
     *
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        if (!$this->adminRepository->updateFromArray($request->validated())) abort(404);

        return redirect()->route('admin.admin.index')->with('success', 'Data updated successfully!');
    }

    /**
     * Deletes user admin panel and prohibits the delete current of the authorized user.
     *
     * @param DeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(DeleteRequest $request): JsonResponse
    {
        if ($request->id === (int)Auth::id()) {
            return response()->json(
                ['message' => 'You cannot delete the current user.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $this->adminRepository->delete($request->id);

        return response()->json(['message' => 'Data deleted successfully.']);
    }
}
