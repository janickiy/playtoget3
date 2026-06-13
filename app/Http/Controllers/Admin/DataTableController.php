<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommunityStatus;
use App\Enums\EventStatus;
use App\Enums\UserStatus;
use App\Models\Admin;
use App\Models\Community;
use App\Models\Content;
use App\Models\Event;
use App\Models\Settings;
use App\Models\User;
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
     * Возвращает JSON-данные пользователей сайта для таблицы DataTables.
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function users(): JsonResponse
    {
        $row = User::query()->where('status', '<>', UserStatus::Deleted->value);

        return Datatables::of($row)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="js-user-checkbox" value="' . $row->id . '">';
            })
            ->addColumn('name', function ($row) {
                return e($row->displayName());
            })
            ->addColumn('status_css', function ($row) {
                return UserStatus::cssColorFor((int) $row->status);
            })
            ->addColumn('actions', function ($row) {
                $showBtn = '<a title="просмотр" class="btn btn-xs btn-info" href="' . route('admin.users.show', ['id' => $row->id]) . '"><span class="fa fa-eye"></span></a> &nbsp;';
                $editBtn = '<a title="редактировать" class="btn btn-xs btn-primary" href="' . route('admin.users.edit', ['id' => $row->id]) . '"><span class="fa fa-edit"></span></a> &nbsp;';

                if ((int) $row->status === UserStatus::Blocked->value) {
                    $statusBtn = '<a title="разблокировать" class="btn btn-xs btn-success statusRow" href="' . route('admin.users.unblock', ['id' => $row->id]) . '" data-id="' . $row->id . '" data-action="unblock"><span class="fa fa-unlock"></span></a> &nbsp;';
                } else {
                    $statusBtn = '<a title="заблокировать" class="btn btn-xs btn-warning statusRow" href="' . route('admin.users.block', ['id' => $row->id]) . '" data-id="' . $row->id . '" data-action="block"><span class="fa fa-lock"></span></a> &nbsp;';
                }

                $deleteBtn = '<a title="удалить" class="btn btn-xs btn-danger deleteRow" href="' . route('admin.users.destroy', ['id' => $row->id]) . '" data-id="' . $row->id . '"><span class="fa fa-trash"></span></a>';

                return '<div class="nobr"> ' . $showBtn . $editBtn . $statusBtn . $deleteBtn . '</div>';
            })
            ->editColumn('status', function ($row) {
                return UserStatus::labelFor((int) $row->status);
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d/m/Y H:i');
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
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
     * Возвращает JSON-данные комьюнити для таблицы DataTables.
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function communities(): JsonResponse
    {
        $row = Community::query();

        return Datatables::of($row)
            ->addColumn('actions', function ($row) {
                $showBtn = '<a title="просмотр" class="btn btn-xs btn-info" href="' . route('admin.communities.show', ['id' => $row->id]) . '"><span class="fa fa-eye"></span></a> &nbsp;';
                $editBtn = '<a title="редактировать" class="btn btn-xs btn-primary" href="' . route('admin.communities.edit', ['id' => $row->id]) . '"><span class="fa fa-edit"></span></a> &nbsp;';
                $deleteBtn = '<a title="удалить" class="btn btn-xs btn-danger deleteRow" href="' . route('admin.communities.destroy', ['id' => $row->id]) . '" data-id="' . $row->id . '"><span class="fa fa-trash"></span></a>';

                return '<div class="nobr"> ' . $showBtn . $editBtn . $deleteBtn . '</div>';
            })
            ->editColumn('type', function ($row) {
                return match ((string) $row->type) {
                    'team' => 'Команда',
                    'group' => 'Группа',
                    default => (string) $row->type,
                };
            })
            ->addColumn('status_css', function ($row) {
                return CommunityStatus::cssColorFor((int) $row->status);
            })
            ->editColumn('status', function ($row) {
                return CommunityStatus::labelFor((int) $row->status);
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d/m/Y H:i');
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Возвращает JSON-данные мероприятий для таблицы DataTables.
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function events(): JsonResponse
    {
        $row = Event::query();

        return Datatables::of($row)
            ->addColumn('actions', function ($row) {
                $showBtn = '<a title="просмотр" class="btn btn-xs btn-info" href="' . route('admin.events.show', ['id' => $row->id]) . '"><span class="fa fa-eye"></span></a> &nbsp;';
                $editBtn = '<a title="редактировать" class="btn btn-xs btn-primary" href="' . route('admin.events.edit', ['id' => $row->id]) . '"><span class="fa fa-edit"></span></a> &nbsp;';
                $deleteBtn = '<a title="удалить" class="btn btn-xs btn-danger deleteRow" href="' . route('admin.events.destroy', ['id' => $row->id]) . '" data-id="' . $row->id . '"><span class="fa fa-trash"></span></a>';

                return '<div class="nobr"> ' . $showBtn . $editBtn . $deleteBtn . '</div>';
            })
            ->addColumn('status_css', function ($row) {
                return EventStatus::cssColorFor((int) $row->status);
            })
            ->editColumn('description', function ($row) {
                return Str::limit(strip_tags((string) $row->description), 120);
            })
            ->editColumn('status', function ($row) {
                return EventStatus::labelFor((int) $row->status);
            })
            ->editColumn('date_from', function ($row) {
                return $row->date_from ? Carbon::parse($row->date_from)->format('d/m/Y H:i') : '';
            })
            ->editColumn('date_to', function ($row) {
                return $row->date_to ? Carbon::parse($row->date_to)->format('d/m/Y H:i') : '';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d/m/Y H:i');
            })
            ->rawColumns(['actions'])
            ->make(true);
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
