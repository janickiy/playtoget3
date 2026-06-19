<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\CommunityData;
use App\Helpers\FrontAssets;
use App\Http\Requests\Admin\Communities\DeleteRequest;
use App\Http\Requests\Admin\Communities\EditRequest;
use App\Repositories\AdminCommunityRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommunitiesController extends Controller
{
    /**
     * Connects community repository and basic admin controller settings.
     */
    public function __construct(
        private readonly AdminCommunityRepository $communityRepository,
    ) {
        parent::__construct();
    }

    /**
     * Shows list community.
     */
    public function index(): View
    {
        return view('admin.communities.index', [
            'title' => 'Communities',
        ]);
    }

    /**
     * Shows page view selected community.
     */
    public function show(int $id): View
    {
        $row = $this->communityRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.communities.show', [
            'row' => $row,
            'title' => 'View community',
            'typeLabel' => $this->communityRepository->typeLabel((string) $row->type),
            'statusLabel' => $this->communityRepository->statusLabel((int) $row->status),
            'avatarUrl' => FrontAssets::adminCommunityAvatar($row),
        ]);
    }

    /**
     * Shows form editing selected community.
     */
    public function edit(int $id): View
    {
        $row = $this->communityRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.communities.create_edit', [
            'row' => $row,
            'title' => 'Edit community',
            'typeOptions' => $this->communityRepository->typeOptions(),
            'statusOptions' => $this->communityRepository->statusOptions(),
            'avatarUrl' => FrontAssets::adminCommunityAvatar($row),
        ]);
    }

    /**
     * Updates community from validated data forms.
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $updated = $this->communityRepository->updateFromData(CommunityData::fromArray($request->validated()));
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        abort_if(! $updated, 404);

        return redirect()
            ->route('admin.communities.index')
            ->with('success', 'Data updated successfully');
    }

    /**
     * Deletes selected community.
     */
    public function destroy(DeleteRequest $request, int $id): JsonResponse
    {
        $this->communityRepository->delete($id);

        return response()->json(['message' => 'Data deleted successfully.']);
    }
}
