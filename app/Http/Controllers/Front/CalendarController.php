<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\EventRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request, EventRepository $events): View
    {
        $month = $this->month($request);
        $monthStart = $month->startOfMonth();
        $monthEnd = $month->endOfMonth();
        $calendarStart = $monthStart->startOfWeek(CarbonInterface::MONDAY);
        $calendarEnd = $monthEnd->endOfWeek(CarbonInterface::SUNDAY);

        return view('front.calendar.index', [
            'title' => 'Календарь',
            'month' => $month,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'calendarStart' => $calendarStart,
            'calendarEnd' => $calendarEnd,
            'daysWithEvents' => $events->calendarDays($monthStart, $monthEnd),
            'prevMonthUrl' => route('front.calendar.index', ['month' => $month->subMonth()->format('Y-m')]),
            'nextMonthUrl' => route('front.calendar.index', ['month' => $month->addMonth()->format('Y-m')]),
            'currentMonthUrl' => route('front.calendar.index'),
        ]);
    }

    private function month(Request $request): CarbonImmutable
    {
        $month = (string) $request->query('month', '');

        if (
            preg_match('/^\d{4}-\d{2}$/', $month) === 1
            && checkdate((int) substr($month, 5, 2), 1, (int) substr($month, 0, 4))
        ) {
            return CarbonImmutable::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        }

        return CarbonImmutable::now()->startOfMonth();
    }
}
