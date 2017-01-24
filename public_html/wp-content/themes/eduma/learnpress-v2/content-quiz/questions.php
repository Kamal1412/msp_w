<?php
/**
 * Template for displaying the list of questions for the quiz
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$course = LP()->global['course'];
$quiz   = LP()->global['course-item'];
$user   = LP()->user;
if ( !$quiz || learn_press_quiz_is_hide_question( $quiz->id ) ) {
	return;
}
$status       = $user->get_quiz_status( $quiz->id, $course->id );
$heading      = apply_filters( 'learn_press_list_questions_heading', __( 'List of questions', 'eduma' ) );
$no_permalink = $user->has_quiz_status( array( '', 'completed', 'viewed' ), $quiz->id, $course->id );
?>

<?php if ( $heading ) { ?>
	<h3 class="list-question-title"><?php echo $heading; ?></h3>
<?php } ?>

<?php if ( $quiz->has( 'questions' ) ): ?>

	<div class="quiz-questions lp-group-content-wrap list-quiz-questions<?php echo ( $status == 'completed' ) ? ' completed-questions' : ''; ?>" id="learn-press-quiz-questions">

		<?php do_action( 'learn_press_before_quiz_questions' ); ?>

		<ul class="quiz-questions-list">
			<?php if ( $questions = $quiz->get_questions() ) foreach ( $questions as $question ) { ?>
				<li data-id="<?php echo $question->ID; ?>" <?php learn_press_question_class( $question->ID, array( 'user' => $user, 'quiz' => $quiz ) ); ?>>

					<?php do_action( 'learn_press_before_quiz_question_title', $question->ID, $quiz->id ); ?>

					<?php if ( $no_permalink ) { ?>
						<?php printf( '<h4 class="question-title">%s</h4>', get_the_title( $question->ID ) ); ?>
					<?php } else { ?>
						<?php printf( '<h4 class="question-title"><a class="js-action" href="%s">%s</a></h4>', $quiz->get_question_link( $question->ID ), get_the_title( $question->ID ) ); ?>
					<?php } ?>

					<?php do_action( 'learn_press_after_quiz_question_title', $question->ID, $quiz->id ); ?>

				</li>
			<?php } ?>
		</ul>

		<?php do_action( 'learn_press_after_quiz_questions' ); ?>

	</div>

<?php else: ?>

	<?php learn_press_display_message( apply_filters( 'learn_press_quiz_no_questions_notice', __( 'This quiz hasn\'t got any questions', 'eduma' ) ) ); ?>

<?php endif; ?>






