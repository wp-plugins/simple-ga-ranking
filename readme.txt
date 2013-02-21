=== Simple GA Ranking  ===
Contributors: horike
Tags:  form, ranking, popular, google analytics
Requires at least: 3.5.1
Tested up to: 3.5.1
Stable tag: 1.0

Ranking plugin using data from google analytics.

== Description ==

Ranking plugin using data from google analytics.

= How use =
1. Google analytics settings from the management screen(http://path/to/wordpress/wp-admin/options-general.php?page=su_ranking)
2. Set shortcode [sga_ranking], then show ranking.
3. you can filter a ranking for post_type and Taxonomies. Example, [sga_ranking post_type="post,page"],[sg_ranking post_type="post" category__in="wordpress"]
4. Parameters that can be used in the shortcode are as follows:post_type, exclude_post_type, 'taxonomy_slug'__in, 'taxonomy_slug'__not_in. Set the value to the slug.
5. It provides 'sga_ranking_get_date( $args = array() )' function  for those who want to customize from the HTML of the ranking.

= Translators =
* Japanese(ja) - [Horike Takahiro](http://twitter.com/horike37)

You can send your own language pack to me.

Please contact to me.

* @[horike37](http://twitter.com/horike37) on twitter
* [Horike Takahiro](https://www.facebook.com/horike.takahiro) on facebook

= Contributors =
* [Horike Takahiro](http://twitter.com/horike37)

== Installation ==

1. Upload `simple-ga-ranking` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Screenshots ==

== Changelog ==
= 1.0 =
* first release. 