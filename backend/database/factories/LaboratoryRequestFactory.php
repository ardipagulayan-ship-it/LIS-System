<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\LaboratoryRequest; // Ensure this matches your model location
use App\Models\Patient;
use App\Models\Doctor; // <-- Need Doctor model again
use App\Models\User; // Keep for referring_doctor and created_by
use App\Models\SampleType;
use App\Models\Ward;
use App\Models\Room;
use App\Models\PatientType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LaboratoryRequest>
 */
class LaboratoryRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LaboratoryRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Fetch IDs from related tables. Assumes they are seeded.
        // Consider adding checks or caching these for performance if seeding many requests.
        $patientId = Patient::inRandomOrder()->first()?->id;
        $doctorId = Doctor::inRandomOrder()->first()?->id; // <-- Get doctor_id from Doctor model
        $userIds = User::pluck('id')->toArray(); // Use User IDs for referring/creator roles
        $sampleTypeIds = SampleType::pluck('id')->toArray();
        $wardIds = Ward::pluck('id')->toArray();
        $patientTypeIds = PatientType::pluck('id')->toArray();
        $priorityLevels = ['Routine', 'Urgent', 'STAT'];

        // Select a random ward and then a random room within that ward
        $selectedWardId = !empty($wardIds) ? $wardIds[array_rand($wardIds)] : null;
        $selectedRoomId = null;
        if ($selectedWardId) {
            $roomIdsInWard = Room::where('ward_id', $selectedWardId)->pluck('id')->toArray();
            if (!empty($roomIdsInWard)) {
                $selectedRoomId = $roomIdsInWard[array_rand($roomIdsInWard)];
            }
        }

        // Randomly assign a referring doctor (user) or leave it null
        $referringDoctorId = (rand(0, 2) === 1 && !empty($userIds)) ? $userIds[array_rand($userIds)] : null;

        // Assign creator user ID
        $creatorId = !empty($userIds) ? $userIds[array_rand($userIds)] : null;

        return [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId, // <-- Use ID from Doctor model
            'sample_id' => !empty($sampleTypeIds) ? $sampleTypeIds[array_rand($sampleTypeIds)] : null,
            'ward_id' => $selectedWardId,
            'room_id' => $selectedRoomId,
            'referring_doctor' => $referringDoctorId, // Use User ID
            'priority_level' => $priorityLevels[array_rand($priorityLevels)],
            'patient_type_id' => !empty($patientTypeIds) ? $patientTypeIds[array_rand($patientTypeIds)] : null,
            'created_by' => $creatorId, // Use User ID
            'request_date' => fake()->dateTimeBetween('-1 month', 'now'),
            // 'status' => fake()->randomElement(['Pending', 'In Progress', 'Completed', 'Cancelled']), // Removed - column dropped by migration
            // 'total_amount' => 0.00, // Removed - column dropped & calculated later
        ];
    }
}