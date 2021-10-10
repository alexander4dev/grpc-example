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
 * @ORM\Entity(repositoryClass="App\Database\Repository\SupplierRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="supplier",
 *   uniqueConstraints={
 *   },
 * )
 */
class Supplier extends AbstractWorkingPlace
{
    use Traits\Timestampable;

    private const REDIS_HASH_KEY = 'suppliers_hash';

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
    protected $public_title;

    /**
     * @OA\Property()
     *
     * @var bool
     *
     * @ORM\Column(
     *   type="boolean",
     *   nullable=false,
     * )
     *
     * @Assert\Type("boolean")
     * @Assert\NotNull
     */
    protected $is_autorus;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *   targetEntity="Offer",
     *   mappedBy="supplier_to",
     *   cascade={"remove"},
     * )
     */
    protected $incomingOffers;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *   targetEntity="Offer",
     *   mappedBy="supplier_from",
     *   cascade={"remove"},
     * )
     */
    protected $outgoingOffers;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *   targetEntity="Sector",
     *   mappedBy="supplier",
     *   cascade={"remove"},
     * )
     */
    protected $sectors;

    /**
     * @OA\Property()
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

    /**
     * @return string
     */
    public static function getHashKey(): string
    {
        return self::REDIS_HASH_KEY;
    }

    public function __construct()
    {
        $this->incomingOffers = new ArrayCollection();
        $this->outgoingOffers = new ArrayCollection();
        $this->sectors = new ArrayCollection();

        parent::__construct();
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
     * @return string|null
     */
    public function getPublicTitle(): ?string
    {
        return $this->public_title;
    }

    /**
     * @param string $publicTitle
     * @return self
     */
    public function setPublicTitle(string $publicTitle): self
    {
        $this->public_title = $publicTitle;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsAutorus(): bool
    {
        return $this->is_autorus;
    }

    /**
     * @param bool $isAutorus
     * @return self
     */
    public function setIsAutorus(bool $isAutorus): self
    {
        $this->is_autorus = $isAutorus;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getIncomingOffers(): Collection
    {
        return $this->incomingOffers;
    }

    /**
     * @param Offer $offer
     * @return self
     */
    public function addIncomingOffer(Offer $offer): self
    {
        $this->incomingOffers->add($offer);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getOutgoingOffers(): Collection
    {
        return $this->outgoingOffers;
    }

    /**
     * @param Offer $offer
     * @return self
     */
    public function addOutgoingOffer(Offer $offer): self
    {
        $this->outgoingOffers->add($offer);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getSectors(): Collection
    {
        return $this->sectors;
    }

    /**
     * @param Sector $sector
     * @return self
     */
    public function addSector(Sector $sector): self
    {
        $this->sectors->add($sector);

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
