<?php
/**
 * Native WordPress comments on ordinary editorial posts.
 * The comment form is displayed only when WP comments_open() is true.
 */
if ( ! defined( 'ABSPATH' ) || post_password_required() ) { return; }
?>
<section id="comments" class="pvdak-article-comments" aria-labelledby="pvdak-comments-title">
    <div class="pvdak-comments-heading">
        <div><span class="pvdak-article-eyebrow">گفت‌وگوی خوانندگان</span><h2 id="pvdak-comments-title">دیدگاه‌ها</h2></div>
        <span><?php echo esc_html( get_comments_number() ); ?> دیدگاه</span>
    </div>
    <?php if ( have_comments() ): ?>
        <ol class="pvdak-comment-list">
            <?php wp_list_comments( array(
                'style' => 'ol',
                'type' => 'comment',
                'avatar_size' => 44,
                'short_ping' => true,
                'max_depth' => (int) get_option( 'thread_comments_depth', 3 ),
            ) ); ?>
        </ol>
        <div class="pvdak-comments-pagination"><?php
            the_comments_pagination( array(
                'prev_text' => 'دیدگاه‌های قبلی',
                'next_text' => 'دیدگاه‌های بعدی',
            ) );
        ?></div>
    <?php endif; ?>
    <?php if ( comments_open() ): ?>
        <?php comment_form( array(
            'class_form' => 'pvdak-comment-form',
            'title_reply' => 'دیدگاه خود را بنویسید',
            'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
            'title_reply_after' => '</h3>',
            'comment_notes_before' => '<p class="pvdak-comment-help">دیدگاه‌ها پس از بررسی، طبق تنظیمات وردپرس نمایش داده می‌شوند. لطفاً اطلاعات خصوصی پرونده یا اشخاص را درج نکنید.</p>',
            'comment_field' => '<p class="comment-form-comment"><label for="comment">متن دیدگاه <span class="required">*</span></label><textarea id="comment" name="comment" rows="5" maxlength="4000" required></textarea></p>',
            'label_submit' => 'ارسال دیدگاه',
            'class_submit' => 'pvdak-comment-submit',
        ) ); ?>
    <?php else: ?>
        <p class="pvdak-comments-closed">امکان ثبت دیدگاه برای این مطلب بسته است.</p>
    <?php endif; ?>
</section>
