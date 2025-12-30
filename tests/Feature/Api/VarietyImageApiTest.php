<?php

namespace Tests\Feature\Api;

use App\Models\Commodity;
use App\Models\User;
use App\Models\Variety;
use App\Models\VarietyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VarietyImageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_requires_authentication(): void
    {
        $commodity = Commodity::factory()->create();
        $variety = Variety::factory()->create(['commodity_id' => $commodity->id]);

        $response = $this->post("/api/varieties/{$variety->id}/images", [
            'images' => [UploadedFile::fake()->image('a.jpg')],
        ]);

        $response->assertStatus(302);
    }

    public function test_upload_forbidden_for_non_admin(): void
    {
        Storage::fake('public');

        $commodity = Commodity::factory()->create();
        $variety = Variety::factory()->create(['commodity_id' => $commodity->id]);
        $user = User::factory()->nonAdmin()->create();

        $response = $this->actingAs($user)->post("/api/varieties/{$variety->id}/images", [
            'images' => [UploadedFile::fake()->image('a.jpg')],
        ]);

        $response->assertStatus(403);
    }

    public function test_upload_validates_count_and_mime_and_size(): void
    {
        Storage::fake('public');

        $commodity = Commodity::factory()->create();
        $variety = Variety::factory()->create(['commodity_id' => $commodity->id]);
        $admin = User::factory()->admin()->create();

        $tooMany = [];
        for ($i = 0; $i < 6; $i++) {
            $tooMany[] = UploadedFile::fake()->image("{$i}.jpg");
        }

        $res1 = $this->actingAs($admin)->postJson("/api/varieties/{$variety->id}/images", [
            'images' => $tooMany,
        ]);
        $res1->assertStatus(422);

        $res2 = $this->actingAs($admin)->postJson("/api/varieties/{$variety->id}/images", [
            'images' => [UploadedFile::fake()->create('x.gif', 10, 'image/gif')],
        ]);
        $res2->assertStatus(422);

        $res3 = $this->actingAs($admin)->postJson("/api/varieties/{$variety->id}/images", [
            'images' => [UploadedFile::fake()->create('big.jpg', 6000, 'image/jpeg')],
        ]);
        $res3->assertStatus(422);
    }

    public function test_upload_creates_records_and_stores_files_and_sets_primary_when_first(): void
    {
        Storage::fake('public');

        $commodity = Commodity::factory()->create();
        $variety = Variety::factory()->create(['commodity_id' => $commodity->id]);
        $admin = User::factory()->admin()->create();

        $images = [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.png'),
        ];

        $response = $this->actingAs($admin)->post("/api/varieties/{$variety->id}/images", [
            'images' => $images,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => [['id', 'variety_id', 'image_path', 'image_url', 'is_primary', 'order']]]);

        $this->assertDatabaseCount('variety_images', 2);

        $primaryCount = VarietyImage::query()
            ->where('variety_id', $variety->id)
            ->where('is_primary', true)
            ->count();
        $this->assertSame(1, $primaryCount);

        $stored = VarietyImage::query()->where('variety_id', $variety->id)->get();
        foreach ($stored as $img) {
            $this->assertTrue(Storage::disk('public')->exists($img->image_path));
            $this->assertStringContainsString("varieties/variety_{$variety->id}_", $img->image_path);
        }
    }

    public function test_set_primary_switches_previous_primary(): void
    {
        Storage::fake('public');

        $commodity = Commodity::factory()->create();
        $variety = Variety::factory()->create(['commodity_id' => $commodity->id]);
        $admin = User::factory()->admin()->create();

        $img1 = VarietyImage::create([
            'variety_id' => $variety->id,
            'image_path' => 'varieties/variety_'.$variety->id.'_a.jpg',
            'is_primary' => true,
            'order' => 1,
        ]);
        $img2 = VarietyImage::create([
            'variety_id' => $variety->id,
            'image_path' => 'varieties/variety_'.$variety->id.'_b.jpg',
            'is_primary' => false,
            'order' => 2,
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/varieties/{$variety->id}/images/{$img2->id}/primary");

        $response->assertOk()->assertJsonPath('data.id', $img2->id);

        $this->assertDatabaseHas('variety_images', ['id' => $img1->id, 'is_primary' => false]);
        $this->assertDatabaseHas('variety_images', ['id' => $img2->id, 'is_primary' => true]);
    }

    public function test_delete_disallows_deleting_last_image_and_deletes_file_on_success(): void
    {
        Storage::fake('public');

        $commodity = Commodity::factory()->create();
        $variety = Variety::factory()->create(['commodity_id' => $commodity->id]);
        $admin = User::factory()->admin()->create();

        $img1Path = 'varieties/variety_'.$variety->id.'_a.jpg';
        $img2Path = 'varieties/variety_'.$variety->id.'_b.jpg';
        Storage::disk('public')->put($img1Path, 'a');
        Storage::disk('public')->put($img2Path, 'b');

        $img1 = VarietyImage::create([
            'variety_id' => $variety->id,
            'image_path' => $img1Path,
            'is_primary' => true,
            'order' => 1,
        ]);
        $img2 = VarietyImage::create([
            'variety_id' => $variety->id,
            'image_path' => $img2Path,
            'is_primary' => false,
            'order' => 2,
        ]);

        $res1 = $this->actingAs($admin)
            ->deleteJson("/api/varieties/{$variety->id}/images/{$img1->id}");
        $res1->assertOk();

        $this->assertFalse(Storage::disk('public')->exists($img1Path));
        $this->assertSoftDeleted('variety_images', ['id' => $img1->id]);
        $this->assertDatabaseHas('variety_images', ['id' => $img2->id, 'is_primary' => true]);

        $res2 = $this->actingAs($admin)
            ->deleteJson("/api/varieties/{$variety->id}/images/{$img2->id}");
        $res2->assertStatus(422);
    }
}
