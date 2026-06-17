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
     * Connects репозиторий announcements и базовые настройки админ-контроллера.
     */
    public function __construct(
        private readonly AnnouncementRepository $announcementRepository,
    ) {
        parent::__construct();
    }

    /**
     * Shows list announcements.
     */
    public function index(): View
    {
        return view('admin.announcements.index', [
            'title' => 'Announcements',
        ]);
    }

    /**
     * Shows form adding announcement.
     */
    public function create(): View
    {
        return view('admin.announcements.create_edit', [
            'title' => 'Add announcement',
        ]);
    }

    /**
     * Creates announcement из валидированных data form.
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
            ->with('success', 'Data added successfully');
    }

    /**
     * Shows page view selected announcement.
     */
    public function show(int $id): View
    {
        $row = $this->announcementRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.announcements.show', [
            'row' => $row,
            'title' => 'View announcement',
        ]);
    }

    /**
     * Shows form editing selected announcement.
     */
    public function edit(int $id): View
    {
        $row = $this->announcementRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.announcements.create_edit', [
            'row' => $row,
            'title' => 'Edit announcement',
        ]);
    }

    /**
     * Updates announcement из валидированных data form.
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
            ->with('success', 'Data updated successfully');
    }

    /**
     * Deletes selected announcement.
     */
    public function destroy(DeleteRequest $request, int $id): JsonResponse
    {
        $this->announcementRepository->delete($id);

        return response()->json(['message' => 'Data deleted successfully.']);
    }
}
