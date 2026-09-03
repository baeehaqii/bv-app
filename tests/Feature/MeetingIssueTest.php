<?php

use App\Enums\IssueStatus;
use App\Filament\Resources\MeetingIssues\Pages\ListMeetingIssues;
use App\Models\MeetingIssue;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/** Sheet "Issues To Discuss" dari dokumen Weekly Meeting (format Level 10). */
function meetingUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    return tap(User::create([
        'name' => 'Meeting Admin',
        'email' => 'meeting-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]))->syncRoles(['super_admin']);
}

function isu(array $atribut = []): MeetingIssue
{
    return MeetingIssue::create(array_merge([
        'meeting_date' => now()->toDateString(),
        'pic' => 'Gerry',
        'issue' => 'Payment Workflow',
        'status' => IssueStatus::OPEN,
    ], $atribut));
}

beforeEach(function () {
    $this->actingAs(meetingUser());
    Gate::before(fn() => true);
});

it('menampilkan issue beserta PIC dan resolusinya', function () {
    isu(['resolution' => 'Payment Alignment Meeting (Today)', 'priority_score' => 1]);

    Livewire::test(ListMeetingIssues::class)
        ->assertOk()
        ->assertCanSeeTableRecords(MeetingIssue::all())
        ->assertSee('Payment Workflow')
        ->assertSee('Payment Alignment Meeting (Today)')
        ->assertSee('Gerry');
});

it('mengurutkan prioritas 1 di atas, dan yang belum diberi skor di bawah', function () {
    $tanpaSkor = isu(['issue' => 'Belum diprioritaskan']);
    $utama = isu(['issue' => 'Prioritas utama', 'priority_score' => 1]);
    $kedua = isu(['issue' => 'Prioritas kedua', 'priority_score' => 2]);

    Livewire::test(ListMeetingIssues::class)
        ->assertCanSeeTableRecords([$utama, $kedua, $tanpaSkor], inOrder: true);
});

it('status bisa diubah langsung dari daftar', function () {
    $isu = isu();

    Livewire::test(ListMeetingIssues::class)
        ->call('updateTableColumnState', 'status', (string) $isu->id, IssueStatus::RESOLVED->value);

    expect($isu->refresh()->status)->toBe(IssueStatus::RESOLVED);
});

it('agenda rapat ditampilkan sebagai keterangan, bukan data yang bisa diubah', function () {
    Livewire::test(ListMeetingIssues::class)
        ->assertActionExists('agenda')
        ->mountAction('agenda')
        ->assertActionMounted('agenda');

    // Isi modalnya diuji langsung: Filament merender konten modal terpisah dari
    // HTML halaman, jadi assertSee di halaman tidak membuktikan apa pun.
    $agenda = view('filament.resources.meeting-issues.agenda')->render();

    expect($agenda)->toContain('Conclude &amp; Rate')
        ->and($agenda)->toContain('Perbarui OKR SEBELUM rapat dimulai')
        // Agendanya statis — tidak ada tabel yang bisa dibuat berbeda dari yang dijalankan.
        ->and(\Illuminate\Support\Facades\Schema::hasTable('meeting_agendas'))->toBeFalse();
});
