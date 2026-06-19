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
     * Connects users repository and basic settings of the admin controller.
     */
    public function __construct(
        private readonly AdminUserRepository $userRepository,
    ) {
        parent::__construct();
    }

    /**
     * Shows a page with a list of site users.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.users.index', [
            'title' => 'Users',
        ]);
    }

    /**
     * Shows the card selected user
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
            'title' => 'View user',
        ]);
    }

    /**
     * Shows form editing selected user.
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
            'title' => 'Edit user',
        ]);
    }

    /**
     * Updates user from validated data form.
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
            ->with('success', 'Data updated successfully!');
    }

    /**
     * Blocks selected user.
     *
     * @param StatusRequest $request
     * @return JsonResponse
     */
    public function block(StatusRequest $request): JsonResponse
    {
        $this->userRepository->setBlocked($request->id, true);

        return response()->json(['message' => 'User blocked.']);
    }


    /**
     * Unblocks selected user
     *
     * @param StatusRequest $request
     * @return JsonResponse
     */
    public function unblock(StatusRequest $request): JsonResponse
    {
        $this->userRepository->setBlocked($request->id, false);

        return response()->json(['message' => 'User unblocked.']);
    }

    /**
     * Marks the selected user as deleted.
     *
     * @param DeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(DeleteRequest $request): JsonResponse
    {
        $this->userRepository->markDeleted($request->id);

        return response()->json(['message' => 'User is deleted.']);
    }

    /**
     * Runs mass action on selected users.
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
            'message' => 'Action completed successfully.',
            'count' => $count,
        ]);
    }
}
