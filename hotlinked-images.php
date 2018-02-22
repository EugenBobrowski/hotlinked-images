<?php
/*
Plugin Name: Hotlined Images
Description: Hotlined Images
Version: 1.0
Author: Eugen Bobrowski
*/

class Hotlinked_Images {
	protected static $instance;
	public $posts_per_page = 20;
	public $page = 1;
	public $post_count;
	public $found_posts;
	public $max_num_pages;

	private function __construct() {
        $direct = false;
		if ( defined( 'WPINC' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		} elseif ( file_exists( './wp-load.php' ) ) {
			require_once './wp-load.php';
			$direct = true;
		} else {
			die( 'Fuck off!!!' );
		}

		if ( ! empty( $_GET['paged'] ) ) {
			$this->page = absint( $_GET['paged'] );
		}
		if ( ! empty( $_GET['posts_per_page'] ) ) {
			$this->posts_per_page = absint( $_GET['posts_per_page'] );
		}
		if ($direct) {
		    $this->direct_page();
        }

	}

	function admin_menu() {
		add_menu_page( 'Hotlinked Images', 'Hotlinked Images', 'manage_options',
			'hotlinked_images',
			array( $this, 'page' ) );
	}

	function page() {


		$result = $this->query();

		?>

        <div class="wrap">
            <h1><?php echo get_admin_page_title(); ?></h1>

            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                <tr>
                    <th scope="col" id="title" class="manage-column column-title column-primary">
                        <span>Post</span>
                    </th>
                </tr>
                </thead>

                <tbody id="the-comment-list" data-wp-lists="list:comment">
				<?php

				foreach ( $result as $post_id => $item ) {
					?>
                    <tr id="post-<?php echo $post_id; ?>" class="ratings even thread-even depth-1 approved">

                        <td class="title column-title has-row-actions column-primary page-title">
							<?php
							$imgs = '';
							if ( ! empty( $item['founded'] ) ) {
								$title_link = '<a class="row-title" href="' . site_url() . '/wp-admin/post.php?post=' . $post_id . '&amp;action=edit" aria-label="“' . $item['title'] . '” (Edit)">' . $item['title'] . '</a>';

								foreach ( $item['founded'] as $src ) {
									$imgs .= '<p><img src="' . $src . '" style="vertical-align: middle; max-width: 80px; max-height: 80px"/> ' . $src . '</p>';
								}
							} else {
								$title_link = $item['title'];
							}
							?>
                            <strong><?php echo $title_link; ?><?php if ( $item['status'] !== 'publish' ) {
									echo '- ' . $item['status'];
								} ?></strong>

							<?php echo $imgs; ?>
                        </td>

                    </tr>
					<?php
				}

				?>


                </tbody>


                <tfoot>

                <tr>
                    <th scope="col" id="title" class="manage-column column-title column-primary">
                        <span>Post</span>
                    </th>
                </tr>
                </tfoot>

            </table>

            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo $this->found_posts; ?> items</span>
                <span class="pagination-links">
                    <span class="tablenav-pages-navspan" aria-hidden="true">«</span>

                        <a class="prev-page" href="<?php echo add_query_arg( array( 'paged' => $this->page - 1 ) ); ?>">
                            <span class="screen-reader-text">Previous page</span>
                            <span aria-hidden="true">‹</span>
                        </a>

                    <span class="paging-input">
                        <label for="current-page-selector" class="screen-reader-text">Current Page</label>
                        <input class="current-page" id="current-page-selector" type="text" name="paged"
                               value="<?php echo $this->page; ?>"
                               size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> of <span
                                    class="total-pages"><?php echo $this->max_num_pages; ?></span></span></span>
                        <a class="next-page" href="<?php echo add_query_arg( array( 'paged' => $this->page + 1 ) ); ?>">
                            <span class="screen-reader-text">Next page</span>
                            <span aria-hidden="true">›</span>
                        </a>
                        <span class="tablenav-pages-navspan" aria-hidden="true">»</span>
                </span>
            </div>


        </div>
		<?php
	}

	public function direct_page() {


		$result = $this->query();

		foreach ( $result as $post_id => $item ) {
			?>
            <p id="post-<?php echo $post_id; ?>" class="ratings even thread-even depth-1 approved">


					<?php
					$imgs = '';
					if ( ! empty( $item['founded'] ) ) {
						$title_link = '<a class="row-title" href="' . site_url() . '/wp-admin/post.php?post=' . $post_id . '&amp;action=edit" aria-label="“' . $item['title'] . '” (Edit)">' . $item['title'] . '</a>';

						foreach ( $item['founded'] as $src ) {
							$imgs .= '<p><img src="' . $src . '" style="vertical-align: middle; max-width: 80px; max-height: 80px"/> ' . $src . '</p>';
						}
					} else {
						$title_link = $item['title'];
					}
					?>
                    <strong><?php echo $title_link; ?><?php if ( $item['status'] !== 'publish' ) {
							echo '- ' . $item['status'];
						} ?></strong>

					<?php echo $imgs; ?>

            </p>

			<?php

		}

		?>
        <p>
			<?php if ($this->page != 1) {
				?>
                <a class="prev-page" href="<?php echo add_query_arg( array( 'paged' => $this->page - 1 ) ); ?>">
                    <span aria-hidden="true">‹</span>
                    <span class="screen-reader-text">Previous page</span>
                </a>
				<?php
			} ?>
            Page No. <?php echo $this->page ?> of <?php echo  $this->max_num_pages; ?>
			<?php if ($this->page != $this->max_num_pages) {
				?>
                <a class="next-page" href="<?php echo add_query_arg( array( 'paged' => $this->page + 1 ) ); ?>">
                    <span class="screen-reader-text">Next page</span>
                    <span aria-hidden="true">›</span>
                </a>
				<?php
			} ?>

        </p>
        <?php


	}

	public function query() {

		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'paged'          => $this->page,
			'posts_per_page' => $this->posts_per_page,

		);
		// The Query
		$q = new WP_Query( $args );

		$res = array();

		if ( $q->have_posts() ) {
			// The Loop

			$this->post_count    = $q->post_count;
			$this->found_posts   = $q->found_posts;
			$this->max_num_pages = $q->max_num_pages;

			echo '<p>$query1->post_count: ' . $q->post_count . '</p>';
			echo '<p>$query1->found_posts: ' . $q->found_posts . '</p>';
			echo '<p>$query1->max_num_pages: ' . $q->max_num_pages . '</p>';

			while ( $q->have_posts() ) {
				$q->the_post();
				$res[ $q->post->ID ] = array(
					'title'   => $q->post->post_title,
					'status'  => $q->post->post_status,
					'founded' => array(),
				);
				$content             = $q->post->post_content;
				$content             = apply_filters( 'the_content', $content );
				$content             = str_replace( ']]>', ']]&gt;', $content );

				preg_match_all( '@src="([^"]+)"@', $content, $match );

				$srcs = array_pop( $match );

				foreach ( $srcs as $src ) {
					if ( strpos( $src, site_url() ) === 0 ) {
						continue;
					}
					$res[ $q->post->ID ]['founded'][] = $src;
				}

			}

			/* Restore original Post Data
			 * NB: Because we are using new WP_Query we aren't stomping on the
			 * original $wp_query and it does not need to be reset with
			 * wp_reset_query(). We just need to set the post data back up with
			 * wp_reset_postdata().
			 */
			wp_reset_postdata();
		}

		return $res;

	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Hotlinked_Images::get_instance();