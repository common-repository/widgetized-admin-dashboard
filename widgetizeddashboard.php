<?php
/*
Plugin Name: Widgetized Dashboard
Plugin URI: http://www.Pressography.com/plugins/widgetized-admin-dashboard
Description: Allows you to easily change the admin dashboard widgets via the Design > Widgets page, just like sidebar widgets
Version: 1.6
Author: Jason DeVelvis
Author URI: http://www.Pressography.com/
*/

/*
Includes
- Dashboard is required to be included in order to run wp_dashboard_setup, but only for the widgets page
*/
if (strpos($_SERVER['REQUEST_URI'],"widgets.php")) {
    require_once(ABSPATH . '/wp-admin/includes/dashboard.php');

  /* Register WP Dashboard Dynamic Sidebar and Default Dashboard Widgets */
  function aw_register_dashboard() {
    wp_dashboard_setup();
  }                                               
  add_action('init','aw_register_dashboard', 10);  
}

//Constants
define('DASHSIDEBARNAME','wp_dashboard');

function wd_return_actual_dash_widgets() {
    // These are the widgets grouped by sidebar
    $sidebars_widgets = wp_get_sidebars_widgets();
    if ( empty( $sidebars_widgets ) )
        $sidebars_widgets = wp_get_widget_defaults();

    // for the sake of PHP warnings
    if ( empty( $sidebars_widgets['wp_dashboard'] ) )
        $sidebars_widgets['wp_dashboard'] = array();

    return $sidebars_widgets['wp_dashboard'];
}

function wd_widget_rss($args, $widget_args = 1) {
    extract($args, EXTR_SKIP);
    if ( is_numeric($widget_args) )
        $widget_args = array( 'number' => $widegt_args );
    $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
    extract($widget_args, EXTR_SKIP);

    $options = get_option('admin_widget_rss');

    if ( !isset($options[$number]) )
        return;

    if ( isset($options[$number]['error']) && $options[$number]['error'] )
        return;

    $url = $options[$number]['url'];
    while ( strstr($url, 'http') != $url )
        $url = substr($url, 1);
    if ( empty($url) )
        return;

    require_once(ABSPATH . WPINC . '/rss.php');

    $rss = fetch_rss($url);
    $link = clean_url(strip_tags($rss->channel['link']));
    while ( strstr($link, 'http') != $link )
        $link = substr($link, 1);
    $desc = attribute_escape(strip_tags(html_entity_decode($rss->channel['description'], ENT_QUOTES)));
    $title = $options[$number]['title'];
    if ( empty($title) )
        $title = htmlentities(strip_tags($rss->channel['title']));
    if ( empty($title) )
        $title = $desc;
    if ( empty($title) )
        $title = __('Unknown Feed');
    $url = clean_url(strip_tags($url));
    if ( file_exists(dirname(__FILE__) . '/rss.png') )
        $icon = str_replace(ABSPATH, get_option('siteurl').'/', dirname(__FILE__)) . '/rss.png';
    else
        $icon = get_option('siteurl').'/wp-includes/images/rss.png';
    $title = "<a class='rsswidget' href='$url' title='" . attribute_escape(__('Syndicate this content')) ."'><img style='background:orange;color:white;border:none;' width='14' height='14' src='$icon' alt='RSS' /></a> <a class='rsswidget' href='$link' title='$desc'>$title</a>";

    echo $before_widget;
    echo $before_title . $title . $after_title;

    wd_widget_rss_output( $rss, $options[$number] );

    echo $after_widget;
}

function wd_widget_rss_output( $rss, $args = array() ) {
    if ( is_string( $rss ) ) {
        require_once(ABSPATH . WPINC . '/rss.php');
        if ( !$rss = fetch_rss($rss) )
            return;
    } elseif ( is_array($rss) && isset($rss['url']) ) {
        require_once(ABSPATH . WPINC . '/rss.php');
        $args = $rss;
        if ( !$rss = fetch_rss($rss['url']) )
            return;
    } elseif ( !is_object($rss) ) {
        return;
    }

    extract( $args, EXTR_SKIP );

    $items = (int) $items;
    if ( $items < 1 || 20 < $items )
        $items = 10;
    $show_summary  = (int) $show_summary;
    $show_author   = (int) $show_author;
    $show_date     = (int) $show_date;

    if ( is_array( $rss->items ) && !empty( $rss->items ) ) {
        $rss->items = array_slice($rss->items, 0, $items);
        echo '<ul>';
        foreach ($rss->items as $item ) {
            while ( strstr($item['link'], 'http') != $item['link'] )
                $item['link'] = substr($item['link'], 1);
            $link = clean_url(strip_tags($item['link']));
            $title = attribute_escape(strip_tags($item['title']));
            if ( empty($title) )
                $title = __('Untitled');
            $desc = '';
                if ( isset( $item['description'] ) && is_string( $item['description'] ) )
                    $desc = str_replace(array("\n", "\r"), ' ', attribute_escape(strip_tags(html_entity_decode($item['description'], ENT_QUOTES))));
                elseif ( isset( $item['summary'] ) && is_string( $item['summary'] ) )
                    $desc = str_replace(array("\n", "\r"), ' ', attribute_escape(strip_tags(html_entity_decode($item['summary'], ENT_QUOTES))));

            $summary = '';
            if ( isset( $item['description'] ) && is_string( $item['description'] ) )
                $summary = $item['description'];
            elseif ( isset( $item['summary'] ) && is_string( $item['summary'] ) )
                $summary = $item['summary'];

            $desc = str_replace(array("\n", "\r"), ' ', attribute_escape(strip_tags(html_entity_decode($summary, ENT_QUOTES))));

            if ( $show_summary ) {
                $desc = '';
                $summary = wp_specialchars( $summary );
                $summary = "<div class='rssSummary'>$summary</div>";
            } else {
                $summary = '';
            }

            $date = '';
            if ( $show_date ) {
                if ( isset($item['pubdate']) )
                    $date = $item['pubdate'];
                elseif ( isset($item['published']) )
                    $date = $item['published'];

                if ( $date ) {
                    if ( $date_stamp = strtotime( $date ) )
                        $date = '<span class="rss-date">' . date_i18n( get_option( 'date_format' ), $date_stamp ) . '</span>';
                    else
                        $date = '';
                }
            }

            $author = '';
            if ( $show_author ) {
                if ( isset($item['dc']['creator']) )
                    $author = ' <cite>' . wp_specialchars( strip_tags( $item['dc']['creator'] ) ) . '</cite>';
                elseif ( isset($item['author_name']) )
                    $author = ' <cite>' . wp_specialchars( strip_tags( $item['author_name'] ) ) . '</cite>';
            }

            echo "<li><a class='rsswidget' href='$link' title='$desc'>$title</a>{$date}{$summary}{$author}</li>";
        }
        echo '</ul>';
    } else {
        echo '<ul><li>' . __( 'An error has occurred; the feed is probably down. Try again later.' ) . '</li></ul>';
    }
}
                            
function wd_widget_rss_control($widget_args) {
    global $wp_registered_widgets;
    static $updated = false;

    if ( is_numeric($widget_args) )
        $widget_args = array( 'number' => $widget_args );
    $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
    extract($widget_args, EXTR_SKIP);

    $options = get_option('admin_widget_rss');
    if ( !is_array($options) )
        $options = array();                   
        
    $urls = array();
    foreach ( $options as $option )
        if ( isset($option['url']) )
            $urls[$option['url']] = true;

    if ( !$updated && 'POST' == $_SERVER['REQUEST_METHOD'] && !empty($_POST['sidebar']) ) {
        $sidebar = (string) $_POST['sidebar'];

        $sidebars_widgets = wp_get_sidebars_widgets();
        if ( isset($sidebars_widgets[$sidebar]) )
            $this_sidebar =& $sidebars_widgets[$sidebar];
        else
            $this_sidebar = array();

        foreach ( $this_sidebar as $_widget_id ) {
            if ( 'wd_widget_rss' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
                $widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
                if ( !in_array( "admin_rss-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
                    unset($options[$widget_number]);
            }
        }

        foreach( (array) $_POST['widget-rss'] as $widget_number => $widget_rss ) {
            $widget_rss = stripslashes_deep( $widget_rss );
            $url = sanitize_url(strip_tags($widget_rss['url']));
            $options[$widget_number] = wp_widget_rss_process( $widget_rss, !isset($urls[$url]) );
            $options[$widget_number]['height'] = strip_tags($widget_rss['height']);
            $options[$widget_number]['width'] = strip_tags($widget_rss['width']);
        }

        update_option('admin_widget_rss', $options);
        $updated = true;
    }

    if ( -1 == $number ) {
        $title = '';
        $url = '';
        $items = 10;
        $error = false;
        $number = '%i%';
        $show_summary = 0;
        $show_author = 0;
        $show_date = 0;
        $width = 'half';
        $height = 'single';
    } else {
        extract( (array) $options[$number] );
    }

    wd_widget_rss_form( compact( 'number', 'height', 'width', 'title', 'url', 'items', 'error', 'show_summary', 'show_author', 'show_date' ) );
}

function wd_widget_rss_form( $args, $inputs = null ) {
    $default_inputs = array( 'url' => true, 'height' => true, 'width' => true, 'title' => true, 'items' => true, 'show_summary' => true, 'show_author' => true, 'show_date' => true );
    $inputs = wp_parse_args( $inputs, $default_inputs );
    extract( $args );
    $number = attribute_escape( $number );
    $title  = attribute_escape( $title );
    $url    = attribute_escape( $url );
    $height   = attribute_escape( $height );
    $width   = attribute_escape( $width );
    $items  = (int) $items;
    if ( $items < 1 || 20 < $items )
        $items  = 10;
    $show_summary   = (int) $show_summary;
    $show_author    = (int) $show_author;
    $show_date      = (int) $show_date;

    if ( $inputs['url'] ) :
?>
    <p>
        <label for="admin_rss-url-<?php echo $number; ?>"><?php _e('Choose the RSS feed URL here, or enter your own:'); ?></label>
            <p><select id="rssDropdown-<?php echo $number; ?>" onchange="changeFeed(<?php echo $number; ?>)">
                <option value=''>Type Your Own Feed URL:</option>
                <option value='http://feeds.feedburner.com/Pressography'>Pressography</option>
                <option value='http://feeds.feedburner.com/LorelleOnWordpress'>Lorelle On Wordpress</option>
                <option value='http://feeds.feedburner.com/ProbloggerHelpingBloggersEarnMoney'>ProBlogger</option>
                <option value='http://feeds.feedburner.com/weblogtoolscollection/UXMP'>Weblog Tools Collection</option>
                <option value='http://wordpress.org/development/feed/'>Wordpress Development Blog</option>
                <option value='http://planet.wordpress.org/feed/'>Wordpress Planet News</option>
            </select></p>
            <input class="widefat" id="admin_rss-url-<?php echo $number; ?>" name="widget-rss[<?php echo $number; ?>][url]" type="text" value="<?php echo $url; ?>" />
    </p>
<?php endif; if ( $inputs['title'] ) : ?>
    <p>
        <label for="admin_rss-title-<?php echo $number; ?>"><?php _e('Give the feed a title (optional):'); ?>
            <input class="widefat" id="admin_rss-title-<?php echo $number; ?>" name="widget-rss[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" />
        </label>
    </p>
<?php endif; if ( $inputs['items'] ) : ?>
    <p>
        <label for="admin_rss-items-<?php echo $number; ?>"><?php _e('How many items would you like to display?'); ?></label>
            <select id="admin_rss-items-<?php echo $number; ?>" name="widget-rss[<?php echo $number; ?>][items]">
                <?php
                    for ( $i = 1; $i <= 20; ++$i )
                        echo "<option value='$i' " . ( $items == $i ? "selected='selected'" : '' ) . ">$i</option>";
                ?>
            </select>
    </p>
<?php endif; if ( $inputs['width'] ) : ?>
    <p>
        <label for="admin_rss-width-<?php echo $number; ?>"><?php _e('How wide should this widget be?'); ?></label>
            <select id="admin_rss-width-<?php echo $number; ?>" name="widget-rss[<?php echo $number; ?>][width]">
                <option <?php echo $width == 'fourth' ? 'selected' : ''; ?> value='fourth'>Quarter</option>
                <option <?php echo $width == 'third' ? 'selected' : ''; ?> value='third'>Third</option>
                <option <?php echo $width == 'half' || $width == '' ? 'selected' : ''; ?> value='half'>Half</option>
                <option <?php echo $width == 'full' ? 'selected' : ''; ?> value='full'>Full</option>
            </select>        
    </p>
<?php endif; if ( $inputs['height'] ) : ?>
    <p>
        <label for="admin_rss-height-<?php echo $number; ?>"><?php _e('How high should this widget be?'); ?></label>
            <select id="admin_rss-height-<?php echo $number; ?>" name="widget-rss[<?php echo $number; ?>][height]">
                <option <?php echo $height == 'single' ? 'selected' : ''; ?> value='single'>Normal</option>
                <option <?php echo $height == 'double' ? 'selected' : ''; ?> value='double'>Double</option>
            </select>
        
    </p>
<?php endif; if ( $inputs['show_summary'] ) : ?>
    <p>
        <label for="admin_rss-show-summary-<?php echo $number; ?>">
            <input id="admin_rss-show-summary-<?php echo $number; ?>" name="widget-rss[<?php echo $number; ?>][show_summary]" type="checkbox" value="1" <?php if ( $show_summary ) echo 'checked="checked"'; ?>/>
            <?php _e('Display item content?'); ?>
        </label>
    </p>
<?php endif; if ( $inputs['show_author'] ) : ?>
    <p>
        <label for="admin_rss-show-author-<?php echo $number; ?>">
            <input id="admin_rss-show-author-<?php echo $number; ?>" name="widget-rss[<?php echo $number; ?>][show_author]" type="checkbox" value="1" <?php if ( $show_author ) echo 'checked="checked"'; ?>/>
            <?php _e('Display item author if available?'); ?>
        </label>
    </p>
<?php endif; if ( $inputs['show_date'] ) : ?>
    <p>
        <label for="admin_rss-show-date-<?php echo $number; ?>">
            <input id="admin_rss-show-date-<?php echo $number; ?>" name="widget-rss[<?php echo $number; ?>][show_date]" type="checkbox" value="1" <?php if ( $show_date ) echo 'checked="checked"'; ?>/>
            <?php _e('Display item date?'); ?>
        </label>
    </p>
    <input type="hidden" name="widget-rss[<?php echo $number; ?>][submit]" value="1" />
<?php
    endif;
    
    if (strpos($_SERVER['REQUEST_URI'],'index.php')) {
        $sidebars_widgets = wp_get_sidebars_widgets();
        
        foreach ( $sidebars_widgets[DASHSIDEBARNAME] as $sidebar_widget_id ) { 
?>
            <input type="hidden" name='widget-id[]' value="<?php echo $sidebar_widget_id; ?>" />
<?php
        }
    }
    
    foreach ( array_keys($default_inputs) as $input ) :
        if ( 'hidden' === $inputs[$input] ) :
            $id = str_replace( '_', '-', $input );
?>
    <input type="hidden" id="admin_rss-<?php echo $id; ?>-<?php echo $number; ?>" name="widget-rss[<?php echo $number; ?>][<?php echo $input; ?>]" value="<?php echo $$input; ?>" />
<?php
        endif;
    endforeach;
}

function wd_widget_rss_register() {
    if ( !$options = get_option('admin_widget_rss') )
        $options = array();
    $widget_ops = array('classname' => 'admin_widget_rss', 
      'description' => __( 'Dashboard: Displays entries from any RSS or Atom feed' ));
    $control_ops = array('width' => 400, 'height' => 200, 'id_base' => 'admin_rss');
    $name = __('RSS');

    $id = false;
    foreach ( array_keys($options) as $o ) {
        // Old widgets can have null values for some reason
        if ( !isset($options[$o]['url']) || !isset($options[$o]['title']) || !isset($options[$o]['items']) )
            continue;
        $id = "admin_rss-$o"; // Never never never translate an id
        
        $widget_ops['all_link'] = $options[$o]['url'];
        $widget_ops['feed_link'] = $options[$o]['url'];
        $widget_ops['width'] = $options[$o]['width'];
        $widget_ops['height'] = $options[$o]['height'];
        
        wp_register_sidebar_widget($id, $name, 'wd_widget_rss', $widget_ops, array('number' => $o));
        wp_register_widget_control($id, $name, 'wd_widget_rss_control', $control_ops, array( 'number' => $o ));
    }

    // If there are none, we register the widget's existance with a generic template
    if ( !$id ) {
        wp_register_sidebar_widget( 'admin_rss-1', $name, 'wd_widget_rss', $widget_ops, array( 'number' => -1 ) );
        wp_register_widget_control( 'admin_rss-1', $name, 'wd_widget_rss_control', $control_ops, array( 'number' => -1 ) );
    }
}

function wd_widgets_init() {
    if ( !is_blog_installed() || !is_admin())
        return;
   
    //get rid of the problem feed widgets, regardless of what sidebar this is
    wp_unregister_sidebar_widget("dashboard_primary");
    wp_unregister_widget_control("dashboard_primary");
    wp_unregister_sidebar_widget("dashboard_secondary");
    wp_unregister_widget_control("dashboard_secondary");
    
   if ($_GET['sidebar'] == DASHSIDEBARNAME) {
   
        //Register the good Dashboard RSS feed widget
        wd_widget_rss_register();
                
        //Add "Dashboard" to the Dashboard widget names, if we're on the widgets.php page
        if (strpos($_SERVER['REQUEST_URI'],'widgets.php')) {
            global $wp_registered_widgets;
            
            foreach ($wp_registered_widgets as $key => $value) {
                if (in_array($wp_registered_widgets[$key]['id'], array('dashboard_recent_comments','dashboard_incoming_links','dashboard_plugins')) || strpos($wp_registered_widgets[$key]['id'],'admin_rss-') > -1) {
                    $wp_registered_widgets[$key]['name'] = "Dashboard: " . $wp_registered_widgets[$key]['name'];
                }
            }
        }
        
        do_action('widgetized_dashboard_widget_register');
   } else if (!strpos($_SERVER['REQUEST_URI'],'index.php')) {
       //If this isn't the dashboard, and this isn't the widgets page looking 
       //at the dashboard sidebar, then remove the other dashboard widgets
        wp_unregister_sidebar_widget("dashboard_recent_comments");
        wp_unregister_widget_control("dashboard_recent_comments");
        wp_unregister_sidebar_widget("dashboard_incoming_links");
        wp_unregister_widget_control("dashboard_incoming_links");
        wp_unregister_sidebar_widget("dashboard_plugins");
        wp_unregister_widget_control("dashboard_plugins");
   } else {
        //This must be the dashboard page - register the good Dashboard RSS feed widget
        wd_widget_rss_register();
   }
}

function wd_widgets_head() {
    if (strpos($_SERVER['REQUEST_URI'],'index.php') || strpos($_SERVER['REQUEST_URI'],'widgets.php')) {
?>
<script type="text/javascript">
    function changeFeed(number) {
      var str = "";
      str = jQuery("#rssDropdown-" + number + " option:selected").val();
      jQuery("#admin_rss-url-" + number).val(str);
    };
<?php
        if (strpos($_SERVER['REQUEST_URI'],'widgets.php')) {
?>
            //Clean up the error where $sidebar= isn't appended to the querystring for admin widgets
            jQuery(document).ready(function() {
                jQuery(".widget-action").each(function () {
                    var url = jQuery(this).attr("href");
                    if (url.indexOf("&sidebar=") < 0) {
                        url += "&sidebar=<?php echo DASHSIDEBARNAME; ?>";
                        jQuery(this).attr("href", url);
                    }
                });
            });
<?php   } ?>
</script>
<?php
    }
}

add_filter('admin_head', wd_widgets_head, 10);
add_action('wp_dashboard_setup', wd_widgets_init, 10);                                                      
add_filter('wp_dashboard_widgets', wd_return_actual_dash_widgets, 10);

?>