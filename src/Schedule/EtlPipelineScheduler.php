<?php
// src/Schedule/EtlPipelineScheduler.php
namespace App\Schedule;

use App\Message\EtlPipelineMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule(name: 'etl_pipeline')]
class EtlPipelineScheduler implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->with(
                RecurringMessage::cron('0 * * * *', new EtlPipelineMessage())
            );
    }

}