# Installation

1. [Download](https://github.com/tkduman/dlike/archive/master.zip) the plugin.
2. Log in to your admin panel.
3. Navigate to Plugins and select Add New.
4. Click on Upload Plugin and select the downloaded zip file from step 1.
5. You will see a console output like:

```
Installing Plugin from uploaded file: dlike-master.zip
Unpacking the package…

Installing the plugin…

Plugin installed successfully.
```

This means that everything went perfect! Woo! Now, let's keep going on.

6. Click on Activate Plugin.
7. Navigate to Appearance, Widgets.
8. Drag and drop the "DLIKE - Top 10 list for the dlike widget" to your desired sidebar/footer.
9 Add a title for your widget, if you want to. (Optional)
10. Go to your homepage and start using it!
11. Enjoy!

# Demo sites

* https://test.dumandocs.com (Using Hueman theme)
* https://test.dumanstudios.com (Using stock wordpress theme)

# Some cool features

* Even if you remove the extension at a point and then re-install it, your likes will remain and you can just use your old data.
* You can use it with any theme, it'll just blend in with the stylesheet of your theme.
* It uses AJAX so that you don't have to reload the page every time you like/unlike something. Thanks to XHR magic.
* It's neither folder dependent nor hardcoded. Due to that it'll install with zero issues to any wordpress installation.
* Heart symbols under posts are drawn with SVG, they will look great no matter how zoomed in you are in your screen.
* Top 10 widget automatically creates list numbers and sorts itself by like amounts.
* Pretty much everything is done with Wordpress API and all injection attacks are tested for safety. _(If you find some open an [issue](https://github.com/tkduman/dlike/issues) please.)_
* Normally like button is embedded inside the post content but if theme allows displaying full post content on the front page, like button will also be clickable on the frontpage. Otherwise you need to go into the post, as it's supposed to be.

# Roadmap

* Will add an options page and will provide some options for pruning likes.
* Will provide more freedom over which symbol is used to favorite/like a post. (Heart, star etc.)
* Code improvements.

# Fun fact

I've created this as my intern application project for [Özgür Yazılım](https://ozguryazilim.com.tr). Özgür Yazılım have stated that this code is not for their company and I can use as a reference in the future. I hope I get to use this widget as a reference someday, somewhere. (:
