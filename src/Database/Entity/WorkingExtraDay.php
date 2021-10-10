<?php

declare(strict_types=1);

namespace App\Database\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema()
 *
 * @ORM\Entity(repositoryClass="App\Database\Repository\WorkingExtraDayRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="working_extra_day",
 *   uniqueConstraints={
 *      @ORM\UniqueConstraint(columns={"working_place_id", "date"})
 *   },
 * )
 */
class WorkingExtraDay
{
    use Traits\Timestampable;

    private const DATE_FORMAT = 'Y-m-d';

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
     *   inversedBy="workingExtraDays",
     * )
     */
    protected $working_place;

    /**
     * @OA\Property(
     *   type="string",
     *   format="date",
     *   example="2019-05-09",
     * )
     *
     * @var DateTime|null
     * 
     * @ORM\Column(
     *   type="date",
     *   nullable=false,
     * )
     *
     * @Assert\Type("datetime")
     * @Assert\NotNull
     */
    protected $date;

    /**
     * @OA\Property()
     *
     * @var bool|null
     * 
     * @ORM\Column(
     *   type="boolean",
     *   nullable=false,
     * )
     *
     * @Assert\Type("boolean")
     * @Assert\NotNull
     */
    protected $is_working;

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
     *   nullable=true,
     * )
     *
     * @Assert\Type("datetime")
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
     *   nullable=true,
     * )
     *
     * @Assert\Type("datetime")
     */
    protected $time_to;

    /**
     * @return string
     */
    public static function getDateFormat(): string
    {
        return self::DATE_FORMAT;
    }

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
        $workingPlace->addWorkingExtraDay($this);
        $this->working_place = $workingPlace;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     * @return self
     */
    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsWorking(): ?bool
    {
        return $this->is_working;
    }

    /**
     * @param bool $isWorking
     * @return self
     */
    public function setIsWorking(bool $isWorking): self
    {
        $this->is_working = $isWorking;

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
     * @param DateTime|null $timeFrom
     * @return self
     */
    public function setTimeFrom(?DateTime $timeFrom): self
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
     * @param DateTime|null $timeTo
     * @return self
     */
    public function setTimeTo(?DateTime $timeTo): self
    {
        $this->time_to = $timeTo;

        return $this;
    }
}
