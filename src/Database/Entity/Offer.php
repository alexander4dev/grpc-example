<?php

declare(strict_types=1);

namespace App\Database\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema()
 * 
 * @ORM\Entity(repositoryClass="App\Database\Repository\OfferRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="offer",
 *   uniqueConstraints={
 *   },
 * )
 */
class Offer
{
    use Traits\Timestampable;

    private const REDIS_HASH_KEY = 'offers_hash';

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
     * @OA\Property(
     *   format="uuid",
     * )
     * 
     * @var string|null
     *
     * @ORM\Column(
     *   type="string",
     *   length=36,
     *   nullable=false,
     *   unique=true
     * )
     *
     * @Assert\Uuid
     * @Assert\NotNull
     */
    protected $uuid;

    /**
     * @OA\Property(
     *   type="string",
     *   format="uuid",
     *   description="UUID поставщика, сделавшего предложение",
     * )
     *
     * @var Supplier|null
     *
     * @ORM\ManyToOne(
     *   targetEntity="Supplier",
     *   inversedBy="outgoingOffers",
     * )
     */
    protected $supplier_from;

    /**
     * @OA\Property(
     *   type="string",
     *   format="uuid",
     *   description="UUID поставщика, получившего предложение",
     * )
     * 
     * @var Supplier|null
     *
     * @ORM\ManyToOne(
     *   targetEntity="Supplier",
     *   inversedBy="incomingOffers",
     * )
     */
    protected $supplier_to;

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
    protected $is_enabled;

    /**
     * @var DeliverySchedule|null
     *
     * @ORM\OneToMany(
     *   targetEntity="DeliverySchedule",
     *   mappedBy="offer",
     *   cascade={"remove"}
     * )
     */
    protected $deliverySchedule;

    /**
     * @var DeliveryExtra|null
     *
     * @ORM\OneToMany(
     *   targetEntity="DeliveryExtra",
     *   mappedBy="offer",
     *   cascade={"remove"}
     * )
     */
    protected $deliveryExtra;

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
    protected $order_initializing_minutes;

    /**
     * @return string
     */
    public static function getHashKey(): string
    {
        return self::REDIS_HASH_KEY;
    }

    public function __construct()
    {
        $this->deliverySchedule = new ArrayCollection();
        $this->deliveryExtra = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return self
     */
    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Supplier|null
     */
    public function getSupplierFrom(): ?Supplier
    {
        return $this->supplier_from;
    }

    /**
     * @param Supplier $supplier
     * @return self
     */
    public function setSupplierFrom(Supplier $supplier): self
    {
        $supplier->addOutgoingOffer($this);
        $this->supplier_from = $supplier;

        return $this;
    }

    /**
     * @return Supplier|null
     */
    public function getSupplierTo(): ?Supplier
    {
        return $this->supplier_to;
    }

    /**
     * @param Supplier $supplier
     * @return self
     */
    public function setSupplierTo(Supplier $supplier): self
    {
        $supplier->addIncomingOffer($this);
        $this->supplier_to = $supplier;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsEnabled(): ?bool
    {
        return $this->is_enabled;
    }

    /**
     * @param bool $isEnabled
     * @return self
     */
    public function setIsEnabled(bool $isEnabled): self
    {
        $this->is_enabled = $isEnabled;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getDeliverySchedule(): Collection
    {
        return $this->deliverySchedule;
    }

    /**
     * @param DeliverySchedule $deliverySchedule
     * @return self
     */
    public function addDeliverySchedule(DeliverySchedule $deliverySchedule): self
    {
        $this->deliverySchedule->add($deliverySchedule);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getDeliveryExtra(): Collection
    {
        return $this->deliveryExtra;
    }

    /**
     * @param DeliveryExtra $deliveryExtra
     * @return self
     */
    public function addDeliveryExtra(DeliveryExtra $deliveryExtra): self
    {
        $this->deliveryExtra->add($deliveryExtra);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrderInitializingMinutes(): ?int
    {
        return $this->order_initializing_minutes;
    }

    /**
     * @param int $orderInitializingMinutes
     * @return self
     */
    public function setOrderInitializingMinutes(int $orderInitializingMinutes): self
    {
        $this->order_initializing_minutes = $orderInitializingMinutes;

        return $this;
    }
}
