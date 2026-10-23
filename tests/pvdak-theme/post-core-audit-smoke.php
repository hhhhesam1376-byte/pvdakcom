<?php
/** Source-only guard for installation audit. */
$src=file_get_contents(dirname(__DIR__,2).'/scripts/pvdak-post-core-audit.php');
foreach(array('0.8.28','0.8.2','pvdak_maintenance_enabled','pvdak_core_intake_open',
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
