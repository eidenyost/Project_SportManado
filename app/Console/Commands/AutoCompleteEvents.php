<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;

class AutoCompleteEvents extends Command
{
    protected $signature = 'events:auto-complete';

    protected $description = 'Mark active events past their end time as completed';

    public function handle(): int
    {
        $count = 0;
        Event::where('status', 'active')
            ->where(function ($query) {
                $query->where('event_end_at', '<=', now())
                    ->orWhere(fn ($legacy) => $legacy->whereNull('event_end_at')->where('event_date', '<', today()));
            })
            ->chunkById(100, function ($events) use (&$count) {
                foreach ($events as $event) {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($event, &$count) {
                        $locked=Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
                        if ($locked->status !== 'active' || now()->lt($locked->endsAt())) return;
                        $locked->update(['status'=>'completed']);
                        $count++;
                    });
                }
            });

        $this->info("{$count} event(s) marked as completed.");

        return Command::SUCCESS;
    }
}
