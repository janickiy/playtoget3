<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Settings\SettingsData;
use App\Http\Requests\Admin\Settings\DeleteRequest;
use App\Http\Requests\Admin\Settings\EditRequest;
use App\Http\Requests\Admin\Settings\StoreRequest;
use App\Models\Settings;
use App\Repositories\SettingsRepository;
use App\Service\SettingsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly SettingsRepository $settingsRepository,
    ) {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.settings.index', [
            'title' => 'Настройки',
        ]);
    }

    /**
     * @param string $type
     * @return View
     */
    public function create(string $type): View
    {
        return view('admin.settings.create_edit', [
            'type' => $type,
            'title' => 'Добавление настроек',
        ]);
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $value = $request->input('value');

            if ($request->hasFile('value')) {
                $value = $this->settingsService->storeFile($request);
            }

            $this->settingsRepository->createFromData(
                SettingsData::fromArray([
                    ...$request->validated(),
                    'value' => $value,
                    'published' => $request->boolean('published'),
                ]),
            );
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Информация успешно добавлена');
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->settingsRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.settings.create_edit', [
            'row' => $row,
            'type' => $row->type,
            'title' => 'Редактирование настроек',
        ]);
    }

    public function update(EditRequest $request): RedirectResponse
    {
        try {
            /** @var Settings|null $settings */
            $settings = $this->settingsRepository->find((int) $request->id);

            abort_if($settings === null, 404);

            $value = $request->input('value', $settings->filePath());

            if ($request->hasFile('value')) {
                $value = $this->settingsService->updateFile($settings, $request);

                if ($value === false) {
                    return redirect()
                        ->route('admin.settings.index')
                        ->with('error', 'Не удалось сохранить файл!');
                }
            }

            $this->settingsRepository->updateFromData(
                SettingsData::fromArray([
                    ...$request->validated(),
                    'id' => (int) $request->id,
                    'value' => $value,
                    'published' => $request->boolean('published'),
                ]),
            );
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Данные обновлены');
    }

    /**
     * @param DeleteRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(DeleteRequest $request, int $id): JsonResponse
    {
        $this->settingsRepository->remove($id);

        return response()->json(['message' => 'Данные успешно удалены.']);
    }
}
