<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Settings\DeleteRequest;
use App\Http\Requests\Admin\Settings\EditRequest;
use App\Http\Requests\Admin\Settings\StoreRequest;
use App\Repositories\SettingsRepository;
use App\Services\SettingsService;
use Exception;
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

    public function index(): View
    {
        return view('cp.settings.index', [
            'title' => 'Настройки',
        ]);
    }

    public function create(string $type): View
    {
        return view('cp.settings.create_edit', [
            'type' => $type,
            'title' => 'Добавление настроек',
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $value = $request->input('value');

            if ($request->hasFile('value')) {
                $value = $this->settingsService->storeFile($request);
            }

            $this->settingsRepository->create(
                ArrayData::from([
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

    public function edit(int $id): View
    {
        $row = $this->settingsRepository->find($id);

        abort_if($row === null, 404);

        return view('cp.settings.create_edit', [
            'row' => $row,
            'type' => $row->type,
            'title' => 'Редактирование настроек',
        ]);
    }

    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $settings = $this->settingsRepository->find((int) $request->id);

            abort_if($settings === null, 404);

            $value = $request->input('value');

            if ($request->hasFile('value')) {
                $value = $this->settingsService->updateFile($settings, $request);

                if ($value === false) {
                    return redirect()
                        ->route('admin.settings.index')
                        ->with('error', 'Не удалось сохранить файл!');
                }
            }

            $this->settingsRepository->updateWithMapping(
                (int) $request->id,
                ArrayData::from([
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
            ->with('success', 'Данные обновлены');
    }

    public function destroy(DeleteRequest $request, int $id): void
    {
        $this->settingsRepository->remove($id);
    }
}
