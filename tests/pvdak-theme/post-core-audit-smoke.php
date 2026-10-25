<?php
/** Source-only guard for installation audit. */
$src=file_get_contents(dirname(__DIR__,2).'/scripts/pvdak-post-core-audit.php');
foreach(array('0.8.43','0.8.14','pvdak_maintenance_enabled','pvdak_core_intake_open',
'pvdak_core_member_intake_open','pvdak_registration_open',
'pvdak_core_subscription_gate_on','pvdak_support_ticket',
'pvdak_core_with_assignment_locks','pvdak_recovery_request',
'PVD_POST_CORE_AUDIT_SUMMARY') as $needle){
    if(strpos($src,$needle)===false){fwrite(STDERR,"AUDIT_SOURCE_MISSING: ".$needle."\n");exit(1);}
}
foreach(array('wp_insert_post(', 'wp_insert_user(', 'update_option(', 'wp_mail(') as $write){
    if(strpos($src,$write)!==false){fwrite(STDERR,"AUDIT_NOT_READ_ONLY: ".$write."\n");exit(1);}
}
echo "PVD_POST_CORE_AUDIT_SOURCE_OK\n";

/**
 * D04: employer preview is intentionally maintenance-OFF. Guard the two real
 * WP-CLI audits against accidentally reviving the old maintenance-ON gate.
 * Public preview still requires noindex/nofollow and closed private intakes.
 */
$root=dirname(__DIR__,2);
foreach(array(
    'scripts/pvdak-role-capabilities-smoke.php',
    'scripts/pvdak-anonymous-live-smoke.php'
) as $file){
    $audit=file_get_contents($root.'/'.$file);
    foreach(array("apply_filters( 'wp_robots', array() )",
                  "function_exists( 'pvdak_preview_robots' )",
                  "! empty( \$preview_robots['noindex'] )",
                  "! empty( \$preview_robots['nofollow'] )",
                  "PVDAK_PUBLIC_LAUNCH_APPROVED") as $required){
        if(strpos($audit,$required)===false){
            fwrite(STDERR,"D04_PREVIEW_GUARD_MISSING: ".$file." ".$required."\n");exit(1);
        }
    }
    if(strpos($audit,"maintenance remains ON")!==false ||
       strpos($audit,"Public maintenance enabled")!==false){
        fwrite(STDERR,"D04_STALE_MAINTENANCE_ASSUMPTION: ".$file."\n");exit(1);
    }
}
$selftest=file_get_contents($root.'/scripts/pvdak-anonymous-counter-selftest.php');
if(strpos($selftest,"function pvdak_preview_robots(")===false ||
   strpos($selftest,"'fail' !== \$GLOBALS['pvdak_test_mode']")===false){
    fwrite(STDERR,"D04_NEGATIVE_FIXTURE_MISSING\n");exit(1);
}
echo "D04_PREVIEW_SOURCE_GUARDS_OK\n";
