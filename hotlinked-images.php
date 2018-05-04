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
	public $pulling;
	public $founded;

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
		if ( $direct ) {
			$this->direct_page();
		}

	}

	public function admin_menu() {
		add_menu_page( 'Hotlinked Images', 'Hotlinked Images', 'manage_options',
			'hotlinked_images',
			array( $this, 'page' ) );
	}

	public function page() {


		$result = $this->query();

		$this->pull_images( $result );

		$url = remove_query_arg('pull_images_of_post');

		?>
        <style>
            .hli-box {
                display: inline-block;
                width: 60px;
                height: 60px;
                position: relative;
                overflow: hidden;
                box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.1), inset 0 0 0 1px rgba(0, 0, 0, 0.05);
                -webkit-box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.1), inset 0 0 0 1px rgba(0, 0, 0, 0.05);
                background: #eeeeee;
                line-height: 60px;
                text-align: center;
                vertical-align: middle;
            }

            .hli-box img {
                vertical-align: middle;
                max-width: 60px;
                max-height: 60px
        </style>

        <div class="wrap">
            <h1><?php echo get_admin_page_title(); ?></h1>
            <p>
                <a class="button-primary"
                   href="<?php echo add_query_arg( array( 'pull_images_of_post' => 'all', ) ); ?>">
                    Download
                </a>
            </p>
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
									if ( isset( $this->pulling[ $post_id ][ $src ] ) ) {
										$downloaded = ' <span style="font-weight: bold;">[downloaded]</span>';
									} else {
										$downloaded = '';
									}
									$imgs .= '<p><span class="hli-box"><img src="' . $src . '"/></span> ' . $src . $downloaded . '</p>';
								}
							} else {
								$title_link = $item['title'];
							}
							?>
                            <strong><?php echo $title_link; ?><?php if ( $item['status'] !== 'publish' ) {
									echo '- ' . $item['status'];
								} ?></strong>

							<?php echo $imgs; ?>

                            <div class="row-actions">
                                <span class="id">ID: <?php echo $post_id; ?> | </span>
                                <span class="edit"><a
                                            href="<?php echo site_url( '/wp-admin/post.php?post=' . $post_id . '&amp;action=edit' ); ?>"
                                            aria-label="Edit “<?php echo $item['title']; ?>”">Edit</a> | </span>
                                <span
                                        class="view"><a
                                            href="<?php the_permalink( $post_id ); ?>"
                                            rel="bookmark"
                                            aria-label="View “<?php echo $item['title']; ?>”">View</a> | </span>
                                <span
                                        class="view"><a
                                            href="<?php echo add_query_arg( array( 'pull_images_of_post' => $post_id ) ); ?>"
                                            rel="bookmark"
                                            aria-label="Download for “<?php echo $item['title']; ?>”">Download</a></span>
                            </div>
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

            <div class="tablenav bottom">

                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo $this->found_posts; ?> items</span>
                    <span class="pagination-links">
                        <?php if ( $this->page == 1 ) { ?>
                            <span class="tablenav-pages-navspan" aria-hidden="true">«</span>
                        <?php } else { ?>
                            <a class="first-page" href="<?php echo add_query_arg( array( 'paged' => 1 ), $url ); ?>"><span
                                        class="screen-reader-text">First page</span><span
                                        aria-hidden="true">«</span></a>
                        <?php } ?>


                        <a class="prev-page" href="<?php echo add_query_arg( array( 'paged' => $this->page - 1 ), $url ); ?>">
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
                        <a class="next-page" href="<?php echo add_query_arg( array( 'paged' => $this->page + 1 ), $url ); ?>">
                            <span class="screen-reader-text">Next page</span>
                            <span aria-hidden="true">›</span>
                        </a>
						<?php if ( $this->page == $this->max_num_pages ) { ?>
                            <span class="tablenav-pages-navspan" aria-hidden="true">»</span>
						<?php } else { ?>
                            <a class="last-page"
                               href="<?php echo add_query_arg( array( 'paged' => $this->max_num_pages ), $url ); ?>">
                                <span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span>
                            </a>
						<?php } ?>



                </span>
                </div>
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
			<?php if ( $this->page != 1 ) {
				?>
                <a class="prev-page" href="<?php echo add_query_arg( array( 'paged' => $this->page - 1 ) ); ?>">
                    <span aria-hidden="true">‹</span>
                    <span class="screen-reader-text">Previous page</span>
                </a>
				<?php
			} ?>
            Page No. <?php echo $this->page ?> of <?php echo $this->max_num_pages; ?>
			<?php if ( $this->page != $this->max_num_pages ) {
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
					'founded' => $this->find_images( $q->post ),
				);

			}

			wp_reset_postdata();
		}

		return $res;

	}

	/**
	 * @param $post WP_Post
	 */
	public function find_images( $post, $apply_the_content = false ) {
		$post = get_post( $post );
		$res  = array();

		$content = $post->post_content;

		if ( $apply_the_content ) {
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );
		}

		preg_match_all( '@src="([^"]+)"@', $content, $match );

		$srcs = array_pop( $match );

		foreach ( $srcs as $src ) {
			if ( strpos( $src, site_url() ) === 0 || in_array( parse_url( $src, PHP_URL_HOST ), array( 'www.youtube.com' ) ) ) {
				continue;
			}
			$file_type = wp_check_filetype( $src, array(
				// Image formats.
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif'          => 'image/gif',
				'png'          => 'image/png',
				'bmp'          => 'image/bmp',
				'tiff|tif'     => 'image/tiff',
				'ico'          => 'image/x-icon',
			) );
			if ( $file_type['ext'] === false ) {
				continue;
			}

			$res[] = $src;
		}

		$res = array_unique( $res );

		return $res;

	}

	#region download

	/**
	 * @param $post WP_Post
	 */
	public function pull_images( $founded ) {

		if ( empty( $_GET['pull_images_of_post'] ) ) {
			return;
		}
		$this->pulling = array();
		$posts = $_GET['pull_images_of_post'];
		if ( $posts === 'all' ) {
			foreach ( $founded as $post_id => $report ) {
				if ( count( $report['founded'] ) ) {
					$this->pull_post_images( $post_id );
				}
			}
		} elseif ( ! empty( absint( $posts ) ) ) {
			$this->pull_post_images( $posts );
		}
	}

	/**
	 * @param $post WP_Post|int|string post or post id
	 */
	public function pull_post_images( $post ) {
		$post = get_post( $post );

		if ( empty( $post ) ) {
			return false;
		}


		$images        = $this->find_images( $post, false );
		$content       = $post->post_content;

		if ( ! count( $images ) ) {
			?>
            <div class="notice notice-warning">
                <p><?php printf( __( 'The hotlinked images of post “%s” was not founded', 'hli' ), $post->post_title ); ?></p>
            </div>
			<?php
			return false;
		}

		$modified = false;
		foreach ( $images as $image ) {
                $attachment_id = $this->insert_attachment_from_url( $image, $post->ID );

			if ( ! $attachment_id ) {
				continue;
			}

			$this->pulling[ $post->ID ][ $image ] = $attachment_id;

			$hosted = wp_get_attachment_url( $attachment_id );

			$content = str_replace( $image, $hosted, $content );

			$modified = true;

		}

		if ( $modified ) {
			wp_update_post( array(
				'ID'           => $post->ID,
				'post_content' => $content,
			) );
		}

		$finded_img = count($images);
		$downloaded = count($this->pulling[ $post->ID ]);

		if ($downloaded == 0) {
			?>
            <div class="notice notice-warning is-dismissible">
                <p><?php printf( __( 'No one hotlinked images of post “%s” can be downloaded', 'hli' ), $post->post_title ); ?></p>
            </div>
			<?php
        } elseif ($finded_img > $downloaded) {
		    var_dump($finded_img, $downloaded);
			?>
            <div class="notice notice-warning is-dismissible">
                <p><?php printf( __( 'Some hotlinked images of post “%s” can be downloaded', 'hli' ), $post->post_title ); ?></p>
            </div>
			<?php
		} else {
			?>
            <div class="notice notice-success is-dismissible">
                <p><?php printf( __( 'The hotlinked images of post “%s” was downloaded', 'hli' ), $post->post_title ); ?></p>
            </div>
			<?php
        }

		return true;
	}

	public function insert_attachment_from_url( $url, $post_id = null ) {

		if ( ! class_exists( 'WP_Http' ) ) {
			include_once( ABSPATH . WPINC . '/class-http.php' );
		}

		$http     = new WP_Http();
		$response = $http->request( $url );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( $response['response']['code'] != 200 ) {
			return false;
		}

		$upload = wp_upload_bits( basename( $url ), null, $response['body'] );
		if ( ! empty( $upload['error'] ) ) {
			return false;
		}

		$file_path        = $upload['file'];
		$file_name        = basename( $file_path );
		$file_type        = wp_check_filetype( $file_name, null );
		$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
		$wp_upload_dir    = wp_upload_dir();

		$post_info = array(
			'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
			'post_mime_type' => $file_type['type'],
			'post_title'     => $attachment_title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Create the attachment
		$attach_id = wp_insert_attachment( $post_info, $file_path, $post_id );

		// Include image.php
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;

	}

	#endregion download

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Hotlinked_Images::get_instance();