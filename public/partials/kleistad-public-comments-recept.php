<?php
/**
 * De template voor het tonen van commentaar op kleistad_recepten
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 *
 * @since Kleistad 4.1.0
 */

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
				$comments_number = get_comments_number();
			if ( '1' === $comments_number ) {
				echo esc_html( sprintf( 'Een reactie op &ldquo;%s&rdquo;', get_the_title() ), true );
			} else {
				echo esc_html( sprintf( '%1$s reacties op &ldquo;%2$s&rdquo;', number_format_i18n( $comments_number ), get_the_title() ), true );
			}
			?>
		</h2>

		<?php the_comments_navigation(); ?>

		<ol class="comment-list">
			<?php
				wp_list_comments(
					array(
						'style'       => 'ol',
						'short_ping'  => true,
						'avatar_size' => 42,
					)
				);
			?>
		</ol>

		<?php the_comments_navigation(); ?>

	<?php endif; ?>

	<?php
	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
		?>
	<p class="no-comments"><?php echo 'Gesloten voor reacties'; ?></p>
	<?php endif; ?>

	<?php
		comment_form(
			array(
				'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">',
				'title_reply_after'  => '</h2>',
			)
		);
		?>

</div><!-- .comments-area -->
