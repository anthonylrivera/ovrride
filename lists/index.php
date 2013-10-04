<?php
/**
 * OvR Lists - The main template file for OvR Lists
 *
 *
 * @package OvR Lists
 * @since Version 0.0.1
 */

# Include Functions
include 'include/lists.php';

# Report all PHP errors on page
# For Development use only
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors','On');

$list = new Trip_List();
?>
<!DOCTYPE html>
  <head>
    <meta charset="utf-8">
    <title>OvR Trip Lists</title>
  </head>
  <body>
    <h1>OvR Trip Lists</h1>
    <br>
    <form action="index.php" method="post" name="trip_list">
      <label>Select a Trip:</label>
      <br>
      <select id="trip" name="trip">
      <?php echo $list->trip_options($_POST['trip']); ?>
      </select>
      <br>
      <label>Order Status: </label>
      <a onclick="javascript:checkAll('trip_list', true);" href="javascript:void();">check all</a> /
      <a onclick="javascript:checkAll('trip_list', false);" href="javascript:void();">uncheck all</a><br />
      <input type="checkbox" name="processing" value="processing" <?php if(isset($_POST['processing']) || !isset($_POST['trip'])) echo 'checked';?>>Proccessing</input>
      <input type="checkbox" name="pending" value="pending" <?php if(isset($_POST['pending']) || !isset($_POST['trip'])) echo 'checked'; ?>>Pending</input>
      <input type="checkbox" name="cancelled" value="cancelled" <?php if(isset($_POST['cancelled'])) echo 'checked'; ?>>Cancelled</input>
      <input type="checkbox" name="failed" value="failed" <?php if(isset($_POST['failed'])) echo 'checked'; ?>>Failed</input>
      <input type="checkbox" name="on-hold" value="on-hold" <?php if(isset($_POST['on-hold'])) echo 'checked'; ?>>On-hold</input>
      <input type="checkbox" name="completed" value="completed" <?php if(isset($_POST['completed'])) echo 'checked'; ?>>Completed</input>
      <input type="checkbox" name="refunded" value="refunded" <?php if(isset($_POST['refunded'])) echo 'checked'; ?>>Refunded</input>
      <br>
      <input type="submit" value="Generate List" />
      </form>
      <br>
      <?php 
        if(isset($_POST['trip']) && $_POST['trip'] != ""){
            if($orders=$list->find_orders_by_trip($_POST['trip'])){
              $html = "";
                foreach($orders as $order){
                    $data = $list->get_order_data($order,$_POST['trip']);
                    $html .= $list->table_row($data);
                }
                print $list->table_header($data);
                print $html;
                print $list->table_close();
            }
            else{
                print "No orders found for this trip";
            }
        }
      ?>
  </body>
  <script type="text/javascript">
  // TODO: Extrack to external js file
  function checkAll(formname, checktoggle)
  {
    var checkboxes = new Array(); 
    checkboxes = document[formname].getElementsByTagName('input');
 
    for (var i=0; i<checkboxes.length; i++)  {
      if (checkboxes[i].type == 'checkbox')   {
        checkboxes[i].checked = checktoggle;
      }
    }
  }
  </script>
</html>