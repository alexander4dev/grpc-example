<?php

declare(strict_types=1);

namespace App\Database\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(
 *   name="type",
 *   type="string",
 * )
 * @ORM\Table(
 *   name="abstract_working_place",
 *   uniqueConstraints={
 *   },
 * )
 */
abstract class AbstractWorkingPlace
{
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
     * @var Collection
     * 
     * @ORM\OneToMany(
     *   targetEntity="WorkingSchedule",
     *   mappedBy="working_place",
     *   cascade={"remove"},
     * )
     */
    protected $workingSchedule;

    /**
     * @var Collection
     * 
     * @ORM\OneToMany(
     *   targetEntity="WorkingExtraDay",
     *   mappedBy="working_place",
     *   cascade={"remove"},
     * )
     */
    protected $workingExtraDays;

    public function __construct()
    {
        $this->workingSchedule = new ArrayCollection();
        $this->workingExtraDays = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection
     */
    public function getWorkingSchedule(): Collection
    {
        return $this->workingSchedule;
    }

    /**
     * @param WorkingSchedule $schedule
     * @return self
     */
    public function addWorkingSchedule(WorkingSchedule $schedule): self
    {
        $this->workingSchedule->add($schedule);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getWorkingExtraDays(): Collection
    {
        return $this->workingExtraDays;
    }

    /**
     * @param WorkingExtraDay $extraDay
     * @return self
     */
    public function addWorkingExtraDay(WorkingExtraDay $extraDay): self
    {
        $this->workingExtraDays->add($extraDay);

        return $this;
    }
}
