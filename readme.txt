=== WP Recommender ===
Contributors: Peter Ljubic
Tags: aliiike, recommender system, collaborative filtering, recommendations, widget, plugin, post, link, ajax
Requires at least: 2.4
Tested up to: 2.7
Stable tag: 0.1.5

Plugin gives recommendations based on posts user have seen on your blog.

== Description ==


This plugin finds and suggests relevant, potentially interesting content from
your blog to visitors. The goal is to push the relevant content to the visitor
with the prospect of increase of page views on your blog and eventually 
satisfied and returning visitor. 

Plugin is able to recommend similar posts and pages using two different
approaches. The first is content based and produces list of the most similar 
items based on words occurring in both items. Advantage of this approach lies
in is its simplicity for users to install and it works straight from the start
(there's no so-called "cold start" period from the collaborative filtering 
approach).

The second approach is employing collaborative filtering to identify post and
pages that might interest user. Suggested items are calculated from the users'
browsing history that plugin logs on the aliiike server. Server accepts such
logs only if you are registered and you created an account. Disadvantage of 
this approach is the fact that you need to wait until enough data is logged.
If the traffic on your site is low it can last for a while. Advantage of this
approach is the fact that you are modeling users' behaviour (rather than the
contents), which in normal circumstances should easily outperform the content
based approach.

Important aspect of plugin is its ability to serve recommendation lists to a
settable percentage of users only. In combination with the Google Analytics
this feature allows you to perform so-called A/B testing to measure the 
influence of the recommended items on your visitors' browsing behavior (or 
sales increase or decrease in case you are running a web shop).

See the [Aliiike recommender homepage](http://aliiike.com/wordpress/ "plugin homepage") 
as well as the rest of the [site](http://aliiike.com) for more information.


== Installation ==

Two different approaches require a bit different installation procedure and
have different prerequisites. First, I describe the simpler, content based
approach installation.

I. Content based

This approach is currently supported on MySQL database only. In case you use it
the installation procedure is the following:

1. Download the plugin and unpack it to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

Should you face problems when creating the fulltext index on database, try to
run manually the following SQL query:

CREATE FULLTEXT INDEX `comparator` ON `wp_posts` (`post_title`, `post_content`)

II. Collaborative filtering

This approach logs the visits on your site on aliiike.com server. Logging 
and recommending requires an account ID therefore:

1. You have to register and create an account for your site on aliiike.com.
2. Download the plugin and unpack it to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Insert an account id and secret key in the recommender settings page.

In both approaches you Customize the look and feel via 'Plugins' | 'Aliiike Recommender' menu.

== Frequently Asked Questions ==

I welcome you to send some questions on peter.ljubic - AT - aliiike.com.
