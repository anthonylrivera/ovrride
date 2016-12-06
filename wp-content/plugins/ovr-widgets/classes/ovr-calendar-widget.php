<?php
class ovr_calendar_widget extends WP_Widget {
  function __construct() {
    parent::__construct(
    // Base ID of your widget
    'ovr_calendar',

    // Widget name will appear in UI
    'OvR Calendar',

    // Widget description
    array( 'description' => 'Calendar for small tile display or full page' )
    );
    add_action( 'wp_ajax_nopriv_ovr_calendar', array( $this, "generate_calendar_ajax") );
    add_action( 'wp_ajax_ovr_calendar', array( $this, "generate_calendar_ajax") );
  }
  public function form( $instance ) {

  }
  public function generate_calendar_ajax() {

    if ( ! wp_verify_nonce( $_REQUEST['ovr_calendar_shift'], 'ovr_calendar' ) ) {
      error_log("WTF?");
      error_log($_REQUEST['ovr_calendar_shift']);
      die('OvR Calendar Ajax nonce failed');
    }


    // Create php date object with correct timezone for calendar generation
    $date = new DateTime($_POST['calendarDate'], new DateTimeZone('America/New_York'));

    wp_send_json( array("html" => $this->generate_calendar($date), "month_year" => $date->format('F Y') ) );
  }
  public function generate_calendar( $date ) {
    global $wpdb;
    $date->setTimezone(new DateTimeZone('America/New_York'));
    $currentDay = new DateTime('now');
    if ( $currentDay->format('m') == $date->format('m') ) {
      $date = new DateTime('now', new DateTimeZone('America/New_York'));
    }
    if ( $date == $currentDay) {
      $activate = true;
    } else {
      $activate = false;
    }
    $month = $date->format('m');
    $day = $date->format('d');
    $year = $date->format('Y');
    $sqlDate = $date->format('F %, Y');

    // Find trips happening this month
    $trips = $wpdb->get_results("SELECT `wp_posts`.`post_title`, STR_TO_DATE(`wp_postmeta`.`meta_value`, '%M %d, %Y') as `Date`, `wp_posts`.`guid`
    FROM `wp_posts`
    JOIN `wp_postmeta` ON `wp_posts`.`ID` = `wp_postmeta`.`post_id`
    JOIN `wp_term_relationships` ON `wp_posts`.`ID` = `wp_term_relationships`.`object_id`
    JOIN `wp_term_taxonomy` ON `wp_term_relationships`.`term_taxonomy_id` = `wp_term_taxonomy`.`term_taxonomy_id`
    JOIN `wp_terms` ON `wp_term_taxonomy`.`term_id` = `wp_terms`.`term_id`
    WHERE `wp_posts`.`post_status` = 'publish'
    AND `wp_posts`.`post_type`='product'
    AND `wp_term_taxonomy`.`taxonomy` = 'product_type'
    AND `wp_terms`.`name` = 'trip'
    AND `wp_postmeta`.`meta_key` = '_wc_trip_start_date'
    AND `wp_postmeta`.`meta_value` LIKE '{$sqlDate}'
    ORDER BY `Date`", ARRAY_A);

    $search_date = $year . "-" . $month . "-";

    // loop through month and assemble
    $date->modify('last day of this month');
    $lastDay = $date->format('d');
    $end_week_offset = $date->format('w');
    $date->modify('first day of this month');
    $start_week_offset = $date->format('w');
    $days = '';

    // All calendars will have space for 6 weeks
    for($i = 1; $i <= 42; $i++ ) {
      $adjustedDay = $i - $start_week_offset;
      // Popover datafield
      $data = '';
      if ( $i <= $start_week_offset || $adjustedDay > $lastDay) {
        // Pad beginning and end of month with empty squares
        $add = '&nbsp;';
      } else if ( $i > $start_week_offset ) {
        // Add number to day
        $add = $i - $start_week_offset;
        $icon = false;
        $calendarDate = $date->format('Y-m-') . str_pad($adjustedDay, 2 , "0", STR_PAD_LEFT);
        // If the current calendar date exists in the trips array add the trip info
        while( $trips[0]['Date'] == $calendarDate ) {
          if ( ! $icon ) {
              $icon = true;
          }
          // Remove trip from array after processing
          $temp = array_shift($trips);
          if ( $data === '' ) {
            $data .= 'data-placement="auto-bottom" data-content="';
          }

          $stripped_title = preg_replace("/(.*[^:]):*\s[ADFJMNOS][aceopu][bcglnprtvy].\s[0-9\-]{1,5}[snrtdh]{1,2}/", "$1", $temp['post_title']);
          $data .= '<a href=\''.$temp['guid'].'\'>'. $stripped_title .'</a><br />';
        }
        // If data was added to day then add an icon with link info
        if ( $icon ) {
          $data .= '"';
          $add .= '<i class="fa fa-snowflake-o icon winter" ' . $data . ' aria-hidden="true"></i>';
        }

      }
      // Should the current date be highlighted on this calendar?
      if ( $activate && $adjustedDay == $day) {
        $days .= '<li class="active">';
      } else {
        $days .= '<li>';
      }
      $days .= $add . '</li>';
    }

    return $days;
  }
  public function widget( $args, $instance ) {

    $days = $this->generate_calendar(new DateTime('now'));
    $date = new DateTime('now');
    $month_year = $date->format('F Y');
    wp_enqueue_style('jquery.webui-popover-style', plugin_dir_url( dirname(__FILE__) ) . 'css/jquery.webui-popover.min.css');
    wp_enqueue_script( 'jquery.webui-popover-js', plugin_dir_url( dirname(__FILE__) ) . 'js/jquery.webui-popover.min.js', array('jquery'), false, true);
    wp_enqueue_script( 'jquery_spin_js', plugin_dir_url( dirname(__FILE__) ) . 'js/jquery.spin.js', array('jquery','spin_js'), false, true);
    wp_enqueue_script( 'spin_js', plugin_dir_url( dirname(__FILE__) ) . 'js/spin.min.js');
    wp_enqueue_script( 'ovr_calendar_js', plugin_dir_url( dirname(__FILE__) ) . 'js/ovr-calendar-widget.js', array('jquery.webui-popover-js', 'jquery_spin_js'), false, true);
    wp_enqueue_style('ovr_calendar_style', plugin_dir_url( dirname(__FILE__) ) . 'css/ovr-calendar-widget.min.css');

    $nonced_url = wp_nonce_url( admin_url( 'admin-ajax.php'), 'ovr_calendar', 'ovr_calendar_shift' );
    wp_localize_script('ovr_calendar_js', 'ovr_calendar_vars', array( 'ajax_url' => $nonced_url ) );

    echo <<<FRONTEND
    <div class="ovr_calendar_widget">
      <div class="ovr_calendar_widget_inner">
        <div class="ovr_calendar_widget_content">
          <div class="ovr_calendar">
            <div class="month">
              <ul>
                <li class="prev"><i class="fa fa-arrow-left fa-lg" aria-hidden="true"></i></li>
                <li class="next"><i class="fa fa-arrow-right fa-lg" aria-hidden="true"></i></li>
                <li>
                  <h4 class="month_year">{$month_year}</h4>
                </li>
              </ul>
            </div>
            <ul class="weekdays">
              <li>S</li>
              <li>M</li>
              <li>T</li>
              <li>W</li>
              <li>T</li>
              <li>F</li>
              <li>S</li>
            </ul>

            <ul class="days">
            {$days}
            </ul>
          </div>
        </div>
      </div>
    </div>
FRONTEND;
  }
}
