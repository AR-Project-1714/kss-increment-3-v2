<?php

namespace Tests\Feature\BlackBox;

use App\Enums\MaintenanceStatus;
use App\Enums\ReportStatus;
use App\Enums\SafetyStatus;
use App\Models\DailyReport;
use App\Models\SystemNotification;
use App\Services\SystemNotificationService;
use Illuminate\Support\Facades\Storage;

class SystemNotificationTest extends BlackBoxTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-08-12 10:00:00');
    }

    public function test_laporan_operasional_memindahkan_notifikasi_sesuai_event(): void
    {
        $sender = $this->operator('A');
        $receiver = $this->operator('B');
        $manager = $this->manager();

        $this->actingAs($sender)
            ->post(route('report-ops.store'), [
                'status' => ReportStatus::Submitted->value,
                'report_date' => '2026-08-12',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
            ])
            ->assertRedirect(route('report-ops.index'));

        $report = DailyReport::where('created_by', $sender->id)->firstOrFail();
        $awaitingSignature = SystemNotification::where('user_id', $receiver->id)->firstOrFail();

        $this->assertSame('report:operational:'.$report->id.':awaiting-acknowledgement', $awaitingSignature->event_key);
        $this->assertTrue($awaitingSignature->expires_at->equalTo(now()->addDays(14)));
        $this->assertDatabaseMissing('system_notifications', [
            'user_id' => $sender->id,
            'event_key' => $awaitingSignature->event_key,
        ]);

        $this->actingAs($receiver)
            ->post(route('report-ops.sign', $report))
            ->assertSessionHas('success');

        $this->assertNotNull($awaitingSignature->fresh()->resolved_at);
        $managerNotification = SystemNotification::query()
            ->where('user_id', $manager->id)
            ->where('event_key', 'report:operational:'.$report->id.':awaiting-approval')
            ->firstOrFail();

        $this->actingAs($manager)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('items.0.id', $managerNotification->id)
            ->assertJsonPath('items.0.expires_at', '26 Agt 2026, 10:00');

        $this->actingAs($manager)
            ->patchJson(route('notifications.read', $managerNotification))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNotNull($managerNotification->fresh()->read_at);

        $this->actingAs($manager)
            ->post(route('manajer.reports.approve', $report))
            ->assertRedirect(route('manajer.archive'));

        $this->assertNotNull($managerNotification->fresh()->resolved_at);
        $approved = SystemNotification::query()
            ->where('user_id', $sender->id)
            ->where('event_key', 'report:operational:'.$report->id.':approved')
            ->firstOrFail();
        $this->assertTrue($approved->expires_at->equalTo(now()->addDays(7)));
    }

    public function test_laporan_pemeliharaan_dan_k3_memberi_notifikasi_manajer(): void
    {
        $manager = $this->manager();
        $maintenance = $this->maintenance();
        $safety = $this->safety();

        $this->actingAs($maintenance)
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Submitted->value,
                'report_date' => '2026-08-12',
                'work_time_start' => '07:00',
                'work_time_end' => '16:00',
            ])
            ->assertRedirect(route('pemeliharaan.index'));

        $this->actingAs($safety)
            ->post(route('safety.store'), [
                'status' => SafetyStatus::Submitted->value,
                'report_date' => '2026-08-12',
                'work_time_start' => '07:00',
                'work_time_end' => '16:00',
            ])
            ->assertRedirect(route('safety.index'));

        $this->assertDatabaseCount('system_notifications', 2);
        $this->assertSame(
            2,
            SystemNotification::query()->where('user_id', $manager->id)->active()->count()
        );
    }

    public function test_backup_manual_memperbarui_notifikasi_terbaru_admin(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.backup.generate'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Backup manual berhasil dibuat.');

        $notification = SystemNotification::where('user_id', $admin->id)
            ->where('event_key', 'system:backup:latest')
            ->firstOrFail();

        $this->assertSame('success', $notification->severity);
        $this->assertSame('Backup manual berhasil', $notification->title);
        $this->assertTrue($notification->expires_at->equalTo(now()->addDays(7)));

        $this->travel(1)->hour();
        app(SystemNotificationService::class)->backupSucceeded('backup-kss-otomatis-terbaru.json', true);

        $notification->refresh();
        $this->assertSame(1, SystemNotification::where('user_id', $admin->id)->count());
        $this->assertSame('Backup otomatis berhasil', $notification->title);
        $this->assertStringContainsString('backup-kss-otomatis-terbaru.json', $notification->message);
        $this->assertTrue($notification->created_at->equalTo(now()));
    }

    public function test_kotak_hanya_menampilkan_notifikasi_aktif_dan_dapat_menandai_semua_dibaca(): void
    {
        $user = $this->operator('A');

        foreach ([
            ['event_key' => 'active', 'resolved_at' => null, 'expires_at' => now()->addDay()],
            ['event_key' => 'expired', 'resolved_at' => null, 'expires_at' => now()->subMinute()],
            ['event_key' => 'resolved', 'resolved_at' => now(), 'expires_at' => now()->addDay()],
        ] as $item) {
            SystemNotification::create(array_merge($item, [
                'user_id' => $user->id,
                'title' => ucfirst($item['event_key']),
                'message' => 'Pengujian status notifikasi.',
            ]));
        }

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.title', 'Active')
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($user)
            ->patchJson(route('notifications.read-all'))
            ->assertOk();

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertJsonPath('unread_count', 0);
    }
}
