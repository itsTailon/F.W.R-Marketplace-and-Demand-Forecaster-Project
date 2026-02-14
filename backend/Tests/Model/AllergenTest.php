<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Allergen;

class AllergenTest extends TestCase {

    // Array of all allergen values stored as base data in the DB
    private array $allergens = [
        "celery",
        "gluten",
        "crustaceans",
        "eggs",
        "fish",
        "lupin",
        "milk",
        "molluscs",
        "mustard",
        "nuts",
        "peanuts",
        "sesame-seeds",
        "soya",
        "sulphites",
    ];

    public function testAllergenExists() {
        foreach ($this->allergens as $allergen) {
            $this->assertTrue(Allergen::allergenExists($allergen));
        }
    }

    public function testGetAllergensList() {
        foreach (Allergen::getAllergensList() as $allergen) {
            $this->assertTrue(in_array($allergen, $this->allergens));
        }
    }

}