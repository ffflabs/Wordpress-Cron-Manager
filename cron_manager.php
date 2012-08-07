<?php
/*
  Plugin Name: FFF Cron Manager
  Plugin URI: http://wordpress.org/extend/plugins/cron_manager/
  Description: List and delete cron jobs from WP Cron
  Author: Felipe Figueroa
  Version: 0.7
  Author URI: http://ffflabs.com/wordpress/
  Loosely based on Simon Wheatley's http://wordpress.org/extend/plugins/cron-view/
 */
$curtime=time();
global $wpdb;

 
if(!$intervalo=get_option('intervalo_cron')) {
	add_option('intervalo_cron',100);
} else if(isset($_POST['intervalo_cron'])) {
	update_option('intervalo_cron',intval($_POST['intervalo_cron']));
	
	
} 
	

	




if (isset($_POST['snapshot'])) {
	add_option('cronsnapshot_' . $curtime, get_option('cron'));
	 
	
} else if ($_POST['action']=='takesnapshot') {


add_option('cronsnapshot_' . $curtime, get_option('cron'));
$snapshots=$wpdb->get_results("select replace(option_name,'cronsnapshot_','') as restoretimestamp from {$wpdb->options} where option_name like '%cronsnapshot_%';");
	foreach ($snapshots as $snapshot) {
		echo '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '" id="cronsnapshot_'.$snapshot->restoretimestamp.'">';
		echo "<a class='deletesnapshot deleteimg' href='javascript:void(0);' rel='{$snapshot->restoretimestamp}'></a>";
		echo '<input type="hidden" id="restoretimestamp" name="restoretimestamp" value="'.$snapshot->restoretimestamp.'"/>';
		echo '<input type="submit" id="restorecron" name="restorecron" value="Restore '.date('Y-m-d h:i:s', $snapshot->restoretimestamp).' Cron Snapshot"/>';
		echo '</form>';
	}
die();
} else if ($_POST['action']=='deletesnapshot' && !empty($_POST['snapshottime']) && $snapshottime=intval($_POST['snapshottime']) ) {
	delete_option('cronsnapshot_' . $snapshottime);
$snapshots=$wpdb->get_results("select replace(option_name,'cronsnapshot_','') as restoretimestamp from {$wpdb->options} where option_name like '%cronsnapshot_%';");
	foreach ($snapshots as $snapshot) {
		echo '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '" id="cronsnapshot_'.$snapshot->restoretimestamp.'">';
		echo "<a class='deletesnapshot deleteimg' href='javascript:void(0);' rel='{$snapshot->restoretimestamp}'></a>";
		echo '<input type="hidden" id="restoretimestamp" name="restoretimestamp" value="'.$snapshot->restoretimestamp.'"/>';
		echo '<input type="submit" id="restorecron" name="restorecron" value="Restore '.date('Y-m-d h:i:s', $snapshot->restoretimestamp).' Cron Snapshot"/>';
		echo '</form>';
	}
die();	
	
} else if (isset($_POST['restorecron']) && isset($_POST['restoretimestamp']) && get_option('cronsnapshot_' . intval($_POST['restoretimestamp']))) {

	update_option('cron', get_option('cronsnapshot_' . intval($_POST['restoretimestamp'])));
} else if (isset($_GET['timestamp']) && isset($_GET['hook']) && isset($_GET['action']) && $_GET['action'] == 'deletecron') {

	$crons = _get_cron_array();
	$timestamp = intval($_GET['timestamp']);
	$hook = $_GET['hook'];
	unset($crons[$timestamp][$hook]);
	
	
        _set_cron_array($crons);
	echo $timestamp . $hook;
	foreach($crons as $timestamp => $content) {
		if (sizeof($content)==0) unset($crons[$timestamp]);
	}
	_set_cron_array($crons);
	die();
} else if (isset($_GET['snapshottimestamp']) && isset($_GET['action']) && $_GET['action'] == 'deletesnapshot') {

	$optionname = 'cronsnapshot_' . $_GET['snapshottimestamp'];
	delete_option( $optionname );
	echo $optionname;
	
	 
	
} else {
	$snapshot_count = $wpdb->get_results("SELECT COUNT(*) snapshots FROM  {$wpdb->options}  where option_name like '%cronsnapshot_%'");

	if (!$snapshot_count)
		add_option('cronsnapshot_' . $curtime, get_option('cron'));
}



add_action('admin_menu', 'fff_menu');
add_action( 'admin_head', 'fff_cron_manager_head' );

function fff_menu() {
	add_menu_page('Cron Manager', 'Cron Manager', 'create_users', 'fff_cron_manager', 'fff_cron_manager');
}

function fff_cron_manager_head() {
	
		wp_deregister_script('jquery');
		wp_register_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
		wp_enqueue_script('jquery');
		wp_deregister_script('datatables');
		wp_register_script('datatables', 'https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.min.js');
		wp_enqueue_script('datatables');
		
echo '<style type="text/css">
		  @import "https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables.css";
	
	.cron_manager { border-collapse:collapse;}
	.cron_manager th {border:1px solid #333;  border-collapse:collapse;text-align:center;padding:2px 5px;}
	.cron_manager td {border:1px solid #CCC;border-collapse:collapse;text-align:center;border:1px solid #999;text-align:center;}
	.cron_manager td:first-child, .listapais th:first-child { text-align:left;}
		  	.cron_manager tr:nth-child(odd) { background:#FFF; }
	.cron_manager tr:nth-child(even) { background:#F7F7F7; }
	.cron_manager tbody tr:hover {background:#F0FfF0; color:#336633;}
	ul.argumentos {float:left;list-style-type:none;}
	ul.argumentos li {float:left;list-style-type:none;margin-left:10px;}
	.deletethiscron {margin:auto;text-align:center;}
	 .deleteimg {display: inline-block;height: 20px;width: 20px;background: url(/wp-content/plugins/cron_manager/delete-icon.gif) no-repeat;margin: -5px 5px -5px 5px;}
	.dataTables_wrapper {width:90%;}
	.cron_manager_input {float: left;
margin: -5px 5px -15px 5px !important;
vertical-align: center;
height: 25px; }
	 </style>';
	?>
	   

	<script>
		jQuery('document').ready(function() {
			jQuery('.deletethiscron').click(function() {
				var wichcron=jQuery(this).attr('rel');
				var geturl=jQuery(this).attr('alt');
				jQuery.get(geturl,function(data) {
					jQuery('#'+data).slideUp();
				});
			});
		
			jQuery('#TakeSnapshot').click(function() {
				jQuery.ajax({
					  type: "POST",
					data: {action : 'takesnapshot'},
					 context: document.body
				  }).done(function(data) { 
					jQuery('#available_snapshots').html(data);
				  });
			  });	
			jQuery('.deletesnapshot').live('click',function() {
				var Snapshottime=jQuery(this).attr('rel');
				jQuery.ajax({
					  type: "POST",
					data: {action : 'deletesnapshot',snapshottime:Snapshottime},
					 context: document.body
				  }).done(function(data) { 
					jQuery('#available_snapshots').html(data);
				  });
			  });	
			  
			jQuery('#BorrarMasivo').click(function() {
				jQuery('.borrarmasivo:checked').each(function() {
					var geturl=jQuery(this).attr('alt');
						jQuery.get(geturl,function(data) {
							jQuery('#'+data).slideUp();
						});
					
				});
			
			});
			jQuery('#selectAll').click(function() {
				if(jQuery(this).is(':checked')) {
					jQuery('.borrarmasivo').attr('checked','checked');
				} else {
					jQuery('.borrarmasivo').removeAttr('checked');
				}
			});
			
			 var oTable =jQuery('#tablacron').dataTable( {
 "sDom": 'frti',
 "iDisplayLength": -1
  } );
		});
	</script>
	<?php
}
	
function fff_cron_manager() {
global $wpdb;
	
	$crons = _get_cron_array();
/*foreach($crons as $cron => $content) {
		echo '<pre>';print_r($cron);echo 'Contiene '.sizeof($content).' elementos;</pre>';
		
	}*/
	$ahora = time();

	 
	
	 
	
	echo '<h2>Available Cron Snapshots</h2><div id="available_snapshots">';
	$snapshots=$wpdb->get_results("select replace(option_name,'cronsnapshot_','') as restoretimestamp from {$wpdb->options} where option_name like '%cronsnapshot_%';");
	foreach ($snapshots as $snapshot) {
		echo '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '" id="cronsnapshot_'.$snapshot->restoretimestamp.'">';
		echo "<a class='deletesnapshot deleteimg' href='javascript:void(0);' rel='{$snapshot->restoretimestamp}'></a>";
		echo '<input type="hidden" id="restoretimestamp" name="restoretimestamp" value="'.$snapshot->restoretimestamp.'"/>';
		echo '<input type="submit" id="restorecron" name="restorecron" value="Restore '.date('Y-m-d h:i:s', $snapshot->restoretimestamp).' Cron Snapshot"/>';
		echo '</form>';
	}
	echo "</div><h2>This is your current Wordpress Cron</h2>";

	$schedule = wp_get_schedules();
	
	?>
<input class="cron_manager_input"  type="checkbox" id="selectAll" value="1"/>
<input class="cron_manager_input" type="button" id="BorrarMasivo" value="Delete Selected"/>
<input class="cron_manager_input" type="button" id="TakeSnapshot" value="Take Snapshot"/>

	<table id="tablacron" class="wp-list-table plugins cron_manager">
<thead>
		<tr><th style="width:65px;"><?php _e('Delete', 'cron_manager'); ?></th>
			<th  ><?php _e('Next run GMT (timestamp)', 'cron_manager'); ?></th>
			<th ><?php _e('Seconds left', 'cron_manager'); ?></th>
			<th  ><?php _e('Type of Schedule', 'cron_manager'); ?></th>
			<th  ><?php _e('Hook Name', 'cron_manager'); ?></th>
			<th  ><?php _e('Arg[]', 'cron_manager'); ?></th>
		</tr>
</thead>
<tbody>
	<?php
	foreach ($crons as $timestamp => $cronhooks) :
		foreach ($cronhooks as $hook => $cronjobs) :
			foreach ($cronjobs as $cronjob) :
				echo '<tr id="' . $timestamp . $hook . '"><td>';
				echo "<input class=\"borrarmasivo\" type=\"checkbox\" id=\"cb_". $timestamp . $hook."\"  alt='{$_SERVER['REQUEST_URI']}&timestamp=$timestamp&action=deletecron&hook=$hook' rel='$timestamp.$hook'  />";
				echo "<a class='deletethiscron deleteimg' href='javascript:void(0);' alt='{$_SERVER['REQUEST_URI']}&timestamp=$timestamp&action=deletecron&hook=$hook' rel='$timestamp.$hook'></td>";
				
				echo '<td style="text-align:center;">' . date('Y-m-d h:i:s', $timestamp) . ' - ' . $timestamp;
				echo '</td><td style="text-align:center;">' . ($timestamp - $ahora) . '</td><td>';


				if ($cronjob['schedule']) {
					echo $schedule [$cronjob['schedule']]['display'];
				} else {
					?><em><?php _e('One-off event', 'cron_manager'); ?></em><?php
				}
				?>
				</td>
				<td><?php echo $hook; ?></td>
				<td><ul class="argumentos"><?php foreach ($cronjob['args'] as $num => $arg)
					echo "<li>[{$num}]:$arg</li>"; ?></ul></td>
				</tr>
				<?php
				endforeach;
			endforeach;
		endforeach;
		?>
	</tbody>
	</table>



<?php
}

