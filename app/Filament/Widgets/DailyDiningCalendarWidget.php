<?php

namespace App\Filament\Widgets;

use App\Models\StaffCheckIn;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DailyDiningCalendarWidget extends CalendarWidget
{
    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    protected bool $dateClickEnabled = true;

    protected bool $eventClickEnabled = true;

    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        return StaffCheckIn::query()
            ->whereBetween('checked_at', [$info->start, $info->end])
            ->get()
            ->groupBy(fn ($item) => $item->checked_at->toDateString())
            ->map(function ($group, $date) {
                $count = $group->count();
                $firstRecord = $group->first();
                $color = $count > 10 ? '#22c55e' : '#bbf7d0'; // green-500 : green-200
                $textColor = $count > 10 ? '#ffffff' : '#15803d'; // white : green-700

                return CalendarEvent::make($firstRecord)
                    ->title("🍽️ {$count}人")
                    ->start($date)
                    ->end($date)
                    ->allDay()
                    ->backgroundColor($color)
                    ->textColor($textColor)
                    ->extendedProp('date', $date);
            })
            ->values();
    }

    public function onDateClick(DateClickInfo $info): void
    {
        $this->dispatch('dateSelected', date: $info->date);
    }

    public function onEventClick(EventClickInfo $info, Model $record, ?string $action = null): void
    {
        $this->dispatch('dateSelected', date: $info->event->getExtendedProps()['date']);
    }
}
