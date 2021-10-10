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
 * @ORM\Entity(repositoryClass="App\Database\Repository\SectorRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="sector",
 *   uniqueConstraints={
 *   },
 * )
 */
class Sector
{
    use Traits\Timestampable;

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
     * @OA\Property()
     * 
     * @var string|null
     * 
     * @ORM\Column(
     *   type="string",
     *   nullable=false,
     * )
     *
     * @Assert\Type("string")
     * @Assert\Length(min=1, max=255)
     * @Assert\NotNull
     */
    protected $title;

    /**
     * @OA\Property(
     *   type="string",
     *   format="uuid",
     *   description="UUID поставщика",
     * )
     *
     * @var Supplier|null
     * 
     * @ORM\ManyToOne(
     *   targetEntity="Supplier",
     *   inversedBy="sectors",
     * )
     */
    protected $supplier;

    /**
     * @var Collection
     * 
     * @ORM\OneToMany(
     *   targetEntity="SectorDeliveryInterval",
     *   mappedBy="sector",
     *   cascade={"remove"}
     * )
     */
    protected $deliveryIntervals;

    /**
     * @OA\Property(
     *   minimum=0,
     * )
     * 
     * @var int|null
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
    protected $delivery_accepting_minutes;


    public function __construct()
    {
        $this->deliveryIntervals = new ArrayCollection();
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
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Supplier|null
     */
    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    /**
     * @param Supplier $supplier
     * @return self
     */
    public function setSupplier(Supplier $supplier): self
    {
        $supplier->addSector($this);
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getDeliveryIntervals(): Collection
    {
        return $this->deliveryIntervals;
    }

    /**
     * @param SectorDeliveryInterval $deliveryInterval
     * @return self
     */
    public function addDeliveryInterval(SectorDeliveryInterval $deliveryInterval): self
    {
        $this->deliveryIntervals->add($deliveryInterval);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDeliveryAcceptingMinutes(): ?int
    {
        return $this->delivery_accepting_minutes;
    }

    /**
     * @param int $deliveryAcceptingMinutes
     * @return self
     */
    public function setDeliveryAcceptingMinutes(int $deliveryAcceptingMinutes): self
    {
        $this->delivery_accepting_minutes = $deliveryAcceptingMinutes;

        return $this;
    }
}
