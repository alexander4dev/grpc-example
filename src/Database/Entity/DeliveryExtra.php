<?php

declare(strict_types=1);

namespace App\Database\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema()
 *
 * @ORM\Entity(repositoryClass="App\Database\Repository\DeliveryExtraRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="delivery_extra",
 *   uniqueConstraints={
 *      @ORM\UniqueConstraint(columns={"offer_id", "order_date"})
 *   },
 * )
 */
class DeliveryExtra
{
    use Traits\Timestampable;

    private const DATE_FORMAT = 'Y-m-d H:i';

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
     * @var Offer|null
     *
     * @ORM\ManyToOne(
     *   targetEntity="Offer",
     *   inversedBy="deliveryExtra",
     * )
     */
    protected $offer;

    /**
     * @OA\Property(
     *   type="string",
     *   format="date-time",
     *   example="2019-05-09 12:00",
     * )
     *
     * @var DateTime|null
     * 
     * @ORM\Column(
     *   type="datetime",
     *   nullable=false,
     * )
     *
     * @Assert\Type("datetime")
     * @Assert\NotNull
     */
    protected $order_date;

    /**
     * @OA\Property()
     *
     * @var boolean|null
     * 
     * @ORM\Column(
     *   type="boolean",
     *   nullable=false,
     * )
     *
     * @Assert\Type("boolean")
     * @Assert\NotNull
     */
    protected $is_supply;

    /**
     * @OA\Property(
     *   type="string",
     *   format="date-time",
     *   example="2019-05-10 12:00",
     * )
     *
     * @var DateTime|null
     * 
     * @ORM\Column(
     *   type="datetime",
     *   nullable=true,
     * )
     *
     * @Assert\Type("datetime")
     */
    protected $delivery_date;

    /**
     * @return string
     */
    public static function getDateFormat(): string
    {
        return self::DATE_FORMAT;
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
        $offer->addDeliveryExtra($this);
        $this->offer = $offer;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getOrderDate(): ?DateTime
    {
        return $this->order_date;
    }

    /**
     * @param DateTime $orderDate
     * @return self
     */
    public function setOrderDate(DateTime $orderDate): self
    {
        $this->order_date = $orderDate;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsSupply(): ?bool
    {
        return $this->is_supply;
    }

    /**
     * @param bool $isSupply
     * @return self
     */
    public function setIsSupply(bool $isSupply): self
    {
        $this->is_supply = $isSupply;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDeliveryDate(): ?DateTime
    {
        return $this->delivery_date;
    }

    /**
     * @param DateTime $deliveryDate
     * @return self
     */
    public function setDeliveryDate(?DateTime $deliveryDate): self
    {
        $this->delivery_date = $deliveryDate;

        return $this;
    }
}
