<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\Content;
use App\Models\Settings;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class DataTableController extends Controller
{
    /**
     * Возвращает JSON-данные пользователей админки для таблицы DataTables.
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function admin(): JsonResponse
    {
        $row = Admin::query();

        return Datatables::of($row)
            ->addColumn('action', function ($row) {
                $editBtn = '<a title="редактировать" class="btn btn-xs btn-primary"  href="' . route('admin.admin.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';

                if ((int) $row->id !== (int) Auth::id())
                    $deleteBtn = '<a title="удалить" class="btn btn-xs btn-danger deleteRow" href="' . route('admin.admin.destroy', ['id' => $row->id]) . '" data-id="' . $row->id . '"><span class="fa fa-trash"></span></a>';
                else
                    $deleteBtn = '';

                return '<div class="nobr"> ' . $editBtn . $deleteBtn . '</div>';
            })
            ->editColumn('role', function ($row) {
                return Admin::$role_name[$row->role];
            })
            ->rawColumns(['action', 'id'])->make(true);
    }

    /**
     * Возвращает JSON-данные категорий для таблицы DataTables.
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function content(): JsonResponse
    {
        $row = Content::query();

        return Datatables::of($row)
            ->addColumn('actions', function ($row) {
                $showBtn = '<a title="просмотр" class="btn btn-xs btn-info" href="' . route('admin.content.show', ['id' => $row->id]) . '"><span class="fa fa-eye"></span></a> &nbsp;';
                $editBtn = '<a title="редактировать" class="btn btn-xs btn-primary"  href="' . route('admin.content.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';
                $deleteBtn = '<a title="удалить" class="btn btn-xs btn-danger deleteRow" href="' . route('admin.content.destroy', ['id' => $row->id]) . '" data-id="' . $row->id . '"><span class="fa fa-trash"></span></a>';

                return '<div class="nobr"> ' . $showBtn . $editBtn . $deleteBtn . '</div>';
            })
            ->editColumn('text', function ($row) {
                return Str::limit(strip_tags((string) $row->text), 120);
            })
            ->editColumn('published', function ($row) {
                return $row->published == 1 ? 'да' : 'нет';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d/m/Y H:i');
            })
            ->rawColumns(['actions'])->make(true);
    }

    /**
     * @return JsonResponse
     * @throws \Exception
     */
    public function settings(): JsonResponse
    {
        $row = Settings::query();

        return Datatables::of($row)
            ->addColumn('actions', function ($row) {
                $editBtn = '<a title="редактировать" class="btn btn-xs btn-primary"  href="' . route('admin.settings.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';
                $deleteBtn = '<a title="удалить" class="btn btn-xs btn-danger deleteRow" href="' . route('admin.settings.destroy', ['id' => $row->id]) . '" data-id="' . $row->id . '"><span class="fa fa-trash"></span></a>';

                return '<div class="nobr"> ' . $editBtn . $deleteBtn . '</div>';
            })
            ->editColumn('published', function ($row) {
                return $row->published == 1 ? 'да' : 'нет';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d/m/Y H:i');
            })
            ->rawColumns(['actions'])->make(true);
    }

}
