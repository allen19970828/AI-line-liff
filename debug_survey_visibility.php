<?php

use App\Models\Survey;
use App\Models\User;

echo '--- Current Time: ' . now()->toDateTimeString() . " ---\n\n";

echo "--- Surveys ---\n";
$surveys = Survey::all();
foreach ($surveys as $s) {
    echo "ID: {$s->id}\n";
    echo "Title: {$s->title}\n";
    echo "Status: {$s->status}\n";
    echo "School ID: {$s->school_id}\n";
    echo "Start At: {$s->start_at}\n";
    echo "End At: {$s->end_at}\n";
    echo 'Target Roles: ' . json_encode($s->target_roles) . "\n";

    $isOpen = $s->status === 'active'
        && (is_null($s->start_at) || $s->start_at <= now())
        && (is_null($s->end_at) || $s->end_at >= now());

    echo 'Is Open (Logic Check): ' . ($isOpen ? 'YES' : 'NO') . "\n";
    echo "----------------\n";
}

echo "\n--- Users with Staff Link ---\n";
$users = User::with('staff')->get();
foreach ($users as $u) {
    /** @var User $u */
    if ($u->staff) {
        echo "User ID: {$u->id}, Name: {$u->name}, School ID: {$u->school_id}\n";
        echo "  Linked Staff ID: {$u->staff->id}, Name: {$u->staff->name}, School ID: {$u->staff->school_id}\n";

        // Check filtering logic
        foreach ($surveys as $s) {
            if ($u->school_id !== $s->school_id && !$u->canBypassSchoolScope()) {
                echo "  -> Survey {$s->id}: Locked (School Mismatch)\n";

                continue;
            }

            $userRoles = ['staff'];
            if (!empty($s->target_roles)) {
                $matches = !empty(array_intersect($s->target_roles, $userRoles));
                echo "  -> Survey {$s->id}: Role Match? " . ($matches ? 'YES' : 'NO') . "\n";
            } else {
                echo "  -> Survey {$s->id}: Role Match? YES (No restrictions)\n";
            }
        }
        echo "----------------\n";
    } else {
        echo "User ID: {$u->id}, Name: {$u->name} (No Staff Link)\n";
    }
}
