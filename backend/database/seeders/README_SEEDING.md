# Database Seeding Guide

This guide explains how to manually seed specific parts of the database without running the entire `DatabaseSeeder`.

## Running Individual Seeders

You can run individual seeders using Laravel's artisan command:

```bash
php artisan db:seed --class=SeederClassName
```

## Available Seeders

### Test-Related Seeders

#### 1. Test Categories
Creates the standard test categories (Hematology, Clinical Chemistry, etc.)
```bash
php artisan db:seed --class=TestCategorySeeder
```

#### 2. Comprehensive Test Parameters (Recommended)
Creates test categories, tests, and test parameters with proper relationships:
```bash
php artisan db:seed --class=ComprehensiveTestParameterSeeder
```

This seeder will:
- Create/use 8 test categories
- Create 50+ tests across all categories
- Create 200+ test parameters with proper relationships
- All data is non-random and properly matched

#### 3. Original Test Parameters (Basic)
Creates basic CBC and Blood Typing parameters:
```bash
php artisan db:seed --class=TestParameterSeeder
```

#### 4. Reference Ranges
Creates reference ranges for test parameters:
```bash
php artisan db:seed --class=ReferenceRangeSeeder
```

#### 4.1 Comprehensive Reference Ranges (Recommended)
Creates adult reference ranges for the most common analytes matching your comprehensive test parameters:
```bash
php artisan db:seed --class=ComprehensiveReferenceRangeSeeder
```
This uses the correct columns (`age_min`, `age_max`, `age_unit`, `si_unit`, `conv_unit`) and is safe to re-run.

### Delta Check & QC Rules

#### 5. Delta Check Rules
Creates delta check rules for test parameters:
```bash
php artisan db:seed --class=DeltaCheckRuleSeeder
```

#### 6. QC Rules
Creates QC rules for quality control:
```bash
php artisan db:seed --class=QcRuleSeeder
```

### CDS (Clinical Decision Support) Rules

#### 7. CDS Correlation Rules (Recommended)
Creates clinically meaningful CDS rules using real thresholds and parameter correlations:
```bash
php artisan db:seed --class=CdsCorrelationRuleSeeder
```
Rules included (if parameters exist):
- Diabetes correlation: GLU ≥ 11.1 mmol/L OR HbA1c ≥ 6.5%
- Critical Potassium: K < 2.5 or > 6.5 mmol/L (stop auto-verify)
- Transaminases both high: ALT > 41 and AST > 40 (add comment)
- Microcytic anemia: HGB low AND MCV < 80 fL → reflex Ferritin
- High triglycerides (> 4.5 mmol/L): LDL calculation unreliable (add comment)
- Sepsis markers high: CRP > 100 mg/L OR PCT > 2.0 ng/mL (stop auto-verify)

### Other Seeders

#### 7. Users
```bash
php artisan db:seed --class=UserSeeder
```

#### 8. User Roles
```bash
php artisan db:seed --class=UserRoleSeeder
```

#### 9. Patients
```bash
php artisan db:seed --class=PatientSeeder
```

#### 10. Doctors
```bash
php artisan db:seed --class=DoctorSeeder
```

#### 11. Wards
```bash
php artisan db:seed --class=WardSeeder
```

#### 12. Rooms
```bash
php artisan db:seed --class=RoomSeeder
```

#### 13. Patient Types
```bash
php artisan db:seed --class=PatientTypeSeeder
```

#### 14. Sample Types
```bash
php artisan db:seed --class=SampleTypeSeeder
```

#### 15. QC Sigma Config
```bash
php artisan db:seed --class=QcAnalyteSigmaConfigSeeder
```

#### 16. QC Sigma Results
```bash
php artisan db:seed --class=QcSigmaMetricResultSeeder
```

## Recommended Seeding Order

If you want to seed test-related data, follow this order:

1. **Test Categories** (if not already seeded):
   ```bash
   php artisan db:seed --class=TestCategorySeeder
   ```

2. **Tests and Parameters** (comprehensive):
   ```bash
   php artisan db:seed --class=ComprehensiveTestParameterSeeder
   ```

3. **Reference Ranges** (optional):
   ```bash
   php artisan db:seed --class=ReferenceRangeSeeder
   ```

4. **Delta Check Rules** (optional):
   ```bash
   php artisan db:seed --class=DeltaCheckRuleSeeder
   ```

5. **QC Rules** (optional):
   ```bash
   php artisan db:seed --class=QcRuleSeeder
   ```

## Notes

- Most seeders use `firstOrCreate` or `updateOrCreate`, so they can be run multiple times safely
- The `ComprehensiveTestParameterSeeder` will create categories if they don't exist, so you can run it independently
- Use `--force` flag if you need to bypass confirmation in production:
  ```bash
  php artisan db:seed --class=ComprehensiveTestParameterSeeder --force
  ```

## Running Multiple Seeders

You can also create a custom seeder that runs multiple seeders:

```php
php artisan make:seeder CustomSeeder
```

Then in the seeder:
```php
public function run(): void
{
    $this->call([
        TestCategorySeeder::class,
        ComprehensiveTestParameterSeeder::class,
        ReferenceRangeSeeder::class,
    ]);
}
```

Run it with:
```bash
php artisan db:seed --class=CustomSeeder
```

