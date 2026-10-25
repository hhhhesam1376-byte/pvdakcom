<?php
/** Original WordPress-native article comments, replies and submission form. */
if ( ! defined( 'ABSPATH' ) || post_password_required() ) { return; }
?>
<section id="comments" class="pvdak-article-comments" aria-labelledby="pvdak-comments-title">
    <header class="pvdak-comments-heading">
        <div>
            <h2 id="pvdak-comments-title">دیدگاه‌ها و گفتگو</h2>
            <p><?php echo comments_open() ? 'نظر خود را درباره این مقاله با ما در میان بگذارید.' : 'دیدگاه‌های این نوشته را در این بخش ببینید.'; ?></p>
        </div>
        <span><?php echo esc_html( get_comments_number() ); ?> دیدگاه</span>
    </header>
    <div class="pvdak-comments-compose">
        <?php if ( comments_open() ): ?>
            <?php comment_form( array(
                'class_form' => 'pvdak-comment-form',
                'title_reply' => 'ثبت دیدگاه',
                'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
                'title_reply_after' => '</h3>',
                'comment_notes_before' => '<p class="pvdak-comment-help">دیدگاه‌ها مطابق تنظیمات وردپرس بررسی می‌شوند. لطفاً اطلاعات خصوصی اشخاص یا پرونده‌ها را درج نکنید.</p>',
                'comment_field' => '<p class="comment-form-comment"><label for="comment">متن دیدگاه <span class="required">*</span></label><textarea id="comment" name="comment" rows="5" maxlength="4000" placeholder="دیدگاه خود را بنویسید…" required></textarea></p>',
                'label_submit' => 'ارسال دیدگاه',
                'class_submit' => 'pvdak-comment-submit',
            ) ); ?>
        <?php else: ?>
            <p class="pvdak-comments-closed">ثبت دیدگاه برای این نوشته در حال حاضر فعال نیست.</p>
        <?php endif; ?>
    </div>
    <?php if ( have_comments() ): ?>
        <h3 class="pvdak-comment-list-title">دیدگاه‌های ثبت‌شده</h3>
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
</section>
