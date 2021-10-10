<?php

declare(strict_types=1);

namespace App\Database\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema()
 *
 * @ORM\Entity(repositoryClass="App\Database\Repository\DeliveryScheduleRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="delivery_schedule",
 *   uniqueConstraints={
 *      @ORM\UniqueConstraint(columns={"offer_id", "day_number", "order_time"})
 *   },
 * )
 */
class DeliverySchedule
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
     * @var Offer|null
     *
     * @ORM\ManyToOne(
     *   targetEntity="Offer",
     *   inversedBy="deliverySchedule",
     * )
     */
    protected $offer;

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
     * @Assert\Type("integer")
     * @Assert\Range(min=1, max=7)
     * @Assert\NotNull
     */
    protected $day_number;

    /**
     * @OA\Property(
     *   type="string",
     *   format="time-hour",
     *   example="09:00",
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
    protected $order_time;

    /**
     * @OA\Property(
     *   minimum=0,
     * )
     * 
     * @var Int|null
     * 
     * @ORM\Column(
     *   type="integer",
     *   nullable=false,
     *   options={
     *     "unsigned": true,
     *   },
     * )
     *
     * @Assert\Type("integer")
     * @Assert\Range(min=0)
     * @Assert\NotNull
     */
    protected $delivery_minutes;

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
     * @return Offer|null
     */
    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    /**
     * @param Offer $offer
     * @return self
     */
    public function setOffer(Offer $offer): self
    {
        $offer->addDeliverySchedule($this);
        $this->offer = $offer;

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
    public function getOrderTime(): ?DateTime
    {
        return $this->order_time;
    }

    /**
     * @param DateTime $orderTime
     * @return self
     */
    public function setOrderTime(DateTime $orderTime): self
    {
        $this->order_time = $orderTime;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDeliveryMinutes(): ?int
    {
        return $this->delivery_minutes;
    }

    /**
     * @param int $minutes
     * @return self
     */
    public function setDeliveryMnutes(int $minutes): self
    {
        $this->delivery_minutes = $minutes;

        return $this;
    }
}
