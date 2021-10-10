<?php

declare(strict_types=1);

namespace App\Database\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema()
 *
 * @ORM\Entity(repositoryClass="App\Database\Repository\WorkingScheduleRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="working_schedule",
 *   uniqueConstraints={
 *      @ORM\UniqueConstraint(columns={"working_place_id", "day_number"})
 *   },
 * )
 */
class WorkingSchedule
{
    use Traits\Timestampable;

    private const TIME_FORMAT = 'H:i:s';

    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(
     *   type="integer",
     *   options={
     *     "unsigned": true,
     *   },
     * )
     */
    protected $id;

    /**
     * @var AbstractWorkingPlace|null
     * 
     * @ORM\ManyToOne(
     *   targetEntity="AbstractWorkingPlace",
     *   inversedBy="workingSchedule",
     * )
     */
    protected $working_place;

    /**
     * @OA\Property(
     *   minimum=1,
     *   maximum=7,
     *   example=1,
     * )
     * 
     * @var int|null
     * 
     * @ORM\Column(
     *  type="integer",
     *  nullable=false,
     *  options={
     *    "unsigned": true,
     *  }
     * )
     *
     * @Assert\Range(min=1, max=7)
     * @Assert\NotNull
     */
    protected $day_number;

    /**
     * @OA\Property(
     *   type="string",
     *   format="time-hour",
     *   example="09:00:00",
     * )
     * 
     * @var DateTime|null
     * 
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     *
     * @Assert\Type("datetime")
     * @Assert\NotNull
     */
    protected $time_from;

    /**
     * @OA\Property(
     *   type="string",
     *   format="time-hour",
     *   example="18:00:00",
     * )
     * 
     * @var DateTime|null
     * 
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     *
     * @Assert\Type("datetime")
     * @Assert\NotNull
     */
    protected $time_to;

    /**
     * @return string
     */
    public static function getTimeFormat(): string
    {
        return self::TIME_FORMAT;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return AbstractWorkingPlace|null
     */
    public function getWorkingPlace(): ?AbstractWorkingPlace
    {
        return $this->working_place;
    }

    /**
     * @param AbstractWorkingPlace $workingPlace
     * @return self
     */
    public function setWorkingPlace(AbstractWorkingPlace $workingPlace): self
    {
        $workingPlace->addWorkingSchedule($this);
        $this->working_place = $workingPlace;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDayNumber(): ?int
    {
        return $this->day_number;
    }

    /**
     * @param int $dayNumber
     * @return self
     */
    public function setDayNumber(int $dayNumber): self
    {
        $this->day_number = $dayNumber;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getTimeFrom(): ?DateTime
    {
        return $this->time_from;
    }

    /**
     * @param DateTime $timeFrom
     * @return self
     */
    public function setTimeFrom($timeFrom): self
    {
        $this->time_from = $timeFrom;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getTimeTo(): ?DateTime
    {
        return $this->time_to;
    }

    /**
     * @param DateTime $timeTo
     * @return self
     */
    public function setTimeTo($timeTo): self
    {
        $this->time_to = $timeTo;

        return $this;
    }
}
