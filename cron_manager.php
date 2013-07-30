<?php
/*
  Plugin Name: FFF Cron Manager
  Plugin URI: http://wordpress.org/extend/plugins/cron_manager/
  Description: List and delete cron jobs from WP Cron
  Author: Felipe Figueroa
  Version: 1.0.0
  Author URI: http://ffflabs.com/wordpress/
  Loosely based on Simon Wheatley's http://wordpress.org/extend/plugins/cron-view/
 */

// Plugin Folder Path
if ( ! defined( 'FFF_CRON_MANAGER_PLUGIN_DIR' ) )
	define( 'FFF_CRON_MANAGER_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) . '/' );

// Plugin Folder URL
if ( ! defined( 'FFF_CRON_MANAGER_PLUGIN_URL' ) )
	define( 'FFF_CRON_MANAGER_PLUGIN_URL', plugin_dir_url( FFF_CRON_MANAGER_PLUGIN_DIR ) . basename( dirname( __FILE__ ) ) . '/' );

// Plugin Root File
if ( ! defined( 'FFF_CRON_MANAGER_PLUGIN_FILE' ) )
	define( 'FFF_CRON_MANAGER_PLUGIN_FILE', __FILE__ );

$curtime=time();
global $wpdb;

if(!$intervalo=get_option('intervalo_cron')) {
	add_option('intervalo_cron',100);
} else if(isset($_POST['intervalo_cron'])) {
	update_option('intervalo_cron',intval($_POST['intervalo_cron']));
} 	

function fff_cron_manager_init() {
	add_action('admin_menu', 'fff_menu');
	add_action('admin_head', 'fff_cron_manager_head' );
}
add_action('init', 'fff_cron_manager_init');


if (isset($_POST['snapshot'])) {
	add_option('cronsnapshot_' . $curtime, get_option('cron'));
} else if (isset($_POST['action']) && $_POST['action']=='takesnapshot') {

add_option('cronsnapshot_' . $curtime, get_option('cron'));
$snapshots = $wpdb->get_results( "SELECT REPLACE(option_name, 'cronsnapshot_', '') AS restoretimestamp FROM {$wpdb->options} WHERE option_name LIKE '%cronsnapshot_%';" );
foreach ($snapshots as $snapshot) {
	echo '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '" id="cronsnapshot_'.$snapshot->restoretimestamp.'">';
	echo "<a class='deletesnapshot deleteimg' href='javascript:void(0);' rel='{$snapshot->restoretimestamp}'></a>";
	echo '<input type="hidden" id="restoretimestamp" name="restoretimestamp" value="'.$snapshot->restoretimestamp.'"/>';
	echo '<input class="button" type="submit" id="restorecron" name="restorecron" value="Restore '.date('Y-m-d h:i:s', $snapshot->restoretimestamp).' Cron Snapshot"/>';
	echo '</form>';
}
die();
} else if (isset($_POST['action']) && $_POST['action']=='deletesnapshot' && !empty($_POST['snapshottime']) && $snapshottime=intval($_POST['snapshottime']) ) {
	delete_option('cronsnapshot_' . $snapshottime);
	$snapshots = $wpdb->get_results( "SELECT REPLACE(option_name, 'cronsnapshot_', '') AS restoretimestamp FROM {$wpdb->options} WHERE option_name LIKE '%cronsnapshot_%';" );
	foreach ($snapshots as $snapshot) {
		echo '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '" id="cronsnapshot_'.$snapshot->restoretimestamp.'">';
		echo "<a class='deletesnapshot deleteimg' href='javascript:void(0);' rel='{$snapshot->restoretimestamp}'></a>";
		echo '<input type="hidden" id="restoretimestamp" name="restoretimestamp" value="'.$snapshot->restoretimestamp.'"/>';
		echo '<input class="button" type="submit" id="restorecron" name="restorecron" value="Restore '.date('Y-m-d h:i:s', $snapshot->restoretimestamp).' Cron Snapshot"/>';
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
	$snapshot_count = $wpdb->get_results( "SELECT COUNT(*) snapshots FROM  {$wpdb->options}  WHERE option_name LIKE '%cronsnapshot_%'" );

	if (!$snapshot_count) {
		add_option('cronsnapshot_' . $curtime, get_option('cron'));
	}	
}

function fff_menu() {
	add_menu_page('Cron Manager', 'Cron Manager', 'create_users', 'fff_cron_manager', 'fff_cron_manager');
}

function fff_cron_manager_scripts() {
	wp_deregister_script('datatables');
	wp_register_script('datatables', 'https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.min.js', array('jquery'));
	wp_enqueue_script('datatables');
	wp_enqueue_style( 'datatables', 'https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables.css');
	wp_enqueue_style( 'cron_manager', FFF_CRON_MANAGER_PLUGIN_URL . '/cron_manager.css' );
}
add_action('admin_enqueue_scripts', 'fff_cron_manager_scripts');

function fff_cron_manager_head() {
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
			data: { action : 'takesnapshot'},
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
		});
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
	echo '<div class="wrap">';
	echo get_screen_icon('generic');
	echo '<h2>' . __('Cron Manager', 'fff_cron_manager') .'</h2>';
	echo '<h3>Available Cron Snapshots</h3><div id="available_snapshots">';
	$snapshots=$wpdb->get_results("SELECT REPLACE(option_name, 'cronsnapshot_', '') AS restoretimestamp FROM {$wpdb->options} WHERE option_name LIKE '%cronsnapshot_%';");
	foreach ($snapshots as $snapshot) {
		echo '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '" id="cronsnapshot_'.$snapshot->restoretimestamp.'">';
		echo "<a class='deletesnapshot deleteimg' href='javascript:void(0);' rel='{$snapshot->restoretimestamp}'></a>";
		echo '<input type="hidden" id="restoretimestamp" name="restoretimestamp" value="'.$snapshot->restoretimestamp.'"/>';
		echo '<input class="button" type="submit" id="restorecron" name="restorecron" value="Restore '.date('Y-m-d h:i:s', $snapshot->restoretimestamp).' Cron Snapshot"/>';
		echo '</form>';
	}
	echo "</div><h3>This is your current Wordpress Cron</h3>";

	$schedule = wp_get_schedules();
	
	?>
	<div class="cron_manager_actions">
		<input class="button" type="button" id="BorrarMasivo" value="Delete Selected"/>
		<input class="button" type="button" id="TakeSnapshot" value="Take Snapshot"/>
	</div>
	<table id="tablacron" class="wp-list-table plugins cron_manager">
		<thead>
			<tr>
				<th style="width:65px;"><label class="selectit"><input type="checkbox" id="selectAll" value="1"/></label> <?php _e('Delete', 'cron_manager'); ?></th>
				<th><?php _e('Next run GMT (timestamp)', 'cron_manager'); ?></th>
				<th><?php _e('Seconds left', 'cron_manager'); ?></th>
				<th><?php _e('Type of Schedule', 'cron_manager'); ?></th>
				<th><?php _e('Hook Name', 'cron_manager'); ?></th>
				<th><?php _e('Arg[]', 'cron_manager'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ($crons as $timestamp => $cronhooks) :
				foreach ($cronhooks as $hook => $cronjobs) :
					foreach ($cronjobs as $cronjob) :
						$out = "";
						echo '<tr id="' . $timestamp . $hook . '"><td>';
						echo "<input class=\"borrarmasivo\" type=\"checkbox\" id=\"cb_". $timestamp . $hook."\"  alt='{$_SERVER['REQUEST_URI']}&timestamp=$timestamp&action=deletecron&hook=$hook' rel='$timestamp.$hook'  />";
						echo "<a class='deletethiscron deleteimg' href='javascript:void(0);' alt='{$_SERVER['REQUEST_URI']}&timestamp=$timestamp&action=deletecron&hook=$hook' rel='$timestamp.$hook'></td>";
						
						echo '<td style="text-align:center;">' . date('Y-m-d h:i:s', $timestamp) . ' - ' . $timestamp . '</td>';
						$delta = $timestamp - $ahora;
						if($delta <= 0) {
							$out .= '<span class="fancy">' . $delta . '</span>';
						} else {
							$out .= $delta;
						}	
						
						echo '<td style="text-align:center;">' . $out . '</td><td>';
		
						if ($cronjob['schedule']) {
							echo $schedule [$cronjob['schedule']]['display'];
						} else {
							?><em><?php _e('One-off event', 'cron_manager'); ?></em><?php
						}
						?>
						</td>
						<td><?php echo $hook; ?></td>
						<td>
							<ul class="argumentos"><?php foreach ($cronjob['args'] as $num => $arg)
							echo "<li>[{$num}]:$arg</li>"; ?></ul></td>
						</tr>
					<?php
					endforeach;
				endforeach;
			endforeach;
			?>
		</tbody>
	</table>
	</div><!-- wrap -->
<?php
}
