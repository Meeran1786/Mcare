<?php

namespace Tests\Feature;

use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PharmacyFilesTest extends TestCase
{
    use RefreshDatabase;

    private Pharmacy $pharmacy;
    private User $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        // The distance query uses MySQL maths functions that SQLite lacks.
        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        foreach (['acos' => 'acos', 'cos' => 'cos', 'sin' => 'sin', 'radians' => 'deg2rad'] as $name => $fn) {
            $pdo->sqliteCreateFunction($name, $fn, 1);
        }
        $pdo->sqliteCreateFunction('LEAST', fn (...$a) => min($a));
        $pdo->sqliteCreateFunction('GREATEST', fn (...$a) => max($a));

        $this->vendor = User::create(['mobile_number' => '9200000001', 'role' => 'vendor', 'status' => 'active']);
        $this->pharmacy = Pharmacy::create([
            'user_id' => $this->vendor->id,
            'owner_name' => 'Owner',
            'pharmacy_name' => 'Shop',
            'license_number' => 'LIC-1',
            'registration_number' => 'REG-1',
            'address_line' => '1 Main Road',
            'area' => 'Center',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'pincode' => '500001',
            'latitude' => 17.38,
            'longitude' => 78.48,
            'verification_status' => 'pending',
            'is_verified' => true,
            'is_delivery_available' => true,
        ]);
        Sanctum::actingAs($this->vendor);
    }

    private function img(string $name): UploadedFile
    {
        return UploadedFile::fake()->image($name, 400, 300);
    }

    public function test_licence_and_gst_take_several_private_pages(): void
    {
        $this->post('/api/vendor/pharmacy/documents', [
            'license_files' => [$this->img('l1.jpg'), $this->img('l2.jpg'), $this->img('l3.jpg')],
            'gst_files' => [$this->img('g1.jpg'), $this->img('g2.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $p = $this->pharmacy->fresh();
        $this->assertSame(3, $p->files()->where('kind', 'license')->count());
        $this->assertSame(2, $p->files()->where('kind', 'gst')->count());
        // First page still in the old column for old apps and admin.
        $this->assertSame($p->files()->where('kind', 'license')->first()->path, $p->license_file);
        foreach ($p->files as $f) {
            Storage::disk('local')->assertExists($f->path);
            Storage::disk('public')->assertMissing($f->path);
        }

        $details = $this->getJson('/api/vendor/pharmacy/details')->assertOk();
        $this->assertCount(3, $details->json('data.pharmacy.license_pages'));
        $this->assertStringContainsString('signature=', $details->json('data.pharmacy.license_pages.0'));

        // Uploading again replaces all pages.
        $this->post('/api/vendor/pharmacy/documents', [
            'license_files' => [$this->img('new.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(1, $p->files()->where('kind', 'license')->count());
        $this->assertSame(2, $p->files()->where('kind', 'gst')->count());
    }

    public function test_old_single_file_upload_still_works(): void
    {
        $this->post('/api/vendor/pharmacy/documents', [
            'license_file' => UploadedFile::fake()->create('licence.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();

        $p = $this->pharmacy->fresh();
        Storage::disk('local')->assertExists($p->license_file);
        $this->assertCount(1, $p->documentPageUrls('license'));
    }

    public function test_shop_photos_up_to_five_and_patients_see_them(): void
    {
        $res = $this->post('/api/vendor/pharmacy/photos', [
            'photos' => [$this->img('a.jpg'), $this->img('b.jpg'), $this->img('c.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $p = $this->pharmacy->fresh();
        $this->assertNotNull($p->pharmacy_photo); // first one became the cover
        $this->assertCount(2, $res->json('data.photos'));

        $this->post('/api/vendor/pharmacy/photos', [
            'photos' => [$this->img('d.jpg'), $this->img('e.jpg'), $this->img('f.jpg')],
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $id = $res->json('data.photos.0.id');
        $this->deleteJson("/api/vendor/pharmacy/photos/{$id}")->assertOk()->assertJsonCount(1, 'data.photos');

        $list = $this->getJson('/api/pharmacies?search=Shop')->assertOk();
        $this->assertCount(2, $list->json('data.data.0.photo_urls'));
        $this->assertArrayNotHasKey('files', $list->json('data.data.0'));
    }

    public function test_cannot_delete_another_pharmacys_photo(): void
    {
        $other = User::create(['mobile_number' => '9200000002', 'role' => 'vendor', 'status' => 'active']);
        $theirs = Pharmacy::create(array_merge($this->pharmacy->only([
            'owner_name', 'license_number', 'registration_number', 'address_line', 'area', 'city', 'state', 'pincode', 'latitude', 'longitude',
        ]), ['user_id' => $other->id, 'pharmacy_name' => 'Other']));
        $photo = $theirs->files()->create(['kind' => 'photo', 'path' => 'pharmacies/photos/x.jpg']);

        $this->deleteJson("/api/vendor/pharmacy/photos/{$photo->id}")->assertNotFound();
        $this->assertDatabaseHas('pharmacy_files', ['id' => $photo->id]);
    }

    public function test_nearby_list_marks_new_pharmacies_and_skips_far_ones(): void
    {
        $far = User::create(['mobile_number' => '9200000003', 'role' => 'vendor', 'status' => 'active']);
        $old = Pharmacy::create(array_merge($this->pharmacy->only([
            'owner_name', 'license_number', 'registration_number', 'address_line', 'area', 'city', 'state', 'pincode',
        ]), ['user_id' => $far->id, 'pharmacy_name' => 'Far Away', 'latitude' => 17.60, 'longitude' => 78.48,
            'is_verified' => true, 'is_delivery_available' => true]));
        $this->pharmacy->forceFill(['created_at' => now()->subDays(90)])->save();

        $res = $this->getJson('/api/pharmacies?lat=17.381&lng=78.481&radius=5')->assertOk();
        $this->assertSame(['Shop'], array_column($res->json('data.data'), 'pharmacy_name'));
        $this->assertFalse($res->json('data.data.0.is_new'));

        $this->pharmacy->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->assertTrue($this->getJson('/api/pharmacies?lat=17.381&lng=78.481')->json('data.data.0.is_new'));
    }

    public function test_clear_one_or_all_notifications(): void
    {
        foreach ([1, 2, 3] as $n) {
            \App\Models\Notification::create(['user_id' => $this->vendor->id, 'title' => "N{$n}", 'body' => 'x', 'type' => 'info']);
        }
        $other = User::create(['mobile_number' => '9200000004', 'role' => 'user', 'status' => 'active']);
        \App\Models\Notification::create(['user_id' => $other->id, 'title' => 'Theirs', 'body' => 'x', 'type' => 'info']);

        $first = \App\Models\Notification::where('user_id', $this->vendor->id)->first();
        $this->deleteJson("/api/notifications/{$first->id}")->assertOk();
        $this->deleteJson('/api/notifications')->assertOk()->assertJsonPath('data.cleared', 2);
        $this->assertSame(0, \App\Models\Notification::where('user_id', $this->vendor->id)->count());
        $this->assertSame(1, \App\Models\Notification::where('user_id', $other->id)->count());
    }
}
