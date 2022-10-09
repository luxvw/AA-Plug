=== AA Plug ===
Tags: debloat, open graph, ogp, markdown, maintenance, mastodon, indexnow
Requires at least: 4.4
Tested up to: 4.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

De-bloats, Tweaks, Markdown, OGP, IndexNow, Auto-post to Mastodon, Maintenance, etc.


== Description ==

**Major features**:

- Debloat your site to get better performance.
- Write your posts in **Markdown** or pure **HTML**, in addition to the default AutoP behavior.
- Share your posts on **Mastodon** with rich preview generated from **Open Graph protocol**.
- Submit your posts to **IndexNow** for faster search engine indexing.
- and more.

**Major anti-features**:

- Default debloating behaviors that are not configurable.
- Multisites are not supported yet.


## Features

### Write posts in Markdown

- Option to toggle **Markdown** parser per post/page, powered by [Parsedown Extra](https://github.com/erusev/parsedown-extra) with extended syntax such as 
	- Custom emoji: `:grin:`
	- Spoiler text: `||I am your farther.||` ( Discord flavor )
	- Add `loading="lazy"` attribute to images

Note:

This feature is for people who write their articles offline with their personal choice of text editor.  
**If you need a WYSIWYG editor, don't enable this feature**. It will disable your fancy TinyMCE as well as not-so-fancy Quicktags.  
For WordPress 5.0+, [Classic Editor](https://wordpress.org/plugins/classic-editor/) should be installed and activated.


### Add Open Graph and description meta tags

- Option to add [**Open Graph**](https://ogp.me/) meta tags and description tag.

You can set `og:img` per post. 
If omited, it will fallback to the featured image and then the first image of the post.  
Twitter card tags are generally compatible with OGP, thus skipped, except for `twitter:cards` .


### Auto-Post to Mastodon

- Option to share new posts to your **Mastodon** account.

Media uploading is not supported, since Mastodon reads **Open Graph meta tags** to generate a preview card with image.  
If you need more features, try "Mastodon Autopost" plugin instead.


### Auto-Submit to IndexNow

- Option to auto-submit URLs to **IndexNow**.

Trashed and slug-changed URLs will be submitted too, as to submit the changed HTTP status.  
If you need a somewhat fancier interface or want to keep a log of submitted URLs, 
try the official "IndexNow" Plugin instead.


### Comment IP Privacy Control

- Option to disable or anonymize Comment IP logging for visitors.
- Comment IP and User-Agent logging for registered users willed be disabled at the same time.


### Debloats

- Features disabled
	- WPEmoji ( WP 4.2+ )
	- Embeds ( WP 4.4+ )
	- Attachment pages ( [Reference](https://gschoppe.com/wordpress/disable-attachment-pages) )
	- Core "Update" nag from admin notice area (you'll still see it elsewhere)
	- Content filters: `convert_smilies`, `wptexturize`, `capital_P_dangit`,
	- `make_clickable` filter for `comment_text`, might reduce spam.


- Scripts detached
	- Heartbeat, Autosave
	- MediaElement.js
	- Password strength meter ( 800kb+ )
	- Jquery Migrate (from frontend)

- The following tags will be removed from head area
	- Generator meta tag
	- link tags
		- `wlwmanifest` (Windows Live Writer Manifest)
		- `EditURI` (Really Simple Discovery service endpoint)
		- `shortlink`
		- oEmbed Discovery
		- REST API endpoint
	- `recent_comments_style` inline style shipped with "Recent Comments" widget
	- WPEmoji inline styles and scripts

As a result, these tags will be removed.

```html
	<meta name="generator"   content="WordPress x.x.x" />
	<link rel="wlwmanifest"  type="application/wlwmanifest+xml" href="../wordpress/wp-includes/wlwmanifest.xml" />
	<link rel="EditURI"      type="application/rsd+xml"         href="../wordpress/xmlrpc.php?rsd"  title="RSD" />
	<link rel="shortlink"                                       href=".../?p=:id" />
	<link rel="alternate"    type="application/json"            href="../wp-json/wp/v2/posts/:id" />
	<link rel="alternate"    type="application/json+oembed"     href="../wp-json/oembed/1.0/embed?url=..." />
	<link rel="alternate"    type="text/xml+oembed"             href="../wp-json/oembed/1.0/embed?url=..." />
	<link rel="https://api.w.org/"                              href="../wp-json/" />
	<link rel="dns-prefetch" href="//s.w.org" />
	<script>...</script><style>img.wp-smiley,img.emoji{...}</style>
	<style>.recentcomments a{...}</style>
```

- Aggressive debloats
	- Option to remove the heavy media templates and scripts to speed up your admin panel, not recommended unless you know what you're doing.



### Others

- Prevent user name enumeration by disabling `author=ID` redirect.
- Option to disable **author archive**.
- Option to turn on 503 **maintenance mode**.
- Auto-delete `readme.html` and `license.txt` and chmod `wp-config.php` after core upgrade.
- Insert custom code to page head and foot, useful for extra scripts or styles.
- Feed
	- Option for items in full-text feed to link back to their original source.
- Updates
	- Option to disable auto-update.
	- Option to disable update checks against wordpress.org server.
- Login page
	- Remove WordPress branding from login page.
	- Option to restrict login methods, allow Email or username login only.
- XMP-RPC
	- Option to disable pingback or all XMP-RPC methods.
- REST API
	- Option to restrict REST API public access (WP 4.7+) or disable it (WP 4.4~4.6)


## Plugin footprints

This plugin writes into following fields of your database.

* `wp_options` table: `aa_settings`, transients
* `wp_posts` table: `post_mime_type`
* `wp_postmeta` table: `_image`

Settings and transients will be deleted after uninstall, `post_mime_type` and `_image` will stay.



== Changelog ==

= 1.0.0 =
* Released.


== Frequently Asked Questions ==

= What's the point ? =

Tired of "go pro" nags, bloated fancy stuff and long list of little plugins that makes life harder.
So I just wrote my own wheels for my own sake.

= Plugin name is stupid. =

So it stays on top of my plugin list, thanks to the alphabet chart.

= No translations ? =

Not yet, no.

