<?php

namespace App\Domain\Models\AgeCategory;
use App\Domain\Models\IModel;

class AgeCategory implements IModel
{
    private $idAgeCategory;
    private $nameAgeCategory;
    private $minAge;
    private $maxAge;
    private $countTestsForGoldForMan;
    private $countTestForBronzeForMan;
    private $countTestFromSilverForMan;
    private $countTestsForGoldForWoman;
    private $countTestForBronzeForWoman;
    private $countTestFromSilverForWoman;
    private $allTests;

    public function __construct(
        int $idAgeCategory,
        string $nameAgeCategory,
        int $minAge,
        int $maxAge,
        int $countTestForBronzeForMan,
        int $countTestFromSilverForMan,
        int $countTestsForGoldForMan,
        int $countTestForBronzeForWoman,
        int $countTestFromSilverForWoman,
        int $countTestsForGoldForWoman,
        int $allTests
    )
    {
        $this->idAgeCategory = $idAgeCategory;
        $this->nameAgeCategory = $nameAgeCategory;
        $this->minAge = $minAge;
        $this->maxAge = $maxAge;
        $this->countTestForBronzeForMan = $countTestForBronzeForMan;
        $this->countTestFromSilverForMan = $countTestFromSilverForMan;
        $this->countTestsForGoldForMan = $countTestsForGoldForMan;
        $this->countTestForBronzeForWoman = $countTestForBronzeForWoman;
        $this->countTestFromSilverForWoman = $countTestFromSilverForWoman;
        $this->countTestsForGoldForWoman = $countTestsForGoldForWoman;
        $this->allTests = $allTests;
    }

    /**
     * @return int
     */
    public function getAllTests(): int
    {
        return $this->allTests;
    }

    /**
     * @return int
     */
    public function getCountTestForBronzeForMan(): int
    {
        return $this->countTestForBronzeForMan;
    }

    /**
     * @return int
     */
    public function getCountTestForBronzeForWoman(): int
    {
        return $this->countTestForBronzeForWoman;
    }

    /**
     * @return int
     */
    public function getCountTestFromSilverForMan(): int
    {
        return $this->countTestFromSilverForMan;
    }

    /**
     * @return int
     */
    public function getCountTestFromSilverForWoman(): int
    {
        return $this->countTestFromSilverForWoman;
    }

    /**
     * @return int
     */
    public function getCountTestsForGoldForMan(): int
    {
        return $this->countTestsForGoldForMan;
    }

    /**
     * @return int
     */
    public function getCountTestsForGoldForWoman(): int
    {
        return $this->countTestsForGoldForWoman;
    }

    /**
     * @return int
     */
    public function getIdAgeCategory(): int
    {
        return $this->idAgeCategory;
    }

    /**
     * @return int
     */
    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    /**
     * @return int
     */
    public function getMinAge(): int
    {
        return $this->minAge;
    }

    /**
     * @return string
     */
    public function getNameAgeCategory(): string
    {
        return $this->nameAgeCategory;
    }

    public function toArray(): array
    {
        // TODO: Implement toArray() method.
    }
}