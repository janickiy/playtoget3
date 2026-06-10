@extends('front.layouts.app')

@php
    $monthNames = [
        1 => 'Январь',
        2 => 'Февраль',
        3 => 'Март',
        4 => 'Апрель',
        5 => 'Май',
        6 => 'Июнь',
        7 => 'Июль',
        8 => 'Август',
        9 => 'Сентябрь',
        10 => 'Октябрь',
        11 => 'Ноябрь',
        12 => 'Декабрь',
    ];
    $weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    $today = \Carbon\CarbonImmutable::now()->toDateString();
    $cursor = $calendarStart;
@endphp

@section('content')
    <div class="content-groups friends calendar-page">
        <div class="photo-caption calendar-caption">
            <h3>Календарь</h3>
        </div>

        <div class="calendar-toolbar">
            <a class="calendar-nav" href="{{ $prevMonthUrl }}">Назад</a>
            <div class="calendar-title">{{ $monthNames[(int) $month->format('n')] }} {{ $month->format('Y') }}</div>
            <a class="calendar-nav" href="{{ $nextMonthUrl }}">Вперед</a>
            <a class="calendar-today" href="{{ $currentMonthUrl }}">Сегодня</a>
        </div>

        <div class="calendar-grid">
            @foreach ($weekDays as $dayName)
                <div class="calendar-weekday">{{ $dayName }}</div>
            @endforeach

            @while ($cursor->lte($calendarEnd))
                @php
                    $dateKey = $cursor->toDateString();
                    $dayEvents = $daysWithEvents->get($dateKey);
                    $isCurrentMonth = $cursor->betweenIncluded($monthStart, $monthEnd);
                    $classes = [
                        'calendar-day',
                        'calendar-day-muted' => ! $isCurrentMonth,
                        'calendar-day-today' => $dateKey === $today,
                        'calendar-day-events' => (bool) $dayEvents,
                    ];
                @endphp

                <a @class($classes)
                   href="{{ route('front.events.index', ['date' => $dateKey]) }}"
                   title="Мероприятия на {{ $cursor->format('d.m.Y') }}">
                    <span class="calendar-day-number">{{ $cursor->format('j') }}</span>
                    @if ($dayEvents)
                        <span class="calendar-event-count">{{ $dayEvents['count'] }}</span>
                        <span class="calendar-event-list">
                            @foreach ($dayEvents['events'] as $event)
                                <span>{{ $event['time'] }} {{ $event['name'] }}</span>
                            @endforeach
                        </span>
                    @endif
                </a>

                @php $cursor = $cursor->addDay(); @endphp
            @endwhile
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .calendar-page {
            padding-bottom: 30px;
        }

        .calendar-caption {
            margin-bottom: 14px;
        }

        .calendar-toolbar {
            align-items: center;
            border: 1px solid #d8dce2;
            border-radius: 4px;
            display: grid;
            grid-template-columns: 90px 1fr 90px 90px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .calendar-title {
            color: #2b2b2b;
            font-size: 20px;
            font-weight: 600;
            line-height: 44px;
            text-align: center;
        }

        .calendar-nav,
        .calendar-today {
            background: #f8f9fb;
            border-right: 1px solid #d8dce2;
            color: #337ab7;
            display: block;
            font-size: 13px;
            line-height: 44px;
            text-align: center;
            text-decoration: none;
        }

        .calendar-today {
            border-left: 1px solid #d8dce2;
            border-right: 0;
        }

        .calendar-nav:hover,
        .calendar-today:hover {
            background: #eef5fb;
            text-decoration: none;
        }

        .calendar-grid {
            border-left: 1px solid #d8dce2;
            border-top: 1px solid #d8dce2;
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .calendar-weekday {
            background: #f0f2f5;
            border-bottom: 1px solid #d8dce2;
            border-right: 1px solid #d8dce2;
            color: #6f7585;
            font-size: 12px;
            font-weight: 600;
            line-height: 34px;
            text-align: center;
            text-transform: uppercase;
        }

        .calendar-day {
            background: #fff;
            border-bottom: 1px solid #d8dce2;
            border-right: 1px solid #d8dce2;
            color: #333;
            min-height: 112px;
            padding: 8px;
            position: relative;
            text-decoration: none;
        }

        .calendar-day:hover {
            background: #f7fbff;
            text-decoration: none;
        }

        .calendar-day-muted {
            background: #fafafa;
            color: #b3b3b3;
        }

        .calendar-day-today {
            box-shadow: inset 0 0 0 2px #337ab7;
        }

        .calendar-day-events {
            background: #eef7ff;
        }

        .calendar-day-number {
            font-size: 18px;
            font-weight: 600;
        }

        .calendar-event-count {
            background: #337ab7;
            border-radius: 10px;
            color: #fff;
            display: inline-block;
            font-size: 12px;
            line-height: 20px;
            min-width: 20px;
            position: absolute;
            right: 8px;
            text-align: center;
            top: 8px;
        }

        .calendar-event-list {
            display: block;
            margin-top: 10px;
        }

        .calendar-event-list span {
            color: #337ab7;
            display: block;
            font-size: 12px;
            line-height: 16px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 640px) {
            .calendar-toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .calendar-title {
                grid-column: 1 / -1;
                grid-row: 1;
            }

            .calendar-nav,
            .calendar-today {
                border-top: 1px solid #d8dce2;
            }

            .calendar-today {
                grid-column: 1 / -1;
            }

            .calendar-grid {
                display: block;
            }

            .calendar-weekday {
                display: none;
            }

            .calendar-day {
                display: block;
                min-height: 82px;
            }
        }
    </style>
@endpush
