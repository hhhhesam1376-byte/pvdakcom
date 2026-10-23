<?php
/**
 * Source-only release-proof navigation-test guard.
 * No WordPress, database, network or user data.
 */
$root = dirname( __DIR__, 2 );
$source = file_get_contents( $root . '/tests/pvdak-theme/navigation-smoke.php' );
$css = file_get_contents( $root . '/style.css' );
if (!preg_match('/^Version:[ \t]*([0-9]+(?:\\.[0-9]+)*)[ \t]*$/mi', $css, $found)) {
    fwrite(STDERR, "NAV_VERSION_FAIL: theme version missing\n"); exit(1);
}
$minimum = '0.8.12';
$cases = array(
    array('0.8.11',false),
    array('0.8.12',true),
    array('0.8.28',true),
    array('0.8.29',true),
    array('0.9.0',true),
);
foreach ($cases as $case) {
    $actual = version_compare($case[0],$minimum,'>=');
    if($actual!==$case[1]){fwrite(STDERR,"NAV_VERSION_FAIL: comparison\n");exit(1);}
}
if(version_compare($found[1],$minimum,'<') ||
   strpos($source,'version_compare( $theme_header_version[1],')===false ||
   strpos($source,"'Version: 0.8.12'")!==false) {
    fwrite(STDERR, "NAV_VERSION_FAIL: pinned or outdated CSS check\n");exit(1);
}
echo "PVDAK_NAV_VERSION_OK: minimum CSS version and future releases accepted (source only)\n";
