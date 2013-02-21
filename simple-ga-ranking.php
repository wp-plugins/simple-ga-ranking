<?php
/*
Plugin Name: Simple GA Ranking
Author: Horike Takahiro
Plugin URI: http://www.kakunin-pl.us
Description: Ranking plugin using data from google analytics.
Version: 1.0
Author URI: http://www.kakunin-pl.us
Domain Path: /languages
Text Domain: 

Copyright 2013 horike takahiro (email : horike37@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'SGA_RANKING_DOMAIN' ) )
	define( 'SGA_RANKING_DOMAIN', 'sga-ranking' );
	
if ( ! defined( 'SGA_RANKING_PLUGIN_URL' ) )
	define( 'SGA_RANKING_PLUGIN_URL', plugins_url() . '/' . dirname( plugin_basename( __FILE__ ) ));

if ( ! defined( 'SGA_RANKING_PLUGIN_DIR' ) )
	define( 'SGA_RANKING_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ));

load_plugin_textdomain( SGA_RANKING_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages' );

require_once( SGA_RANKING_PLUGIN_DIR . '/admin/admin.php' );
require_once( SGA_RANKING_PLUGIN_DIR . '/lib/gapi.class.php' );

function sga_ranking_get_date( $args = array() ) {

	$options = get_option( 'sga_ranking_options' );
	try {

		$r = wp_parse_args( $args );
/*		if ( isset($r['start_date']) )
			$options['start_date'] = $r['start_date'];

		if ( isset($r['end_date']) )
			$options['end_date'] = $r['end_date'];*/

		if ( isset($r['period']) )
			$options['period'] = $r['period'];

		if ( isset($r['display_count']) )
			$options['display_count'] = $r['display_count'];

		if ( empty( $options['display_count'] ) )
			$options['display_count'] = apply_filters( 'sga_ranking_default_display_count', 10 );

		if ( empty( $options['period'] ) )
			$options['period'] = apply_filters( 'sga_ranking_default_period', 30 );

		$options['end_date'] = date_i18n( 'Y-m-d' );
		$options['start_date']   = date_i18n( 'Y-m-d', strtotime( $options['end_date'] . '-' . $options['period'] . 'day' ) );

		$transient_key = 'sga_ranking_' . $options['period'] . '_' . $options['display_count'];
		if ( !empty($r) ) {
			if ( array_key_exists( 'post_type', $r ) )
				$transient_key .= '_post_type_' . $r['post_type'];

			if ( array_key_exists( 'exclude_post_type', $r ) )
				$transient_key .= '_exclude_post_type_' . $r['exclude_post_type'];

			foreach ( $r as $k => $v ) {
				if ( strpos( $k, '__in' ) !== false )
					$transient_key .= '_' . $k . '_' . $r[$k];

				if ( strpos( $k, '__not_in' ) !== false )
					$transient_key .= '_' . $k . '_' . $r[$k];
			}
		}
		$transient_key = md5($transient_key);
		$transient_key = substr( $transient_key, 0, 30 );

		if ($id = get_transient($transient_key)) {
			return $id;
		} else {
			$ga = new gapi( $options['email'], $options['pass'] );
			$ga->requestReportData( 
					$options['profile_id'],
					array('hostname', 'pagePath'),
					array('visits'), array('-visits'),
					$filter='',
					$start_date=$options['start_date'],
					$end_date=$options['end_date'] 
			);

			$cnt = 0;
			$post_ids = array();
			foreach($ga->getResults() as $result) {
				$max = (int)$options['display_count'];
				if ( $cnt >= $max )
					break;

				$post_id = url_to_postid($result->getPagepath());
				if ( $post_id == 0 )
					continue;

				if ( !empty($r) ) {
					if ( array_key_exists( 'post_type', $r ) ) {
						$post_type = explode(',', $r['post_type'] );
						if ( !in_array( get_post($post_id)->post_type, $post_type ) )
							continue;
					}

					if ( array_key_exists( 'exclude_post_type', $r ) ) {
						$exclude_post_type = explode(',', $r['exclude_post_type'] );
						if ( in_array( get_post($post_id)->post_type, $exclude_post_type ) )
							continue;
					}

					$tax_in_flg = true;
					foreach ( $r as $key => $val ) {
						if ( strpos( $key, '__in' ) !== false ) {
							$tax = str_replace( '__in', '', $key );
							$tax_in = explode(',', $r[$key] );
							$post_terms = get_the_terms( $post_id, $tax );
							$tax_in_flg = false;
							if ( !empty($post_terms) && is_array($post_terms) ) {
								foreach ( $post_terms as $post_term ) {
									if ( in_array( $post_term->slug, $tax_in ) )
										$tax_in_flg = true;
								}
							}
							break;
						}
					}
					if ( !$tax_in_flg )
						continue;

					$tax_not_in_flg = true;
					foreach ( $r as $key => $val ) {
						if ( strpos( $key, '__not_in' ) !== false ) {
							$tax = str_replace( '__not_in', '', $key );
							$tax_in = explode(',', $r[$key] );
							$post_terms = get_the_terms( $post_id, $tax );
							$tax_not_in_flg = false;
							if ( !empty($post_terms) && is_array($post_terms) ) {
								foreach ( $post_terms as $post_term ) {
									if ( !in_array( $post_term->slug, $tax_in ) )
										$tax_not_in_flg = true;
								}
							}
							break;
						}
					}
					if ( !$tax_not_in_flg )
						continue;
				}

				$post_ids[] = $post_id;
				$cnt++;
			}
			if ( !empty($post_ids) ) {
				delete_transient($transient_key);
				$dasad=set_transient(
					$transient_key,
					$post_ids,
					intval(apply_filters('sga_ranking_cache_expire', 24*60*60))
				);
 				return $post_ids;
			}
		}
	} catch (Exception $e) { 
		if ( is_user_logged_in() )
			print 'Simple GA Ranking Error: ' . $e->getMessage(); 
	}
}

add_filter( 'widget_text', 'do_shortcode' );
add_shortcode('sga_ranking', 'sga_ranking_shortcode');
function sga_ranking_shortcode( $atts ) {

	$options = get_option( 'sga_ranking_options' );
	$ids = sga_ranking_get_date($atts);

	if ( empty( $ids ) )
		return;

	$key = 'sga_ranking_' . $options['period'] . '_' . $options['display_count'] . '_html';
	$key = md5($key);
	$key = substr( $key, 0, 30 );
	if ($html = get_transient($key)) {
		return $html;
	} else {
		$cnt = 1;
		$output = '<ol class="sga-ranking">';
		foreach( $ids as $id ) {
			$output .= '<li class="sga-ranking-list sga-ranking-list-'.$cnt.'"><a href="'.get_permalink($id).'" title="'.get_the_title($id).'">'.get_the_title($id).'</a></li>';
			$cnt++;
		}
		$output .= '</ol>';
		delete_transient($key);
		set_transient(
			$key,
			$output,
			intval(apply_filters('sga_ranking_html_cache_expire', 24*60*60))
		);
		return $output;
	}
}