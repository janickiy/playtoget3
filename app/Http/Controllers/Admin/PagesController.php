<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Pages\DeleteRequest;
use App\Http\Requests\Admin\Pages\EditRequest;
use App\Http\Requests\Admin\Pages\StoreRequest;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PagesController extends Controller
{
    public function __construct(
        private readonly PagesRepository $pageRepository,
    ) {
        parent::__construct();
    }

    public function index(): View
    {
        return view('cp.pages.index', [
            'title' => 'Страницы и разделы',
        ]);
    }

    public function create(): View
    {
        return view('cp.pages.create_edit', [
            'options' => $this->pageRepository->getOption(),
            'title' => 'Добавление раздела',
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->pageRepository->create(
                ArrayData::from([
                    ...$request->validated(),
                    'published' => $request->boolean('published'),
                    'seo_sitemap' => $request->boolean('seo_sitemap'),
                    'main' => $request->boolean('main'),
                ]),
            );
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Данные успешно добавлены');
    }

    public function edit(int $id): View
    {
        $row = $this->pageRepository->find($id);

        abort_if($row === null, 404);

        return view('cp.pages.create_edit', [
            'row' => $row,
            'options' => $this->pageRepository->getOption(),
            'title' => 'Редактирование раздела',
        ]);
    }

    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->pageRepository->updateWithMapping(
                (int) $request->id,
                ArrayData::from([
                    ...$request->validated(),
                    'published' => $request->boolean('published'),
                    'seo_sitemap' => $request->boolean('seo_sitemap'),
                    'main' => $request->boolean('main'),
                ]),
            );
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Данные успешно обновлены');
    }

    public function destroy(DeleteRequest $request, int $id): void
    {
        $this->pageRepository->delete($id);
    }
}
