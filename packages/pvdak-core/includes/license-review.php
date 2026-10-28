<?php
/**
 * Staff-recorded, time-limited professional eligibility review.
 *
 * This is a MANUAL ADMINISTRATIVE RECORD, not an automatic check against
 * any bar association or official license registry. Do not publish its
 * contents. The real-world verification procedure must be approved separately.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_review_methods() {
    return array(
        'official_source' => 'بررسی مستقل در منبع رسمی معتبر',
        'document_review' => 'بررسی اولیه مدارک (برای ارجاع کافی نیست)',
    );
}

/**
 * Bind an independent review to the exact private lawyer identity that was
 * examined. A changed owner, name, city, membership type or approval state
 * invalidates the old review even if its expiry date is still in the future.
 * Legacy records without a signature must be explicitly reviewed again.
 */
function pvdak_core_license_review_identity_signature( $profile_id ) {
    $id = pvdak_core_request_positive_id( $profile_id );
    if ( ! $id || 'pvdak_professional' !== get_post_type( $id ) ||
         'private' !== get_post_status( $id ) ||
         'lawyer' !== get_post_meta( $id, '_pvdak_member_type', true ) ||
         'approved' !== get_post_meta( $id, '_pvdak_member_state', true ) ) { return ''; }
    $owner = pvdak_core_request_positive_id( get_post_field( 'post_author', $id ) );
    $name = get_post_field( 'post_title', $id );
    $city = get_post_meta( $id, '_pvdak_member_city', true );
    if ( ! is_string( $name ) || ! is_string( $city ) ||
         ! $owner || ! get_userdata( $owner ) || '' === trim( $name ) ||
         '' === trim( $city ) ) { return ''; }
    return hash( 'sha256', wp_json_encode( array( $owner, $name, $city, 'lawyer', 'approved' ) ) );
}

/**
 * Fail closed unless a manager recorded an official-source review with an
 * expiry date. An ordinary membership approval cannot substitute for it.
 */
function pvdak_core_license_review_current( $profile_id ) {
    $id = pvdak_core_request_positive_id( $profile_id );
    $signature = pvdak_core_license_review_identity_signature( $id );
    $saved = get_post_meta( $id, '_pvdak_license_review_identity', true );
    if ( ! is_string( $saved ) || '' === $signature || ! preg_match( '/^[a-f0-9]{64}$/D', $saved ) ||
         ! hash_equals( $saved, $signature ) ) { return false; }
    if ( 'checked' !== get_post_meta( $id, '_pvdak_license_review_status', true ) ||
         'official_source' !== get_post_meta( $id, '_pvdak_license_review_method', true ) ) {
        return false;
    }
    $date = get_post_meta( $id, '_pvdak_license_review_expires', true );
    if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $date ) || ! wp_checkdate(
        (int) substr( $date, 5, 2 ), (int) substr( $date, 8, 2 ), (int) substr( $date, 0, 4 ), $date
    ) ) { return false; }
    $reviewer = pvdak_core_request_positive_id( get_post_meta( $id, '_pvdak_license_review_by', true ) );
    // A deleted or de-privileged reviewer must not remain a current authority.
    if ( ! $reviewer || ! get_userdata( $reviewer ) || ! user_can( $reviewer, 'manage_options' ) ||
         $date < current_time( 'Y-m-d' ) ) { return false; }
    // The audit is the accountable record of a deliberate independent check.
    // A direct metadata edit or a later pending audit cannot grant referrals.
    $audit = get_post_meta( $id, '_pvdak_license_review_audit', false );
    if ( ! is_array( $audit ) || ! $audit ) { return false; }
    $last = end( $audit );
    return is_array( $last ) &&
        ( $last['to'] ?? null ) === 'checked' &&
        ( $last['method'] ?? null ) === 'official_source' &&
        ( $last['expires'] ?? null ) === $date &&
        ( $last['identity_bound'] ?? null ) === true &&
        pvdak_core_request_positive_id( $last['by'] ?? null ) === $reviewer;
}

function pvdak_core_license_review_metabox() {
    add_meta_box( 'pvdak_professional_license_review',
        'بررسی مستقل صلاحیت حرفه‌ای',
        'pvdak_core_license_review_metabox_html',
        'pvdak_professional', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'pvdak_core_license_review_metabox' );

function pvdak_core_license_review_metabox_html( $post ) {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    wp_nonce_field( 'pvdak_license_review_' . $post->ID, '_pvdak_license_review_nonce' );
    $status = get_post_meta( $post->ID, '_pvdak_license_review_status', true );
    $method = get_post_meta( $post->ID, '_pvdak_license_review_method', true );
    $expiry = get_post_meta( $post->ID, '_pvdak_license_review_expires', true );
    $status = is_string( $status ) ? $status : '';
    $method = is_string( $method ) ? $method : '';
    $expiry = is_string( $expiry ) ? $expiry : '';
    echo '<p>این بخش فقط سابقه بررسی داخلی توسط مدیر مجاز را ثبت می‌کند. سیستم هیچ استعلام خودکاری انجام نمی‌دهد و تا تکمیل رویه مصوب نمی‌توان تأیید رسمی بودن پروانه را به کاربران اعلام کرد.</p>';
    echo '<p><strong>پیش از تأیید:</strong> منبع رسمی، تطبیق هویت و وضعیت اعتبار را خارج از این فرم بررسی کنید. مدارک، شماره ملی، شماره پروانه و نشانی‌های خصوصی را در یادداشت وارد نکنید. صرف بررسی اولیه مدارک مجوز ارجاع نیست.</p>';
    echo '<p><label for="pvdak_review_method">روش بررسی</label><br>';
    echo '<select id="pvdak_review_method" name="pvdak_review_method"><option value="">انتخاب نشده</option>';
    foreach ( pvdak_core_review_methods() as $key => $label ) {
        echo '<option value="' . esc_attr( $key ) . '" ' . selected( $method, $key, false ) . '>' .
            esc_html( $label ) . '</option>';
    }
    echo '</select></p>';
    echo '<p><label for="pvdak_review_expiry">تاریخ پایان اعتبار بررسی</label><br>';
    echo '<input type="date" id="pvdak_review_expiry" name="pvdak_review_expiry" value="' .
        esc_attr( $expiry ) . '"></p>';
    echo '<p><label for="pvdak_review_note">یادداشت کوتاه منبع بررسی (بدون شماره شناسایی/مدارک شخصی)</label><br>';
    echo '<textarea id="pvdak_review_note" name="pvdak_review_note" class="widefat" maxlength="300" rows="2"></textarea></p>';
    echo '<label><input type="checkbox" name="pvdak_review_confirm" value="1"> بررسی مستقل در منبع رسمی را اکنون انجام داده‌ام و نتیجه جدید را تأیید می‌کنم.</label>';
    echo '<p><small>برای نگه‌داشتن بررسی قبلی این گزینه را خالی بگذارید؛ تغییر هویت پروفایل یا انقضای تاریخ، اعتبار قبلی را خودکار از بین می‌برد. تأیید مجدد نیازمند انتخاب آگاهانه این گزینه است.</small></p>';
    if ( 'checked' === $status && ! pvdak_core_license_review_current( $post->ID ) ) {
        echo '<p><strong>این بررسی برای ارجاع معتبر نیست؛ ممکن است تاریخ منقضی یا روش بررسی نامعتبر باشد.</strong></p>';
    }
    $audit = get_post_meta( $post->ID, '_pvdak_license_review_audit', false );
    if ( $audit ) {
        echo '<details><summary>تاریخچه بررسی‌ها</summary><ul>';
        foreach ( array_reverse( array_slice( $audit, -8 ) ) as $event ) {
            if ( ! is_array( $event ) ) { continue; }
            $at = isset( $event['at'] ) && is_string( $event['at'] ) ? $event['at'] : '';
            $to = isset( $event['to'] ) && is_string( $event['to'] ) ? $event['to'] : '';
            echo '<li>' . esc_html( $at ) . ' — ' . esc_html( $to ) . '</li>';
        }
        echo '</ul></details>';
    }
}

function pvdak_core_save_license_review( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
         ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ||
         'pvdak_professional' !== get_post_type( $post_id ) ) { return; }
    if ( ! isset( $_POST['_pvdak_license_review_nonce'] ) || ! is_string( $_POST['_pvdak_license_review_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_license_review_nonce'] ) ),
             'pvdak_license_review_' . $post_id ) ) { return; }

    $methods = pvdak_core_review_methods();
    $method = isset( $_POST['pvdak_review_method'] ) && is_string( $_POST['pvdak_review_method'] ) ?
        sanitize_key( wp_unslash( $_POST['pvdak_review_method'] ) ) : '';
    $expiry = isset( $_POST['pvdak_review_expiry'] ) && is_string( $_POST['pvdak_review_expiry'] ) ?
        sanitize_text_field( wp_unslash( $_POST['pvdak_review_expiry'] ) ) : '';
    $confirmed = isset( $_POST['pvdak_review_confirm'] ) && is_string( $_POST['pvdak_review_confirm'] ) &&
        '1' === wp_unslash( $_POST['pvdak_review_confirm'] );
    $valid_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expiry ) && wp_checkdate(
        (int) substr( $expiry, 5, 2 ), (int) substr( $expiry, 8, 2 ), (int) substr( $expiry, 0, 4 ), $expiry
    );
    $signature = pvdak_core_license_review_identity_signature( $post_id );
    $approved = $confirmed && 'official_source' === $method && $valid_date &&
        $expiry >= current_time( 'Y-m-d' ) && '' !== $signature;
    // A past expiry or unqualified method resets validity instead of
    // silently carrying the previous accepted state forward.
    $new = $approved ? 'checked' : 'pending';
    $old = get_post_meta( $post_id, '_pvdak_license_review_status', true );
    $old_method = get_post_meta( $post_id, '_pvdak_license_review_method', true );
    $old_expiry = get_post_meta( $post_id, '_pvdak_license_review_expires', true );
    $old = is_string( $old ) ? $old : '';
    $old_method = is_string( $old_method ) ? $old_method : '';
    $old_expiry = is_string( $old_expiry ) ? $old_expiry : '';
    // A normal edit does not silently re-certify an old review. If identity
    // changed, the stored signature no longer matches and eligibility closes.
    if ( ! $confirmed && 'checked' === $old && $old_method === $method &&
         $old_expiry === $expiry ) { return; }
    if ( ! $confirmed && $new === $old && $old_method === $method &&
         $old_expiry === $expiry ) { return; }

    // Revoke the previous approval before any non-atomic metadata update.
    // A partial write or failed audit must never leave a current review.
    if ( 'pending' !== $old &&
         false === update_post_meta( $post_id, '_pvdak_license_review_status', 'pending' ) ) { return; }
    if ( 'pending' !== (string) get_post_meta( $post_id, '_pvdak_license_review_status', true ) ) { return; }
    $changes = array(
        '_pvdak_license_review_method' => isset( $methods[ $method ] ) ? $method : '',
        '_pvdak_license_review_expires' => $valid_date ? $expiry : '',
        '_pvdak_license_review_by' => $approved ? get_current_user_id() : 0,
        '_pvdak_license_review_identity' => $approved ? $signature : '',
    );
    foreach ( $changes as $key => $value ) {
        $before = get_post_meta( $post_id, $key, true );
        if ( ! is_scalar( $before ) || (string) $before !== (string) $value ) {
            if ( false === update_post_meta( $post_id, $key, $value ) ) { return; }
        }
        $stored = get_post_meta( $post_id, $key, true );
        if ( ! is_scalar( $stored ) || (string) $stored !== (string) $value ) { return; }
    }
    $note = isset( $_POST['pvdak_review_note'] ) && is_string( $_POST['pvdak_review_note'] ) ?
        sanitize_text_field( wp_unslash( $_POST['pvdak_review_note'] ) ) : '';
    if ( false === add_post_meta( $post_id, '_pvdak_license_review_audit', array(
        'from' => $old, 'to' => $new, 'method' => $method,
        'identity_bound' => $approved,
        'expires' => $valid_date ? $expiry : '',
        'note' => wp_html_excerpt( $note, 300 ),
        'by' => get_current_user_id(), 'at' => current_time( 'mysql', true ),
    ), false ) ) { return; }
    if ( $approved ) {
        if ( false === update_post_meta( $post_id, '_pvdak_license_review_status', 'checked' ) ||
             'checked' !== get_post_meta( $post_id, '_pvdak_license_review_status', true ) ) {
            update_post_meta( $post_id, '_pvdak_license_review_status', 'pending' );
        }
    }
}
// Existing membership and specialties save at priority 10; revocation review at 30.
add_action( 'save_post_pvdak_professional', 'pvdak_core_save_license_review', 15 );
