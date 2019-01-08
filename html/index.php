<?php

	use mc\mcDataStructure;
function autoload($className) {
	$className = str_replace('\\', '/', $className);
	require(__DIR__ . '/classes/'.$className. '.class.php');
	//printf(__DIR__ . '/classes/'.$className. '.class.php');
	//require_once(__DIR__.'/classes/TeamSpeak3/TeamSpeak3.php'); //Teamspeak 3 Framework
}

	Error_Reporting( E_ALL | E_STRICT );
	Ini_Set( 'display_errors', true );

spl_autoload_register('autoload');
$servers = array(
	"minecraft1.xavizus.com",
	"minecraft2.xavizus.com",
	"minecraft3.xavizus.com",
	"minecraft4.xavizus.com"
);

//$minecraftServer = new McDataStructure('minecraft1.xavizus.com');

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<title>Xavizus Server Status</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
	<style>tr td,tr th {text-align:center !important}tr td.motd,tr th.motd{text-align:left !important;}</style>
	<style>.status{width:50px;}</style>
	<style>.serverType{width:50px}</style>
	<!-- HTML5 shim -->
    <!--[if lt IE 9]>
    	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
	<script src="js/Chart.bundle.js"></script>
</head>

<body>
	<div class="container">
		<div class="row" style="margin:15px 0;">
			<h1>Xavizus Server Status Development Test</h1>
			<p>This is a basic implementation of reading server meta and online/offline status.</p>
		</div>
		<div class="row">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th class="serverType">ServerType</th>
						<th class="status">Status<span class="badge badge-success"><i class="icon-ok icon-white"></i></span><span class="badge badge-important"><i class="icon-remove icon-white"></i></span></th>
						<th class="motd">Server</th>
						<th>Users Online</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($servers as $server): ?>
					<?php $stats = new mcDataStructure($server); ?>
					<tr>
						<td>
							<span class="serverType"></span>
						</td>
						<td>
							<?php if($stats->get_online()): ?>
							<span class="badge badge-success"><i class="icon-ok icon-white"></i></span>
							<?php else: ?>
							<span class="badge badge-important"><i class="icon-remove icon-white"></i></span>
							<?php endif; ?>
						</td>
						<td class="motd">
							<?php echo $stats->get_description(); ?> 
							<code>
								<?php echo $server; ?>
							</code>
						</td>
						<td>
							<?php printf('%u/%u', $stats->get_onlineplayers(), $stats->get_maxplayers()); ?>
						</td>
					</tr>
					<?php unset($stats); ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="row">
			<p>This page is using PHP to check if Minecraft servers are online and query their listing information. <a href="https://github.com/xPaw/PHP-Minecraft-Query">Read more about xPaw's original PHP 5.5 implementation here.</a></p>
		</div>
		<canvas id="myChart" width="800" height="450"></canvas>
		<script>
var ctx = document.getElementById("myChart");
var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ["2018-01-12 15:00", "2018-01-12 15:15", "2018-01-13 15:30", "2018-01-12 15:45", "2018-01-12 16:00", "2018-01-12 16:15", "2018-01-12 16:30", "2018-01-12 16:45", "2018-01-13 17:00", "2018-01-12 17:15", "2018-01-12 17:30", "2018-01-12 17:45"],
        datasets: [{
            label: '# players online',
            data: [12, 19, 3, 5, 2, 3, 3, 4, 5, 4, 1, 7],
			backgroundColor: 'rgba(63,127,191,1)',
			borderColor: 'rgba(51,102,153,1)',
            borderWidth: 1,
        }]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero:true
                }
            }],
			xAxes: [{
					barPercentage: 1.0,
					categoryPercentage: 1.0,
					gridLines: {
						offsetGridLines: true,
					}
			}]
        },
    }
});
</script>
	</div>
</body>
</html>