<?php
    // database/factories/TestFactory.php
    namespace Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use App\Models\Test;
    use App\Models\TestCategory; // Assuming TestCategory exists

    /**
     * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Test>
     */
    class TestFactory extends Factory
    {
        /**
         * The name of the factory's corresponding model.
         *
         * @var string
         */
        protected $model = Test::class;

        /**
         * Define the model's default state.
         *
         * @return array<string, mixed>
         */
        public function definition(): array
        {
            $tests = [
                'Complete Blood Count (CBC)' => 450.00,
                'Blood Glucose (Fasting)' => 350.50,
                'Lipid Profile' => 450.00,
                'Urinalysis' => 300.00,
                'Thyroid Function Test (TFT)' => 550.00,
                'Liver Function Test (LFT)' => 400.00,
                'Kidney Function Test (KFT)' => 650.00,
                'Electrolytes Panel' => 350.00,
                'Hemoglobin A1c (HbA1c)' => 600.00,
                'Culture & Sensitivity' => 800.00,
                'Gram Stain' => 250.00,
            ];
            $testName = fake()->randomElement(array_keys($tests));

            return [
                'name' => $testName,
                'description' => fake()->sentence(3),
                'price' => $tests[$testName],
                // Assign a category - Ensure TestCategory exists and has records, or handle null
                'test_category_id' => TestCategory::exists() ? TestCategory::inRandomOrder()->first()->id : null,
            ];
        }
    }