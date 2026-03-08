<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Category;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchCategoryException;

class CategoryTest extends TestCase {

    /**
     * Testing that categoryExists() method works for existing and non-existing categories
     */
    public function testCategoryExists() {
        // Add category to database
        $category = "testCategory";
        Category::create($category);

        // Check category now exists
        $this->assertTrue(Category::categoryExists($category));

        // Delete category (also clean-up)
        Category::delete($category);

        // Test again given that category now doesn't exist
        $this->assertFalse(Category::categoryExists($category));
    }

    /**
     * Test create method for categories works as expected
     */
    public function testCategoryCreate() {
        // Empty string to test it is NOT accepted
        $emptyCategory = "        ";

        // Fail test if exception not thrown
        $exceptionThrown = false;
        try {
            Category::create($emptyCategory);
        } catch (MissingValuesException $e) {
            $exceptionThrown = true;
        }
        if (!$exceptionThrown) {
            $this->fail("Accepted empty string as valid category");
        }

        // Check accepting valid category
        $validCategory = "valid category";
        Category::create($validCategory);

        // Ensure it is now present in DB
        $this->assertTrue(Category::categoryExists($validCategory));

        // Clean-up
        Category::delete($validCategory);
    }

    /**
     * Test method returning list of available categories
     */
    public function testGetCategoryList() {
        // Create test categories for method application
        $category1 = "testCategory1";
        Category::create($category1);
        $category2 = "testCategory2";
        Category::create($category2);
        $category3 = "testCategory3";
        Category::create($category3);
        $category4 = "testCategory4";
        Category::create($category4);

        $categories = array($category1, $category2, $category3, $category4);

        // Iterate through results of getCategoryList and verify they're correct
        foreach (Category::getCategoryList() as $category) {
            $this->assertTrue(in_array($category, $categories));
        }

        // Clean-up
        foreach ($categories as $category) {
            Category::delete($category);
        }
    }

    /**
     * Test delete method for categories
     */
    public function testDeleteCategory() {
        // Create category to test deletion on
        $category1 = "testCategory1";
        Category::create($category1);

        // Check presence before and after deletion
        $this->assertTrue(Category::categoryExists($category1));
        Category::delete($category1);
        $this->assertFalse(Category::categoryExists($category1));

        // Check deletion attempt with non-existent category
        $exceptionThrown = false;
        try {
            Category::delete("testCategory2");
        } catch (NoSuchCategoryException $e) {
            $exceptionThrown = true;
        }

        if (!$exceptionThrown) {
            $this->fail("Failed to recognise non-existent category and throw error");
        }

    }

}
