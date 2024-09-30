<?php
// src/Entity/Time.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class Time
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Date::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Date $date;

    #[ORM\ManyToOne(targetEntity: Week::class)]
    #[ORM\JoinColumn(nullable: true)]
    private Week $week;

    #[ORM\Column(type: 'string', length: 10)]
    private string $month;

    #[ORM\Column(type: 'integer')]
    private int $year;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): Date
    {
        return $this->date;
    }

    public function setDate(Date $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getWeek(): Week
    {
        return $this->week;
    }

    public function setWeek(Week $week): self
    {
        $this->week = $week;
        return $this;
    }

    public function getMonth(): string
    {
        return $this->month;
    }

    public function setMonth(string $month): self
    {
        $this->month = $month;
        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
    }
}