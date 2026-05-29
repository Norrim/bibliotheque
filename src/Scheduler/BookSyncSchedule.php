<?php

declare(strict_types=1);

namespace App\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Planification de la mise à jour nocturne du catalogue : la commande
 * app:books:sync est exécutée chaque nuit à 3h.
 *
 * Le planificateur est rendu « stateful » afin de rattraper une exécution
 * manquée (machine éteinte, worker indisponible) au redémarrage du worker.
 */
#[AsSchedule]
final class BookSyncSchedule implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->add(
                RecurringMessage::cron('0 3 * * *', new RunCommandMessage('app:books:sync --limit=100')),
            )
            ->stateful($this->cache);
    }
}
