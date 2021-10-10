<?php

declare(strict_types=1);

namespace App\Database\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema()
 * 
 * @ORM\Entity(repositoryClass="App\Database\Repository\SectorDeliveryIntervalRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="sector_delivery_interval",
 *   uniqueConstraints={
 *   },
 * )
 */
class SectorDeliveryInterval
{
    use Traits\Timestampable;

    private const TIME_FORMAT = 'H:i';

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
     *
     */
    protected $id;

    /**
     * @var Sector|null
     * 
     * @ORM\ManyToOne(
     *   targetEntity="Sector",
     *   inversedBy="deliveryIntervals",
     * )
     */
    protected $sector;

    /**
     * @OA\Property(
     *   type="string",
     *   format="time-hour",
     *   example="12:00",
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
     *   example="15:00",
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
     * @return Sector|null
     */
    public function getSector(): ?Sector
    {
        return $this->sector;
    }

    /**
     * @param Sector $sector
     * @return self
     */
    public function setSector(Sector $sector): self
    {
        $sector->addDeliveryInterval($this);
        $this->sector = $sector;

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
    public function setTimeFrom(DateTime $timeFrom): self
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
    public function setTimeTo(DateTime $timeTo): self
    {
        $this->time_to = $timeTo;

        return $this;
    }
}
