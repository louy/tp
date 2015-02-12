=== Plugin Name ===
Contributors: louyx
Author URL: http://l0uy.com/
Tags: twitter, oauth, login, tweet, tweetbutton, comment, publish, connect, admin, plugin, comments, wpmu, button
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 1.4

All the tools you need to integrate your wordpress and twitter.

== Description ==

TweetPress, gives you all the tools you need to integrate your wordpress and twitter, including "Login with Twitter" and "Comment via Twitter"...
highly customizable and easy to use.


= Key Features =

* Allow your visitors to comment using their twitter ids
* Adds a tweet button to your posts, so your visitors can share your content.
* Allow your blog users to sign in with their twitter ids. one click signin!
* Automatically publish new posts to a twitter account.
* Easily customizable by theme authors.
* Add a follow button to your blog

== Changelog ==

= 2.0 =
* Now using CodeBird PHP Twitter library
* Many bugfixes and cleanups

= 1.4 =
* Update to use twitter 1.1 api version
* Now using avatars.io for comments avatars

= 1.3.5 =
* Adding new features to tweetbutton (#hashtags)
* General bug fixes

= 1.3.4 =
* Hiding a notice

= 1.3.3 =
* Fixing auto-publish

= 1.3.2 =
* Fixing notice when comment registeration is enabled, and TP comments are enabled too.

= 1.3.1 =
* Fixing a bug with tweetbutton css

= 1.3 =
* Moving Tweetpress App options page to the network admin in Multi-Site installations

= 1.2.9 =
* A small fix in the options page

= 1.2.8 =
* Fixing a typo

= 1.2.6 =
* Few small fixes

= 1.2 =
* TweetPress now works awesomely on multi-site wordpress installations.

= 1.0 =
* now using the wp-oauth.php script http://gist.github.com/585267

= 0.5 =
* add follow button
* some minor changes

= 0.4 =
* TweetButton is now more customizable from the theme.
* Some i18n issues fixed.

= 0.3 =
* plugin automatically sets related twitter usernames to the source and post author if available.
* the tweetbutton source is now the website twitter not the author's

= 0.2 =
* adding the "publish" function.
* making the login image changable by the theme.

= 0.1 =
* Initial release

== Installation ==

1. Download the plugin, unzip it and upload it to `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Settings &raquo; TweetPress and enter your key and secret.

== Frequently Asked Questions ==

= When I click "Sign in with Twitter", I get a 404 error page. what can I do? =

Well, TweetPress uses rewrite, so check your Settings > Permalink page and make sure rewrite is enabled.

= I'm getting an error when I try to sign in with my twitter user: "Twitter user not recognized!" =

Make sure you've linked your twitter and wordpress accounts, you can do that in your /wp-admin/profile.php page.

= The comments login button isn't showing!

It may be because your theme is a bit old or doesn't use the new Wordpress standards.
You have to modify your theme to use this function.

In your comments.php file (or wherever your comments form is), you need to do the following.
1. Find the three inputs for the author, email, and url information. They need to have those ID's on the inputs (author, email, url). This is what the default theme and all standardized themes use, but some may be slightly different. You'll have to alter them to have these ID's in that case.
1. Just before the first input, add this code: &lt;div id="comment-user-details"&gt; &lt;?php do_action('alt_comment_login'); ?&gt;
1. Just below the last input (not the comment text area, just the name/email/url inputs, add this: <code>&lt;/div&gt;</code>

That will add the necessary pieces to allow the script to work.

