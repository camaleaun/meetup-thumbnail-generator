<?php
/**
 * Plugin Name:       Meetup Thumbnail Generator
 * Description:       Event thumbnails for WordPress meetups made of core blocks: 1280 x 720 on desktop and a 4:5 post (1080 x 1350) on phones, with no speaker, one or two speakers, with or without sponsors.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Gilberto Tavares
 * Author URI:        https://profiles.wordpress.org/camaleaun/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       meetup-thumbnail-generator
 *
 * @package MeetupThumbnailGenerator
 */

defined( 'ABSPATH' ) || exit;

const MTG_VERSION = '0.1.0';

/**
 * The variations: number of speakers and whether the sponsors row is shown.
 *
 * @return array<string, array{title: string, speakers: int, sponsors: bool}>
 */
function mtg_variations() {
	return array(
		'one-speaker-sponsors'  => array( 'title' => 'One speaker, with sponsors', 'speakers' => 1, 'sponsors' => true ),
		'one-speaker'           => array( 'title' => 'One speaker, no sponsors', 'speakers' => 1, 'sponsors' => false ),
		'two-speakers-sponsors' => array( 'title' => 'Two speakers, with sponsors', 'speakers' => 2, 'sponsors' => true ),
		'two-speakers'          => array( 'title' => 'Two speakers, no sponsors', 'speakers' => 2, 'sponsors' => false ),
		'no-speaker-sponsors'   => array( 'title' => 'No speaker, with sponsors', 'speakers' => 0, 'sponsors' => true ),
		'no-speaker'            => array( 'title' => 'No speaker, no sponsors', 'speakers' => 0, 'sponsors' => false ),
	);
}

/**
 * Block comment attributes as JSON.
 *
 * @param array $attrs Block attributes.
 * @return string
 */
function mtg_attrs( array $attrs ) {
	return $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '';
}

/**
 * Group block.
 *
 * @param string   $class_name Class name.
 * @param string[] $inner      Inner blocks markup.
 * @param string   $tag        Tag name.
 * @param array    $extra      Extra attributes.
 * @return string
 */
function mtg_group( $class_name, array $inner, $tag = 'div', array $extra = array() ) {
	$attrs = array_merge( 'div' === $tag ? array() : array( 'tagName' => $tag ), $extra, array( 'className' => $class_name ) );
	return '<!-- wp:group' . mtg_attrs( $attrs ) . " -->\n<$tag class=\"wp-block-group $class_name\">" . implode( '', $inner ) . "</$tag>\n<!-- /wp:group -->\n";
}

/**
 * Paragraph block.
 *
 * @param string $class_name Class name.
 * @param string $html       Content.
 * @return string
 */
function mtg_paragraph( $class_name, $html ) {
	return '<!-- wp:paragraph' . mtg_attrs( array( 'className' => $class_name ) ) . " -->\n<p class=\"$class_name\">$html</p>\n<!-- /wp:paragraph -->\n";
}

/**
 * Image block with an image from the plugin.
 *
 * @param string $class_name Class name.
 * @param string $file       File in assets/images.
 * @param string $alt        Alternative text.
 * @return string
 */
function mtg_image( $class_name, $file, $alt ) {
	$src   = esc_url( plugins_url( 'assets/images/' . $file, __FILE__ ) );
	$attrs = array(
		'sizeSlug'        => 'full',
		'linkDestination' => 'none',
		'className'       => $class_name,
	);
	return '<!-- wp:image' . mtg_attrs( $attrs ) . " -->\n<figure class=\"wp-block-image size-full $class_name\"><img src=\"$src\" alt=\"" . esc_attr( $alt ) . "\"/></figure>\n<!-- /wp:image -->\n";
}

/**
 * Heading block (h1).
 *
 * @param string $class_name Class name.
 * @param string $html       Content.
 * @return string
 */
function mtg_heading( $class_name, $html, $level = 1 ) {
	$attrs = 2 === $level ? array( 'className' => $class_name ) : array( 'level' => $level, 'className' => $class_name );
	return '<!-- wp:heading' . mtg_attrs( $attrs ) . " -->\n<h$level class=\"wp-block-heading $class_name\">$html</h$level>\n<!-- /wp:heading -->\n";
}

/**
 * The thumbnail block markup for a variation. Only the content can be
 * edited (contentOnly): texts, photos and logos.
 *
 * @param int  $speakers 0, 1 or 2.
 * @param bool $sponsors Whether to show the sponsors row.
 * @return string
 */
function mtg_thumbnail_markup( $speakers, $sponsors ) {
	$details = array();
	if ( 1 === $speakers ) {
		$details[] = mtg_group(
			'mtg-guest',
			array(
				mtg_image( 'mtg-avatar', 'speaker.jpg', 'Photo of Speaker Name' ),
				mtg_paragraph( 'mtg-guest-name', 'Speaker Name' ),
			)
		);
	} elseif ( 2 === $speakers ) {
		// Two talks: the photos partly overlap below the box, and each talk
		// title with its speaker goes in the box, one above the other.
		$details[] = mtg_group(
			'mtg-avatars',
			array(
				mtg_image( 'mtg-avatar', 'speaker.jpg', 'Photo of First Speaker' ),
				mtg_image( 'mtg-avatar', 'speaker-2.jpg', 'Photo of Second Speaker' ),
			)
		);
	}
	$details[] = mtg_paragraph( 'mtg-description', 'Venue: <strong>Venue name</strong><br>Street address, 123<br>City - State' );

	$bottom = array();
	if ( $sponsors ) {
		$bottom[] = mtg_group(
			'mtg-sponsors',
			array(
				mtg_paragraph( 'mtg-sponsors-label', 'SPONSORS' ),
				mtg_image( 'mtg-sponsor is-tall', 'sponsor-1.png', 'Sponsor 1' ),
				mtg_image( 'mtg-sponsor is-tall', 'sponsor-2.png', 'Sponsor 2' ),
			)
		);
	}
	$bottom[] = mtg_paragraph( 'mtg-signup', 'Free registration' );

	if ( 2 === $speakers ) {
		$talks = array();
		foreach ( array( array( 'First talk title goes here', 'First Speaker' ), array( 'Second talk title goes here', 'Second Speaker' ) ) as $talk ) {
			$talks[] = mtg_group(
				'mtg-talk',
				array(
					mtg_heading( 'mtg-talk-title', $talk[0], 2 ),
					mtg_paragraph( 'mtg-talk-speaker', $talk[1] ),
				)
			);
		}
		$title_box = mtg_group( 'mtg-title-box', array( mtg_group( 'mtg-talks', $talks ) ) );
	} else {
		$title     = $speakers ? 'Your talk title goes here, in up to two lines' : 'Meetup night: networking and lightning talks';
		$title_box = mtg_group( 'mtg-title-box', array( mtg_heading( 'mtg-title', $title ) ) );
	}

	return mtg_group(
		'mtg-thumb',
		array(
			mtg_group(
				'mtg-thumb-header',
				array(
					mtg_group(
						'mtg-brand',
						array(
							mtg_image( 'mtg-logo', 'wordpress-w-mark.png', 'WordPress' ),
							mtg_group(
								'mtg-type-wrap',
								array(
									mtg_paragraph( 'mtg-type mtg-type-light', 'WordPress Meetup City' ),
									mtg_paragraph( 'mtg-type', '#1 In person' ),
								)
							),
						)
					),
					mtg_group(
						'mtg-toolbar',
						array(
							mtg_paragraph( 'mtg-date', 'NOV 09' ),
							mtg_paragraph( 'mtg-time', '7:00 PM' ),
						)
					),
				),
				'header'
			),
			$title_box,
			mtg_group( 'mtg-details', $details ),
			mtg_group( 'mtg-bottom', $bottom ),
		),
		'div',
		array( 'templateLock' => 'contentOnly' )
	);
}

/**
 * Styles on the front end and in the editor canvas.
 */
function mtg_enqueue_assets() {
	wp_enqueue_style( 'meetup-thumbnail-generator', plugins_url( 'assets/style.css', __FILE__ ), array(), MTG_VERSION );
}
add_action( 'enqueue_block_assets', 'mtg_enqueue_assets' );

/**
 * Patterns (one per variation) and the template that shows only the page
 * content: no header, footer or admin bar.
 */
function mtg_register() {
	register_block_pattern_category( 'meetup-thumbnail', array( 'label' => __( 'Meetup thumbnail', 'meetup-thumbnail-generator' ) ) );

	foreach ( mtg_variations() as $slug => $variation ) {
		register_block_pattern(
			'meetup-thumbnail-generator/' . $slug,
			array(
				'title'      => 'Meetup thumbnail: ' . $variation['title'],
				'categories' => array( 'meetup-thumbnail' ),
				'content'    => mtg_thumbnail_markup( $variation['speakers'], $variation['sponsors'] ),
			)
		);
	}

	if ( function_exists( 'register_block_template' ) ) {
		register_block_template(
			'meetup-thumbnail-generator//meetup-thumbnail',
			array(
				'title'       => __( 'Meetup thumbnail', 'meetup-thumbnail-generator' ),
				'description' => __( 'Only the page content, to take the thumbnail screenshot.', 'meetup-thumbnail-generator' ),
				'post_types'  => array( 'page' ),
				'content'     => '<!-- wp:post-content {"className":"mtg-thumb-page","layout":{"type":"default"}} /-->',
			)
		);
	}
}
add_action( 'init', 'mtg_register' );

/**
 * Demo content: one page per variation with the thumbnail template, and a
 * home page that lists them. Used by the Playground blueprint.
 */
function mtg_install_demo() {
	$items = '';
	foreach ( mtg_variations() as $slug => $variation ) {
		$existing = get_page_by_path( $slug );
		$id       = $existing ? $existing->ID : wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $variation['title'],
				'post_content' => mtg_thumbnail_markup( $variation['speakers'], $variation['sponsors'] ),
				'page_template' => 'meetup-thumbnail',
			)
		);
		update_post_meta( $id, '_wp_page_template', 'meetup-thumbnail' );
		$items .= '<li><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( $variation['title'] ) . '</a> · <a href="' . esc_url( admin_url( 'post.php?post=' . $id . '&action=edit' ) ) . '">edit</a></li>';
	}

	$home = '<!-- wp:heading -->' . "\n" . '<h2 class="wp-block-heading">Pick a thumbnail</h2>' . "\n" . '<!-- /wp:heading -->' . "\n\n"
		. '<!-- wp:paragraph -->' . "\n" . '<p>Each page is one event thumbnail made of core blocks. Open it, then edit it to change the texts, photos and logos.</p>' . "\n" . '<!-- /wp:paragraph -->' . "\n\n"
		. '<!-- wp:list -->' . "\n" . '<ul class="wp-block-list">' . str_replace( array( '<li>', '</li>' ), array( "<!-- wp:list-item -->\n<li>", "</li>\n<!-- /wp:list-item -->\n" ), $items ) . '</ul>' . "\n" . '<!-- /wp:list -->' . "\n\n"
		. '<!-- wp:heading {"level":3} -->' . "\n" . '<h3 class="wp-block-heading">Export</h3>' . "\n" . '<!-- /wp:heading -->' . "\n\n"
		. '<!-- wp:list -->' . "\n" . '<ul class="wp-block-list"><!-- wp:list-item -->' . "\n" . '<li><strong>Event thumbnail (16:9):</strong> browser DevTools, device toolbar at 1280 × 720, then "Capture screenshot".</li>' . "\n" . '<!-- /wp:list-item -->' . "\n\n" . '<!-- wp:list-item -->' . "\n" . '<li><strong>Instagram post (4:5):</strong> 360 × 450 with device pixel ratio 3, for 1080 × 1350.</li>' . "\n" . '<!-- /wp:list-item --></ul>' . "\n" . '<!-- /wp:list -->';

	$home_page = get_page_by_path( 'thumbnail-generator' );
	$home_id   = $home_page ? $home_page->ID : wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => 'thumbnail-generator',
			'post_title'   => 'Meetup Thumbnail Generator',
			'post_content' => $home,
		)
	);
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_id );
	update_option( 'blogname', 'Meetup Thumbnail Generator' );
}
