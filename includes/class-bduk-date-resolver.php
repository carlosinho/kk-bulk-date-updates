<?php
/**
 * Shared date resolution for preview and live bulk updates.
 *
 * @package BDUK
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves post date changes from form data for preview and live updates.
 */
class BDUK_Date_Resolver {

	/**
	 * Resolve date changes for a single post.
	 *
	 * @param object $post       Post row with ID, post_title, post_type, post_date, post_modified.
	 * @param array  $form_data  Sanitized form data.
	 * @return array {
	 *     @type array $update_data    Columns to write (includes GMT companions).
	 *     @type array $updated_fields Field names that will change.
	 *     @type array $date_changes   Old/new pairs for changed fields.
	 *     @type array $current_dates  Current values for preview display.
	 *     @type array $new_dates      New values for preview display.
	 *     @type bool  $has_preview    Whether preview output should include this post.
	 * }
	 */
	public function resolve( $post, array $form_data ) {
		$update_data        = array();
		$updated_fields     = array();
		$date_changes       = array();
		$current_dates      = array();
		$new_dates          = array();
		$new_published_date = null;

		$both_dates_selected = in_array( 'post_date', $form_data['date_fields'], true )
			&& in_array( 'post_modified', $form_data['date_fields'], true );

		if ( in_array( 'post_date', $form_data['date_fields'], true ) ) {
			$old_date = $post->post_date;
			$new_date = $this->calculate_new_date( $old_date, $form_data, 'post_date', 0, $post );

			if ( $old_date && $new_date ) {
				$current_dates['post_date'] = $old_date;
				$new_dates['post_date']     = $new_date;
				$new_published_date         = $new_date;

				if ( $this->dates_differ( $old_date, $new_date ) ) {
					$update_data['post_date']     = $new_date;
					$update_data['post_date_gmt'] = get_gmt_from_date( $new_date );
					$updated_fields[]             = 'post_date';
					$date_changes['post_date']    = array(
						'old' => $old_date,
						'new' => $new_date,
					);
				}
			}
		}

		if ( 'match_modified_to_published' === $form_data['update_method'] && ! isset( $current_dates['post_date'] ) ) {
			$current_dates['post_date'] = $post->post_date;
			$new_dates['post_date']     = $post->post_date;
		}

		$should_handle_modified = in_array( 'post_modified', $form_data['date_fields'], true )
			|| 'match_modified_to_published' === $form_data['update_method'];

		if ( $should_handle_modified ) {
			$old_date = $post->post_modified;

			if ( 'match_modified_to_published' === $form_data['update_method'] ) {
				$new_date = $this->calculate_modified_date_from_published( $post->post_date, $form_data['modified_date_offset'] );
			} elseif ( $both_dates_selected && $new_published_date ) {
				$new_date = $this->calculate_modified_date_from_published( $new_published_date, $form_data['modified_date_offset'] );
			} else {
				$new_date = $this->calculate_new_date( $old_date, $form_data, 'post_modified', $form_data['modified_date_offset'], $post );
			}

			if ( $old_date && $new_date ) {
				$current_dates['post_modified'] = $old_date;
				$new_dates['post_modified']     = $new_date;

				if ( $this->dates_differ( $old_date, $new_date ) ) {
					$update_data['post_modified']     = $new_date;
					$update_data['post_modified_gmt'] = get_gmt_from_date( $new_date );
					$updated_fields[]                 = 'post_modified';
					$date_changes['post_modified']    = array(
						'old' => $old_date,
						'new' => $new_date,
					);
				}
			}
		}

		return array(
			'update_data'    => $update_data,
			'updated_fields' => $updated_fields,
			'date_changes'   => $date_changes,
			'current_dates'  => $current_dates,
			'new_dates'      => $new_dates,
			'has_preview'    => ! empty( $update_data ),
		);
	}

	/**
	 * Map a resolved result to preview table row data.
	 *
	 * @param object $post     Post row.
	 * @param array  $resolved Result from resolve().
	 * @return array|null
	 */
	public function to_preview_row( $post, array $resolved ) {
		if ( empty( $resolved['update_data'] ) ) {
			return null;
		}

		return array(
			'post_id'       => $post->ID,
			'post_title'    => $post->post_title,
			'post_type'     => $post->post_type,
			'current_dates' => $resolved['current_dates'],
			'new_dates'     => $resolved['new_dates'],
		);
	}

	/**
	 * Calculate new date based on update method.
	 *
	 * @param string      $current_date   Current datetime string.
	 * @param array       $form_data      Sanitized form data.
	 * @param string      $date_field     Target field name.
	 * @param int         $offset_minutes Offset in minutes.
	 * @param object|null $post           Post row.
	 * @return string|null
	 */
	private function calculate_new_date( $current_date, array $form_data, $date_field, $offset_minutes = 0, $post = null ) {
		$timestamp = strtotime( $current_date );

		switch ( $form_data['update_method'] ) {
			case 'add_days':
				$new_timestamp = $timestamp + ( $form_data['days_value'] * DAY_IN_SECONDS );
				break;

			case 'subtract_days':
				$new_timestamp = $timestamp - ( $form_data['days_value'] * DAY_IN_SECONDS );
				break;

			case 'match_modified_to_published':
				if ( 'post_modified' === $date_field && $post ) {
					$timestamp     = strtotime( $post->post_date );
					$new_timestamp = $timestamp;
				} else {
					return $current_date;
				}
				break;

			default:
				return $current_date;
		}

		$new_timestamp += ( $offset_minutes * MINUTE_IN_SECONDS );

		return date( 'Y-m-d H:i:s', $new_timestamp );
	}

	/**
	 * Calculate modified date from a published date plus offset.
	 *
	 * @param string $published_date Published datetime string.
	 * @param int    $offset_minutes Offset in minutes.
	 * @return string
	 */
	private function calculate_modified_date_from_published( $published_date, $offset_minutes = 0 ) {
		$timestamp = strtotime( $published_date );
		$timestamp += ( $offset_minutes * MINUTE_IN_SECONDS );

		return date( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Compare two datetimes reliably.
	 *
	 * @param string $old_date Existing datetime.
	 * @param string $new_date Candidate datetime.
	 * @return bool
	 */
	private function dates_differ( $old_date, $new_date ) {
		$old_timestamp = strtotime( $old_date );
		$new_timestamp = strtotime( $new_date );

		if ( false === $old_timestamp || false === $new_timestamp ) {
			return (string) $old_date !== (string) $new_date;
		}

		return $old_timestamp !== $new_timestamp;
	}
}
